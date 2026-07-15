<?php

namespace App\Services\Scraper;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Scrapes product data from Flipkart product pages.
 *
 * Flipkart heavily uses JS rendering. This service tries two approaches:
 *   1. Direct HTTP with browser-like headers (works ~60% of the time)
 *   2. Playwright sidecar (same Node.js scraper, different extraction logic)
 *
 * Extracted fields mirror AmazonScraperService so the rest of the pipeline
 * (AI generation, export, images) works unchanged.
 */
class FlipkartScraperService
{
    private string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36';

    /**
     * Main entry point — returns normalized product data array.
     *
     * @throws \Exception if scraping fails or no title found
     */
    public function scrape(string $url): array
    {
        // Try HTTP first (faster, no browser overhead)
        try {
            $data = $this->scrapeHttp($url);
            if (!empty($data['title'])) return $data;
        } catch (\Exception $e) {
            Log::info("Flipkart HTTP scrape failed, trying Playwright: " . $e->getMessage());
        }

        // Fall back to Playwright
        return $this->scrapePlaywright($url);
    }

    /**
     * Extract the product ID from a Flipkart URL.
     * Flipkart uses /p/itm... or pid=... query param.
     */
    public function extractProductId(string $url): ?string
    {
        // /p/itmXXXXXXXX pattern
        if (preg_match('/\/p\/(itm[a-zA-Z0-9]+)/', $url, $m)) {
            return $m[1];
        }
        // pid= query param
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $params);
        return $params['pid'] ?? null;
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function scrapeHttp(string $url): array
    {
        $response = Http::withHeaders([
            'User-Agent'      => $this->userAgent,
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language' => 'en-IN,en;q=0.9',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Referer'         => 'https://www.flipkart.com/',
            'sec-ch-ua'       => '"Chromium";v="125", "Not.A/Brand";v="24"',
            'sec-fetch-dest'  => 'document',
            'sec-fetch-mode'  => 'navigate',
            'sec-fetch-site'  => 'same-origin',
            'Cache-Control'   => 'no-cache',
        ])->timeout(30)->get($url);

        if (!$response->successful()) {
            throw new \Exception("HTTP {$response->status()} from Flipkart");
        }

        $html = $response->body();

        if (str_contains($html, 'captcha') || str_contains($html, 'robot')) {
            throw new \Exception('Flipkart served a CAPTCHA/bot challenge');
        }

        return $this->parseHtml($html, $url);
    }

    private function scrapePlaywright(string $url): array
    {
        $scraperScript = base_path('scraper-service/scrape.js');

        if (!file_exists($scraperScript)) {
            throw new \Exception('Playwright scraper not found at scraper-service/scrape.js');
        }

        $escapedUrl = escapeshellarg($url);
        $output = shell_exec("node {$scraperScript} {$escapedUrl} 2>&1");

        if (!$output) {
            throw new \Exception('Playwright returned no output');
        }

        // scrape.js outputs JSON
        $data = json_decode($output, true);

        if (!$data || !empty($data['error'])) {
            throw new \Exception($data['error'] ?? 'Playwright scrape failed');
        }

        // Normalize Playwright output to our standard format
        return $this->normalize([
            'title'         => $data['title'] ?? null,
            'brand'         => $data['brand'] ?? $this->extractBrandFromTitle($data['title'] ?? ''),
            'price'         => $data['price'] ?? null,
            'description'   => $data['description'] ?? null,
            'bullets'       => $data['bullets'] ?? $data['highlights'] ?? [],
            'images'        => $data['images'] ?? [],
            'category'      => $data['category'] ?? null,
            'specifications' => $data['specifications'] ?? [],
            'rating'        => $data['rating'] ?? null,
            'reviews_count' => $data['reviewsCount'] ?? null,
        ], $url);
    }

    /**
     * Parse raw Flipkart HTML. Flipkart embeds product JSON in a <script> tag
     * as window.__INITIAL_STATE__ or similar — we try that first, then fall
     * back to DOM selectors.
     */
    private function parseHtml(string $html, string $url): array
    {
        // ── Try embedded JSON state ───────────────────────────────────────────
        if (preg_match('/window\.__INITIAL_STATE__\s*=\s*(\{.+?\});\s*<\/script>/s', $html, $m)) {
            try {
                $state = json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
                $product = $this->extractFromInitialState($state);
                if (!empty($product['title'])) {
                    return $this->normalize($product, $url);
                }
            } catch (\Exception $e) {
                // Fall through to DOM parsing
            }
        }

        // ── Try pageDataV4 JSON (newer Flipkart pages) ───────────────────────
        if (preg_match('/pageDataV4\s*=\s*(\{.+?\});\s*(?:window|var)/s', $html, $m)) {
            try {
                $pageData = json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
                $product = $this->extractFromPageData($pageData);
                if (!empty($product['title'])) {
                    return $this->normalize($product, $url);
                }
            } catch (\Exception $e) {
                // Fall through
            }
        }

        // ── DOM-based extraction (fallback) ───────────────────────────────────
        return $this->normalize($this->parseWithDom($html), $url);
    }

    private function extractFromInitialState(array $state): array
    {
        // Flipkart's state structure varies — walk to find pdpData or pageData
        $pdp = $state['pdpData'] ?? $state['pageData'] ?? $state['productData'] ?? [];

        $product = $pdp['product'] ?? $pdp['details'] ?? $pdp ?? [];
        if (isset($state['product'])) $product = $state['product'];

        return [
            'title'         => $product['title'] ?? $product['name'] ?? null,
            'brand'         => $product['brand'] ?? $product['brandName'] ?? null,
            'price'         => $product['price'] ?? $product['flipkartPrice'] ?? $product['mrp'] ?? null,
            'description'   => $product['description'] ?? $product['shortDescription'] ?? null,
            'bullets'       => $product['highlights'] ?? $product['keyFeatures'] ?? [],
            'images'        => $this->extractImages($product),
            'category'      => $product['category'] ?? $product['breadcrumbs'][0] ?? null,
            'specifications' => $product['specifications'] ?? $product['attributes'] ?? [],
        ];
    }

    private function extractFromPageData(array $pageData): array
    {
        $slots = $pageData['page']['data'] ?? [];
        $product = [];

        foreach ($slots as $slot) {
            $widget = $slot['widget'] ?? [];
            $type   = $widget['type'] ?? '';
            $data   = $widget['data'] ?? [];

            if (str_contains($type, 'PRODUCT_SUMMARY') || str_contains($type, 'TITLE')) {
                $product['title'] = $data['title'] ?? $data['value'] ?? null;
                $product['brand'] = $data['brand'] ?? null;
            }
            if (str_contains($type, 'HIGHLIGHTS') || str_contains($type, 'KEY_FEATURES')) {
                $product['bullets'] = array_column($data['highlights'] ?? $data['values'] ?? [], 'text');
            }
            if (str_contains($type, 'MEDIA') || str_contains($type, 'IMAGE')) {
                $product['images'] = array_column($data['media'] ?? $data['images'] ?? [], 'url');
            }
            if (str_contains($type, 'DESCRIPTION')) {
                $product['description'] = $data['description'] ?? $data['text'] ?? null;
            }
        }

        return $product;
    }

    /**
     * Pure DOM regex fallback — works when JSON state is not embedded.
     */
    private function parseWithDom(string $html): array
    {
        $title = null;
        $brand = null;
        $price = null;
        $description = null;
        $bullets = [];
        $images = [];

        // Title — multiple possible selectors
        foreach ([
            '/<span[^>]+class="[^"]*B_NuCI[^"]*"[^>]*>([^<]+)<\/span>/i',
            '/<h1[^>]+class="[^"]*yhB1nd[^"]*"[^>]*>([^<]+)<\/h1>/i',
            '/<span[^>]+class="[^"]*_35KyD6[^"]*"[^>]*>([^<]+)<\/span>/i',
        ] as $pattern) {
            if (preg_match($pattern, $html, $m)) {
                $title = html_entity_decode(trim($m[1]));
                break;
            }
        }

        // Price
        if (preg_match('/<div[^>]+class="[^"]*_30jeq3[^"]*"[^>]*>₹([0-9,]+)/i', $html, $m)) {
            $price = '₹' . $m[1];
        }

        // Brand from breadcrumb or title attribute
        if (preg_match('/"brand"\s*:\s*"([^"]+)"/i', $html, $m)) {
            $brand = $m[1];
        }

        // Highlights/bullets
        preg_match_all('/<li[^>]+class="[^"]*_21Ahn0[^"]*"[^>]*>([^<]+)<\/li>/i', $html, $m);
        if (!empty($m[1])) {
            $bullets = array_map(fn($b) => html_entity_decode(trim($b)), $m[1]);
        }

        // Images — Flipkart uses /_next/image or q-images CDN
        preg_match_all('/"url"\s*:\s*"(https:\/\/rukminim\d*\.flixcart\.com\/image\/[^"]+)"/', $html, $m);
        if (!empty($m[1])) {
            // Upgrade to highest resolution: replace /128/ or /416/ with /832/
            $images = array_values(array_unique(array_map(
                fn($u) => preg_replace('/\/\d+\/\d+\//', '/832/832/', $u),
                $m[1]
            )));
        }

        // Description
        if (preg_match('/<div[^>]+class="[^"]*_1mXcCf[^"]*"[^>]*>(.*?)<\/div>/is', $html, $m)) {
            $description = strip_tags(html_entity_decode($m[1]));
        }

        if (!$title) {
            throw new \Exception('Flipkart: could not extract product title. The page may be JS-rendered — try enabling Playwright scraper.');
        }

        return compact('title', 'brand', 'price', 'description', 'bullets', 'images');
    }

    private function extractImages(array $product): array
    {
        $images = [];

        foreach (['images','media','gallery','productImages'] as $key) {
            if (!empty($product[$key]) && is_array($product[$key])) {
                foreach ($product[$key] as $img) {
                    if (is_string($img)) $images[] = $img;
                    elseif (is_array($img)) {
                        $url = $img['url'] ?? $img['src'] ?? $img['path'] ?? null;
                        if ($url) $images[] = $url;
                    }
                }
                if (!empty($images)) break;
            }
        }

        // Upgrade resolution
        return array_values(array_unique(array_map(
            fn($u) => preg_replace('/\/\d+\/\d+\//', '/832/832/', $u),
            $images
        )));
    }

    private function extractBrandFromTitle(string $title): ?string
    {
        // First word is often brand on Flipkart (e.g. "Samsung Galaxy S24...")
        $parts = explode(' ', trim($title));
        return $parts[0] ?? null;
    }

    /**
     * Normalize all scraped data into the standard format expected by
     * ProductImport model and the AI generation pipeline.
     */
    private function normalize(array $raw, string $url): array
    {
        $bullets = $raw['bullets'] ?? [];
        if (is_string($bullets)) {
            $bullets = array_filter(explode("\n", $bullets));
        }
        $bullets = array_values(array_filter(array_map('trim', $bullets)));

        $images = array_values(array_filter($raw['images'] ?? [], fn($u) => filter_var($u, FILTER_VALIDATE_URL)));

        return [
            'title'          => trim($raw['title'] ?? ''),
            'brand'          => trim($raw['brand'] ?? ''),
            'manufacturer'   => trim($raw['brand'] ?? ''),
            'price'          => $raw['price'] ?? null,
            'currency'       => 'INR',
            'description'    => trim(strip_tags($raw['description'] ?? '')),
            'bullets'        => $bullets,
            'images'         => $images,
            'category'       => $raw['category'] ?? null,
            'specifications' => $raw['specifications'] ?? [],
            'rating'         => $raw['rating'] ?? null,
            'reviews_count'  => $raw['reviews_count'] ?? null,
            'source_url'     => $url,
            'platform'       => 'flipkart',
        ];
    }
}
