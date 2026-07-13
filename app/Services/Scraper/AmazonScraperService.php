<?php

namespace App\Services\Scraper;

use App\Models\ProductImport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AmazonScraperService
{
    private array $headers;
    private int $timeout;
    private int $retries;

    public function __construct()
    {
        $this->headers = [
            'User-Agent' => config('services.scraper.user_agent', 
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'),
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection' => 'keep-alive',
            'Upgrade-Insecure-Requests' => '1',
            'Cache-Control' => 'max-age=0',
        ];
        $this->timeout = config('services.scraper.timeout', 30);
        $this->retries = config('services.scraper.retry_times', 3);
    }

    /**
     * Scrape Amazon product page and return structured data
     */
    public function scrape(string $url): array
    {
        $asin = $this->extractAsin($url);
        $normalizedUrl = $asin ? "https://www.amazon.com/dp/{$asin}" : $url;

        $html = $this->fetchPage($normalizedUrl);
        
        if (!$html) {
            throw new \Exception('Failed to fetch Amazon product page. The URL may be invalid or Amazon blocked the request.');
        }

        return $this->parseProductData($html, $normalizedUrl, $asin);
    }

    /**
     * Scrape and update ProductImport model
     */
    public function scrapeAndStore(ProductImport $import): ProductImport
    {
        $import->update(['status' => 'scraping']);

        try {
            $data = $this->scrape($import->amazon_url);

            // CRITICAL: Amazon frequently returns a 200 OK with no usable product
            // data (bot-check shell page, JS-only render, blocked request) instead
            // of an obvious CAPTCHA string. If we don't validate that we actually
            // got a title, the import gets marked "scraped" with empty fields and
            // the UI lies to the user ("Ready to Generate!" with nothing to generate).
            if (empty($data['title'])) {
                throw new \Exception(
                    'Amazon blocked the scrape request or returned no product data. '
                    . 'This commonly happens with server-side HTTP requests — Amazon requires '
                    . 'JavaScript execution and browser fingerprinting that simple HTTP requests '
                    . 'cannot replicate. Try the Playwright scraper fallback, or try again in a few minutes.'
                );
            }

            $import->update([
                'status' => 'scraped',
                'asin' => $data['asin'] ?? $import->asin,
                'original_title' => $data['title'],
                'original_brand' => $data['brand'],
                'original_manufacturer' => $data['manufacturer'],
                'original_description' => $data['description'],
                'original_bullet_points' => $data['bullet_points'],
                'original_specifications' => $data['specifications'],
                'original_images' => $data['images'],
                'original_category' => $data['category'],
                'original_attributes' => $data['attributes'],
                'product_weight' => $data['weight'],
                'product_dimensions' => $data['dimensions'],
                'original_price' => $data['price'],
                'original_price_currency' => $data['currency'],
                'raw_scraped_data' => $data,
                'scraped_at' => now(),
            ]);

        } catch (\Exception $e) {
            $import->update([
                'status' => 'failed',
                'scrape_error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $import->fresh();
    }

    /**
     * Extract ASIN from Amazon URL
     */
    public function extractAsin(string $url): ?string
    {
        // Pattern 1: /dp/ASIN
        if (preg_match('/\/dp\/([A-Z0-9]{10})/i', $url, $matches)) {
            return strtoupper($matches[1]);
        }
        // Pattern 2: /product/ASIN
        if (preg_match('/\/product\/([A-Z0-9]{10})/i', $url, $matches)) {
            return strtoupper($matches[1]);
        }
        // Pattern 3: /gp/product/ASIN
        if (preg_match('/\/gp\/product\/([A-Z0-9]{10})/i', $url, $matches)) {
            return strtoupper($matches[1]);
        }
        // Pattern 4: ASIN= query param
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $params);
        if (!empty($params['ASIN'])) {
            return strtoupper($params['ASIN']);
        }
        return null;
    }

    /**
     * Fetch HTML page with retries
     */
    private function fetchPage(string $url): ?string
    {
        for ($attempt = 1; $attempt <= $this->retries; $attempt++) {
            try {
                // Add random delay between retries
                if ($attempt > 1) {
                    sleep(rand(2, 5));
                }

                $response = Http::withHeaders($this->headers)
                    ->timeout($this->timeout)
                    ->get($url);

                if ($response->successful()) {
                    $html = $response->body();
                    // Check if we got a CAPTCHA / bot-check page (Amazon uses several
                    // different variants depending on marketplace and detection method)
                    $blockSignals = [
                        'Type the characters you see in this image',
                        'Robot Check',
                        'Enter the characters you see below',
                        "Sorry, we just need to make sure you're not a robot",
                        'To discuss automated access to Amazon data',
                        'api-services-support@amazon.com',
                        'captcha',
                    ];
                    foreach ($blockSignals as $signal) {
                        if (stripos($html, $signal) !== false) {
                            Log::warning("Amazon bot-check/CAPTCHA detected on attempt {$attempt} (matched: \"{$signal}\")");
                            continue 2; // retry the outer loop
                        }
                    }
                    // Sanity check: a real product page always has #productTitle
                    // or #title in the markup. If neither is present, Amazon
                    // served us an empty shell / block page we don't recognize yet.
                    if (!str_contains($html, 'id="productTitle"') && !str_contains($html, 'id="title"')) {
                        Log::warning("Amazon response on attempt {$attempt} has no recognizable product markup — likely blocked or JS-rendered shell.");
                        continue;
                    }
                    return $html;
                }

                Log::warning("Amazon scrape attempt {$attempt} failed: HTTP {$response->status()}");

            } catch (\Exception $e) {
                Log::error("Amazon scrape attempt {$attempt} exception: " . $e->getMessage());
                if ($attempt === $this->retries) throw $e;
            }
        }

        Log::error("Amazon scraper exhausted all {$this->retries} attempts for URL: {$url} — Amazon is blocking server-side requests (bot detection / CAPTCHA / JS-only render).");

        return null;
    }

    /**
     * Parse product data from HTML using DOM
     */
    private function parseProductData(string $html, string $url, ?string $asin): array
    {
        // Use PHP DOM for parsing
        $dom = new \DOMDocument();
        @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);

        $data = [
            'asin' => $asin,
            'url' => $url,
            'title' => $this->extractTitle($xpath),
            'brand' => $this->extractBrand($xpath, $html),
            'manufacturer' => $this->extractManufacturer($xpath, $html),
            'description' => $this->extractDescription($xpath, $html),
            'bullet_points' => $this->extractBulletPoints($xpath),
            'specifications' => $this->extractSpecifications($xpath),
            'images' => $this->extractImages($xpath, $html),
            'category' => $this->extractCategory($xpath),
            'attributes' => $this->extractAttributes($xpath),
            'weight' => $this->extractWeight($html),
            'dimensions' => $this->extractDimensions($html),
            'price' => $this->extractPrice($xpath),
            'currency' => 'USD',
            'scraped_at' => now()->toIso8601String(),
        ];

        return $data;
    }

    private function extractTitle(\DOMXPath $xpath): ?string
    {
        $selectors = [
            '//span[@id="productTitle"]',
            '//h1[@id="title"]',
            '//h1[contains(@class,"a-size-large")]',
        ];
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                return trim($nodes->item(0)->textContent);
            }
        }
        return null;
    }

    private function extractBrand(\DOMXPath $xpath, string $html): ?string
    {
        // Try DOM selectors
        $selectors = [
            '//a[@id="bylineInfo"]',
            '//span[@class="author"]',
            '//*[@id="brand"]',
        ];
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                $text = trim($nodes->item(0)->textContent);
                $text = preg_replace('/^(Brand:|Visit the|Store)[\s:]*/i', '', $text);
                if (!empty($text)) return trim($text);
            }
        }
        // Try regex on HTML
        if (preg_match('/"brand"\s*:\s*"([^"]+)"/i', $html, $m)) return $m[1];
        if (preg_match('/data-brand="([^"]+)"/i', $html, $m)) return $m[1];
        return null;
    }

    private function extractManufacturer(\DOMXPath $xpath, string $html): ?string
    {
        if (preg_match('/Manufacturer[:\s]+([^\n<]{3,60})/i', $html, $m)) {
            return trim(strip_tags($m[1]));
        }
        return null;
    }

    private function extractDescription(\DOMXPath $xpath, string $html): ?string
    {
        $selectors = [
            '//*[@id="productDescription"]//p',
            '//*[@id="aplus_feature_div"]//div',
            '//*[@id="feature-bullets"]',
        ];
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                $text = '';
                foreach ($nodes as $node) {
                    $text .= ' ' . trim($node->textContent);
                }
                $text = trim($text);
                if (strlen($text) > 50) return $text;
            }
        }
        return null;
    }

    private function extractBulletPoints(\DOMXPath $xpath): array
    {
        $bullets = [];
        $selectors = [
            '//*[@id="feature-bullets"]//li/span[not(@class="a-list-item")]',
            '//*[@id="feature-bullets"]//li[@class="a-spacing-mini"]//span',
            '//*[@id="feature-bullets"]//span[@class="a-list-item"]',
        ];
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                foreach ($nodes as $node) {
                    $text = trim($node->textContent);
                    if (strlen($text) > 10 && !in_array($text, $bullets)) {
                        $bullets[] = $text;
                    }
                }
                if (!empty($bullets)) break;
            }
        }
        return array_slice($bullets, 0, 10);
    }

    private function extractSpecifications(\DOMXPath $xpath): array
    {
        $specs = [];
        $tableNodes = $xpath->query('//*[@id="productDetails_techSpec_section_1"]//tr | //*[@id="prodDetails"]//tr');
        if ($tableNodes) {
            foreach ($tableNodes as $row) {
                $cells = $xpath->query('.//td|.//th', $row);
                if ($cells && $cells->length >= 2) {
                    $key = trim($cells->item(0)->textContent);
                    $val = trim($cells->item(1)->textContent);
                    if (!empty($key) && !empty($val)) {
                        $specs[$key] = $val;
                    }
                }
            }
        }
        return $specs;
    }

    private function extractImages(\DOMXPath $xpath, string $html): array
    {
        $images = [];
        // Try to extract from JSON data in page
        if (preg_match_all('/"hiRes"\s*:\s*"(https:[^"]+)"/', $html, $m)) {
            $images = array_unique($m[1]);
        }
        if (empty($images) && preg_match_all('/"large"\s*:\s*"(https:[^"]+\.jpg[^"]*)"/', $html, $m)) {
            $images = array_unique($m[1]);
        }
        // Fallback: img tags
        if (empty($images)) {
            $nodes = $xpath->query('//img[@id="landingImage"]|//img[@id="imgBlkFront"]');
            if ($nodes) {
                foreach ($nodes as $img) {
                    $src = $img->getAttribute('data-old-hires') ?: $img->getAttribute('src');
                    if ($src && str_contains($src, 'amazon')) {
                        $images[] = $src;
                    }
                }
            }
        }
        return array_values(array_slice(array_filter($images), 0, 10));
    }

    private function extractCategory(\DOMXPath $xpath): ?string
    {
        $nodes = $xpath->query('//*[@id="wayfinding-breadcrumbs_feature_div"]//a');
        if ($nodes && $nodes->length > 0) {
            $cats = [];
            foreach ($nodes as $node) {
                $text = trim($node->textContent);
                if (!empty($text)) $cats[] = $text;
            }
            return implode(' > ', $cats);
        }
        return null;
    }

    private function extractAttributes(\DOMXPath $xpath): array
    {
        $attrs = [];
        $nodes = $xpath->query('//*[contains(@id,"detail-bullets")]//li');
        if ($nodes) {
            foreach ($nodes as $node) {
                $text = trim($node->textContent);
                if (str_contains($text, ':')) {
                    [$k, $v] = explode(':', $text, 2);
                    $attrs[trim($k)] = trim($v);
                }
            }
        }
        return $attrs;
    }

    private function extractWeight(string $html): ?string
    {
        if (preg_match('/Item Weight[:\s]+([\d\.,]+ (?:pounds?|ounces?|kg|grams?|lbs?))/i', $html, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function extractDimensions(string $html): ?string
    {
        if (preg_match('/(?:Product Dimensions?|Package Dimensions?)[:\s]+([^\n<]{5,60})/i', $html, $m)) {
            return trim(strip_tags($m[1]));
        }
        return null;
    }

    private function extractPrice(\DOMXPath $xpath): ?float
    {
        $selectors = [
            '//*[@id="priceblock_ourprice"]',
            '//*[@id="priceblock_dealprice"]',
            '//*[contains(@class,"a-price-whole")]',
            '//*[@id="price"]',
        ];
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                $text = trim($nodes->item(0)->textContent);
                $clean = preg_replace('/[^0-9.]/', '', $text);
                if (is_numeric($clean)) return (float)$clean;
            }
        }
        return null;
    }
}
