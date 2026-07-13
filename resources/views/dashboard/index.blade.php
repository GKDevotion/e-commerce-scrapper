@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('topbar-actions')
<a href="{{ route('listings.create') }}" class="topbar-btn">
    <i class="bi bi-plus-lg"></i> New Listing
</a>
@endsection

@section('content')
@php $user = auth()->user(); @endphp

<!-- Welcome Banner -->
<div class="alb-card fade-in-up mb-4" style="background:linear-gradient(135deg,#E31837 0%,#b01028 100%);border:none;color:white;">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h2 style="font-family:'Sora',sans-serif;font-size:22px;font-weight:800;margin:0 0 6px;">
                Welcome back, {{ $user->name }}! 👋
            </h2>
            <p style="opacity:0.85;margin:0;font-size:14px;">
                @if($stats['completed_listings'] > 0)
                    You've generated {{ $stats['completed_listings'] }} listing{{ $stats['completed_listings'] != 1 ? 's' : '' }} so far. Keep going!
                @else
                    Ready to build your first AI-powered Amazon listing?
                @endif
            </p>
        </div>
        <a href="{{ route('listings.create') }}" style="background:rgba(255,255,255,0.2);color:white;border:1.5px solid rgba(255,255,255,0.4);padding:10px 24px;border-radius:10px;text-decoration:none;font-weight:700;font-size:14px;white-space:nowrap;transition:all 0.15s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
            <i class="bi bi-plus-lg me-2"></i>Create Listing
        </a>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3 fade-in-up fade-in-up-delay-1">
        <div class="alb-stat">
            <div class="alb-stat-icon red"><i class="bi bi-collection"></i></div>
            <div>
                <div class="alb-stat-value">{{ $stats['total_imports'] }}</div>
                <div class="alb-stat-label">Total Imports</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 fade-in-up fade-in-up-delay-2">
        <div class="alb-stat">
            <div class="alb-stat-icon green"><i class="bi bi-cpu"></i></div>
            <div>
                <div class="alb-stat-value">{{ $stats['completed_listings'] }}</div>
                <div class="alb-stat-label">AI Generated</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 fade-in-up fade-in-up-delay-3">
        <div class="alb-stat">
            <div class="alb-stat-icon blue"><i class="bi bi-download"></i></div>
            <div>
                <div class="alb-stat-value">{{ $stats['total_exports'] }}</div>
                <div class="alb-stat-label">Exports</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 fade-in-up fade-in-up-delay-4">
        <div class="alb-stat">
            <div class="alb-stat-icon orange"><i class="bi bi-lightning-charge"></i></div>
            <div>
                <div class="alb-stat-value" style="font-size:20px;">
                    @if($stats['listings_remaining'] === 'Unlimited')
                        <span style="font-size:15px;">∞</span>
                    @else
                        {{ $stats['listings_remaining'] }}
                    @endif
                </div>
                <div class="alb-stat-label">Listings Left</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Quick Start -->
    @if($stats['total_imports'] === 0)
    <div class="col-12 fade-in-up">
        <div class="alb-card text-center" style="padding:48px 24px;">
            <div style="width:80px;height:80px;background:#FEE2E8;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:36px;color:#E31837;">
                <i class="bi bi-robot"></i>
            </div>
            <h3 style="font-family:'Sora',sans-serif;font-size:20px;font-weight:700;margin-bottom:10px;">Create Your First Listing</h3>
            <p style="color:#6B7280;font-size:14px;max-width:400px;margin:0 auto 24px;line-height:1.6;">
                Paste an Amazon product URL, enter your brand name, and let our AI generate a unique, optimized listing in seconds.
            </p>
            <a href="{{ route('listings.create') }}" class="btn-alb-primary btn">
                <i class="bi bi-arrow-right-circle me-2"></i>Get Started
            </a>
        </div>
    </div>
    @else

    <!-- Recent Generations -->
    <div class="col-lg-8 fade-in-up">
        <div class="alb-card">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-clock-history me-2" style="color:#E31837;"></i>Recent Generations</h3>
                <a href="{{ route('listings.index') }}" style="font-size:13px;color:#E31837;text-decoration:none;font-weight:600;">View all →</a>
            </div>
            @if($recentGenerations->isEmpty())
                <div style="text-align:center;padding:32px;color:#9CA3AF;">
                    <i class="bi bi-inbox" style="font-size:32px;display:block;margin-bottom:10px;"></i>
                    No generations yet. <a href="{{ route('listings.create') }}" style="color:#E31837;">Create one →</a>
                </div>
            @else
            <div style="display:flex;flex-direction:column;gap:2px;">
                @foreach($recentGenerations as $gen)
                <a href="{{ route('generations.view', $gen->id) }}" style="display:flex;align-items:center;gap:14px;padding:12px;border-radius:10px;text-decoration:none;color:inherit;transition:background 0.15s;" onmouseover="this.style.background='#F9FAFB'" onmouseout="this.style.background='transparent'">
                    <!-- Thumbnail -->
                    <div style="width:50px;height:50px;border-radius:8px;overflow:hidden;flex-shrink:0;background:#F3F4F6;display:flex;align-items:center;justify-content:center;">
                        @if($gen->productImport?->primary_image)
                            <img src="{{ $gen->productImport->primary_image }}" alt="" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <i class="bi bi-box" style="display:none;color:#9CA3AF;font-size:20px;"></i>
                        @else
                            <i class="bi bi-box" style="color:#9CA3AF;font-size:20px;"></i>
                        @endif
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13.5px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $gen->generated_title ?? $gen->productImport?->original_title ?? 'Untitled' }}
                        </div>
                        <div style="font-size:12px;color:#9CA3AF;margin-top:3px;">
                            {{ $gen->brand_name }} • {{ $gen->created_at->diffForHumans() }}
                        </div>
                    </div>
                    @if($gen->status === 'completed')
                        <span style="background:#D1FAE5;color:#065F46;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;white-space:nowrap;">Done</span>
                    @elseif($gen->status === 'generating')
                        <span style="background:#DBEAFE;color:#1E40AF;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;white-space:nowrap;">
                            <span class="spinner-border spinner-border-sm me-1" style="width:10px;height:10px;border-width:2px;"></span>AI Working
                        </span>
                    @elseif($gen->status === 'failed')
                        <span style="background:#FEE2E2;color:#991B1B;font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;white-space:nowrap;">Failed</span>
                    @endif
                </a>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4 fade-in-up">
        <!-- Usage Card -->
        <div class="alb-card mb-4">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-pie-chart me-2" style="color:#E31837;"></i>Usage</h3>
                <span style="font-size:12px;color:#9CA3AF;">{{ $user->plan?->name ?? 'Free' }} Plan</span>
            </div>
            <div>
                <div style="display:flex;justify-content:space-between;font-size:12.5px;color:#6B7280;margin-bottom:6px;">
                    <span>Listings Generated</span>
                    <strong style="color:#111827;">{{ $user->listings_used }} / {{ $user->plan?->listings_limit_display ?? '5' }}</strong>
                </div>
                <div class="usage-bar-track mb-3">
                    <div class="usage-bar-fill" style="width:{{ $stats['usage_percentage'] }}%;background:{{ $stats['usage_percentage'] >= 80 ? '#EF4444' : '#E31837' }};"></div>
                </div>
                @if($stats['usage_percentage'] >= 80)
                <div style="background:#FEE2E2;border:1px solid #FCA5A5;border-radius:8px;padding:10px 14px;font-size:12.5px;color:#991B1B;margin-bottom:12px;">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    You're running low on listings. Upgrade to continue.
                </div>
                @endif
                <a href="{{ route('billing.plans') }}" style="display:block;text-align:center;background:#FEE2E8;color:#E31837;border-radius:9px;padding:10px;font-size:13px;font-weight:700;text-decoration:none;transition:background 0.15s;" onmouseover="this.style.background='#FECDD3'" onmouseout="this.style.background='#FEE2E8'">
                    <i class="bi bi-arrow-up-circle me-1"></i>Upgrade Plan
                </a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="alb-card">
            <h3 class="alb-card-title mb-3"><i class="bi bi-grid me-2" style="color:#E31837;"></i>Quick Actions</h3>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <a href="{{ route('listings.create') }}" style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:#F9FAFB;border-radius:10px;text-decoration:none;color:#374151;font-size:13.5px;font-weight:500;transition:all 0.15s;border:1px solid #F3F4F6;" onmouseover="this.style.background='#FEE2E8';this.style.color='#E31837'" onmouseout="this.style.background='#F9FAFB';this.style.color='#374151'">
                    <i class="bi bi-plus-circle" style="font-size:18px;color:#E31837;"></i>
                    New Amazon Import
                </a>
                <a href="{{ route('listings.index') }}" style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:#F9FAFB;border-radius:10px;text-decoration:none;color:#374151;font-size:13.5px;font-weight:500;transition:all 0.15s;border:1px solid #F3F4F6;" onmouseover="this.style.background='#F0F9FF';this.style.color='#3B82F6'" onmouseout="this.style.background='#F9FAFB';this.style.color='#374151'">
                    <i class="bi bi-collection" style="font-size:18px;color:#3B82F6;"></i>
                    Browse My Listings
                </a>
                <a href="{{ route('billing.plans') }}" style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:#F9FAFB;border-radius:10px;text-decoration:none;color:#374151;font-size:13.5px;font-weight:500;transition:all 0.15s;border:1px solid #F3F4F6;" onmouseover="this.style.background='#F5F3FF';this.style.color='#8B5CF6'" onmouseout="this.style.background='#F9FAFB';this.style.color='#374151'">
                    <i class="bi bi-stars" style="font-size:18px;color:#8B5CF6;"></i>
                    View All Plans
                </a>
                <a href="{{ route('profile.index') }}" style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:#F9FAFB;border-radius:10px;text-decoration:none;color:#374151;font-size:13.5px;font-weight:500;transition:all 0.15s;border:1px solid #F3F4F6;" onmouseover="this.style.background='#ECFDF5';this.style.color='#10B981'" onmouseout="this.style.background='#F9FAFB';this.style.color='#374151'">
                    <i class="bi bi-person-gear" style="font-size:18px;color:#10B981;"></i>
                    Account Settings
                </a>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
