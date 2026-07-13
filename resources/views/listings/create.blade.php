@extends('layouts.app')
@section('title', 'New Listing')
@section('page-title', 'New Listing')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">

        <!-- Step Indicator -->
        <div class="alb-card mb-4 fade-in-up" style="padding:20px 24px;">
            <div class="d-flex align-items-center justify-content-between">
                @php $steps = ['Import URL', 'AI Generate', 'Compare & Edit', 'Export']; @endphp
                @foreach($steps as $i => $step)
                <div class="d-flex align-items-center {{ $i < count($steps)-1 ? 'flex-grow-1' : '' }}">
                    <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                        <div style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;
                            background:{{ $i === 0 ? '#E31837' : '#F3F4F6' }};
                            color:{{ $i === 0 ? 'white' : '#9CA3AF' }};">
                            {{ $i + 1 }}
                        </div>
                        <span style="font-size:11px;font-weight:600;color:{{ $i === 0 ? '#E31837' : '#9CA3AF' }};white-space:nowrap;">{{ $step }}</span>
                    </div>
                    @if($i < count($steps)-1)
                    <div style="flex:1;height:2px;background:{{ $i === 0 ? '#E31837' : '#F3F4F6' }};margin:0 8px;margin-bottom:20px;"></div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Main Form Card -->
        <div class="alb-card fade-in-up fade-in-up-delay-1">
            <div style="margin-bottom:24px;">
                <h2 style="font-family:'Sora',sans-serif;font-size:20px;font-weight:700;color:#111827;margin:0 0 6px;">
                    Import Amazon Product
                </h2>
                <p style="color:#6B7280;font-size:14px;margin:0;">
                    Paste any Amazon product URL and we'll scrape the data, then generate a unique branded listing for you.
                </p>
            </div>

            <form method="POST" action="{{ route('listings.store') }}" id="importForm">
                @csrf

                <!-- Amazon URL -->
                <div class="alb-form-group">
                    <label class="alb-label">
                        <i class="bi bi-link-45deg me-1" style="color:#E31837;"></i>
                        Amazon Product URL <span style="color:#E31837;">*</span>
                    </label>
                    <div style="position:relative;">
                        <input type="url" name="amazon_url" id="amazonUrl"
                            class="alb-input @error('amazon_url') is-invalid @enderror"
                            placeholder="https://www.amazon.com/dp/B0XXXXXX or https://www.amazon.in/..."
                            value="{{ old('amazon_url') }}" required
                            oninput="detectAsin(this.value)">
                        <div id="asinBadge" style="display:none;position:absolute;right:12px;top:50%;transform:translateY(-50%);background:#D1FAE5;color:#065F46;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;">
                            ASIN: <span id="asinValue"></span>
                        </div>
                    </div>
                    @error('amazon_url')
                        <div style="color:#EF4444;font-size:12px;margin-top:4px;"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                    @enderror
                    <div style="font-size:12px;color:#9CA3AF;margin-top:5px;">
                        Supports amazon.com, amazon.in, amazon.co.uk, amazon.de, and more
                    </div>
                </div>

                <div class="row g-3">
                    <!-- Brand Name -->
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
                                required maxlength="100">
                            @error('target_brand_name')
                                <div style="color:#EF4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Manufacturer -->
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
                                required maxlength="100">
                            @error('target_manufacturer')
                                <div style="color:#EF4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Keywords -->
                <div class="alb-form-group">
                    <label class="alb-label">
                        <i class="bi bi-search me-1" style="color:#E31837;"></i>
                        Target Keywords <span style="color:#9CA3AF;font-weight:400;">(Optional)</span>
                    </label>
                    <textarea name="target_keywords"
                        class="alb-input alb-textarea"
                        placeholder="e.g. stainless steel water bottle, BPA free, insulated, 32oz..."
                        maxlength="500">{{ old('target_keywords') }}</textarea>
                    <div style="font-size:12px;color:#9CA3AF;margin-top:5px;">
                        Add keywords to guide the AI. Separate with commas for best results.
                    </div>
                </div>

                <!-- Info Box -->
                <div style="background:#F0F9FF;border:1px solid #BFDBFE;border-radius:10px;padding:14px 16px;margin-bottom:24px;">
                    <div style="font-size:12.5px;font-weight:700;color:#1E40AF;margin-bottom:8px;">
                        <i class="bi bi-info-circle me-1"></i> What happens next?
                    </div>
                    <div style="display:flex;flex-direction:column;gap:5px;">
                        <div style="font-size:12.5px;color:#3B82F6;display:flex;align-items:flex-start;gap:8px;">
                            <span style="font-weight:700;min-width:18px;">1.</span> We scrape the Amazon product page (title, bullets, specs, images)
                        </div>
                        <div style="font-size:12.5px;color:#3B82F6;display:flex;align-items:flex-start;gap:8px;">
                            <span style="font-weight:700;min-width:18px;">2.</span> GPT-4o rewrites everything with your brand name — 100% unique content
                        </div>
                        <div style="font-size:12.5px;color:#3B82F6;display:flex;align-items:flex-start;gap:8px;">
                            <span style="font-weight:700;min-width:18px;">3.</span> Compare original vs. generated side-by-side, then export
                        </div>
                    </div>
                </div>

                <!-- Usage Warning -->
                @php $remaining = auth()->user()->getRemainingListings(); @endphp
                @if($remaining !== 'Unlimited' && $remaining <= 2)
                <div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;padding:12px 16px;margin-bottom:20px;">
                    <div style="font-size:12.5px;color:#92400E;">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>{{ $remaining }} listing{{ $remaining != 1 ? 's' : '' }} remaining</strong> on your plan.
                        <a href="{{ route('billing.plans') }}" style="color:#E31837;font-weight:700;">Upgrade now →</a>
                    </div>
                </div>
                @endif

                <!-- Submit -->
                <button type="submit" class="btn-alb-primary btn w-100 py-3" id="submitBtn" style="font-size:15px;font-family:'Sora',sans-serif;">
                    <span id="btnText">
                        <i class="bi bi-cloud-download me-2"></i>Import & Generate Listing
                    </span>
                    <span id="btnLoading" style="display:none;">
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        Queueing import...
                    </span>
                </button>

                <div style="text-align:center;margin-top:14px;font-size:12px;color:#9CA3AF;">
                    <i class="bi bi-shield-check me-1"></i>
                    Your import is processed privately. We never use your data to train AI models.
                </div>
            </form>
        </div>

        <!-- Tips -->
        <div class="alb-card mt-4 fade-in-up fade-in-up-delay-2" style="background:#FAFAFA;">
            <h3 style="font-family:'Sora',sans-serif;font-size:14px;font-weight:700;color:#111827;margin-bottom:14px;">
                <i class="bi bi-lightbulb me-2" style="color:#F59E0B;"></i>Tips for Best Results
            </h3>
            <div class="row g-3">
                <div class="col-md-4">
                    <div style="font-size:12.5px;color:#6B7280;">
                        <strong style="color:#374151;display:block;margin-bottom:4px;">Choose Detail-Rich Products</strong>
                        Products with many bullet points and a detailed description give the AI more to work with.
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="font-size:12.5px;color:#6B7280;">
                        <strong style="color:#374151;display:block;margin-bottom:4px;">Add Specific Keywords</strong>
                        Include your target keywords so the AI optimizes for your exact search terms.
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="font-size:12.5px;color:#6B7280;">
                        <strong style="color:#374151;display:block;margin-bottom:4px;">Use Your Real Brand</strong>
                        Enter your actual brand name to ensure all generated content is brand-consistent.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function detectAsin(url) {
    const match = url.match(/\/(?:dp|product|gp\/product)\/([A-Z0-9]{10})/i);
    const badge = document.getElementById('asinBadge');
    if (match) {
        document.getElementById('asinValue').textContent = match[1].toUpperCase();
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

document.getElementById('importForm').addEventListener('submit', function() {
    document.getElementById('btnText').style.display = 'none';
    document.getElementById('btnLoading').style.display = 'inline-flex';
    document.getElementById('submitBtn').disabled = true;
});
</script>
@endpush
@endsection
