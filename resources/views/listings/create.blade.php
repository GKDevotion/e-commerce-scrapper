@extends('layouts.app')
@section('title', 'New Listing')
@section('page-title', 'New Listing')

@section('topbar-actions')
<a href="{{ route('listings.index') }}" class="topbar-btn" style="background:#374151;">
    <i class="bi bi-arrow-left"></i> My Listings
</a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">

        {{-- Limit reached warning --}}
        @if(session('limitReached') || isset($limitReached))
        <div class="alb-card mb-4 fade-in-up" style="border:2px solid #EF4444;background:#FFF5F5;padding:20px 24px;">
            <div class="d-flex align-items-start gap-3">
                <div style="width:44px;height:44px;background:#FEE2E2;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-exclamation-triangle-fill" style="color:#EF4444;font-size:20px;"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-family:'Sora',sans-serif;font-size:15px;font-weight:700;color:#991B1B;margin-bottom:6px;">
                        Listing Limit Reached
                    </div>
                    <div style="font-size:13.5px;color:#B91C1C;line-height:1.6;margin-bottom:14px;">
                        You've used all <strong>{{ auth()->user()->plan?->listings_limit ?? 5 }}</strong> slots on your
                        <strong>{{ auth()->user()->plan?->name ?? 'Free' }}</strong> plan.
                        Delete an existing listing to free a slot, or upgrade.
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('listings.index') }}" class="btn" style="background:#EF4444;color:white;font-size:13px;font-weight:700;padding:9px 20px;border-radius:9px;text-decoration:none;">
                            <i class="bi bi-collection me-1"></i>Manage Listings
                        </a>
                        <a href="{{ route('billing.plans') }}" class="btn" style="background:white;color:#EF4444;border:1.5px solid #EF4444;font-size:13px;font-weight:700;padding:9px 20px;border-radius:9px;text-decoration:none;">
                            <i class="bi bi-stars me-1"></i>Upgrade Plan
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Main form card --}}
        <div class="alb-card fade-in-up">

            {{-- Platform pills --}}
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px;">
                <span style="display:inline-flex;align-items:center;gap:6px;background:#FFF3E0;border:1.5px solid #FFB74D;color:#E65100;font-size:12px;font-weight:700;padding:5px 13px;border-radius:20px;">
                    <span style="width:8px;height:8px;background:#FF9900;border-radius:50%;display:inline-block;"></span>Amazon
                </span>
                <span style="display:inline-flex;align-items:center;gap:6px;background:#E3F2FD;border:1.5px solid #90CAF9;color:#1565C0;font-size:12px;font-weight:700;padding:5px 13px;border-radius:20px;">
                    <span style="width:8px;height:8px;background:#2874F0;border-radius:50%;display:inline-block;"></span>Flipkart
                </span>
                <span style="display:inline-flex;align-items:center;gap:6px;background:#FCE4EC;border:1.5px solid #F48FB1;color:#880E4F;font-size:12px;font-weight:700;padding:5px 13px;border-radius:20px;">
                    <span style="width:8px;height:8px;background:#F43397;border-radius:50%;display:inline-block;"></span>Meesho
                </span>
                <span style="font-size:12px;color:#9CA3AF;display:flex;align-items:center;padding-left:4px;">
                    — platform detected automatically
                </span>
            </div>

            <form method="POST" action="{{ route('listings.store') }}" id="importForm"
                  {{ (session('limitReached') || isset($limitReached)) ? '' : '' }}>
                @csrf

                {{-- URL Field --}}
                <div class="alb-form-group">
                    <label class="alb-label">
                        <i class="bi bi-link-45deg me-1" style="color:#E31837;"></i>
                        Product URL <span style="color:#E31837;">*</span>
                    </label>
                    <div style="position:relative;">
                        <input type="url" name="product_url" id="productUrl"
                            class="alb-input @error('product_url') is-invalid @enderror"
                            placeholder="https://www.amazon.in/dp/... or flipkart.com/... or meesho.com/..."
                            value="{{ old('product_url') }}"
                            oninput="detectPlatform(this.value)"
                            {{ (session('limitReached') || isset($limitReached)) ? 'disabled' : '' }}
                            required autofocus>
                        {{-- Live platform detection badge --}}
                        <div id="platformBadge" style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);
                            font-size:11px;font-weight:700;padding:3px 10px;border-radius:12px;pointer-events:none;">
                        </div>
                    </div>
                    @error('product_url')
                        <div style="color:#EF4444;font-size:12px;margin-top:5px;">
                            <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                        </div>
                    @enderror
                    <div style="font-size:12px;color:#9CA3AF;margin-top:5px;">
                        Paste any Amazon, Flipkart, or Meesho product URL — we handle the rest.
                    </div>
                </div>

                {{-- Brand + Manufacturer --}}
                <div class="row g-3 mb-1">
                    <div class="col-md-6">
                        <div class="alb-form-group">
                            <label class="alb-label">
                                <i class="bi bi-tag me-1" style="color:#E31837;"></i>
                                Your Brand Name <span style="color:#E31837;">*</span>
                            </label>
                            <input type="text" name="target_brand_name"
                                class="alb-input @error('target_brand_name') is-invalid @enderror"
                                placeholder="e.g. PrimeCraft"
                                value="{{ old('target_brand_name', auth()->user()->default_brand) }}"
                                {{ (session('limitReached') || isset($limitReached)) ? 'disabled' : '' }}
                                required maxlength="100">
                            @error('target_brand_name')
                                <div style="color:#EF4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alb-form-group">
                            <label class="alb-label">
                                <i class="bi bi-building me-1" style="color:#E31837;"></i>
                                Manufacturer Name <span style="color:#E31837;">*</span>
                            </label>
                            <input type="text" name="target_manufacturer"
                                class="alb-input @error('target_manufacturer') is-invalid @enderror"
                                placeholder="e.g. PrimeCraft Industries"
                                value="{{ old('target_manufacturer', auth()->user()->default_manufacturer) }}"
                                {{ (session('limitReached') || isset($limitReached)) ? 'disabled' : '' }}
                                required maxlength="100">
                            @error('target_manufacturer')
                                <div style="color:#EF4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Keywords --}}
                <div class="alb-form-group">
                    <label class="alb-label">
                        <i class="bi bi-search me-1" style="color:#E31837;"></i>
                        Target Keywords <span style="color:#9CA3AF;font-weight:400;">(Optional)</span>
                    </label>
                    <textarea name="target_keywords"
                        class="alb-input alb-textarea"
                        placeholder="e.g. stainless steel water bottle, BPA free, insulated, 32oz..."
                        maxlength="500"
                        {{ (session('limitReached') || isset($limitReached)) ? 'disabled' : '' }}>{{ old('target_keywords') }}</textarea>
                    <div style="font-size:12px;color:#9CA3AF;margin-top:5px;">
                        Add keywords to guide the AI. Separate with commas for best results.
                    </div>
                </div>

                {{-- What happens next --}}
                <div style="background:linear-gradient(135deg,#FFF5F5,#FEF2F2);border:1.5px solid #FECACA;border-radius:12px;padding:16px 20px;margin-bottom:20px;">
                    <div style="font-size:12.5px;font-weight:700;color:#E31837;margin-bottom:10px;">
                        <i class="bi bi-lightning-charge-fill me-1"></i> What happens next?
                    </div>
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        @foreach([
                            ['bi-cloud-download','Scrape','We scrape the product page from Amazon, Flipkart, or Meesho'],
                            ['bi-cpu','AI Generate','GPT-4o rewrites everything with your brand name — 100% unique'],
                            ['bi-layout-split','Compare & Export','Review original vs. generated side-by-side, then export'],
                        ] as [$icon,$step,$desc])
                        <div style="display:flex;align-items:flex-start;gap:12px;">
                            <div style="width:30px;height:30px;background:#E31837;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="bi {{ $icon }}" style="color:white;font-size:13px;"></i>
                            </div>
                            <div style="padding-top:4px;">
                                <div style="font-size:12.5px;font-weight:700;color:#374151;">{{ $step }}</div>
                                <div style="font-size:12px;color:#6B7280;margin-top:1px;">{{ $desc }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Remaining slots warning --}}
                @php $remaining = auth()->user()->getRemainingListings(); @endphp
                @if($remaining !== 'Unlimited' && is_int($remaining) && $remaining <= 2 && $remaining > 0)
                <div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;padding:12px 16px;margin-bottom:20px;">
                    <div style="font-size:12.5px;color:#92400E;">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Only <strong>{{ $remaining }} slot{{ $remaining != 1 ? 's' : '' }} remaining</strong> on your plan.
                        <a href="{{ route('billing.plans') }}" style="color:#E31837;font-weight:700;">Upgrade →</a>
                    </div>
                </div>
                @endif

                {{-- Submit --}}
                @if(session('limitReached') || isset($limitReached))
                <button type="button" class="btn w-100 py-3" disabled
                    style="background:#E5E7EB;color:#9CA3AF;border:none;font-size:15px;font-family:'Sora',sans-serif;border-radius:10px;cursor:not-allowed;">
                    <i class="bi bi-lock me-2"></i>Limit Reached — Delete a Listing First
                </button>
                @else
                <button type="submit" class="btn-alb-primary btn w-100 py-3" id="submitBtn"
                    style="font-size:15px;font-family:'Sora',sans-serif;">
                    <span id="btnText">
                        <i class="bi bi-stars me-2"></i>Import & Generate Listing
                    </span>
                    <span id="btnLoading" style="display:none;">
                        <span class="spinner-border spinner-border-sm me-2"></span>Queueing import...
                    </span>
                </button>
                @endif

                <div style="text-align:center;margin-top:12px;font-size:12px;color:#9CA3AF;">
                    <i class="bi bi-shield-check me-1"></i>
                    Your import is processed privately. We never use your data to train AI models.
                </div>
            </form>
        </div>

        {{-- Tips card --}}
        <div class="alb-card mt-4 fade-in-up fade-in-up-delay-1">
            <h3 style="font-family:'Sora',sans-serif;font-size:14px;font-weight:700;color:#111827;margin-bottom:16px;">
                <i class="bi bi-lightbulb me-2" style="color:#F59E0B;"></i>Tips for Best Results
            </h3>
            <div class="row g-3">
                @foreach([
                    ['bi-list-ul','Choose Detail-Rich Products','Products with many bullet points and a detailed description give the AI more to work with.'],
                    ['bi-key','Add Specific Keywords','Include your target search terms so the AI optimises for exactly what your buyers search.'],
                    ['bi-award','Use Your Real Brand','Enter your actual brand name so all generated content is brand-consistent across exports.'],
                ] as [$icon,$title,$body])
                <div class="col-md-4">
                    <div style="display:flex;gap:10px;">
                        <div style="width:32px;height:32px;background:#FEE2E8;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi {{ $icon }}" style="color:#E31837;font-size:14px;"></i>
                        </div>
                        <div>
                            <div style="font-size:12.5px;font-weight:700;color:#374151;margin-bottom:3px;">{{ $title }}</div>
                            <div style="font-size:12px;color:#6B7280;line-height:1.6;">{{ $body }}</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
// Live platform detection from URL
function detectPlatform(url) {
    const badge = document.getElementById('platformBadge');
    if (!url) { badge.style.display = 'none'; return; }

    let platform = null;
    if (/amazon\.(com|in|co\.uk|de|ca|fr|es|it|co\.jp|com\.au|com\.br|com\.mx|ae|sg)/i.test(url)) {
        platform = { label: 'Amazon', bg: '#FFF3E0', color: '#E65100', dot: '#FF9900' };
    } else if (/flipkart\.com/i.test(url)) {
        platform = { label: 'Flipkart', bg: '#E3F2FD', color: '#1565C0', dot: '#2874F0' };
    } else if (/meesho\.com/i.test(url)) {
        platform = { label: 'Meesho', bg: '#FCE4EC', color: '#880E4F', dot: '#F43397' };
    }

    if (platform) {
        badge.innerHTML = `<span style="width:7px;height:7px;background:${platform.dot};border-radius:50%;display:inline-block;margin-right:5px;"></span>${platform.label}`;
        badge.style.cssText += `;display:inline-flex;align-items:center;background:${platform.bg};color:${platform.color};border:1.5px solid ${platform.dot};`;
        badge.style.display = 'inline-flex';
    } else {
        badge.style.display = 'none';
    }
}

// Submit spinner
const form = document.getElementById('importForm');
if (form) {
    form.addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const btnLoad = document.getElementById('btnLoading');
        if (btn) { btn.disabled = true; }
        if (btnText) btnText.style.display = 'none';
        if (btnLoad) btnLoad.style.display = 'inline-flex';
    });
}
</script>
@endpush
@endsection
