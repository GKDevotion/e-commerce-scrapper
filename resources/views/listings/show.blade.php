@extends('layouts.app')
@section('title', 'Listing #' . $import->id)
@section('page-title', 'Listing Import')

@section('topbar-actions')
<a href="{{ route('listings.create') }}" class="topbar-btn">
    <i class="bi bi-plus-lg"></i> New
</a>
@endsection

@section('content')
<div class="row g-4">

    <!-- Left: Import Info & Status -->
    <div class="col-lg-5">

        <!-- Status Card -->
        <div class="alb-card mb-4 fade-in-up">
            <div class="d-flex align-items-center gap-3 mb-4">
                @if($import->primary_image)
                <img src="{{ $import->primary_image }}" alt="Product" style="width:70px;height:70px;border-radius:10px;object-fit:cover;border:1px solid #E5E7EB;">
                @else
                <div style="width:70px;height:70px;border-radius:10px;background:#F3F4F6;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-box" style="font-size:28px;color:#9CA3AF;"></i>
                </div>
                @endif
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:700;color:#111827;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $import->original_title ?? 'Importing product...' }}
                    </div>
                    @if($import->asin)
                    <div style="font-size:12px;color:#6B7280;">ASIN: <strong>{{ $import->asin }}</strong></div>
                    @endif
                    <div style="font-size:12px;color:#9CA3AF;margin-top:2px;">{{ $import->original_category ?? '' }}</div>
                </div>
            </div>

            <!-- Status Timeline -->
            <div style="position:relative;">
                @php
                $statuses = [
                    'pending' => ['Queued', 'bi-clock', 0],
                    'scraping' => ['Scraping Amazon', 'bi-cloud-download', 1],
                    'scraped' => ['Data Extracted', 'bi-check-circle', 2],
                    'processing' => ['AI Generating', 'bi-cpu', 3],
                    'completed' => ['Listing Ready', 'bi-stars', 4],
                    'failed' => ['Failed', 'bi-x-circle', -1],
                ];
                $currentStatus = $import->status;
                $currentStep = $statuses[$currentStatus][2] ?? 0;
                @endphp

                @if($import->status === 'failed')
                <div style="background:#FEE2E2;border:1px solid #FCA5A5;border-radius:10px;padding:14px 16px;margin-bottom:16px;">
                    <div style="font-size:13px;font-weight:700;color:#991B1B;margin-bottom:6px;">
                        <i class="bi bi-x-circle me-1"></i>Import Failed
                    </div>
                    <div style="font-size:12.5px;color:#B91C1C;">{{ $import->scrape_error }}</div>
                    <a href="{{ route('listings.create') }}" style="display:inline-block;margin-top:10px;font-size:12.5px;color:#E31837;font-weight:700;text-decoration:none;">
                        Try again →
                    </a>
                </div>
                @else
                <div style="display:flex;flex-direction:column;gap:0;">
                    @foreach(['Queued', 'Scraping Amazon', 'Data Extracted', 'AI Generating', 'Listing Ready'] as $si => $sLabel)
                    @php
                    $isDone = $currentStep > $si;
                    $isCurrent = $currentStep === $si;
                    @endphp
                    <div style="display:flex;align-items:flex-start;gap:14px;{{ !$loop->last ? 'padding-bottom:16px;' : '' }}">
                        <div style="display:flex;flex-direction:column;align-items:center;">
                            <div style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;
                                background:{{ $isDone ? '#E31837' : ($isCurrent ? '#FEE2E8' : '#F3F4F6') }};
                                color:{{ $isDone ? 'white' : ($isCurrent ? '#E31837' : '#9CA3AF') }};
                                border:{{ $isCurrent ? '2px solid #E31837' : 'none' }};">
                                @if($isDone)<i class="bi bi-check"></i>@else{{ $si + 1 }}@endif
                            </div>
                            @if(!$loop->last)
                            <div style="width:2px;flex:1;min-height:12px;background:{{ $isDone ? '#E31837' : '#F3F4F6' }};margin:2px 0;"></div>
                            @endif
                        </div>
                        <div style="padding-top:4px;">
                            <div style="font-size:13px;font-weight:{{ $isCurrent ? '700' : '500' }};color:{{ $isDone ? '#059669' : ($isCurrent ? '#E31837' : '#9CA3AF') }};">
                                {{ $sLabel }}
                                @if($isCurrent && in_array($currentStatus, ['scraping','processing']))
                                <span class="spinner-border spinner-border-sm ms-1" style="width:12px;height:12px;border-width:2px;color:#E31837;"></span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        <!-- Original Product Info (once scraped) -->
        @if($import->original_title)
        <div class="alb-card fade-in-up">
            <h3 class="alb-card-title mb-3"><i class="bi bi-box me-2" style="color:#E31837;"></i>Scraped Data</h3>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#9CA3AF;letter-spacing:0.05em;margin-bottom:4px;">Title</div>
                    <div style="font-size:13px;color:#374151;line-height:1.4;">{{ $import->original_title }}</div>
                </div>
                @if($import->original_brand)
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#9CA3AF;letter-spacing:0.05em;margin-bottom:4px;">Brand</div>
                    <div style="font-size:13px;color:#374151;">{{ $import->original_brand }}</div>
                </div>
                @endif
                @if($import->original_bullet_points)
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#9CA3AF;letter-spacing:0.05em;margin-bottom:6px;">Bullet Points</div>
                    @foreach($import->original_bullet_points as $bullet)
                    <div style="font-size:12.5px;color:#374151;display:flex;gap:8px;margin-bottom:5px;">
                        <span style="color:#E31837;flex-shrink:0;">•</span>
                        <span>{{ Str::limit($bullet, 120) }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
                @if($import->product_dimensions || $import->product_weight)
                <div style="display:flex;gap:16px;">
                    @if($import->product_weight)
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#9CA3AF;letter-spacing:0.05em;margin-bottom:4px;">Weight</div>
                        <div style="font-size:13px;color:#374151;">{{ $import->product_weight }}</div>
                    </div>
                    @endif
                    @if($import->product_dimensions)
                    <div>
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#9CA3AF;letter-spacing:0.05em;margin-bottom:4px;">Dimensions</div>
                        <div style="font-size:13px;color:#374151;">{{ $import->product_dimensions }}</div>
                    </div>
                    @endif
                </div>
                @endif
                @if(!empty($import->original_images))
                <div>
                    <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#9CA3AF;letter-spacing:0.05em;margin-bottom:8px;">
                        Images ({{ count($import->original_images) }})
                    </div>
                    <div class="row g-2">
                        @foreach($import->original_images as $i => $img)
                        <div class="col-4">
                            <a href="{{ $img }}" target="_blank" title="Image {{ $i + 1 }} — click to open full size" style="display:block;border-radius:8px;overflow:hidden;border:1.5px solid #E5E7EB;aspect-ratio:1;background:#F3F4F6;position:relative;">
                                <img src="{{ $img }}" alt="Product image {{ $i + 1 }}" style="width:100%;height:100%;object-fit:cover;" onerror="this.parentElement.style.display='none'">
                                <span style="position:absolute;bottom:3px;right:4px;background:rgba(0,0,0,0.55);color:white;font-size:9px;font-weight:700;padding:1px 5px;border-radius:6px;">{{ $i + 1 }}</span>
                            </a>
                        </div>
                        @endforeach
                    </div>
                    <div style="font-size:11px;color:#9CA3AF;margin-top:6px;">
                        <i class="bi bi-info-circle me-1"></i>Full download available after listing creation
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- Right: AI Generation -->
    <div class="col-lg-7">
        @if($import->status === 'scraped' || $import->status === 'completed')

        @if($latestGeneration && $latestGeneration->status === 'completed')
        <!-- Generation Exists -->
        <div class="alb-card mb-4 fade-in-up" style="border:2px solid #D1FAE5;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                <div style="width:44px;height:44px;background:#D1FAE5;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-check-circle-fill" style="color:#10B981;font-size:22px;"></i>
                </div>
                <div>
                    <div style="font-family:'Sora',sans-serif;font-size:16px;font-weight:700;color:#111827;">Listing Generated!</div>
                    <div style="font-size:12.5px;color:#6B7280;">AI processed {{ $latestGeneration->total_tokens ?? 0 }} tokens • {{ $latestGeneration->generated_at?->diffForHumans() }}</div>
                </div>
            </div>

            <!-- Preview of generated content -->
            <div style="background:#F9FAFB;border-radius:10px;padding:16px;margin-bottom:16px;">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#9CA3AF;letter-spacing:0.05em;margin-bottom:8px;">Generated Title</div>
                <div style="font-size:14px;font-weight:600;color:#111827;line-height:1.5;">{{ $latestGeneration->generated_title }}</div>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="{{ route('generations.view', $latestGeneration->id) }}" class="btn-alb-primary btn">
                    <i class="bi bi-eye me-2"></i>View Full Comparison
                </a>
                <form method="POST" action="{{ route('generations.generate', $import->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn-alb-outline btn">
                        <i class="bi bi-arrow-clockwise me-2"></i>Regenerate
                    </button>
                </form>
            </div>
        </div>

        @elseif($latestGeneration && $latestGeneration->status === 'generating')
        <!-- Generating -->
        <div class="alb-card mb-4 fade-in-up" style="text-align:center;padding:48px 24px;" id="generatingCard">
            <div style="width:72px;height:72px;border-radius:50%;background:#FEE2E8;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:32px;color:#E31837;">
                <i class="bi bi-cpu spin"></i>
            </div>
            <div style="font-family:'Sora',sans-serif;font-size:18px;font-weight:700;color:#111827;margin-bottom:8px;">AI Is Writing Your Listing</div>
            <div style="font-size:14px;color:#6B7280;max-width:320px;margin:0 auto 24px;line-height:1.6;">
                GPT-4o is generating your unique product title, bullet points, description, and SEO keywords...
            </div>
            <div id="loadingMessages" style="font-size:13px;color:#E31837;font-weight:600;min-height:24px;"></div>
        </div>

        @elseif($latestGeneration && $latestGeneration->status === 'failed')
        <!-- Failed -->
        <div class="alb-card mb-4 fade-in-up" style="border:1.5px solid #FCA5A5;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
                <div style="width:44px;height:44px;background:#FEE2E2;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-x-circle-fill" style="color:#EF4444;font-size:22px;"></i>
                </div>
                <div>
                    <div style="font-family:'Sora',sans-serif;font-size:16px;font-weight:700;color:#111827;">Generation Failed</div>
                    <div style="font-size:12.5px;color:#6B7280;">{{ $latestGeneration->generation_error }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('generations.generate', $import->id) }}">
                @csrf
                <button type="submit" class="btn-alb-primary btn">
                    <i class="bi bi-arrow-clockwise me-2"></i>Try Again
                </button>
            </form>
        </div>

        @else
        <!-- Ready to Generate — choose AI or Manual -->
        <div class="alb-card mb-4 fade-in-up" style="text-align:center;padding:40px 24px;">
            <div style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#FEE2E8,#FFE4E6);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:36px;color:#E31837;">
                <i class="bi bi-stars"></i>
            </div>
            <h3 style="font-family:'Sora',sans-serif;font-size:20px;font-weight:700;margin-bottom:10px;">Ready to Build Your Listing!</h3>
            <p style="color:#6B7280;font-size:14px;max-width:420px;margin:0 auto 28px;line-height:1.6;">
                Product data successfully scraped. Choose how you'd like to create your branded listing.
            </p>

            <div class="row g-3 justify-content-center" style="max-width:560px;margin:0 auto;">
                <div class="col-sm-6">
                    <form method="POST" action="{{ route('generations.generate', $import->id) }}" id="aiGenerateForm">
                        @csrf
                        <button type="submit" class="btn w-100" id="generateBtn" style="background:#E31837;color:white;border:none;border-radius:12px;padding:20px 16px;text-align:left;transition:all 0.15s;height:100%;">
                            <i class="bi bi-cpu" style="font-size:24px;display:block;margin-bottom:10px;" id="genBtnIcon"></i>
                            <span id="genBtnText">
                                <span style="font-family:'Sora',sans-serif;font-size:15px;font-weight:700;display:block;margin-bottom:4px;">Generate with AI</span>
                                <span style="font-size:12px;opacity:0.85;">GPT-4o writes unique, SEO-optimized content automatically</span>
                            </span>
                            <span id="genBtnLoading" style="display:none;">
                                <span class="spinner-border spinner-border-sm me-2"></span>Starting AI...
                            </span>
                        </button>
                    </form>
                    <div style="font-size:11px;color:#9CA3AF;margin-top:8px;">~1,800 tokens • 15–45 seconds</div>
                </div>

                <div class="col-sm-6">
                    <a href="{{ route('generations.manual.create', $import->id) }}" class="btn w-100 text-decoration-none" style="background:white;color:#374151;border:1.5px solid #E5E7EB;border-radius:12px;padding:20px 16px;text-align:left;transition:all 0.15s;height:100%;display:block;" onmouseover="this.style.borderColor='#E31837';this.style.color='#E31837'" onmouseout="this.style.borderColor='#E5E7EB';this.style.color='#374151'">
                        <i class="bi bi-pencil-square" style="font-size:24px;display:block;margin-bottom:10px;"></i>
                        <span style="font-family:'Sora',sans-serif;font-size:15px;font-weight:700;display:block;margin-bottom:4px;">Create Manually</span>
                        <span style="font-size:12px;opacity:0.75;">Edit the scraped content yourself — no AI required</span>
                    </a>
                    <div style="font-size:11px;color:#9CA3AF;margin-top:8px;">Brand auto-swapped • Full control</div>
                </div>
            </div>
        </div>
        @endif

        @else
        <!-- Still scraping -->
        <div class="alb-card fade-in-up" style="text-align:center;padding:48px 24px;">
            <div style="width:72px;height:72px;border-radius:50%;background:#DBEAFE;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:32px;color:#3B82F6;">
                <i class="bi bi-cloud-download spin"></i>
            </div>
            <div style="font-family:'Sora',sans-serif;font-size:18px;font-weight:700;color:#111827;margin-bottom:8px;">
                @if($import->status === 'pending') Queued for Scraping
                @elseif($import->status === 'scraping') Scraping Amazon...
                @else Processing...
                @endif
            </div>
            <p style="font-size:14px;color:#6B7280;max-width:300px;margin:0 auto 24px;line-height:1.6;">
                We're extracting product data from Amazon. This usually takes 10–30 seconds.
            </p>
            <div id="scrapeDots" style="font-size:24px;color:#E31837;letter-spacing:6px;">•••</div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// Auto-refresh if still in progress
@if(in_array($import->status, ['pending', 'scraping', 'processing']) || ($latestGeneration && $latestGeneration->status === 'generating'))
let refreshTimer = setInterval(function() {
    window.location.reload();
}, 4000);
@endif

// Loading messages for generation
const messages = [
    'Analyzing product specifications...',
    'Crafting your unique product title...',
    'Writing 5 compelling bullet points...',
    'Generating SEO-optimized description...',
    'Building backend search terms...',
    'Finalizing A+ content suggestions...',
];
let msgIdx = 0;
const msgEl = document.getElementById('loadingMessages');
if (msgEl) {
    msgEl.textContent = messages[0];
    setInterval(() => {
        msgIdx = (msgIdx + 1) % messages.length;
        msgEl.style.opacity = 0;
        setTimeout(() => {
            msgEl.textContent = messages[msgIdx];
            msgEl.style.opacity = 1;
        }, 200);
        msgEl.style.transition = 'opacity 0.2s';
    }, 2500);
}

// Generate button
const genBtn = document.getElementById('generateBtn');
if (genBtn) {
    genBtn.closest('form').addEventListener('submit', function() {
        document.getElementById('genBtnText').style.display = 'none';
        document.getElementById('genBtnLoading').style.display = 'inline-flex';
        genBtn.disabled = true;
    });
}
</script>
@endpush
@endsection
