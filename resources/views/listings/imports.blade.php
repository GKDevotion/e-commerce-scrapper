@extends('layouts.app')
@section('title', 'My Listings')
@section('page-title', 'My Listings')

@section('topbar-actions')
@php $user = auth()->user(); @endphp
@if($user->canGenerateListing())
<a href="{{ route('listings.create') }}" class="topbar-btn">
    <i class="bi bi-plus-lg"></i> New Listing
</a>
@else
<a href="{{ route('listings.create') }}" class="topbar-btn" style="background:#6B7280;" title="Limit reached — delete a listing first">
    <i class="bi bi-lock me-1"></i> Limit Reached
</a>
@endif
@endsection

@section('content')
@php
$user = auth()->user();
$limit = $user->plan?->listings_limit ?? 5;
$used  = $user->listings_used;
$pct   = $limit === -1 ? 0 : min(100, $used > 0 ? round(($used / $limit) * 100) : 0);
$limitReached = !$user->canGenerateListing();
@endphp

{{-- Usage bar --}}
<div class="alb-card mb-4 fade-in-up" style="padding:16px 20px;">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div style="flex:1;min-width:200px;">
            <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">
                <span>
                    <i class="bi bi-collection me-1" style="color:#E31837;"></i>
                    Listing Slots Used
                </span>
                <span style="color:{{ $limitReached ? '#EF4444' : '#374151' }};">
                    {{ $used }} / {{ $limit === -1 ? 'Unlimited' : $limit }}
                    @if($limitReached)
                    <span style="background:#FEE2E2;color:#991B1B;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:6px;">Full</span>
                    @elseif($pct >= 80)
                    <span style="background:#FEF3C7;color:#92400E;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:6px;">Almost Full</span>
                    @endif
                </span>
            </div>
            <div style="height:8px;background:#F3F4F6;border-radius:99px;overflow:hidden;">
                <div style="height:100%;width:{{ $pct }}%;border-radius:99px;transition:width 0.5s;background:{{ $limitReached ? '#EF4444' : ($pct >= 80 ? '#F59E0B' : '#E31837') }};"></div>
            </div>
        </div>
        @if($limitReached)
        <a href="{{ route('billing.plans') }}" style="background:#E31837;color:white;text-decoration:none;font-size:13px;font-weight:700;padding:9px 20px;border-radius:9px;white-space:nowrap;display:flex;align-items:center;gap:6px;">
            <i class="bi bi-arrow-up-circle"></i>Upgrade Plan
        </a>
        @elseif($limit !== -1)
        <span style="font-size:12px;color:#9CA3AF;white-space:nowrap;">
            {{ $limit - $used }} slot{{ ($limit - $used) != 1 ? 's' : '' }} remaining
        </span>
        @endif
    </div>
</div>

{{-- Limit reached info banner --}}
@if($limitReached)
<div style="background:#FEF3C7;border:1.5px solid #FDE68A;border-radius:12px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:flex-start;gap:14px;" class="fade-in-up">
    <i class="bi bi-exclamation-triangle-fill" style="color:#F59E0B;font-size:20px;flex-shrink:0;margin-top:1px;"></i>
    <div>
        <div style="font-size:14px;font-weight:700;color:#92400E;margin-bottom:4px;">
            You've reached your {{ $user->plan?->name ?? 'Free' }} plan limit of {{ $limit }} listing{{ $limit != 1 ? 's' : '' }}.
        </div>
        <div style="font-size:13px;color:#92400E;line-height:1.6;">
            <strong>To add a new listing:</strong> delete one below to free up a slot, or
            <a href="{{ route('billing.plans') }}" style="color:#E31837;font-weight:700;text-decoration:underline;">upgrade your plan</a>
            for more slots. Deleting a listing immediately resets your count.
        </div>
    </div>
</div>
@endif

{{-- Empty state --}}
@if($imports->isEmpty())
<div class="alb-card text-center fade-in-up" style="padding:64px 24px;">
    <div style="width:80px;height:80px;background:#FEE2E8;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:36px;color:#E31837;">
        <i class="bi bi-collection"></i>
    </div>
    <h3 style="font-family:'Sora',sans-serif;font-size:20px;font-weight:700;margin-bottom:10px;">No Listings Yet</h3>
    <p style="color:#6B7280;font-size:14px;max-width:380px;margin:0 auto 24px;line-height:1.6;">
        Start by importing an Amazon product URL. Our AI will generate a unique listing for your brand in seconds.
    </p>
    @if(!$limitReached)
    <a href="{{ route('listings.create') }}" class="btn-alb-primary btn">
        <i class="bi bi-plus-circle me-2"></i>Import First Product
    </a>
    @endif
</div>

@else

{{-- Filter / Search row --}}
<div class="alb-card mb-4 fade-in-up" style="padding:14px 20px;">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div style="position:relative;flex:1;min-width:200px;">
            <i class="bi bi-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:14px;"></i>
            <input type="text" id="searchInput" placeholder="Search listings..." class="alb-input" style="padding-left:36px;padding-top:8px;padding-bottom:8px;" oninput="filterListings()">
        </div>
        <select id="statusFilter" class="alb-input" style="width:auto;padding:8px 14px;" onchange="filterListings()">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="scraping">Scraping</option>
            <option value="scraped">Scraped</option>
            <option value="completed">Completed</option>
            <option value="failed">Failed</option>
        </select>
        <span style="font-size:13px;color:#9CA3AF;white-space:nowrap;">{{ $imports->total() }} total</span>
    </div>
</div>

{{-- Grid --}}
<div class="row g-3" id="listingsGrid">
    @foreach($imports as $import)
    @php $gen = $import->latestGeneration; @endphp
    <div class="col-md-6 col-xl-4 fade-in-up listing-card" data-status="{{ $import->status }}" data-title="{{ strtolower($import->original_title ?? '') }}">
        <div class="alb-card h-100" style="padding:0;overflow:hidden;">
            {{-- Thumbnail --}}
            <div style="position:relative;height:140px;background:#F3F4F6;overflow:hidden;">
                @if($import->primary_image)
                    <img src="{{ $import->primary_image }}" alt="" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none'">
                @endif
                <div style="position:absolute;inset:0;background:linear-gradient(to bottom,transparent 40%,rgba(0,0,0,0.6));"></div>
                {{-- Status badge --}}
                <div style="position:absolute;top:10px;right:10px;">
                    @if($import->status === 'completed' && $gen?->status === 'completed')
                        <span style="background:#10B981;color:white;font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;">✓ Ready</span>
                    @elseif(in_array($import->status, ['pending','scraping']))
                        <span style="background:#3B82F6;color:white;font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;">
                            <span class="spinner-border spinner-border-sm" style="width:9px;height:9px;border-width:2px;"></span> Importing
                        </span>
                    @elseif($import->status === 'scraped')
                        <span style="background:#F59E0B;color:white;font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;">Ready</span>
                    @elseif($import->status === 'failed')
                        <span style="background:#EF4444;color:white;font-size:11px;font-weight:700;padding:4px 10px;border-radius:20px;">✗ Failed</span>
                    @endif
                </div>
                {{-- ASIN --}}
                @if($import->asin)
                <div style="position:absolute;bottom:8px;left:12px;">
                    <span style="background:rgba(0,0,0,0.6);color:rgba(255,255,255,0.8);font-size:10.5px;font-family:monospace;padding:3px 8px;border-radius:4px;">{{ $import->asin }}</span>
                </div>
                @endif
            </div>

            {{-- Content --}}
            <div style="padding:16px;">
                <div style="font-size:13.5px;font-weight:600;color:#111827;line-height:1.4;margin-bottom:8px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                    @if($gen?->generated_title)
                        <span style="color:#E31837;">✦</span> {{ $gen->generated_title }}
                    @else
                        {{ $import->original_title ?? 'Importing...' }}
                    @endif
                </div>
                <div style="font-size:12px;color:#9CA3AF;margin-bottom:12px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    @if($import->target_brand_name)
                    <span><i class="bi bi-tag me-1"></i>{{ $import->target_brand_name }}</span>
                    @endif
                    <span><i class="bi bi-clock me-1"></i>{{ $import->created_at->diffForHumans() }}</span>
                </div>

                {{-- Action buttons --}}
                <div class="d-flex gap-2">
                    @if($gen?->status === 'completed')
                    <a href="{{ route('generations.view', $gen->id) }}" class="btn btn-sm" style="flex:1;background:#E31837;color:white;font-size:12px;font-weight:700;border-radius:8px;padding:8px;text-align:center;text-decoration:none;border:none;">
                        <i class="bi bi-eye me-1"></i>View
                    </a>
                    @elseif($import->status === 'scraped')
                    <a href="{{ route('listings.show', $import->id) }}" class="btn btn-sm" style="flex:1;background:#E31837;color:white;font-size:12px;font-weight:700;border-radius:8px;padding:8px;text-align:center;text-decoration:none;border:none;">
                        <i class="bi bi-stars me-1"></i>Create Listing
                    </a>
                    @else
                    <a href="{{ route('listings.show', $import->id) }}" class="btn btn-sm" style="flex:1;background:#3B82F6;color:white;font-size:12px;font-weight:700;border-radius:8px;padding:8px;text-align:center;text-decoration:none;border:none;">
                        <i class="bi bi-hourglass-split me-1"></i>Processing
                    </a>
                    @endif

                    <a href="{{ route('listings.show', $import->id) }}" class="btn btn-sm btn-alb-outline" style="padding:8px 12px;font-size:12px;">
                        <i class="bi bi-arrow-right"></i>
                    </a>

                    <form method="POST" action="{{ route('listings.destroy', $import->id) }}"
                        onsubmit="return confirm('Delete this listing? {{ $limitReached ? 'This will free up 1 slot.' : '' }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm" style="border:1.5px solid {{ $limitReached ? '#E31837' : '#E5E7EB' }};background:{{ $limitReached ? '#FEE2E2' : 'white' }};color:#EF4444;padding:8px 12px;font-size:12px;border-radius:8px;"
                            title="{{ $limitReached ? 'Delete to free up a slot' : 'Delete listing' }}">
                            <i class="bi bi-trash"></i>
                            @if($limitReached)<span style="font-size:10px;margin-left:2px;">Free slot</span>@endif
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Pagination --}}
@if($imports->hasPages())
<div class="d-flex justify-content-center mt-4">
    {{ $imports->links('pagination::bootstrap-5') }}
</div>
@endif

@endif

@push('scripts')
<script>
function filterListings() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    document.querySelectorAll('.listing-card').forEach(card => {
        const title      = card.dataset.title || '';
        const cardStatus = card.dataset.status || '';
        const matchSearch = !search || title.includes(search);
        const matchStatus = !status || cardStatus === status;
        card.style.display = (matchSearch && matchStatus) ? '' : 'none';
    });
}
</script>
@endpush
@endsection
