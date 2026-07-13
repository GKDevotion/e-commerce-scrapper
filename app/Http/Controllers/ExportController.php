<?php

namespace App\Http\Controllers;

use App\Models\AiGeneration;
use App\Models\Export;
use App\Services\Export\ExportService;
use App\Services\Export\ImageDownloadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    public function __construct(
        private ExportService $exportService,
        private ImageDownloadService $imageDownloadService
    ) {}

    public function export(Request $request, AiGeneration $generation)
    {
        if ($generation->user_id !== Auth::id()) abort(403, 'Unauthorized.');

        if ($generation->status !== 'completed') {
            return back()->with('error', 'Cannot export — generation is not completed yet.');
        }

        $request->validate([
            'format' => 'required|in:csv,excel,amazon_flat_file,json,pdf',
        ]);

        try {
            $export = $this->exportService->export($generation, $request->format);

            $export->update(['downloaded_at' => now()]);

            if (!Storage::exists($export->file_path)) {
                return back()->with('error', 'Export file not found. Please try again.');
            }

            return Storage::download(
                $export->file_path,
                $export->file_name,
                ['Content-Type' => $this->getMimeType($request->format)]
            );

        } catch (\Exception $e) {
            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Show the dedicated image gallery page.
     */
    public function imagesGallery(AiGeneration $generation)
    {
        if ($generation->user_id !== Auth::id()) abort(403, 'Unauthorized.');

        $generation->load('productImport');

        return view('listings.images', compact('generation'));
    }

    /**
     * Download ALL product images as a single ZIP archive.
     */
    public function downloadImagesZip(AiGeneration $generation)
    {
        if ($generation->user_id !== Auth::id()) abort(403, 'Unauthorized.');

        try {
            $result = $this->imageDownloadService->downloadAsZip($generation);

            // Record it in the exports table for history/tracking, same as other export formats
            $exportRecord = Export::create([
                'user_id'          => $generation->user_id,
                'ai_generation_id' => $generation->id,
                'format'           => 'images_zip',
                'file_path'        => $result['path'],
                'file_name'        => $result['name'],
                'file_size'        => $result['size'],
                'status'           => 'completed',
                'downloaded_at'    => now(),
            ]);

            $message = "Downloaded {$result['count']} image(s).";
            if ($result['failed_count'] > 0) {
                $message .= " {$result['failed_count']} image(s) failed to download (see _download-report.txt in the zip).";
            }

            return Storage::download($result['path'], $result['name'], ['Content-Type' => 'application/zip']);

        } catch (\Exception $e) {
            return back()->with('error', 'Image download failed: ' . $e->getMessage());
        }
    }

    /**
     * Download a single product image by its index in the gallery.
     */
    public function downloadSingleImage(AiGeneration $generation, int $index)
    {
        if ($generation->user_id !== Auth::id()) abort(403, 'Unauthorized.');

        try {
            $result = $this->imageDownloadService->downloadSingle($generation, $index);

            return response($result['bytes'], 200, [
                'Content-Type'        => $result['mime'],
                'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
            ]);

        } catch (\Exception $e) {
            return back()->with('error', 'Image download failed: ' . $e->getMessage());
        }
    }

    private function getMimeType(string $format): string
    {
        return match($format) {
            'csv'              => 'text/csv',
            'excel'            => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'amazon_flat_file' => 'text/tab-separated-values',
            'json'             => 'application/json',
            'pdf'              => 'application/pdf',
            default            => 'application/octet-stream',
        };
    }
}
