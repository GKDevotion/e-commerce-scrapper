@extends('layouts.app')
@section('title', 'Product Images')
@section('page-title', 'Product Images')

@section('topbar-actions')
<a href="{{ route('generations.view', $generation->id) }}" class="topbar-btn" style="background:#374151;">
    <i class="bi bi-arrow-left"></i> Back to Listing
</a>
@endsection

@section('content')
@php
    $import = $generation->productImport;
    $images = $import->original_images ?? [];
    $brandSlug = Str::slug($generation->brand_name ?: 'product');
    $asin = $import->asin ?: 'product';
@endphp

<!-- Header -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 fade-in-up">
    <div>
        <div style="font-size:12.5px;color:#9CA3AF;margin-bottom:4px;">
            <a href="{{ route('listings.index') }}" style="color:#9CA3AF;text-decoration:none;">My Listings</a>
            <span class="mx-1">›</span>
            <a href="{{ route('generations.view', $generation->id) }}" style="color:#9CA3AF;text-decoration:none;">{{ Str::limit($generation->generated_title ?? 'Listing', 40) }}</a>
            <span class="mx-1">›</span>
            <span style="color:#111827;">Images</span>
        </div>
        <h2 style="font-family:'Sora',sans-serif;font-size:18px;font-weight:700;color:#111827;margin:0;">
            <i class="bi bi-images me-2" style="color:#3B82F6;"></i>Product Images
            <span style="font-size:14px;font-weight:400;color:#9CA3AF;margin-left:8px;">{{ count($images) }} image(s) found</span>
        </h2>
    </div>
    <div class="d-flex gap-2">
        @if(!empty($images))
        <a href="{{ route('export.images.zip', $generation->id) }}" class="btn-alb-primary btn">
            <i class="bi bi-file-earmark-zip me-2"></i>Download All as ZIP
        </a>
        @endif
    </div>
</div>

@if(empty($images))
<!-- Empty state -->
<div class="alb-card text-center fade-in-up" style="padding:60px 24px;">
    <i class="bi bi-image" style="font-size:48px;color:#D1D5DB;display:block;margin-bottom:16px;"></i>
    <h3 style="font-family:'Sora',sans-serif;font-size:18px;font-weight:700;margin-bottom:8px;">No Images Found</h3>
    <p style="color:#6B7280;font-size:14px;max-width:360px;margin:0 auto;">
        No images were extracted from this Amazon listing during scraping. This can happen when Amazon serves a partial page or the product has no gallery images.
    </p>
</div>

@else
<!-- Info bar -->
<div class="alb-card mb-4 fade-in-up" style="padding:14px 20px;">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div style="font-size:13px;color:#6B7280;flex:1;">
            <i class="bi bi-info-circle me-1" style="color:#3B82F6;"></i>
            Images are downloaded directly from Amazon's CDN and renamed to
            <code style="background:#F3F4F6;padding:2px 6px;border-radius:4px;font-size:12px;">{{ $brandSlug }}-{{ $asin }}-01.jpg</code>,
            <code style="background:#F3F4F6;padding:2px 6px;border-radius:4px;font-size:12px;">{{ $brandSlug }}-{{ $asin }}-02.jpg</code> etc.
        </div>
        <a href="{{ route('export.images.zip', $generation->id) }}" class="btn-alb-outline btn" style="font-size:12.5px;padding:7px 16px;white-space:nowrap;">
            <i class="bi bi-file-earmark-zip me-1"></i>ZIP ({{ count($images) }} images)
        </a>
    </div>
</div>

<!-- Image grid -->
<div class="row g-3 fade-in-up">
    @foreach($images as $i => $imgUrl)
    @php $num = $i + 1; $filename = "{$brandSlug}-{$asin}-" . str_pad($num, 2, '0', STR_PAD_LEFT); @endphp
    <div class="col-6 col-md-4 col-lg-3">
        <div class="alb-card" style="padding:0;overflow:hidden;">
            <!-- Image preview -->
            <div style="position:relative;background:#F9FAFB;aspect-ratio:1;overflow:hidden;cursor:pointer;" onclick="openLightbox({{ $i }})">
                <img
                    src="{{ $imgUrl }}"
                    alt="Product image {{ $num }}"
                    id="thumb-{{ $i }}"
                    style="width:100%;height:100%;object-fit:contain;padding:8px;transition:transform 0.2s;"
                    onmouseover="this.style.transform='scale(1.04)'"
                    onmouseout="this.style.transform='scale(1)'"
                    onerror="this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;justify-content:center;height:100%;color:#D1D5DB;flex-direction:column;gap:8px;\'><i class=\'bi bi-image-slash\' style=\'font-size:32px;\'></i><span style=\'font-size:11px;color:#9CA3AF;\'>Failed to load</span></div>'"
                >
                <!-- Image number badge -->
                <span style="position:absolute;top:8px;left:8px;background:rgba(0,0,0,0.6);color:white;font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:8px;">
                    {{ $num }}
                </span>
                <!-- Expand hint -->
                <span style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,0.4);color:white;font-size:11px;padding:3px 7px;border-radius:8px;opacity:0;transition:opacity 0.2s;" class="expand-hint">
                    <i class="bi bi-arrows-fullscreen"></i>
                </span>
            </div>

            <!-- Actions row -->
            <div style="padding:10px 12px;display:flex;align-items:center;justify-content:space-between;gap:8px;border-top:1px solid #F3F4F6;">
                <div>
                    <div style="font-size:12px;font-weight:600;color:#374151;">Image {{ $num }}</div>
                    <div style="font-size:10.5px;color:#9CA3AF;">{{ $filename }}</div>
                </div>
                <div class="d-flex gap-1">
                    <!-- Open original in new tab -->
                    <a href="{{ $imgUrl }}" target="_blank" title="Open full size in new tab"
                        style="width:30px;height:30px;border:1.5px solid #E5E7EB;border-radius:7px;display:flex;align-items:center;justify-content:center;color:#6B7280;text-decoration:none;font-size:13px;transition:all 0.15s;"
                        onmouseover="this.style.borderColor='#3B82F6';this.style.color='#3B82F6'"
                        onmouseout="this.style.borderColor='#E5E7EB';this.style.color='#6B7280'">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                    <!-- Download individual -->
                    <a href="{{ route('export.images.single', [$generation->id, $i]) }}"
                        title="Download {{ $filename }}"
                        style="width:30px;height:30px;background:#E31837;border-radius:7px;display:flex;align-items:center;justify-content:center;color:white;text-decoration:none;font-size:13px;transition:background 0.15s;"
                        onmouseover="this.style.background='#b01028'"
                        onmouseout="this.style.background='#E31837'">
                        <i class="bi bi-download"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Download All - sticky bottom bar on mobile -->
<div class="alb-card mt-4 fade-in-up" style="padding:20px 24px;">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <div style="font-size:15px;font-weight:700;color:#111827;">Download all {{ count($images) }} images</div>
            <div style="font-size:13px;color:#6B7280;margin-top:2px;">
                All images packaged into a single ZIP file, renamed with your brand name.
            </div>
        </div>
        <a href="{{ route('export.images.zip', $generation->id) }}" class="btn-alb-primary btn" style="font-size:14px;padding:12px 28px;font-family:'Sora',sans-serif;">
            <i class="bi bi-file-earmark-zip me-2"></i>Download ZIP
        </a>
    </div>
</div>
@endif

{{-- Lightbox --}}
@if(!empty($images))
<div id="lightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.9);z-index:9999;align-items:center;justify-content:center;flex-direction:column;">
    <div style="position:absolute;top:16px;right:16px;display:flex;gap:10px;z-index:2;">
        <a id="lightboxDownload" href="#" class="btn" style="background:#E31837;color:white;border-radius:9px;padding:8px 18px;font-size:13.5px;font-weight:700;text-decoration:none;">
            <i class="bi bi-download me-1"></i>Download
        </a>
        <button onclick="closeLightbox()" style="background:rgba(255,255,255,0.15);border:none;color:white;border-radius:9px;padding:8px 14px;font-size:18px;cursor:pointer;">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <button onclick="changeImage(-1)" style="position:absolute;left:16px;background:rgba(255,255,255,0.15);border:none;color:white;border-radius:50%;width:44px;height:44px;font-size:20px;cursor:pointer;z-index:2;">
        <i class="bi bi-chevron-left"></i>
    </button>
    <img id="lightboxImg" src="" alt="" style="max-width:90vw;max-height:85vh;object-fit:contain;border-radius:8px;">
    <div id="lightboxCaption" style="color:rgba(255,255,255,0.7);font-size:13px;margin-top:12px;text-align:center;"></div>
    <button onclick="changeImage(1)" style="position:absolute;right:16px;background:rgba(255,255,255,0.15);border:none;color:white;border-radius:50%;width:44px;height:44px;font-size:20px;cursor:pointer;z-index:2;">
        <i class="bi bi-chevron-right"></i>
    </button>
</div>
@endif

@push('styles')
<style>
.alb-card:hover .expand-hint { opacity: 1 !important; }
</style>
@endpush

@push('scripts')
<script>
const imageUrls   = @json($images);
const downloadUrls = @json(array_map(fn($i) => route('export.images.single', [$generation->id, $i]), array_keys($images)));
let currentIndex = 0;

function openLightbox(index) {
    currentIndex = index;
    document.getElementById('lightbox').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    updateLightbox();
}

function closeLightbox() {
    document.getElementById('lightbox').style.display = 'none';
    document.body.style.overflow = '';
}

function changeImage(dir) {
    currentIndex = (currentIndex + dir + imageUrls.length) % imageUrls.length;
    updateLightbox();
}

function updateLightbox() {
    document.getElementById('lightboxImg').src = imageUrls[currentIndex];
    document.getElementById('lightboxCaption').textContent = `Image ${currentIndex + 1} of ${imageUrls.length}`;
    document.getElementById('lightboxDownload').href = downloadUrls[currentIndex];
}

// Close on backdrop click
document.getElementById('lightbox')?.addEventListener('click', function(e) {
    if (e.target === this) closeLightbox();
});

// Keyboard nav
document.addEventListener('keydown', function(e) {
    const lb = document.getElementById('lightbox');
    if (!lb || lb.style.display === 'none') return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') changeImage(-1);
    if (e.key === 'ArrowRight') changeImage(1);
});
</script>
@endpush
@endsection
