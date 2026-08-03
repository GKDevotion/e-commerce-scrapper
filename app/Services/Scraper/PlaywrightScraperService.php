<?php

namespace App\Services\Scraper;

use App\Models\ProductImport;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Real Amazon scraper backed by a headless Chromium browser (Playwright),
 * run as a Node.js sidecar process. This is the reliable path — plain HTTP
 * requests (see AmazonScraperService) are routinely blocked by Amazon's bot
 * detection because they can't execute JS, lack a real browser fingerprint,
 * and never pass behavioral checks.
 *
 * Requires the Node sidecar to be installed once:
 *   cd scraper-service && npm install
 *
 * Configure the node binary / script path via .env if needed:
 *   SCRAPER_NODE_BINARY=node
 *   SCRAPER_SCRIPT_PATH=scraper-service/scrape.js
 */
class PlaywrightScraperService
{
    private string $nodeBinary;
    private string $scriptPath;
    private int $timeoutSeconds;

    public function __construct()
    {
        $this->nodeBinary    = config('services.scraper.node_binary', 'node');
        $this->scriptPath    = config('services.scraper.playwright_script', base_path('scraper-service/scrape.js'));
        $this->timeoutSeconds = (int) config('services.scraper.playwright_timeout', 60);
    }

    /**
     * Returns true if the Node sidecar appears to be installed and ready.
     */
    public function isAvailable(): bool
    {
        if (!file_exists($this->scriptPath)) {
            return false;
        }

        $nodeModules = dirname($this->scriptPath) . '/node_modules/playwright';
        return is_dir($nodeModules);
    }

    /**
     * Scrape an Amazon product URL via the Playwright sidecar.
     *
     * @throws \Exception on scrape failure (blocked, timeout, no markup, etc.)
     */
    public function scrape(string $url): array
    {
        if (!$this->isAvailable()) {
            throw new \Exception(
                'Playwright scraper is not installed. Run `cd scraper-service && npm install` on the server '
                . 'to enable reliable Amazon scraping. Falling back to the HTTP scraper, which Amazon frequently blocks.'
            );
        }

        $process = new Process(
            [$this->nodeBinary, $this->scriptPath, $url],
            base_path('scraper-service'),
            null,
            null,
            $this->timeoutSeconds
        );

        $process->run();

        $output = trim($process->getOutput());
        $errorOutput = trim($process->getErrorOutput());

        if (!$process->isSuccessful()) {
            Log::error('Playwright scraper process failed', [
                'url'    => $url,
                'stdout' => $output,
                'stderr' => $errorOutput,
                'exit'   => $process->getExitCode(),
            ]);
        }

        // The script always prints JSON to stdout, even on failure, so try
        // to parse it regardless of exit code before falling back to a
        // generic process-failure exception.
        $decoded = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE || $decoded === null) {
            throw new \Exception(
                'Playwright scraper produced no valid output. '
                . ($errorOutput ?: 'The browser process may have crashed or timed out.')
            );
        }

        if (isset($decoded['error'])) {
            throw new \Exception($this->humanizeError($decoded['error']));
        }

        if (empty($decoded['title'])) {
            throw new \Exception('Playwright scraper ran but returned no product title. The page may not be a valid Amazon product listing.');
        }

        return $decoded;
    }

    /**
     * Scrape and persist results onto a ProductImport, with automatic
     * fallback to the HTTP scraper if Playwright is unavailable.
     */
    public function scrapeAndStore(ProductImport $import): ProductImport
    {
        $import->update(['status' => 'scraping']);

        try {
            $data = $this->scrape($import->amazon_url);

            $import->update([
                'status'                  => 'scraped',
                'asin'                    => $data['asin'] ?? $import->asin,
                'original_title'          => $data['title'],
                'original_brand'          => $data['brand'],
                'original_manufacturer'   => $data['manufacturer'],
                'original_description'    => $data['description'],
                'original_bullet_points'  => $data['bullet_points'],
                'original_specifications' => $data['specifications'],
                'original_images'         => $data['images'],
                'original_category'       => $data['category'],
                'original_attributes'     => $data['attributes'],
                'product_weight'          => $data['weight'],
                'product_dimensions'      => $data['dimensions'],
                'original_price'          => $data['price'],
                'original_price_currency' => $data['currency'],
                'raw_scraped_data'        => $data,
                'scraped_at'              => now(),
            ]);

        } catch (\Exception $e) {
            // Fall back to the HTTP scraper as a last resort if Playwright
            // itself is the problem (not installed, crashed), so the import
            // doesn't dead-end if at all avoidable.
            if (!$this->isAvailable()) {
                Log::warning('Playwright unavailable, falling back to HTTP scraper', ['import_id' => $import->id]);
                return app(AmazonScraperService::class)->scrapeAndStore($import);
            }

            $import->update([
                'status'       => 'failed',
                'scrape_error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $import->fresh();
    }

    private function humanizeError(string $rawError): string
    {
        if (str_starts_with($rawError, 'BLOCKED:')) {
            return 'Amazon detected automated access and served a CAPTCHA / bot-check page. '
                . 'This can happen even with a real browser if the IP has been flagged. Try again shortly, '
                . 'or use a residential proxy / different IP range.';
        }

        if (str_starts_with($rawError, 'NO_PRODUCT_MARKUP:')) {
            return 'The page loaded but did not look like a valid Amazon product page. '
                . 'Double-check the URL points to a real product (e.g. has /dp/ASIN in it).';
        }

        if (str_contains($rawError, 'Timeout') || str_contains($rawError, 'timeout')) {
            return 'The scraper timed out waiting for Amazon to respond. The site may be slow or blocking this server\'s IP.';
        }

        return $rawError;
    }
}
