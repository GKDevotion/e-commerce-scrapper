<?php

namespace App\Http\Controllers;

use App\Jobs\ScrapeAmazonProduct;
use App\Models\ProductImport;
use App\Services\Scraper\AmazonScraperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductImportController extends Controller
{
    public function index()
    {
        $imports = Auth::user()->productImports()
            ->with('latestGeneration')
            ->latest()
            ->paginate(12);

        return view('listings.imports', compact('imports'));
    }

    public function create()
    {
        $user = Auth::user();

        if (!$user->canGenerateListing()) {
            // Don't redirect away — show the create page with a warning so
            // the user can delete an old listing to free up a slot first.
            return view('listings.create', compact('user'))->with('limitReached', true);
        }

        return view('listings.create', compact('user'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->canGenerateListing()) {
            return back()->with('limitReached', true);
        }

        $validated = $request->validate([
            'amazon_url' => ['required', 'url', function ($attr, $value, $fail) {
                $validDomains = [
                    'amazon.com', 'amazon.in', 'amazon.co.uk', 'amazon.de',
                    'amazon.ca', 'amazon.fr', 'amazon.es', 'amazon.it',
                    'amazon.co.jp', 'amazon.com.au', 'amazon.com.br',
                    'amazon.com.mx', 'amazon.ae', 'amazon.sg',
                ];
                $host = strtolower(parse_url($value, PHP_URL_HOST) ?? '');
                $isValid = collect($validDomains)->contains(fn($d) => str_ends_with($host, $d));
                if (!$isValid) {
                    $fail('Please enter a valid Amazon product URL (amazon.com, amazon.in, amazon.co.uk, etc.).');
                }
            }],
            'target_brand_name'   => 'required|string|max:100',
            'target_manufacturer' => 'required|string|max:100',
            'target_keywords'     => 'nullable|string|max:500',
        ]);

        $scraper = app(AmazonScraperService::class);
        $asin    = $scraper->extractAsin($validated['amazon_url']);

        // Use a DB transaction so if the job dispatch fails, the counter
        // and the import record are not left in an inconsistent state.
        $import = DB::transaction(function () use ($user, $validated, $asin) {
            $import = ProductImport::create([
                'user_id'             => $user->id,
                'amazon_url'          => $validated['amazon_url'],
                'asin'                => $asin,
                'target_brand_name'   => $validated['target_brand_name'],
                'target_manufacturer' => $validated['target_manufacturer'],
                'target_keywords'     => $validated['target_keywords'],
                'status'              => 'pending',
            ]);

            // Atomic increment — prevents race conditions if two tabs submit simultaneously
            $user->increment('listings_used');

            return $import;
        });

        ScrapeAmazonProduct::dispatch($import)->onQueue('default');

        return redirect()->route('listings.show', $import->id)
            ->with('success', 'Product import queued! Scraping in progress — takes 10–30 seconds.');
    }

    public function show(ProductImport $import)
    {
        if ($import->user_id !== Auth::id()) abort(403, 'Unauthorized.');

        $import->load(['aiGenerations' => fn($q) => $q->latest()]);
        $latestGeneration = $import->aiGenerations->first();

        return view('listings.show', compact('import', 'latestGeneration'));
    }

    public function destroy(ProductImport $import)
    {
        if ($import->user_id !== Auth::id()) abort(403, 'Unauthorized.');

        $user = Auth::user();

        DB::transaction(function () use ($import, $user) {
            $import->delete();

            // Decrement safely — never go below 0, and recalculate from the
            // actual DB count to self-heal any drift from past inconsistencies.
            $actual = $user->productImports()->count(); // count AFTER delete (already deleted above)
            $user->update([
                'listings_used' => max(0, $actual),
            ]);
        });

        return redirect()->route('listings.index')
            ->with('success', 'Listing deleted. Your usage count has been updated — you now have a free slot.');
    }
}
