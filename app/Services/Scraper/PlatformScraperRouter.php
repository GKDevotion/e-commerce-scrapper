<?php

namespace App\Services\Scraper;

/**
 * Detects the e-commerce platform from a product URL and routes to the
 * appropriate scraper service. All scrapers return the same normalized
 * data shape so the rest of the pipeline (AI, export, images) is unchanged.
 */
class PlatformScraperRouter
{
    // ── Platform detection ────────────────────────────────────────────────────

    public static function detectPlatform(string $url): string
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        if (str_contains($host, 'flipkart.com')) return 'flipkart';
        if (str_contains($host, 'meesho.com'))   return 'meesho';
        if (self::isAmazonHost($host))            return 'amazon';

        throw new \InvalidArgumentException(
            "Unsupported platform URL. Supported: Amazon, Flipkart, Meesho. Got: {$host}"
        );
    }

    public static function isAmazonHost(string $host): bool
    {
        $amazonDomains = [
            'amazon.com', 'amazon.in', 'amazon.co.uk', 'amazon.de',
            'amazon.ca', 'amazon.fr', 'amazon.es', 'amazon.it',
            'amazon.co.jp', 'amazon.com.au', 'amazon.com.br',
            'amazon.com.mx', 'amazon.ae', 'amazon.sg',
        ];
        return collect($amazonDomains)->contains(fn($d) => str_ends_with($host, $d));
    }

    public static function isSupported(string $url): bool
    {
        try {
            self::detectPlatform($url);
            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    public static function getPlatformLabel(string $platform): string
    {
        return match($platform) {
            'flipkart' => 'Flipkart',
            'meesho'   => 'Meesho',
            default    => 'Amazon',
        };
    }

    public static function getPlatformColor(string $platform): string
    {
        return match($platform) {
            'flipkart' => '#2874F0',
            'meesho'   => '#F43397',
            default    => '#FF9900',
        };
    }

    public static function getPlatformIcon(string $platform): string
    {
        return match($platform) {
            'flipkart' => '🛍️',
            'meesho'   => '🛒',
            default    => '📦',
        };
    }

    // ── Scrape dispatch ───────────────────────────────────────────────────────

    /**
     * Detect platform and scrape in one call.
     * Returns ['platform' => '...', 'data' => [...normalized product data...]].
     *
     * @throws \InvalidArgumentException for unsupported platform
     * @throws \Exception on scrape failure
     */
    public function scrape(string $url): array
    {
        $platform = self::detectPlatform($url);
        $service  = $this->resolveService($platform);
        $data     = $service->scrape($url);

        return ['platform' => $platform, 'data' => $data];
    }

    /**
     * Extract the platform-specific product ID from a URL.
     */
    public function extractProductId(string $url): ?string
    {
        $platform = self::detectPlatform($url);
        $service  = $this->resolveService($platform);

        return match($platform) {
            'flipkart' => $service->extractProductId($url),
            'meesho'   => $service->extractProductId($url),
            default    => app(AmazonScraperService::class)->extractAsin($url),
        };
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function resolveService(string $platform): object
    {
        return match($platform) {
            'flipkart' => app(FlipkartScraperService::class),
            'meesho'   => app(MeeshoScraperService::class),
            default    => app(AmazonScraperService::class),
        };
    }
}
