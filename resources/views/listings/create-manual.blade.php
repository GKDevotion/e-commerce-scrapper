@extends('layouts.app')
@section('title', 'Create Listing Manually')
@section('page-title', 'Manual Listing')

@section('topbar-actions')
<a href="{{ route('listings.show', $import->id) }}" class="topbar-btn" style="background:#374151;">
    <i class="bi bi-arrow-left"></i> Back
</a>
@endsection

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 fade-in-up">
    <div>
        <div style="font-size:12.5px;color:#9CA3AF;margin-bottom:4px;">
            <a href="{{ route('listings.index') }}" style="color:#9CA3AF;text-decoration:none;">My Listings</a>
            <span class="mx-1">›</span>
            <a href="{{ route('listings.show', $import->id) }}" style="color:#9CA3AF;text-decoration:none;">Import #{{ $import->id }}</a>
            <span class="mx-1">›</span>
            <span style="color:#111827;">Create Manually</span>
        </div>
        <h2 style="font-family:'Sora',sans-serif;font-size:18px;font-weight:700;color:#111827;margin:0;">
            <i class="bi bi-pencil-square me-2" style="color:#E31837;"></i>Create Listing Manually
        </h2>
    </div>
    <span style="background:#FEF3C7;color:#92400E;font-size:12px;font-weight:700;padding:5px 14px;border-radius:20px;">
        <i class="bi bi-info-circle me-1"></i>No AI used
    </span>
</div>

<div class="row g-4">
    <!-- Left: Original scraped data for reference -->
    <div class="col-lg-4 fade-in-up">
        <div class="alb-card" style="position:sticky;top:80px;">
            <h3 class="alb-card-title mb-3"><i class="bi bi-box me-2" style="color:#9CA3AF;"></i>Original Scraped Data</h3>
            <div style="font-size:12px;color:#9CA3AF;margin-bottom:14px;">Reference only — copy and edit into the form fields on the right.</div>

            @if($import->primary_image)
            <img src="{{ $import->primary_image }}" alt="" style="width:100%;border-radius:10px;margin-bottom:14px;" onerror="this.style.display='none'">
            @endif

            <div style="display:flex;flex-direction:column;gap:14px;">
                <div>
                    <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;color:#9CA3AF;letter-spacing:0.05em;margin-bottom:4px;">Original Title</div>
                    <div style="font-size:13px;color:#6B7280;line-height:1.5;">{{ $import->original_title }}</div>
                </div>
                @if($import->original_brand)
                <div>
                    <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;color:#9CA3AF;letter-spacing:0.05em;margin-bottom:4px;">Original Brand</div>
                    <div style="font-size:13px;color:#6B7280;">{{ $import->original_brand }}</div>
                </div>
                @endif
                @if($import->original_bullet_points)
                <div>
                    <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;color:#9CA3AF;letter-spacing:0.05em;margin-bottom:6px;">Original Bullets</div>
                    @foreach($import->original_bullet_points as $bullet)
                    <div style="font-size:12px;color:#6B7280;margin-bottom:5px;display:flex;gap:6px;">
                        <span style="color:#D1D5DB;flex-shrink:0;">•</span>{{ Str::limit($bullet, 100) }}
                    </div>
                    @endforeach
                </div>
                @endif
                @if($import->original_description)
                <div>
                    <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;color:#9CA3AF;letter-spacing:0.05em;margin-bottom:4px;">Original Description</div>
                    <div style="font-size:12px;color:#6B7280;line-height:1.6;max-height:140px;overflow-y:auto;">{{ Str::limit(strip_tags($import->original_description), 400) }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right: Editable form, pre-filled with brand swapped -->
    <div class="col-lg-8 fade-in-up fade-in-up-delay-1">
        <div class="alb-card">
            <div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;padding:12px 16px;margin-bottom:24px;font-size:12.5px;color:#92400E;">
                <i class="bi bi-magic me-1"></i>
                Title, bullets, and description below are pre-filled from the scraped data with
                <strong>{{ $import->original_brand ?? 'the original brand' }}</strong> already swapped to
                <strong>{{ $import->target_brand_name }}</strong>. Edit freely — nothing here was AI-generated.
            </div>

            <form data-warn-unsaved method="POST" action="{{ route('generations.manual.store', $import->id) }}" id="manualForm">
                @csrf

                <div class="alb-form-group">
                    <label class="alb-label">Internal Name <span style="color:#9CA3AF;font-weight:400;">(optional, for your reference)</span></label>
                    <input type="text" name="generation_name" class="alb-input" placeholder="e.g. v1 — manual draft" value="{{ old('generation_name') }}">
                </div>

                <div class="row g-3 mb-1">
                    <div class="col-md-6">
                        <label class="alb-label">Brand Name <span style="color:#E31837;">*</span></label>
                        <input type="text" name="brand_name" class="alb-input" value="{{ old('brand_name', $import->target_brand_name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">Manufacturer <span style="color:#E31837;">*</span></label>
                        <input type="text" name="manufacturer" class="alb-input" value="{{ old('manufacturer', $import->target_manufacturer) }}" required>
                    </div>
                </div>

                <div class="alb-form-group">
                    <label class="alb-label">Product Title <span style="color:#E31837;">*</span></label>
                    <input type="text" name="generated_title" class="alb-input" value="{{ old('generated_title', $prefilled['title']) }}" required maxlength="500">
                </div>

                <div class="alb-form-group">
                    <label class="alb-label">Bullet Points <span style="color:#E31837;">*</span></label>
                    <div id="bulletsContainer">
                        @php $oldBullets = old('generated_bullet_points', !empty($prefilled['bullets']) ? $prefilled['bullets'] : ['', '', '', '', '']); @endphp
                        @foreach($oldBullets as $i => $bullet)
                        <div class="d-flex gap-2 mb-2 bullet-row">
                            <span style="width:28px;height:42px;display:flex;align-items:center;justify-content:center;color:#9CA3AF;font-size:12px;font-weight:700;flex-shrink:0;">{{ $i + 1 }}</span>
                            <input type="text" name="generated_bullet_points[]" class="alb-input" value="{{ $bullet }}" placeholder="Bullet point {{ $i + 1 }}" maxlength="500">
                            <button type="button" class="btn btn-sm" onclick="this.closest('.bullet-row').remove()" style="border:1px solid #E5E7EB;color:#EF4444;flex-shrink:0;">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                        @endforeach
                    </div>
                    <button type="button" onclick="addBulletRow()" class="btn-alb-outline btn" style="font-size:12.5px;padding:6px 16px;">
                        <i class="bi bi-plus-lg me-1"></i>Add Bullet Point
                    </button>
                </div>

                <div class="alb-form-group">
                    <label class="alb-label">Product Description <span style="color:#E31837;">*</span></label>
                    <textarea name="generated_description" class="alb-input alb-textarea" style="min-height:160px;" required>{{ old('generated_description', $prefilled['description']) }}</textarea>
                </div>

                <div class="alb-form-group">
                    <label class="alb-label">Backend Search Terms <span style="color:#9CA3AF;font-weight:400;">(optional)</span></label>
                    <textarea name="generated_search_terms" class="alb-input" style="min-height:70px;" placeholder="comma, separated, keywords...">{{ old('generated_search_terms') }}</textarea>
                </div>

                <div class="alb-form-group">
                    <label class="alb-label">SEO Keywords <span style="color:#9CA3AF;font-weight:400;">(optional)</span></label>
                    <textarea name="generated_seo_keywords" class="alb-input" style="min-height:70px;" placeholder="comma, separated, keywords...">{{ old('generated_seo_keywords') }}</textarea>
                </div>

                <div class="alb-form-group">
                    <label class="alb-label">Product Highlights <span style="color:#9CA3AF;font-weight:400;">(optional)</span></label>
                    <textarea name="generated_highlights" class="alb-input alb-textarea">{{ old('generated_highlights') }}</textarea>
                </div>

                <div class="alb-form-group">
                    <label class="alb-label">A+ Content <span style="color:#9CA3AF;font-weight:400;">(optional)</span></label>
                    <textarea name="generated_aplus_content" class="alb-input alb-textarea">{{ old('generated_aplus_content') }}</textarea>
                </div>

                <button type="submit" class="btn-alb-primary btn w-100 py-3" style="font-size:15px;font-family:'Sora',sans-serif;" id="submitBtn">
                    <span id="btnText"><i class="bi bi-check-circle me-2"></i>Save Listing</span>
                    <span id="btnLoading" style="display:none;"><span class="spinner-border spinner-border-sm me-2"></span>Saving...</span>
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let bulletIndex = {{ count($oldBullets) }};
function addBulletRow() {
    bulletIndex++;
    const container = document.getElementById('bulletsContainer');
    const row = document.createElement('div');
    row.className = 'd-flex gap-2 mb-2 bullet-row';
    row.innerHTML = `
        <span style="width:28px;height:42px;display:flex;align-items:center;justify-content:center;color:#9CA3AF;font-size:12px;font-weight:700;flex-shrink:0;">${bulletIndex}</span>
        <input type="text" name="generated_bullet_points[]" class="alb-input" placeholder="Bullet point ${bulletIndex}" maxlength="500">
        <button type="button" class="btn btn-sm" onclick="this.closest('.bullet-row').remove()" style="border:1px solid #E5E7EB;color:#EF4444;flex-shrink:0;">
            <i class="bi bi-x"></i>
        </button>
    `;
    container.appendChild(row);
}

document.getElementById('manualForm').addEventListener('submit', function() {
    document.getElementById('btnText').style.display = 'none';
    document.getElementById('btnLoading').style.display = 'inline-flex';
    document.getElementById('submitBtn').disabled = true;
});
</script>
@endpush
@endsection
