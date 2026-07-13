@extends('layouts.app')
@section('title', 'Edit Listing')
@section('page-title', 'Edit Listing')

@section('topbar-actions')
<a href="{{ route('generations.view', $generation->id) }}" class="topbar-btn" style="background:#374151;">
    <i class="bi bi-arrow-left"></i> Back to Listing
</a>
@endsection

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 fade-in-up">
    <div>
        <div style="font-size:12.5px;color:#9CA3AF;margin-bottom:4px;">
            <a href="{{ route('listings.index') }}" style="color:#9CA3AF;text-decoration:none;">My Listings</a>
            <span class="mx-1">›</span>
            <a href="{{ route('generations.view', $generation->id) }}" style="color:#9CA3AF;text-decoration:none;">{{ $generation->generation_name_display }}</a>
            <span class="mx-1">›</span>
            <span style="color:#111827;">Edit</span>
        </div>
        <h2 style="font-family:'Sora',sans-serif;font-size:18px;font-weight:700;color:#111827;margin:0;">
            <i class="bi bi-pencil-square me-2" style="color:#E31837;"></i>Edit Listing
        </h2>
    </div>
    <span style="background:{{ $generation->isAi() ? '#FEE2E8' : '#FEF3C7' }};color:{{ $generation->isAi() ? '#E31837' : '#92400E' }};font-size:12px;font-weight:700;padding:5px 14px;border-radius:20px;">
        @if($generation->isAi())
        <i class="bi bi-stars me-1"></i>Originally AI-generated
        @else
        <i class="bi bi-pencil me-1"></i>Manually created
        @endif
    </span>
</div>

<div class="row justify-content-center">
    <div class="col-lg-9 fade-in-up">
        <div class="alb-card">
            <form data-warn-unsaved method="POST" action="{{ route('generations.update', $generation->id) }}" id="editForm">
                @csrf @method('PUT')

                <div class="alb-form-group">
                    <label class="alb-label">Internal Name <span style="color:#9CA3AF;font-weight:400;">(optional)</span></label>
                    <input type="text" name="generation_name" class="alb-input" value="{{ old('generation_name', $generation->generation_name) }}">
                </div>

                <div class="row g-3 mb-1">
                    <div class="col-md-6">
                        <label class="alb-label">Brand Name <span style="color:#E31837;">*</span></label>
                        <input type="text" name="brand_name" class="alb-input" value="{{ old('brand_name', $generation->brand_name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">Manufacturer <span style="color:#E31837;">*</span></label>
                        <input type="text" name="manufacturer" class="alb-input" value="{{ old('manufacturer', $generation->manufacturer) }}" required>
                    </div>
                </div>

                <div class="alb-form-group">
                    <label class="alb-label">Product Title <span style="color:#E31837;">*</span></label>
                    <input type="text" name="generated_title" class="alb-input" value="{{ old('generated_title', $generation->generated_title) }}" required maxlength="500">
                </div>

                <div class="alb-form-group">
                    <label class="alb-label">Bullet Points <span style="color:#E31837;">*</span></label>
                    <div id="bulletsContainer">
                        @php $editBullets = old('generated_bullet_points', $generation->generated_bullet_points ?: ['']); @endphp
                        @foreach($editBullets as $i => $bullet)
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
                    <textarea name="generated_description" class="alb-input alb-textarea" style="min-height:160px;" required>{{ old('generated_description', $generation->generated_description) }}</textarea>
                </div>

                <div class="alb-form-group">
                    <label class="alb-label">Backend Search Terms</label>
                    <textarea name="generated_search_terms" class="alb-input" style="min-height:70px;">{{ old('generated_search_terms', $generation->generated_search_terms) }}</textarea>
                </div>

                <div class="alb-form-group">
                    <label class="alb-label">SEO Keywords</label>
                    <textarea name="generated_seo_keywords" class="alb-input" style="min-height:70px;">{{ old('generated_seo_keywords', $generation->generated_seo_keywords) }}</textarea>
                </div>

                <div class="alb-form-group">
                    <label class="alb-label">Product Highlights</label>
                    <textarea name="generated_highlights" class="alb-input alb-textarea">{{ old('generated_highlights', $generation->generated_highlights) }}</textarea>
                </div>

                <div class="alb-form-group">
                    <label class="alb-label">A+ Content</label>
                    <textarea name="generated_aplus_content" class="alb-input alb-textarea">{{ old('generated_aplus_content', $generation->generated_aplus_content) }}</textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn-alb-primary btn" style="font-size:15px;padding:13px 32px;font-family:'Sora',sans-serif;" id="submitBtn">
                        <span id="btnText"><i class="bi bi-check-circle me-2"></i>Save Changes</span>
                        <span id="btnLoading" style="display:none;"><span class="spinner-border spinner-border-sm me-2"></span>Saving...</span>
                    </button>
                    <a href="{{ route('generations.view', $generation->id) }}" class="btn-alb-outline btn" style="font-size:15px;padding:13px 32px;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let bulletIndex = {{ count($editBullets) }};
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

document.getElementById('editForm').addEventListener('submit', function() {
    document.getElementById('btnText').style.display = 'none';
    document.getElementById('btnLoading').style.display = 'inline-flex';
    document.getElementById('submitBtn').disabled = true;
});
</script>
@endpush
@endsection
