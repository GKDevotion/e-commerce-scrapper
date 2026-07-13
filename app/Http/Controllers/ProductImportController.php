<?php

namespace App\Http\Controllers;

use App\Jobs\ScrapeAmazonProduct;
use App\Models\ProductImport;
use App\Services\Scraper\AmazonScraperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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
            return redirect()->route('billing.plans')
                ->with('error', 'You have reached your listing limit. Please upgrade your plan.');
        }
        return view('listings.create', compact('user'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->canGenerateListing()) {
            return back()->with('error', 'Listing limit reached. Please upgrade your plan.');
        }

        $validated = $request->validate([
            'amazon_url' => ['required', 'url', function ($attr, $value, $fail) {
                $validDomains = ['amazon.com', 'amazon.in', 'amazon.co.uk', 'amazon.de', 'amazon.ca', 'amazon.fr', 'amazon.es', 'amazon.it', 'amazon.co.jp', 'amazon.com.au', 'amazon.com.br', 'amazon.com.mx', 'amazon.ae', 'amazon.sg'];
                $host = strtolower(parse_url($value, PHP_URL_HOST) ?? '');
                $isValid = collect($validDomains)->contains(fn($d) => str_ends_with($host, $d));
                if (!$isValid) {
                    $fail('Please enter a valid Amazon product URL (amazon.com, amazon.in, amazon.co.uk, etc.).');
                }
            }],
            'target_brand_name'    => 'required|string|max:100',
            'target_manufacturer'  => 'required|string|max:100',
            'target_keywords'      => 'nullable|string|max:500',
        ]);

        $scraper = app(AmazonScraperService::class);
        $asin = $scraper->extractAsin($validated['amazon_url']);

        $import = ProductImport::create([
            'user_id'             => $user->id,
            'amazon_url'          => $validated['amazon_url'],
            'asin'                => $asin,
            'target_brand_name'   => $validated['target_brand_name'],
            'target_manufacturer' => $validated['target_manufacturer'],
            'target_keywords'     => $validated['target_keywords'],
            'status'              => 'pending',
        ]);

        // Increment user listing count
        $user->increment('listings_used');

        // Dispatch scraping job to queue
        ScrapeAmazonProduct::dispatch($import)->onQueue('default');

        return redirect()->route('listings.show', $import->id)
            ->with('success', 'Product import queued! We\'re scraping the Amazon listing — this takes 10–30 seconds.');
    }

    public function show(ProductImport $import)
    {
        if ($import->user_id !== Auth::id()) abort(403, 'Unauthorized.');

        // if (!Cache::get('queue_worker_started_'.$import->user_id)) {

        //     Cache::put('queue_worker_started_'.$import->user_id, true, now()->addHours(1));

        //     Artisan::call('queue:work');
        // }
        
        $import->load(['aiGenerations' => fn($q) => $q->latest()]);
        $latestGeneration = $import->aiGenerations->first();

        return view('listings.show', compact('import', 'latestGeneration'));
    }

    public function destroy(ProductImport $import)
    {
        if ($import->user_id !== Auth::id()) abort(403, 'Unauthorized.');

        // Decrement usage counter
        if (Auth::user()->listings_used > 0) {
            Auth::user()->decrement('listings_used');
        }

        $import->delete();

        return redirect()->route('listings.index')
            ->with('success', 'Listing import and all generated content deleted.');
    }
}
