<?php

namespace App\Services\Export;

use App\Models\AiGeneration;
use App\Models\Export;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportService
{
    /**
     * Export generation in requested format
     */
    public function export(AiGeneration $generation, string $format): Export
    {
        $exportRecord = Export::create([
            'user_id' => $generation->user_id,
            'ai_generation_id' => $generation->id,
            'format' => $format,
            'status' => 'generating',
        ]);

        try {
            $result = match($format) {
                'csv' => $this->exportCsv($generation),
                'excel' => $this->exportExcel($generation),
                'amazon_flat_file' => $this->exportAmazonFlatFile($generation),
                'json' => $this->exportJson($generation),
                'pdf' => $this->exportPdf($generation),
                default => throw new \Exception("Unsupported export format: {$format}"),
            };

            $exportRecord->update([
                'status' => 'completed',
                'file_path' => $result['path'],
                'file_name' => $result['name'],
                'file_size' => $result['size'],
            ]);

        } catch (\Exception $e) {
            $exportRecord->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $exportRecord->fresh();
    }

    private function exportCsv(AiGeneration $gen): array
    {
        $fileName = 'listing_' . $gen->id . '_' . now()->format('Ymd_His') . '.csv';
        $path = 'exports/' . $gen->user_id . '/' . $fileName;

        $bullets = $gen->generated_bullet_points ?? [];
        $rows = [];

        // Header
        $rows[] = ['Field', 'Original Value', 'AI Generated Value'];
        $rows[] = ['Product Title', $gen->productImport->original_title ?? '', $gen->generated_title ?? ''];
        $rows[] = ['Brand', $gen->productImport->original_brand ?? '', $gen->brand_name ?? ''];
        $rows[] = ['Manufacturer', $gen->productImport->original_manufacturer ?? '', $gen->manufacturer ?? ''];
        $rows[] = ['Description', $gen->productImport->original_description ?? '', $gen->generated_description ?? ''];

        $originalBullets = $gen->productImport->original_bullet_points ?? [];
        foreach (range(1, 5) as $i) {
            $rows[] = [
                "Bullet Point {$i}",
                $originalBullets[$i - 1] ?? '',
                $bullets[$i - 1] ?? '',
            ];
        }
        $rows[] = ['Search Terms', '', $gen->generated_search_terms ?? ''];
        $rows[] = ['SEO Keywords', '', $gen->generated_seo_keywords ?? ''];
        $rows[] = ['Highlights', '', $gen->generated_highlights ?? ''];
        $rows[] = ['A+ Content', '', $gen->generated_aplus_content ?? ''];

        $csvContent = '';
        foreach ($rows as $row) {
            $csvContent .= collect($row)->map(fn($cell) => '"' . str_replace('"', '""', $cell) . '"')->implode(',') . "\n";
        }

        Storage::put($path, $csvContent);

        return [
            'path' => $path,
            'name' => $fileName,
            'size' => strlen($csvContent),
        ];
    }

    private function exportExcel(AiGeneration $gen): array
    {
        // Generate Excel-compatible XML (SpreadsheetML)
        $fileName = 'listing_' . $gen->id . '_' . now()->format('Ymd_His') . '.xlsx';
        $path = 'exports/' . $gen->user_id . '/' . $fileName;

        $bullets = $gen->generated_bullet_points ?? [];
        $originalBullets = $gen->productImport->original_bullet_points ?? [];

        $rows = [
            ['Field', 'Original Value', 'AI Generated Value'],
            ['Product Title', $gen->productImport->original_title ?? '', $gen->generated_title ?? ''],
            ['Brand', $gen->productImport->original_brand ?? '', $gen->brand_name ?? ''],
            ['Manufacturer', $gen->productImport->original_manufacturer ?? '', $gen->manufacturer ?? ''],
            ['Description', $gen->productImport->original_description ?? '', $gen->generated_description ?? ''],
        ];

        foreach (range(1, 5) as $i) {
            $rows[] = ["Bullet Point {$i}", $originalBullets[$i-1] ?? '', $bullets[$i-1] ?? ''];
        }

        $rows[] = ['Search Terms', '', $gen->generated_search_terms ?? ''];
        $rows[] = ['SEO Keywords', '', $gen->generated_seo_keywords ?? ''];
        $rows[] = ['Highlights', '', $gen->generated_highlights ?? ''];

        // Build CSV for now (xlsx requires PhpSpreadsheet in real implementation)
        $csvContent = '';
        foreach ($rows as $row) {
            $csvContent .= collect($row)->map(fn($c) => '"' . str_replace('"', '""', $c) . '"')->implode(',') . "\n";
        }

        $xlsxPath = str_replace('.xlsx', '.csv', $path);
        Storage::put($xlsxPath, $csvContent);

        return ['path' => $xlsxPath, 'name' => str_replace('.xlsx', '.csv', $fileName), 'size' => strlen($csvContent)];
    }

    private function exportAmazonFlatFile(AiGeneration $gen): array
    {
        $fileName = 'amazon_flat_file_' . $gen->id . '_' . now()->format('Ymd_His') . '.txt';
        $path = 'exports/' . $gen->user_id . '/' . $fileName;

        $bullets = $gen->generated_bullet_points ?? [];
        $asin = $gen->productImport->asin ?? 'NEW_PRODUCT';

        $content = "TemplateType=fptcustom\tVersion=2019.1.1\tSettingsLanguage=en_US\n";
        $content .= "feed_product_type\titem_sku\tbrand_name\titem_name\tproduct_description\tbullet_point1\tbullet_point2\tbullet_point3\tbullet_point4\tbullet_point5\tmanufacturer\tgeneric_keywords\n";
        $content .= "Text\tText\tText\tText\tText\tText\tText\tText\tText\tText\tText\tText\n";
        $content .= implode("\t", [
            'CUSTOM',
            $asin . '-' . time(),
            $gen->brand_name ?? '',
            $gen->generated_title ?? '',
            strip_tags($gen->generated_description ?? ''),
            $bullets[0] ?? '',
            $bullets[1] ?? '',
            $bullets[2] ?? '',
            $bullets[3] ?? '',
            $bullets[4] ?? '',
            $gen->manufacturer ?? '',
            $gen->generated_search_terms ?? '',
        ]) . "\n";

        Storage::put($path, $content);
        return ['path' => $path, 'name' => $fileName, 'size' => strlen($content)];
    }

    private function exportJson(AiGeneration $gen): array
    {
        $fileName = 'listing_' . $gen->id . '_' . now()->format('Ymd_His') . '.json';
        $path = 'exports/' . $gen->user_id . '/' . $fileName;

        $data = [
            'generated_at' => $gen->generated_at?->toIso8601String(),
            'asin' => $gen->productImport->asin,
            'original_listing' => [
                'title' => $gen->productImport->original_title,
                'brand' => $gen->productImport->original_brand,
                'description' => $gen->productImport->original_description,
                'bullet_points' => $gen->productImport->original_bullet_points,
            ],
            'generated_listing' => [
                'title' => $gen->generated_title,
                'brand' => $gen->brand_name,
                'manufacturer' => $gen->manufacturer,
                'bullet_points' => $gen->generated_bullet_points,
                'description' => $gen->generated_description,
                'search_terms' => $gen->generated_search_terms,
                'seo_keywords' => $gen->generated_seo_keywords,
                'highlights' => $gen->generated_highlights,
                'aplus_content' => $gen->generated_aplus_content,
            ],
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        Storage::put($path, $json);
        return ['path' => $path, 'name' => $fileName, 'size' => strlen($json)];
    }

    private function exportPdf(AiGeneration $gen): array
    {
        $fileName = 'listing_' . $gen->id . '_' . now()->format('Ymd_His') . '.pdf';
        $path = 'exports/' . $gen->user_id . '/' . $fileName;

        $bullets = $gen->generated_bullet_points ?? [];
        $bulletsHtml = '';
        foreach ($bullets as $bullet) {
            $bulletsHtml .= "<li>{$bullet}</li>";
        }

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #333; padding: 20px; }
    h1 { color: #E31837; font-size: 18px; border-bottom: 2px solid #E31837; padding-bottom: 10px; }
    h2 { color: #000; font-size: 14px; margin-top: 20px; }
    .badge { background: #E31837; color: white; padding: 2px 8px; border-radius: 4px; font-size: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background: #E31837; color: white; padding: 8px; text-align: left; }
    td { padding: 6px 8px; border-bottom: 1px solid #eee; }
    .title-box { background: #f9f9f9; border-left: 4px solid #E31837; padding: 12px; margin: 10px 0; }
</style>
</head>
<body>
<h1>Amazon Listing Builder — Generated Listing</h1>
<p><strong>Generated:</strong> {$gen->generated_at?->format('M d, Y H:i')}</p>
<p><strong>Brand:</strong> {$gen->brand_name} | <strong>Manufacturer:</strong> {$gen->manufacturer}</p>
<h2>Product Title</h2>
<div class="title-box">{$gen->generated_title}</div>
<h2>Bullet Points</h2>
<ul>{$bulletsHtml}</ul>
<h2>Product Description</h2>
<p>{$gen->generated_description}</p>
<h2>Search Terms</h2>
<p>{$gen->generated_search_terms}</p>
<h2>SEO Keywords</h2>
<p>{$gen->generated_seo_keywords}</p>
<h2>Highlights</h2>
<p>{$gen->generated_highlights}</p>
</body>
</html>
HTML;

        // In production, use DomPDF. For now, store HTML.
        $htmlPath = str_replace('.pdf', '.html', $path);
        Storage::put($htmlPath, $html);
        return ['path' => $htmlPath, 'name' => str_replace('.pdf', '.html', $fileName), 'size' => strlen($html)];
    }
}
