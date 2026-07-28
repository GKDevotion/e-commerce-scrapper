<?php

namespace App\Http\Controllers;

use App\Jobs\ScrapeAmazonProduct;
use App\Models\ProductImport;
use App\Services\Scraper\PlatformScraperRouter;
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
            'product_url'          => ['required', 'url', function ($attr, $value, $fail) {
                if (!PlatformScraperRouter::isSupported($value)) {
                    $fail('Please enter a valid Amazon, Flipkart, or Meesho product URL.');
                }
            }],
            'target_brand_name'   => 'required|string|max:100',
            'target_manufacturer' => 'required|string|max:100',
            'target_keywords'     => 'nullable|string|max:500',
        ]);

        $url      = $validated['product_url'];
        $platform = PlatformScraperRouter::detectPlatform($url);
        $router   = app(PlatformScraperRouter::class);
        $asin     = $router->extractProductId($url);

        $import = DB::transaction(function () use ($user, $validated, $url, $platform, $asin) {
            $import = ProductImport::create([
                'user_id'             => $user->id,
                'platform'            => $platform,
                'amazon_url'          => $url, // stores URL for all platforms
                'asin'                => $asin,
                'target_brand_name'   => $validated['target_brand_name'],
                'target_manufacturer' => $validated['target_manufacturer'],
                'target_keywords'     => $validated['target_keywords'] ?? null,
                'status'              => 'pending',
            ]);
            $user->increment('listings_used');
            return $import;
        });

        ScrapeAmazonProduct::dispatch($import)->onQueue('default');

        $label = PlatformScraperRouter::getPlatformLabel($platform);
        return redirect()->route('listings.show', $import->id)
            ->with('success', "{$label} product import queued! Scraping in progress — takes 10–30 seconds.");
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
            $actual = $user->productImports()->count();
            $user->update(['listings_used' => max(0, $actual)]);
        });

        return redirect()->route('listings.index')
            ->with('success', 'Listing deleted. Slot freed — you can now add a new listing.');
    }
}
