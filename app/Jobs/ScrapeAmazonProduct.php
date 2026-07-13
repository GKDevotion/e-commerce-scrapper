<?php

namespace App\Jobs;

use App\Models\ProductImport;
use App\Services\Scraper\AmazonScraperService;
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

    public int $tries = 3;
    public int $timeout = 150;
    public int $backoff = 30;

    public function __construct(public ProductImport $import) {}

    public function handle(PlaywrightScraperService $playwrightScraper, AmazonScraperService $httpScraper): void
    {
        $driver = config('services.scraper.driver', 'playwright');

        try {
            if ($driver === 'playwright' && $playwrightScraper->isAvailable()) {
                $playwrightScraper->scrapeAndStore($this->import);
                Log::info("Successfully scraped product import #{$this->import->id} via Playwright");
            } else {
                if ($driver === 'playwright') {
                    Log::warning(
                        "Playwright scraper not installed (run `cd scraper-service && npm install`), "
                        . "falling back to HTTP scraper for import #{$this->import->id}. "
                        . "HTTP scraping is frequently blocked by Amazon's bot detection."
                    );
                }
                $httpScraper->scrapeAndStore($this->import);
                Log::info("Successfully scraped product import #{$this->import->id} via HTTP fallback");
            }
        } catch (\Exception $e) {
            Log::error("Failed to scrape product import #{$this->import->id}: " . $e->getMessage());

            if ($this->attempts() >= $this->tries) {
                $this->import->update([
                    'status'       => 'failed',
                    'scrape_error' => "All {$this->tries} scraping attempts failed. Last error: " . $e->getMessage(),
                ]);
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->import->update([
            'status'       => 'failed',
            'scrape_error' => 'Job failed after all retries: ' . $exception->getMessage(),
        ]);
    }
}
