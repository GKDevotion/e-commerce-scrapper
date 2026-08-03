@extends('layouts.app')
@section('title', 'New Listing')
@section('page-title', 'New Listing')
@section('content')

@php
$enabledPlatforms = \App\Models\PlatformSetting::enabled();
$oldPlatform = old('platform', '');
$user = auth()->user();
@endphp

{{-- No API key warning --}}
@if(!$user->hasOpenAiKey() && !config('services.openai.api_key'))
<div style="background:#FEF3C7;border:1.5px solid #FDE68A;border-radius:12px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:flex-start;gap:14px;" class="fade-in-up">
    <i class="bi bi-exclamation-triangle-fill" style="color:#F59E0B;font-size:20px;flex-shrink:0;margin-top:1px;"></i>
    <div>
        <div style="font-size:14px;font-weight:700;color:#92400E;margin-bottom:4px;">No OpenAI API Key Configured</div>
        <div style="font-size:13px;color:#92400E;line-height:1.6;">
            You haven't added your OpenAI API key. AI generation will be unavailable — you can still create listings manually.
            <a href="{{ route('profile.index') }}" style="color:#E31837;font-weight:700;text-decoration:underline;">Add API key in Settings →</a>
        </div>
    </div>
</div>
@endif

<div class="row justify-content-center">
<div class="col-lg-8 col-xl-7">

    {{-- Step 1: Platform --}}
    <div class="alb-card mb-4 fade-in-up">
        <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9CA3AF;margin-bottom:4px;">Step 1 of 2</div>
        <div style="font-family:'Sora',sans-serif;font-size:17px;font-weight:700;color:#111827;margin-bottom:20px;">Choose Platform</div>

        @if($enabledPlatforms->isEmpty())
        <div style="text-align:center;padding:32px;color:#9CA3AF;font-size:14px;">
            <i class="bi bi-exclamation-circle" style="font-size:32px;display:block;margin-bottom:10px;"></i>
            No platforms are enabled. Ask your admin to enable at least one platform.
        </div>
        @else
        <div class="row g-3" id="platformRow">
            @foreach($enabledPlatforms as $p)
            <div class="{{ $enabledPlatforms->count() === 1 ? 'col-12' : ($enabledPlatforms->count() === 2 ? 'col-6' : 'col-md-4') }}">
                <button type="button"
                    onclick="selectPlatform('{{ $p->key }}','{{ $p->color }}')"
                    id="pcard-{{ $p->key }}"
                    data-color="{{ $p->color }}"
                    style="width:100%;background:{{ $oldPlatform===$p->key ? $p->color.'15' : 'white' }};border:2px solid {{ $oldPlatform===$p->key ? $p->color : '#E5E7EB' }};border-radius:12px;padding:20px 14px;cursor:pointer;text-align:center;transition:all .15s;">
                    <div style="width:48px;height:48px;background:{{ $p->color }}18;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:24px;color:{{ $p->color }};">
                        <i class="{{ $p->icon }}"></i>
                    </div>
                    <div style="font-family:'Sora',sans-serif;font-size:15px;font-weight:700;color:#111827;margin-bottom:4px;">{{ $p->label }}</div>
                    <div id="pcheck-{{ $p->key }}" style="display:{{ $oldPlatform===$p->key ? 'block':'none' }};margin-top:8px;">
                        <span style="background:{{ $p->color }};color:white;font-size:11px;font-weight:700;padding:3px 12px;border-radius:20px;">Selected ✓</span>
                    </div>
                </button>
            </div>
            @endforeach
        </div>
        @error('platform')
        <div style="color:#EF4444;font-size:12.5px;margin-top:12px;"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
        @enderror
        @endif
    </div>

    {{-- Step 2: Form fields --}}
    <div id="step2" style="{{ $oldPlatform ? '' : 'display:none;' }}transition:opacity .3s;opacity:{{ $oldPlatform ? '1' : '0' }};">
        <div class="alb-card mb-4">
            <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9CA3AF;margin-bottom:4px;">Step 2 of 2</div>
            <div style="font-family:'Sora',sans-serif;font-size:17px;font-weight:700;color:#111827;margin-bottom:20px;">Product Details</div>

            <form method="POST" action="{{ route('listings.store') }}" id="importForm">
                @csrf
                <input type="hidden" name="platform" id="platformInput" value="{{ $oldPlatform }}">

                {{-- URL --}}
                <div class="alb-form-group">
                    <label class="alb-label"><i class="bi bi-link-45deg me-1" style="color:#E31837;"></i>Product URL <span style="color:#E31837;">*</span></label>
                    <div style="position:relative;">
                        <input type="url" name="product_url" id="productUrlInput"
                            class="alb-input @error('product_url') is-invalid @enderror"
                            placeholder="Paste the full product page URL..."
                            value="{{ old('product_url') }}"
                            required oninput="detectProductId(this.value)"
                            style="padding-right:130px;">
                        <div id="pidBadge" style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:10.5px;font-weight:700;padding:3px 10px;border-radius:20px;color:white;white-space:nowrap;"></div>
                    </div>
                    @error('product_url')
                    <div style="color:#EF4444;font-size:12px;margin-top:4px;"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                    @enderror
                    <div id="urlHint" style="font-size:12px;color:#9CA3AF;margin-top:5px;">Paste the full product page URL from the selected platform</div>
                </div>

                {{-- Brand + Manufacturer --}}
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="alb-form-group">
                            <label class="alb-label"><i class="bi bi-tag me-1" style="color:#E31837;"></i>Your Brand Name <span style="color:#E31837;">*</span></label>
                            <input type="text" name="target_brand_name"
                                class="alb-input @error('target_brand_name') is-invalid @enderror"
                                placeholder="e.g. PrimeCraft"
                                value="{{ old('target_brand_name', $user->default_brand) }}"
                                required maxlength="100">
                            @error('target_brand_name')
                            <div style="color:#EF4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alb-form-group">
                            <label class="alb-label"><i class="bi bi-building me-1" style="color:#E31837;"></i>Manufacturer Name <span style="color:#E31837;">*</span></label>
                            <input type="text" name="target_manufacturer"
                                class="alb-input @error('target_manufacturer') is-invalid @enderror"
                                placeholder="e.g. PrimeCraft Industries"
                                value="{{ old('target_manufacturer', $user->default_manufacturer) }}"
                                required maxlength="100">
                            @error('target_manufacturer')
                            <div style="color:#EF4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Keywords --}}
                <div class="alb-form-group">
                    <label class="alb-label"><i class="bi bi-search me-1" style="color:#E31837;"></i>Target Keywords <span style="color:#9CA3AF;font-weight:400;">(Optional)</span></label>
                    <textarea name="target_keywords" class="alb-input alb-textarea"
                        placeholder="e.g. stainless steel water bottle, BPA free, insulated, 32oz..."
                        maxlength="500">{{ old('target_keywords') }}</textarea>
                    <div style="font-size:12px;color:#9CA3AF;margin-top:5px;">Separate keywords with commas.</div>
                </div>

                {{-- AI mode indicator --}}
                @if($user->hasOpenAiKey() || config('services.openai.api_key'))
                <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:12px 16px;margin-bottom:20px;">
                    <div style="font-size:12.5px;color:#14532D;"><i class="bi bi-cpu me-1" style="color:#10B981;"></i><strong>AI Mode Active</strong> — listing will be generated automatically using GPT-4o after scraping.</div>
                </div>
                @else
                <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;padding:12px 16px;margin-bottom:20px;">
                    <div style="font-size:12.5px;color:#6B7280;"><i class="bi bi-pencil-square me-1"></i><strong>Manual Mode</strong> — you'll fill in the listing details after scraping (no API key set).</div>
                </div>
                @endif

                {{-- Submit --}}
                <button type="submit" id="submitBtn" class="btn-alb-primary btn w-100 py-3"
                    style="font-size:15px;font-family:'Sora',sans-serif;">
                    <span id="btnText"><i class="bi bi-cloud-download me-2"></i>Import Product</span>
                    <span id="btnLoading" style="display:none;"><span class="spinner-border spinner-border-sm me-2"></span>Queueing...</span>
                </button>
                <div style="text-align:center;margin-top:12px;font-size:12px;color:#9CA3AF;">
                    <i class="bi bi-shield-check me-1"></i>Processed privately. We never use your data to train AI.
                </div>
            </form>
        </div>
    </div>

</div>
</div>

@push('scripts')
<script>
var platforms = {
    amazon:   { color:'#FF9900', hint:'e.g. https://www.amazon.in/dp/B0FHXY7VZ5',             label:'ASIN', regex:/\/(?:dp|product|gp\/product)\/([A-Z0-9]{10})/i },
    flipkart: { color:'#2874F0', hint:'e.g. https://www.flipkart.com/product/p/itmXXXXXXXX',  label:'PID',  regex:/\/p\/(itm[a-zA-Z0-9]+)/i },
    meesho:   { color:'#F43397', hint:'e.g. https://meesho.com/product/p/ar2bkc',              label:'ID',   regex:/\/p\/([a-zA-Z0-9]{4,})(?:[/?#]|$)/i },
};
var currentPlatform = '{{ $oldPlatform }}';

function selectPlatform(key, color) {
    currentPlatform = key;
    document.getElementById('platformInput').value = key;

    // Update all card visuals
    document.querySelectorAll('[id^="pcard-"]').forEach(function(card) {
        var k = card.id.replace('pcard-','');
        var c = card.dataset.color;
        var sel = k === key;
        card.style.border = '2px solid ' + (sel ? c : '#E5E7EB');
        card.style.background = sel ? c+'15' : 'white';
        var check = document.getElementById('pcheck-'+k);
        if (check) check.style.display = sel ? 'block' : 'none';
    });

    var cfg = platforms[key];
    if (cfg) {
        document.getElementById('urlHint').textContent = cfg.hint;
        document.getElementById('productUrlInput').placeholder = cfg.hint;
        detectProductId(document.getElementById('productUrlInput').value);
    }

    var step2 = document.getElementById('step2');
    if (step2.style.display === 'none') {
        step2.style.display = '';
        setTimeout(function() { step2.style.opacity = '1'; }, 20);
        setTimeout(function() { step2.scrollIntoView({ behavior:'smooth', block:'start' }); }, 100);
    }
}

function detectProductId(url) {
    var badge = document.getElementById('pidBadge');
    if (!currentPlatform || !url || !platforms[currentPlatform]) { badge.style.display='none'; return; }
    var cfg = platforms[currentPlatform];
    var match = url.match(cfg.regex);
    if (match) {
        badge.textContent = cfg.label + ': ' + match[1].toUpperCase();
        badge.style.display = 'flex';
        badge.style.background = cfg.color;
        badge.style.alignItems = 'center';
    } else {
        badge.style.display = 'none';
    }
}

document.getElementById('importForm').addEventListener('submit', function(e) {
    if (!currentPlatform) { e.preventDefault(); window.scrollTo(0,0); return; }
    document.getElementById('btnText').style.display = 'none';
    document.getElementById('btnLoading').style.display = 'inline-flex';
    document.getElementById('submitBtn').disabled = true;
});

// Restore state on validation error
(function() {
    if (!currentPlatform) return;
    var cfg = platforms[currentPlatform];
    if (!cfg) return;
    selectPlatform(currentPlatform, cfg.color);
    var step2 = document.getElementById('step2');
    step2.style.display = '';
    step2.style.opacity = '1';
    detectProductId(document.getElementById('productUrlInput').value);
})();
</script>
@endpush
@endsection