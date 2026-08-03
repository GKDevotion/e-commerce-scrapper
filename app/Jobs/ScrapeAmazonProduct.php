<?php

namespace App\Jobs;

use App\Models\ProductImport;
use App\Services\Scraper\AmazonScraperService;
use App\Services\Scraper\FlipkartScraperService;
use App\Services\Scraper\MeeshoScraperService;
use App\Services\Scraper\PlaywrightScraperService;
use App\Services\Scraper\PlatformScraperRouter;
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
        $platform = $this->import->platform ?? 'amazon';
        $url      = $this->import->amazon_url;
        $driver   = config('services.scraper.driver', 'playwright');

        try {
            $this->import->update(['status' => 'scraping']);

            $data = match($platform) {
                'flipkart' => $flipkart->scrape($url),
                'meesho'   => $meesho->scrape($url),
                default    => $this->scrapeAmazon($playwright, $amazonHttp, $url, $driver),
            };

            $this->storeData($data);

            Log::info("Scraped {$platform} import #{$this->import->id} via " .
                ($platform === 'amazon' ? $driver : 'dedicated scraper'));

        } catch (\Exception $e) {
            Log::error("Failed to scrape {$platform} import #{$this->import->id}: " . $e->getMessage());

            if ($this->attempts() >= $this->tries) {
                $this->import->update([
                    'status'       => 'failed',
                    'scrape_error' => "All {$this->tries} attempts failed. Last: " . $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    private function scrapeAmazon(
        PlaywrightScraperService $playwright,
        AmazonScraperService $http,
        string $url,
        string $driver
    ): array {
        if ($driver === 'playwright' && $playwright->isAvailable()) {
            $playwright->scrapeAndStore($this->import);
            return []; // scrapeAndStore writes directly — return empty to skip storeData
        }

        if ($driver === 'playwright') {
            Log::warning("Playwright not installed, falling back to HTTP for import #{$this->import->id}.");
        }

        $http->scrapeAndStore($this->import);
        return []; // same — scrapeAndStore writes directly
    }

    /**
     * Store normalized scraper data for Flipkart/Meesho imports.
     * Amazon uses its own scrapeAndStore() internally.
     */
    private function storeData(array $data): void
    {
        if (empty($data)) return; // Amazon path — already stored

        if (empty($data['title'])) {
            throw new \Exception('No product title found in scraped data.');
        }

        $this->import->update([
            'status'                  => 'scraped',
            'original_title'          => $data['title'],
            'original_brand'          => $data['brand'] ?? null,
            'original_manufacturer'   => $data['manufacturer'] ?? $data['brand'] ?? null,
            'original_description'    => $data['description'] ?? null,
            'original_bullet_points'  => $data['bullets'] ?? [],
            'original_images'         => $data['images'] ?? [],
            'original_category'       => $data['category'] ?? null,
            'original_specifications' => $data['specifications'] ?? [],
            'original_price'          => $data['price'] ?? null,
            'original_price_currency' => $data['currency'] ?? 'INR',
            'scraped_at'              => now(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        $this->import->update([
            'status'       => 'failed',
            'scrape_error' => 'Job failed after all retries: ' . $e->getMessage(),
        ]);
    }
}
