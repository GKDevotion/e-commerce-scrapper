<?php

namespace App\Jobs;

use App\Models\ProductImport;
use App\Services\Scraper\AmazonScraperService;
use App\Services\Scraper\FlipkartScraperService;
use App\Services\Scraper\MeeshoScraperService;
use App\Services\Scraper\PlaywrightScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeAmazonProduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 150;
    public int $backoff = 30;

    public function __construct(public ProductImport $import) {}

    public function handle(
        PlaywrightScraperService $playwright,
        AmazonScraperService     $amazonHttp,
        FlipkartScraperService   $flipkart,
        MeeshoScraperService     $meesho,
    ): void {
        $url = $this->import->amazon_url;

        // PRIMARY: read platform from DB column
        // FALLBACK: detect from URL in case the DB column doesn't exist yet
        //           (happens when migration 000007 hasn't been run)
        $platform = $this->resolvePlatform($url);

        Log::info("ScrapeJob #{$this->import->id}: platform={$platform}, url={$url}");

        try {
            $this->import->update(['status' => 'scraping']);

            match ($platform) {
                'flipkart' => $this->scrapeFlipkart($flipkart, $url),
                'meesho'   => $this->scrapeMeesho($meesho, $url),
                default    => $this->scrapeAmazon($playwright, $amazonHttp, $url),
            };

            Log::info("ScrapeJob #{$this->import->id}: completed ({$platform})");

        } catch (\Exception $e) {
            Log::error("ScrapeJob #{$this->import->id} ({$platform}) attempt #{$this->attempts()} failed: " . $e->getMessage());

            if ($this->attempts() >= $this->tries) {
                $this->import->update([
                    'status'       => 'failed',
                    'scrape_error' => "Scraping failed after {$this->tries} attempts. Last error: " . $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Resolve platform with URL-based fallback.
     *
     * Priority:
     *  1. DB column value (set correctly by ProductImportController)
     *  2. URL hostname detection (fallback if column missing/null)
     *  3. 'amazon' as last resort
     */
    private function resolvePlatform(string $url): string
    {
        // Try DB column first
        try {
            $dbValue = $this->import->platform;
            if ($dbValue && in_array($dbValue, ['amazon', 'flipkart', 'meesho'])) {
                return $dbValue;
            }
        } catch (\Exception $e) {
            Log::warning("ScrapeJob #{$this->import->id}: could not read platform column ({$e->getMessage()}), detecting from URL");
        }

        // Detect from URL hostname
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        if (str_contains($host, 'flipkart.com')) {
            Log::info("ScrapeJob #{$this->import->id}: platform detected from URL = flipkart");
            return 'flipkart';
        }
        if (str_contains($host, 'meesho.com')) {
            Log::info("ScrapeJob #{$this->import->id}: platform detected from URL = meesho");
            return 'meesho';
        }

        return 'amazon';
    }

    // ── Amazon ────────────────────────────────────────────────────────────────

    private function scrapeAmazon(
        PlaywrightScraperService $playwright,
        AmazonScraperService     $http,
        string                   $url
    ): void {
        $driver = config('services.scraper.driver', 'playwright');

        if ($driver === 'playwright' && $playwright->isAvailable()) {
            $playwright->scrapeAndStore($this->import);
            return;
        }

        if ($driver === 'playwright') {
            Log::warning("ScrapeJob #{$this->import->id}: Playwright unavailable, using HTTP fallback. "
                . "Fix: cd scraper-service && npm install && npx playwright install chromium");
        }

        $http->scrapeAndStore($this->import);
    }

    // ── Flipkart ──────────────────────────────────────────────────────────────

    private function scrapeFlipkart(FlipkartScraperService $scraper, string $url): void
    {
        $data = $scraper->scrape($url);
        $this->storeScrapedData($data);
    }

    // ── Meesho ────────────────────────────────────────────────────────────────

    private function scrapeMeesho(MeeshoScraperService $scraper, string $url): void
    {
        $data = $scraper->scrape($url);
        $this->storeScrapedData($data);
    }

    // ── Data writer (Flipkart + Meesho) ───────────────────────────────────────

    private function storeScrapedData(array $data): void
    {
        if (empty($data['title'])) {
            throw new \Exception(
                'Scraper returned no product title. '
                . 'The page may require Playwright. '
                . 'Fix: cd scraper-service && npm install && npx playwright install chromium'
            );
        }

        $this->import->update([
            'status'                  => 'scraped',
            'original_title'          => $data['title'],
            'original_brand'          => $data['brand']          ?? null,
            'original_manufacturer'   => $data['manufacturer']   ?? $data['brand'] ?? null,
            'original_description'    => $data['description']    ?? null,
            'original_bullet_points'  => $data['bullets']        ?? [],
            'original_images'         => $data['images']         ?? [],
            'original_category'       => $data['category']       ?? null,
            'original_specifications' => $data['specifications'] ?? [],
            'original_price'          => $data['price']          ?? null,
            'original_price_currency' => $data['currency']       ?? 'INR',
            'scraped_at'              => now(),
        ]);
    }

    // ── Permanent failure ─────────────────────────────────────────────────────

    public function failed(\Throwable $e): void
    {
        Log::error("ScrapeJob #{$this->import->id} permanently failed: " . $e->getMessage());

        $this->import->update([
            'status'       => 'failed',
            'scrape_error' => 'Job failed after all retries: ' . $e->getMessage(),
        ]);
    }
}
