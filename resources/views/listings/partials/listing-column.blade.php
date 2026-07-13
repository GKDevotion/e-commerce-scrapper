@php
$isGenerated = ($type ?? 'original') === 'generated';
@endphp

<div class="{{ $isGenerated ? 'generated' : 'original' }}">
    <!-- Title -->
    <div class="listing-field" onclick="copyField(this)" title="Click to copy">
        <div class="field-label">
            <i class="bi bi-type" style="color:{{ $isGenerated ? '#E31837' : '#9CA3AF' }};"></i>
            Product Title
            <i class="bi bi-copy ms-auto" style="color:#CBD5E1;font-size:11px;"></i>
        </div>
        <div class="field-value" style="font-weight:{{ $isGenerated ? '600' : '400' }};">
            {{ $title ?? '—' }}
        </div>
    </div>

    <!-- Brand / Manufacturer -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="listing-field" style="margin-bottom:0;" onclick="copyField(this)" title="Click to copy">
                <div class="field-label"><i class="bi bi-tag"></i>Brand</div>
                <div class="field-value">{{ $brand ?? '—' }}</div>
            </div>
        </div>
        <div class="col-6">
            <div class="listing-field" style="margin-bottom:0;" onclick="copyField(this)" title="Click to copy">
                <div class="field-label"><i class="bi bi-building"></i>Manufacturer</div>
                <div class="field-value">{{ $manufacturer ?? '—' }}</div>
            </div>
        </div>
    </div>

    <!-- Bullet Points -->
    <div style="margin-bottom:16px;">
        <div class="field-label" style="padding:0 12px;">
            <i class="bi bi-list-ul" style="color:{{ $isGenerated ? '#E31837' : '#9CA3AF' }};"></i>
            Bullet Points
        </div>
        @if(!empty($bullets))
            @foreach($bullets as $i => $bullet)
            <div class="listing-field" style="display:flex;gap:10px;align-items:flex-start;margin-bottom:6px;" onclick="copyField(this)">
                <span style="min-width:20px;height:20px;border-radius:50%;background:{{ $isGenerated ? '#E31837' : '#9CA3AF' }};color:white;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">{{ $i+1 }}</span>
                <div class="field-value">{{ $bullet }}</div>
            </div>
            @endforeach
        @else
            <div style="padding:10px 12px;color:#9CA3AF;font-size:13px;font-style:italic;">No bullet points available</div>
        @endif
    </div>

    <!-- Description -->
    <div class="listing-field" onclick="copyField(this)" title="Click to copy">
        <div class="field-label">
            <i class="bi bi-text-paragraph" style="color:{{ $isGenerated ? '#E31837' : '#9CA3AF' }};"></i>
            Product Description
            <i class="bi bi-copy ms-auto" style="color:#CBD5E1;font-size:11px;"></i>
        </div>
        <div class="field-value" style="font-size:13px;line-height:1.7;max-height:200px;overflow-y:auto;">
            {!! $description ? nl2br(e(strip_tags($description))) : '<em style="color:#9CA3AF;">No description available</em>' !!}
        </div>
    </div>

    @if($isGenerated)
    <!-- Search Terms -->
    @if($search_terms)
    <div class="listing-field" onclick="copyField(this)" title="Click to copy">
        <div class="field-label">
            <i class="bi bi-search" style="color:#8B5CF6;"></i>
            Backend Search Terms
            <i class="bi bi-copy ms-auto" style="color:#CBD5E1;font-size:11px;"></i>
        </div>
        <div class="field-value" style="font-size:12.5px;color:#6B7280;">{{ $search_terms }}</div>
    </div>
    @endif

    <!-- SEO Keywords -->
    @if($seo_keywords)
    <div class="listing-field" onclick="copyField(this)" title="Click to copy">
        <div class="field-label">
            <i class="bi bi-tags" style="color:#10B981;"></i>
            SEO Keywords
            <i class="bi bi-copy ms-auto" style="color:#CBD5E1;font-size:11px;"></i>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:5px;padding:4px 0;">
            @foreach(array_slice(explode(',', $seo_keywords), 0, 12) as $kw)
            @if(trim($kw))
            <span style="background:#F0FDF4;color:#065F46;border:1px solid #BBF7D0;font-size:11.5px;font-weight:500;padding:3px 10px;border-radius:20px;">{{ trim($kw) }}</span>
            @endif
            @endforeach
        </div>
    </div>
    @endif

    <!-- Highlights -->
    @if($highlights)
    <div class="listing-field" onclick="copyField(this)" title="Click to copy">
        <div class="field-label">
            <i class="bi bi-lightning-charge" style="color:#F59E0B;"></i>
            Product Highlights
            <i class="bi bi-copy ms-auto" style="color:#CBD5E1;font-size:11px;"></i>
        </div>
        <div class="field-value" style="font-size:13px;white-space:pre-line;">{{ $highlights }}</div>
    </div>
    @endif
    @endif
</div>
