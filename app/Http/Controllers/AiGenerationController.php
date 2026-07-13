<?php

namespace App\Http\Controllers;

use App\Models\AiGeneration;
use App\Models\ProductImport;
use App\Services\AI\AiGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiGenerationController extends Controller
{
    public function __construct(private AiGenerationService $aiService) {}

    /**
     * AI-powered generation (calls OpenAI).
     */
    public function generate(Request $request, ProductImport $import)
    {
        if ($import->user_id !== Auth::id()) abort(403, 'Unauthorized.');

        if (!in_array($import->status, ['scraped', 'completed', 'failed'])) {
            return back()->with('error', 'Product is still being scraped. Please wait for scraping to complete.');
        }

        if (!$import->original_title) {
            return back()->with('error', 'No scraped data found. Please try re-importing the product URL.');
        }

        try {
            $generation = $this->aiService->generateListing($import);

            return redirect()->route('generations.view', $generation->id)
                ->with('success', 'AI listing generated successfully! Review and export below.');

        } catch (\Exception $e) {
            return back()->with('error', 'AI generation failed: ' . $e->getMessage() . '. You can also create this listing manually instead.');
        }
    }

    /**
     * Show the manual listing creation form, pre-filled from scraped data
     * with brand/manufacturer already swapped (pure string replacement,
     * no AI involved).
     */
    public function createManual(ProductImport $import)
    {
        if ($import->user_id !== Auth::id()) abort(403, 'Unauthorized.');

        if (!in_array($import->status, ['scraped', 'completed', 'failed'])) {
            return redirect()->route('listings.show', $import->id)
                ->with('error', 'Product is still being scraped. Please wait for scraping to complete.');
        }

        if (!$import->original_title) {
            return redirect()->route('listings.show', $import->id)
                ->with('error', 'No scraped data found. Please try re-importing the product URL.');
        }

        // Pre-fill with brand/manufacturer swapped via simple string
        // replacement — no AI, no rewriting, just the substitution.
        $prefilled = AiGenerationService::applyBrandSubstitution(
            [
                'title'       => $import->original_title,
                'bullets'     => $import->original_bullet_points ?? [],
                'description' => $import->original_description,
            ],
            $import->original_brand,
            $import->original_manufacturer,
            $import->target_brand_name,
            $import->target_manufacturer
        );

        return view('listings.create-manual', compact('import', 'prefilled'));
    }

    /**
     * Store a manually-created listing. No AI call — entirely user-typed
     * (or user-edited, pre-filled) content.
     */
    public function storeManual(Request $request, ProductImport $import)
    {
        if ($import->user_id !== Auth::id()) abort(403, 'Unauthorized.');

        $validated = $request->validate([
            'generation_name'       => 'nullable|string|max:150',
            'generated_title'       => 'required|string|max:500',
            'generated_bullet_points' => 'required|array|min:1',
            'generated_bullet_points.*' => 'nullable|string|max:500',
            'generated_description' => 'required|string',
            'generated_search_terms' => 'nullable|string|max:1000',
            'generated_seo_keywords' => 'nullable|string|max:1000',
            'generated_highlights'   => 'nullable|string|max:2000',
            'generated_aplus_content' => 'nullable|string|max:5000',
            'brand_name'             => 'required|string|max:100',
            'manufacturer'           => 'required|string|max:100',
        ]);

        // Drop empty bullet point rows the user left blank
        $bullets = array_values(array_filter($validated['generated_bullet_points'], fn ($b) => trim((string) $b) !== ''));

        $generation = AiGeneration::create([
            'user_id'            => Auth::id(),
            'product_import_id'  => $import->id,
            'generation_method'  => 'manual',
            'generation_name'    => $validated['generation_name'] ?? null,
            'status'             => 'completed',
            'generated_title'    => $validated['generated_title'],
            'generated_bullet_points' => $bullets,
            'generated_description'   => $validated['generated_description'],
            'generated_search_terms'  => $validated['generated_search_terms'] ?? null,
            'generated_seo_keywords'  => $validated['generated_seo_keywords'] ?? null,
            'generated_highlights'    => $validated['generated_highlights'] ?? null,
            'generated_aplus_content' => $validated['generated_aplus_content'] ?? null,
            'brand_name'         => $validated['brand_name'],
            'manufacturer'       => $validated['manufacturer'],
            'ai_model'           => null,
            'generated_at'       => now(),
        ]);

        $import->user->increment('ai_generations_used');
        $import->update(['status' => 'completed']);

        return redirect()->route('generations.view', $generation->id)
            ->with('success', 'Listing created manually. Review and export below.');
    }

    public function show(AiGeneration $generation)
    {
        if ($generation->user_id !== Auth::id()) abort(403, 'Unauthorized.');

        $generation->load('productImport');

        if ($generation->status !== 'completed') {
            return redirect()->route('listings.show', $generation->product_import_id)
                ->with('info', 'Generation is still in progress.');
        }

        return view('listings.generation', compact('generation'));
    }

    /**
     * Edit form for an existing generation — works for both AI and
     * manually-created listings, so AI output can always be hand-corrected.
     */
    public function edit(AiGeneration $generation)
    {
        if ($generation->user_id !== Auth::id()) abort(403, 'Unauthorized.');

        $generation->load('productImport');

        return view('listings.edit-generation', compact('generation'));
    }

    public function update(Request $request, AiGeneration $generation)
    {
        if ($generation->user_id !== Auth::id()) abort(403, 'Unauthorized.');

        $validated = $request->validate([
            'generation_name'       => 'nullable|string|max:150',
            'generated_title'       => 'required|string|max:500',
            'generated_bullet_points' => 'required|array|min:1',
            'generated_bullet_points.*' => 'nullable|string|max:500',
            'generated_description' => 'required|string',
            'generated_search_terms' => 'nullable|string|max:1000',
            'generated_seo_keywords' => 'nullable|string|max:1000',
            'generated_highlights'   => 'nullable|string|max:2000',
            'generated_aplus_content' => 'nullable|string|max:5000',
            'brand_name'             => 'required|string|max:100',
            'manufacturer'           => 'required|string|max:100',
        ]);

        $bullets = array_values(array_filter($validated['generated_bullet_points'], fn ($b) => trim((string) $b) !== ''));

        $generation->update([
            'generation_name'         => $validated['generation_name'] ?? $generation->generation_name,
            'generated_title'         => $validated['generated_title'],
            'generated_bullet_points' => $bullets,
            'generated_description'   => $validated['generated_description'],
            'generated_search_terms'  => $validated['generated_search_terms'] ?? null,
            'generated_seo_keywords'  => $validated['generated_seo_keywords'] ?? null,
            'generated_highlights'    => $validated['generated_highlights'] ?? null,
            'generated_aplus_content' => $validated['generated_aplus_content'] ?? null,
            'brand_name'              => $validated['brand_name'],
            'manufacturer'            => $validated['manufacturer'],
        ]);

        return redirect()->route('generations.view', $generation->id)
            ->with('success', 'Listing updated successfully.');
    }

    public function toggleFavorite(AiGeneration $generation)
    {
        if ($generation->user_id !== Auth::id()) abort(403, 'Unauthorized.');

        $generation->update(['is_favorite' => !$generation->is_favorite]);

        $msg = $generation->is_favorite
            ? 'Added to favorites.'
            : 'Removed from favorites.';

        return back()->with('success', $msg);
    }

    public function destroy(AiGeneration $generation)
    {
        if ($generation->user_id !== Auth::id()) abort(403, 'Unauthorized.');

        $importId = $generation->product_import_id;
        $generation->delete();

        return redirect()->route('listings.show', $importId)
            ->with('success', 'Generation deleted.');
    }
}
