<?php

namespace App\Services\Export;

use App\Models\AiGeneration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Downloads original Amazon product images (scraped as remote URLs) and
 * packages them into a ZIP for the user, named after their brand/SKU
 * rather than Amazon's CDN filenames.
 */
class ImageDownloadService
{
    private int $timeout;
    private string $userAgent;

    public function __construct()
    {
        $this->timeout   = (int) config('services.scraper.timeout', 30);
        $this->userAgent = config('services.scraper.user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    }

    /**
     * Download all product images for a generation and zip them.
     * Returns ['path' => storage-relative path, 'name' => filename, 'size' => bytes, 'count' => int].
     *
     * @throws \Exception if no images are available or all downloads fail
     */
    public function downloadAsZip(AiGeneration $generation): array
    {
        $import = $generation->productImport;
        $imageUrls = $import->original_images ?? [];

        if (empty($imageUrls)) {
            throw new \Exception('No images were found for this product. The Amazon listing may not have had extractable images.');
        }

        $brandSlug = Str::slug($generation->brand_name ?: 'product');
        $asin      = $import->asin ?: 'product';

        $tempDir = storage_path('app/tmp/images_' . $generation->id . '_' . Str::random(8));
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $downloaded = [];
        $failed = [];

        try {
            foreach ($imageUrls as $index => $url) {
                $num = $index + 1;
                try {
                    $bytes = $this->fetchImageBytes($url);
                    $ext = $this->guessExtension($url, $bytes);
                    $filename = sprintf('%s-%s-%02d.%s', $brandSlug, $asin, $num, $ext);
                    file_put_contents($tempDir . '/' . $filename, $bytes);
                    $downloaded[] = $filename;
                } catch (\Exception $e) {
                    Log::warning("Failed to download image {$num} for generation #{$generation->id}: " . $e->getMessage());
                    $failed[] = $url;
                }
            }

            if (empty($downloaded)) {
                throw new \Exception('All image downloads failed. Amazon may be blocking direct image requests, or the image URLs have expired.');
            }

            // Build the zip
            $zipFileName = "images_{$brandSlug}_{$asin}_" . now()->format('Ymd_His') . '.zip';
            $zipStoragePath = 'exports/' . $generation->user_id . '/' . $zipFileName;
            $zipAbsolutePath = storage_path('app/' . $zipStoragePath);

            $zipDir = dirname($zipAbsolutePath);
            if (!is_dir($zipDir)) {
                mkdir($zipDir, 0755, true);
            }

            $zip = new \ZipArchive();
            if ($zip->open($zipAbsolutePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Could not create ZIP archive on the server.');
            }

            foreach ($downloaded as $filename) {
                $zip->addFile($tempDir . '/' . $filename, $filename);
            }

            // Include a small manifest noting any failures
            if (!empty($failed)) {
                $manifest = "Image Download Report\n";
                $manifest .= "Generated: " . now()->toDateTimeString() . "\n";
                $manifest .= "Successfully downloaded: " . count($downloaded) . "\n";
                $manifest .= "Failed: " . count($failed) . "\n\n";
                if (!empty($failed)) {
                    $manifest .= "Failed URLs (may be expired or blocked):\n";
                    foreach ($failed as $url) {
                        $manifest .= "- {$url}\n";
                    }
                }
                $zip->addFromString('_download-report.txt', $manifest);
            }

            $zip->close();

            $fileSize = filesize($zipAbsolutePath);

            return [
                'path'  => $zipStoragePath,
                'name'  => $zipFileName,
                'size'  => $fileSize,
                'count' => count($downloaded),
                'failed_count' => count($failed),
            ];

        } finally {
            // Always clean up the temp directory
            $this->deleteDirectory($tempDir);
        }
    }

    /**
     * Download a single image by index and return raw bytes + suggested filename.
     * Used for individual image download links.
     */
    public function downloadSingle(AiGeneration $generation, int $index): array
    {
        $import = $generation->productImport;
        $imageUrls = $import->original_images ?? [];

        if (!isset($imageUrls[$index])) {
            throw new \Exception('Image not found at that index.');
        }

        $url = $imageUrls[$index];
        $bytes = $this->fetchImageBytes($url);
        $ext = $this->guessExtension($url, $bytes);

        $brandSlug = Str::slug($generation->brand_name ?: 'product');
        $asin      = $import->asin ?: 'product';
        $filename  = sprintf('%s-%s-%02d.%s', $brandSlug, $asin, $index + 1, $ext);

        return ['bytes' => $bytes, 'filename' => $filename, 'mime' => $this->mimeFromExtension($ext)];
    }

    private function fetchImageBytes(string $url): string
    {
        $response = Http::withHeaders([
            'User-Agent' => $this->userAgent,
            'Referer'    => 'https://www.amazon.com/',
        ])->timeout($this->timeout)->get($url);

        if (!$response->successful()) {
            throw new \Exception("HTTP {$response->status()} fetching image");
        }

        $bytes = $response->body();
        if (strlen($bytes) < 100) {
            throw new \Exception('Response too small to be a valid image');
        }

        return $bytes;
    }

    private function guessExtension(string $url, string $bytes): string
    {
        // Try from URL path first
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            return $ext === 'jpeg' ? 'jpg' : $ext;
        }

        // Fall back to magic bytes
        if (str_starts_with($bytes, "\xFF\xD8\xFF")) return 'jpg';
        if (str_starts_with($bytes, "\x89PNG")) return 'png';
        if (str_starts_with($bytes, 'RIFF') && str_contains(substr($bytes, 8, 4), 'WEBP')) return 'webp';
        if (str_starts_with($bytes, 'GIF8')) return 'gif';

        return 'jpg'; // sane default
    }

    private function mimeFromExtension(string $ext): string
    {
        return match ($ext) {
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
            default => 'image/jpeg',
        };
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
