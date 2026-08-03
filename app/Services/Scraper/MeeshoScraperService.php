<?php

namespace App\Services\Scraper;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Scrapes product data from Meesho product pages.
 *
 * Meesho is a React SPA — most product data is loaded via their internal
 * GraphQL API. This service tries two approaches:
 *   1. Meesho's internal product API (fastest, most reliable)
 *   2. Playwright headless browser (fallback)
 *
 * Meesho product URLs look like:
 *   https://meesho.com/product-name/p/123456789
 *   https://meesho.com/product-name/p/123456789?sourceid=...
 */
class MeeshoScraperService
{
    private string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36';
    private string $apiBase = 'https://meesho.com/api';

    /**
     * Main entry — returns normalized product data.
     *
     * @throws \Exception on failure
     */
    public function scrape(string $url): array
    {
        $productId = $this->extractProductId($url);

        // Try the internal API first — much more reliable than HTML scraping
        if ($productId) {
            try {
                return $this->scrapeApi($productId, $url);
            } catch (\Exception $e) {
                Log::info("Meesho API scrape failed (pid={$productId}): " . $e->getMessage());
            }
        }

        // Playwright fallback
        try {
            return $this->scrapePlaywright($url);
        } catch (\Exception $e) {
            Log::info("Meesho Playwright scrape failed: " . $e->getMessage());
        }

        // Last resort: direct HTTP
        return $this->scrapeHttp($url);
    }

    /**
     * Extract the numeric product ID from a Meesho URL.
     * https://meesho.com/some-product-name/p/123456789
     */
    public function extractProductId(string $url): ?string
    {
        if (preg_match('~/p/(\d+)~', $url, $m)) {
            return $m[1];
        }
        return null;
    }

    // ── Private ───────────────────────────────────────────────────────────────

    /**
     * Meesho exposes an internal GraphQL-like REST API.
     * Endpoint: POST https://meesho.com/api/v1/products/{id}
     */
    private function scrapeApi(string $productId, string $originalUrl): array
    {
        // Meesho internal product API (discovered via network inspection)
        $response = Http::withHeaders([
            'User-Agent'      => $this->userAgent,
            'Accept'          => 'application/json',
            'Accept-Language' => 'en-IN,en;q=0.9',
            'Referer'         => 'https://meesho.com/',
            'Origin'          => 'https://meesho.com',
            'x-request-id'   => \Illuminate\Support\Str::uuid(),
        ])->timeout(20)->get("https://meesho.com/api/v1/products/{$productId}");

        if (!$response->successful()) {
            // Try alternate endpoint
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
                'Accept'     => 'application/json',
                'Referer'    => $originalUrl,
            ])->timeout(20)->get("https://meesho.com/api/v2/product_variants/{$productId}");
        }

        if (!$response->successful()) {
            throw new \Exception("Meesho API returned {$response->status()}");
        }

        $json = $response->json();
        $product = $json['data'] ?? $json['product'] ?? $json ?? [];

        $title = $product['name'] ?? $product['title'] ?? $product['product_name'] ?? null;

        if (!$title) {
            throw new \Exception('Meesho API: no product title in response');
        }

        // Extract images — Meesho uses image_urls array
        $images = [];
        foreach ($product['image_urls'] ?? $product['images'] ?? [] as $img) {
            $url = is_string($img) ? $img : ($img['url'] ?? $img['src'] ?? null);
            if ($url) $images[] = $this->upgradeImageResolution($url);
        }

        // Bullets from description_sections or highlights
        $bullets = [];
        foreach ($product['description_sections'] ?? [] as $section) {
            if (!empty($section['data'])) {
                foreach ((array)$section['data'] as $item) {
                    $text = is_string($item) ? $item : ($item['text'] ?? $item['value'] ?? null);
                    if ($text) $bullets[] = trim($text);
                }
            }
        }
        if (empty($bullets) && !empty($product['highlights'])) {
            $bullets = (array)$product['highlights'];
        }

        // Specifications from product attributes
        $specifications = [];
        foreach ($product['product_attributes'] ?? $product['attributes'] ?? [] as $attr) {
            $k = $attr['name'] ?? $attr['key'] ?? null;
            $v = $attr['value'] ?? null;
            if ($k && $v) $specifications[$k] = $v;
        }

        return $this->normalize([
            'title'          => $title,
            'brand'          => $product['brand'] ?? $product['supplier_name'] ?? $this->extractBrandFromTitle($title),
            'price'          => $product['price'] ?? $product['mrp'] ?? null,
            'description'    => $product['description'] ?? $product['short_description'] ?? null,
            'bullets'        => $bullets,
            'images'         => $images,
            'category'       => $product['category'] ?? $product['category_name'] ?? null,
            'specifications' => $specifications,
            'rating'         => $product['rating'] ?? $product['average_rating'] ?? null,
            'reviews_count'  => $product['review_count'] ?? $product['ratings_count'] ?? null,
        ], $originalUrl);
    }

    private function scrapePlaywright(string $url): array
    {
        $scraperScript = base_path('scraper-service/scrape.js');

        if (!file_exists($scraperScript)) {
            throw new \Exception('Playwright scraper not found');
        }

        $output = shell_exec("node " . escapeshellarg($scraperScript) . " " . escapeshellarg($url) . " 2>&1");

        if (!$output) throw new \Exception('Playwright returned no output');

        $data = json_decode($output, true);
        if (!$data || !empty($data['error'])) {
            throw new \Exception($data['error'] ?? 'Playwright scrape failed');
        }

        return $this->normalize([
            'title'       => $data['title'] ?? null,
            'brand'       => $data['brand'] ?? $this->extractBrandFromTitle($data['title'] ?? ''),
            'price'       => $data['price'] ?? null,
            'description' => $data['description'] ?? null,
            'bullets'     => $data['bullets'] ?? $data['highlights'] ?? [],
            'images'      => $data['images'] ?? [],
            'category'    => $data['category'] ?? null,
            'specifications' => $data['specifications'] ?? [],
        ], $url);
    }

    private function scrapeHttp(string $url): array
    {
        $response = Http::withHeaders([
            'User-Agent'      => $this->userAgent,
            'Accept'          => 'text/html,application/xhtml+xml,*/*;q=0.8',
            'Accept-Language' => 'en-IN,en;q=0.9',
            'Referer'         => 'https://meesho.com/',
        ])->timeout(30)->get($url);

        if (!$response->successful()) {
            throw new \Exception("HTTP {$response->status()} from Meesho");
        }

        $html = $response->body();

        if (str_contains($html, 'captcha') || strlen($html) < 5000) {
            throw new \Exception('Meesho returned a bot-challenge or empty page. Use Playwright scraper.');
        }

        return $this->parseHtml($html, $url);
    }

    private function parseHtml(string $html, string $url): array
    {
        $title = null;
        $price = null;
        $brand = null;
        $description = null;
        $bullets = [];
        $images = [];

        // Meesho embeds __NEXT_DATA__ JSON (Next.js)
        if (preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $m)) {
            try {
                $nextData = json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
                $props    = $nextData['props']['pageProps'] ?? [];
                $product  = $props['product'] ?? $props['productDetail'] ?? $props['initialData']['product'] ?? [];

                $title   = $product['name'] ?? $product['title'] ?? null;
                $brand   = $product['brand'] ?? null;
                $price   = $product['price'] ?? $product['mrp'] ?? null;
                $description = $product['description'] ?? null;

                foreach ($product['image_urls'] ?? $product['images'] ?? [] as $img) {
                    $imgUrl = is_string($img) ? $img : ($img['url'] ?? null);
                    if ($imgUrl) $images[] = $this->upgradeImageResolution($imgUrl);
                }

                foreach ($product['description_sections'] ?? [] as $sec) {
                    foreach ((array)($sec['data'] ?? []) as $item) {
                        $t = is_string($item) ? $item : ($item['text'] ?? null);
                        if ($t) $bullets[] = trim($t);
                    }
                }
            } catch (\Exception $e) {
                // Fall through to regex extraction
            }
        }

        // Regex fallbacks
        if (!$title && preg_match('/<h1[^>]*class="[^"]*sc-[^"]*"[^>]*>([^<]+)<\/h1>/i', $html, $m)) {
            $title = html_entity_decode(trim($m[1]));
        }
        if (!$title && preg_match('/<meta property="og:title" content="([^"]+)"/i', $html, $m)) {
            $title = html_entity_decode(trim($m[1]));
        }
        if (!$description && preg_match('/<meta property="og:description" content="([^"]+)"/i', $html, $m)) {
            $description = html_entity_decode(trim($m[1]));
        }
        if (empty($images)) {
            preg_match_all('/"(https:\/\/images\.meesho\.com\/images\/products\/[^"]+)"/', $html, $m);
            foreach (array_unique($m[1] ?? []) as $img) {
                $images[] = $this->upgradeImageResolution($img);
            }
        }

        if (!$title) {
            throw new \Exception('Meesho: could not extract product title. Enable Playwright for JS-rendered pages.');
        }

        return $this->normalize(compact('title','brand','price','description','bullets','images'), $url);
    }

    private function upgradeImageResolution(string $url): string
    {
        // Meesho uses /128/128/, /400/400/ etc — upgrade to /600/600/
        return preg_replace('/\/\d+\/\d+\//', '/600/600/', $url);
    }

    private function extractBrandFromTitle(string $title): ?string
    {
        $parts = explode(' ', trim($title));
        return $parts[0] ?? null;
    }

    private function normalize(array $raw, string $url): array
    {
        $bullets = $raw['bullets'] ?? [];
        if (is_string($bullets)) {
            $bullets = array_filter(explode("\n", $bullets));
        }
        $bullets = array_values(array_filter(array_map('trim', $bullets)));

        $images = array_values(array_filter(
            $raw['images'] ?? [],
            fn($u) => filter_var($u, FILTER_VALIDATE_URL)
        ));

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
            'platform'       => 'meesho',
        ];
    }
}
