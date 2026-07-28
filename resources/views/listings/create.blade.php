@extends('layouts.app')
@section('title', 'New Listing')
@section('page-title', 'New Listing')

@section('content')

    {{-- Limit reached banner --}}
    @if (session('limitReached') || isset($limitReached))
        <div class="alb-card mb-4 fade-in-up" style="border:2px solid #EF4444;background:#FFF5F5;padding:20px 24px;">
            <div class="d-flex align-items-start gap-3">
                <div
                    style="width:44px;height:44px;background:#FEE2E2;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-exclamation-triangle-fill" style="color:#EF4444;font-size:20px;"></i>
                </div>
                <div style="flex:1;">
                    <div
                        style="font-family:'Sora',sans-serif;font-size:15px;font-weight:700;color:#991B1B;margin-bottom:6px;">
                        Listing Limit Reached</div>
                    <div style="font-size:13.5px;color:#B91C1C;line-height:1.6;margin-bottom:14px;">
                        You've used all <strong>{{ auth()->user()->plan?->listings_limit ?? 5 }}</strong> slots on your
                        <strong>{{ auth()->user()->plan?->name ?? 'Free' }}</strong> plan.
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('listings.index') }}" class="btn"
                            style="background:#EF4444;color:white;font-size:13px;font-weight:700;padding:9px 20px;border-radius:9px;text-decoration:none;">
                            <i class="bi bi-collection me-1"></i>Manage Listings
                        </a>
                        <a href="{{ route('billing.plans') }}" class="btn"
                            style="background:white;color:#EF4444;border:1.5px solid #EF4444;font-size:13px;font-weight:700;padding:9px 20px;border-radius:9px;text-decoration:none;">
                            <i class="bi bi-arrow-up-circle me-1"></i>Upgrade Plan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">

            {{-- ── STEP 1: Platform picker ─────────────────────────────────────────── --}}
            <div class="alb-card mb-4 fade-in-up">
                <div
                    style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9CA3AF;margin-bottom:4px;">
                    Step 1 of 2</div>
                <div style="font-family:'Sora',sans-serif;font-size:17px;font-weight:700;color:#111827;margin-bottom:20px;">
                    Choose a Platform</div>

                @php
                    $platforms = [
                        [
                            'amazon',
                            'Amazon',
                            '#FF9900',
                            'rgba(255,153,0,0.10)',
                            'bi-bag-check-fill',
                            'All regions — .in .com .co.uk',
                        ],
                        [
                            'flipkart',
                            'Flipkart',
                            '#2874F0',
                            'rgba(40,116,240,0.10)',
                            'bi-cart-fill',
                            'India\'s largest marketplace',
                        ],
                        [
                            'meesho',
                            'Meesho',
                            '#F43397',
                            'rgba(244,51,151,0.10)',
                            'bi-shop-window',
                            'Social commerce for India',
                        ],
                    ];
                    $oldPlatform = old('platform', '');
                @endphp

                <div class="row g-3" id="platformRow">
                    @foreach ($platforms as [$pKey, $pLabel, $pColor, $pBg, $pIcon, $pDesc])
                        <div class="col-md-4">
                            <button type="button"
                                onclick="selectPlatform('{{ $pKey }}','{{ $pColor }}','{{ $pBg }}')"
                                id="pcard-{{ $pKey }}" data-color="{{ $pColor }}"
                                data-bg="{{ $pBg }}"
                                style="width:100%;background:{{ $oldPlatform === $pKey ? $pBg : 'white' }};border:2px solid {{ $oldPlatform === $pKey ? $pColor : '#E5E7EB' }};border-radius:12px;padding:20px 14px;cursor:pointer;text-align:center;transition:all 0.15s;">
                                <div
                                    style="width:48px;height:48px;background:{{ $pBg }};border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:24px;color:{{ $pColor }};">
                                    <i class="{{ $pIcon }}"></i>
                                </div>
                                <div
                                    style="font-family:'Sora',sans-serif;font-size:15px;font-weight:700;color:#111827;margin-bottom:4px;">
                                    {{ $pLabel }}</div>
                                <div style="font-size:11.5px;color:#9CA3AF;line-height:1.4;margin-bottom:10px;">
                                    {{ $pDesc }}</div>
                                <div id="pcheck-{{ $pKey }}"
                                    style="display:{{ $oldPlatform === $pKey ? 'block' : 'none' }};">
                                    <span
                                        style="background:{{ $pColor }};color:white;font-size:11px;font-weight:700;padding:3px 12px;border-radius:20px;">Selected
                                        ✓</span>
                                </div>
                            </button>
                        </div>
                    @endforeach
                </div>

                @error('platform')
                    <div style="color:#EF4444;font-size:12.5px;margin-top:12px;"><i
                            class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                @enderror
            </div>

            {{-- ── STEP 2: Form fields ─────────────────────────────────────────────── --}}
            <div id="step2" style="{{ $oldPlatform ? '' : 'display:none;' }}opacity:0;transition:opacity 0.3s;">
                <div class="alb-card mb-4 fade-in-up">
                    <div
                        style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9CA3AF;margin-bottom:4px;">
                        Step 2 of 2</div>
                    <div
                        style="font-family:'Sora',sans-serif;font-size:17px;font-weight:700;color:#111827;margin-bottom:20px;">
                        Product Details</div>

                    <form method="POST" action="{{ route('listings.store') }}" id="importForm">
                        @csrf
                        <input type="hidden" name="platform" id="platformInput" value="{{ $oldPlatform }}">

                        {{-- URL field --}}
                        <div class="alb-form-group">
                            <label class="alb-label">
                                <i class="bi bi-link-45deg me-1" style="color:#E31837;"></i>
                                Product URL <span style="color:#E31837;">*</span>
                            </label>
                            <div style="position:relative;">
                                <input type="url" name="product_url" id="productUrlInput"
                                    class="alb-input @error('product_url') is-invalid @enderror"
                                    placeholder="Paste the full product page URL..." value="{{ old('product_url') }}"
                                    required oninput="detectProductId(this.value)" style="padding-right:130px;">
                                <div id="pidBadge"
                                    style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:10.5px;font-weight:700;padding:3px 10px;border-radius:20px;color:white;white-space:nowrap;">
                                    <span id="pidValue"></span>
                                </div>
                            </div>
                            @error('product_url')
                                <div style="color:#EF4444;font-size:12px;margin-top:4px;"><i
                                        class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>
                            @enderror
                            <div id="urlHint" style="font-size:12px;color:#9CA3AF;margin-top:5px;">
                                @if ($oldPlatform === 'amazon')
                                    e.g. https://www.amazon.in/dp/B0FHXY7VZ5
                                @elseif($oldPlatform === 'flipkart')
                                    e.g. https://www.flipkart.com/product/p/itmXXXXXXXX
                                @elseif($oldPlatform === 'meesho')
                                    e.g. https://meesho.com/product/p/123456789
                                @else
                                    Paste the full product page URL from the selected platform
                                @endif
                            </div>
                        </div>

                        {{-- Brand + Manufacturer --}}
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="alb-form-group">
                                    <label class="alb-label">
                                        <i class="bi bi-tag me-1" style="color:#E31837;"></i>
                                        Your Brand Name <span style="color:#E31837;">*</span>
                                    </label>
                                    <input type="text" name="target_brand_name"
                                        class="alb-input @error('target_brand_name') is-invalid @enderror"
                                        placeholder="e.g. PrimeCraft"
                                        value="{{ old('target_brand_name', auth()->user()->default_brand) }}" required
                                        maxlength="100">
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
                            <textarea name="target_keywords" class="alb-input alb-textarea"
                                placeholder="e.g. stainless steel water bottle, BPA free, insulated, 32oz..." maxlength="500">{{ old('target_keywords') }}</textarea>
                            <div style="font-size:12px;color:#9CA3AF;margin-top:5px;">
                                Add keywords to guide the AI. Separate with commas.
                            </div>
                        </div>

                        {{-- What happens next --}}
                        <div
                            style="background:#F0F9FF;border:1px solid #BFDBFE;border-radius:10px;padding:14px 16px;margin-bottom:20px;">
                            <div style="font-size:12.5px;font-weight:700;color:#1E40AF;margin-bottom:8px;">
                                <i class="bi bi-info-circle me-1"></i> What happens next?
                            </div>
                            <div style="display:flex;flex-direction:column;gap:6px;">
                                <div style="font-size:12.5px;color:#3B82F6;display:flex;gap:8px;">
                                    <span style="font-weight:700;min-width:18px;">1.</span> We scrape the product page from
                                    the selected platform
                                </div>
                                <div style="font-size:12.5px;color:#3B82F6;display:flex;gap:8px;">
                                    <span style="font-weight:700;min-width:18px;">2.</span> GPT-4o rewrites everything with
                                    your brand name — 100% unique
                                </div>
                                <div style="font-size:12.5px;color:#3B82F6;display:flex;gap:8px;">
                                    <span style="font-weight:700;min-width:18px;">3.</span> Compare original vs. generated
                                    side-by-side, then export
                                </div>
                            </div>
                        </div>

                        {{-- Usage warning --}}
                        @php $remaining = auth()->user()->getRemainingListings(); @endphp
                        @if ($remaining !== 'Unlimited' && is_int($remaining) && $remaining <= 2 && $remaining > 0)
                            <div
                                style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;padding:12px 16px;margin-bottom:20px;">
                                <div style="font-size:12.5px;color:#92400E;">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Only <strong>{{ $remaining }} slot{{ $remaining != 1 ? 's' : '' }}
                                        remaining</strong>.
                                    <a href="{{ route('billing.plans') }}" style="color:#E31837;font-weight:700;">Upgrade
                                        →</a>
                                </div>
                            </div>
                        @endif

                        {{-- Submit --}}
                        <button type="submit" id="submitBtn" class="btn-alb-primary btn w-100 py-3"
                            style="font-size:15px;font-family:'Sora',sans-serif;{{ session('limitReached') || isset($limitReached) ? 'opacity:.5;cursor:not-allowed;' : '' }}"
                            {{ session('limitReached') || isset($limitReached) ? 'disabled' : '' }}>
                            <span id="btnText"><i class="bi bi-cloud-download me-2"></i>Import & Generate Listing</span>
                            <span id="btnLoading" style="display:none;"><span
                                    class="spinner-border spinner-border-sm me-2"></span>Queueing...</span>
                        </button>

                        <div style="text-align:center;margin-top:14px;font-size:12px;color:#9CA3AF;">
                            <i class="bi bi-shield-check me-1"></i>Processed privately. We never use your data to train AI.
                        </div>
                    </form>
                </div>

                {{-- Tips --}}
                <div class="alb-card fade-in-up" style="background:#FAFAFA;">
                    <h3
                        style="font-family:'Sora',sans-serif;font-size:14px;font-weight:700;color:#111827;margin-bottom:14px;">
                        <i class="bi bi-lightbulb me-2" style="color:#F59E0B;"></i>Tips for Best Results
                    </h3>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div style="font-size:12.5px;color:#6B7280;">
                                <strong style="color:#374151;display:block;margin-bottom:4px;">Detail-Rich
                                    Products</strong>
                                More bullet points = better AI output.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div style="font-size:12.5px;color:#6B7280;">
                                <strong style="color:#374151;display:block;margin-bottom:4px;">Add Target Keywords</strong>
                                Guides AI to optimize for your search terms.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div style="font-size:12.5px;color:#6B7280;">
                                <strong style="color:#374151;display:block;margin-bottom:4px;">Use Your Real Brand</strong>
                                Ensures all content is brand-consistent.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('styles')
        <style>
            #platformRow button:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(0, 0, 0, .08);
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            // ── Platform config ───────────────────────────────────────────────────────────
            var platforms = {
                amazon: {
                    color: '#FF9900',
                    bg: 'rgba(255,153,0,0.10)',
                    hint: 'e.g. https://www.amazon.in/dp/B0FHXY7VZ5',
                    idLabel: 'ASIN',
                    regex: /\/(?:dp|product|gp\/product)\/([A-Z0-9]{10})/i
                },
                flipkart: {
                    color: '#2874F0',
                    bg: 'rgba(40,116,240,0.10)',
                    hint: 'e.g. https://www.flipkart.com/product/p/itmXXXXXXXX',
                    idLabel: 'PID',
                    regex: /\/p\/(itm[a-zA-Z0-9]+)/i
                },
                meesho: {
                    color: '#F43397',
                    bg: 'rgba(244,51,151,0.10)',
                    hint: 'e.g. https://meesho.com/product/p/123456789',
                    idLabel: 'ID',
                    regex: /\/p\/(\d+)/i
                },
            };
            var currentPlatform = '{{ $oldPlatform }}';

            // ── Select platform ───────────────────────────────────────────────────────────
            function selectPlatform(key, color, bg) {
                currentPlatform = key;
                document.getElementById('platformInput').value = key;

                // Update all platform card visuals
                Object.keys(platforms).forEach(function(k) {
                    var card = document.getElementById('pcard-' + k);
                    var check = document.getElementById('pcheck-' + k);
                    var cfg = platforms[k];
                    var sel = k === key;
                    card.style.border = '2px solid ' + (sel ? cfg.color : '#E5E7EB');
                    card.style.background = sel ? cfg.bg : 'white';
                    check.style.display = sel ? 'block' : 'none';
                });

                // Update URL hint text
                var cfg = platforms[key];
                document.getElementById('urlHint').textContent = cfg.hint;
                document.getElementById('productUrlInput').placeholder = cfg.hint;

                // Re-detect ID from current URL value
                detectProductId(document.getElementById('productUrlInput').value);

                // Reveal Step 2 with animation
                var step2 = document.getElementById('step2');
                if (step2.style.display === 'none') {
                    step2.style.display = '';
                    // Small delay so transition fires
                    setTimeout(function() {
                        step2.style.opacity = '1';
                    }, 20);
                    setTimeout(function() {
                        step2.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 100);
                }
            }

            // ── Detect product ID from URL ────────────────────────────────────────────────
            function detectProductId(url) {
                var badge = document.getElementById('pidBadge');
                var label = document.getElementById('pidValue');
                if (!currentPlatform || !url || !platforms[currentPlatform]) {
                    badge.style.display = 'none';
                    return;
                }

                var cfg = platforms[currentPlatform];
                var match = url.match(cfg.regex);
                if (match) {
                    label.textContent = cfg.idLabel + ': ' + match[1].toUpperCase();
                    badge.style.display = 'flex';
                    badge.style.background = cfg.color;
                    badge.style.alignItems = 'center';
                } else {
                    badge.style.display = 'none';
                }
            }

            // ── Form submit ───────────────────────────────────────────────────────────────
            document.getElementById('importForm').addEventListener('submit', function(e) {
                if (!currentPlatform) {
                    e.preventDefault();
                    document.getElementById('platformRow').scrollIntoView({
                        behavior: 'smooth'
                    });
                    return;
                }
                document.getElementById('btnText').style.display = 'none';
                document.getElementById('btnLoading').style.display = 'inline-flex';
                document.getElementById('submitBtn').disabled = true;
            });

            // ── Restore state on page load (after validation error redirect back) ─────────
            (function init() {
                if (!currentPlatform) return;
                // Restore card selection visuals
                var cfg = platforms[currentPlatform];
                if (!cfg) return;
                selectPlatform(currentPlatform, cfg.color, cfg.bg);
                // Show step2 immediately (no animation delay)
                var step2 = document.getElementById('step2');
                step2.style.display = '';
                step2.style.opacity = '1';
                // Re-detect ID badge from old URL value
                detectProductId(document.getElementById('productUrlInput').value);
            })();
        </script>
    @endpush
@endsection
