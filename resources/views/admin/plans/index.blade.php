@extends('layouts.app')
@section('title', 'Manage Plans')
@section('page-title', 'Subscription Plans')

@section('topbar-actions')
<a href="{{ route('admin.plans.create') }}" class="topbar-btn">
    <i class="bi bi-plus-lg"></i> New Plan
</a>
@endsection

@section('content')
<div class="row g-4">
    @foreach($plans as $plan)
    <div class="col-md-6 col-lg-3 fade-in-up">
        <div class="alb-card h-100" style="border:{{ $plan->is_featured ? '2px solid #E31837' : '1.5px solid #E5E7EB' }};">
            @if($plan->is_featured)
            <div style="background:#E31837;color:white;text-align:center;font-size:10.5px;font-weight:800;padding:5px;border-radius:8px 8px 0 0;margin:-24px -24px 16px;">FEATURED</div>
            @endif
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
                <div>
                    <div style="font-family:'Sora',sans-serif;font-size:17px;font-weight:800;color:#111827;">{{ $plan->name }}</div>
                    <div style="font-size:12px;color:#9CA3AF;">{{ $plan->slug }}</div>
                </div>
                <div style="display:flex;gap:6px;">
                    @if($plan->is_active)
                    <span style="background:#D1FAE5;color:#065F46;font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:20px;">Active</span>
                    @else
                    <span style="background:#F3F4F6;color:#9CA3AF;font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:20px;">Inactive</span>
                    @endif
                </div>
            </div>

            <div style="font-family:'Sora',sans-serif;font-size:28px;font-weight:900;color:#111827;margin-bottom:16px;">
                @if($plan->price_monthly == 0) Free
                @else ${{ number_format($plan->price_monthly, 0) }}<span style="font-size:14px;font-weight:400;color:#9CA3AF;">/mo</span>
                @endif
            </div>

            <div style="display:flex;flex-direction:column;gap:7px;margin-bottom:20px;">
                @php
                $planFeatureList = [
                    $plan->listings_limit_display . ' listings/month',
                    $plan->amazon_publish ? 'SP-API publish ✓' : 'SP-API publish ✗',
                    $plan->bulk_import ? 'Bulk import ✓' : null,
                    $plan->team_accounts ? 'Team accounts ✓' : null,
                    $plan->api_access ? 'API access ✓' : null,
                ];
                @endphp
                @foreach(array_filter($planFeatureList) as $f)
                <div style="font-size:12.5px;color:{{ str_ends_with($f, '✓') ? '#374151' : '#CBD5E1' }};display:flex;gap:6px;">
                    <i class="bi {{ str_ends_with($f, '✓') ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }}" style="font-size:13px;margin-top:1px;flex-shrink:0;"></i>
                    {{ str_replace([' ✓', ' ✗'], '', $f) }}
                </div>
                @endforeach
            </div>

            <div style="border-top:1px solid #F3F4F6;padding-top:14px;display:flex;flex-direction:column;gap:8px;">
                <div style="font-size:12px;color:#9CA3AF;display:flex;justify-content:space-between;">
                    <span>Users on this plan:</span>
                    <strong style="color:#374151;">{{ $plan->users->count() }}</strong>
                </div>
                <a href="{{ route('admin.plans.edit', $plan->id) }}" class="btn-alb-primary btn text-center" style="font-size:13px;padding:9px;">
                    <i class="bi bi-pencil me-1"></i>Edit Plan
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
