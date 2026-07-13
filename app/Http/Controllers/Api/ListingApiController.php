<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ScrapeAmazonProduct;
use App\Models\AiGeneration;
use App\Models\ProductImport;
use App\Services\AI\AiGenerationService;
use App\Services\Export\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ListingApiController extends Controller
{
    public function __construct(
        private AiGenerationService $aiService,
        private ExportService $exportService
    ) {}

    public function index(Request $request)
    {
        $imports = Auth::user()->productImports()
            ->with('latestGeneration')
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data'  => $imports->items(),
            'meta'  => [
                'current_page' => $imports->currentPage(),
                'last_page'    => $imports->lastPage(),
                'total'        => $imports->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->canGenerateListing()) {
            return response()->json(['message' => 'Listing limit reached. Please upgrade your plan.'], 403);
        }

        $validated = $request->validate([
            'amazon_url'          => 'required|url',
            'target_brand_name'   => 'required|string|max:100',
            'target_manufacturer' => 'required|string|max:100',
            'target_keywords'     => 'nullable|string|max:500',
        ]);

        $import = ProductImport::create(array_merge($validated, [
            'user_id' => $user->id,
            'status'  => 'pending',
        ]));

        $user->increment('listings_used');
        ScrapeAmazonProduct::dispatch($import)->onQueue('default');

        return response()->json(['data' => $import, 'message' => 'Import queued.'], 201);
    }

    public function show(ProductImport $import)
    {
        if ($import->user_id !== Auth::id()) return response()->json(['message' => 'Not found.'], 404);
        return response()->json(['data' => $import->load('aiGenerations')]);
    }

    public function destroy(ProductImport $import)
    {
        if ($import->user_id !== Auth::id()) return response()->json(['message' => 'Not found.'], 404);
        $import->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    public function generate(Request $request, ProductImport $import)
    {
        if ($import->user_id !== Auth::id()) return response()->json(['message' => 'Not found.'], 404);

        if ($import->status !== 'scraped') {
            return response()->json(['message' => 'Product must be scraped first. Current status: ' . $import->status], 422);
        }

        try {
            $generation = $this->aiService->generateListing($import);
            return response()->json(['data' => $generation, 'message' => 'Generated successfully.'], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Generation failed: ' . $e->getMessage()], 500);
        }
    }

    public function generations(Request $request)
    {
        $generations = Auth::user()->aiGenerations()
            ->with('productImport')
            ->where('status', 'completed')
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $generations->items(),
            'meta' => ['total' => $generations->total(), 'current_page' => $generations->currentPage()],
        ]);
    }

    public function generation(AiGeneration $generation)
    {
        if ($generation->user_id !== Auth::id()) return response()->json(['message' => 'Not found.'], 404);
        return response()->json(['data' => $generation->load('productImport')]);
    }

    public function export(Request $request, AiGeneration $generation)
    {
        if ($generation->user_id !== Auth::id()) return response()->json(['message' => 'Not found.'], 404);

        $request->validate(['format' => 'required|in:csv,excel,amazon_flat_file,json,pdf']);

        try {
            $export = $this->exportService->export($generation, $request->format);
            return response()->json([
                'data'    => $export,
                'message' => 'Export created.',
                'url'     => url('/api/v1/exports/' . $export->id . '/download'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }
}
