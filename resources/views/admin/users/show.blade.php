@extends('layouts.app')
@section('title', 'User: ' . $user->name)
@section('page-title', 'User Detail')

@section('topbar-actions')
<a href="{{ route('admin.users') }}" class="topbar-btn" style="background:#374151;">
    <i class="bi bi-arrow-left"></i> Back to Users
</a>
@endsection

@section('content')
<div class="row g-4">

    <!-- Left: User Info -->
    <div class="col-lg-4 fade-in-up">
        <div class="alb-card mb-4 text-center" style="padding:32px 24px;">
            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #FEE2E8;margin-bottom:14px;">
            <div style="font-family:'Sora',sans-serif;font-size:20px;font-weight:800;color:#111827;">{{ $user->name }}</div>
            <div style="font-size:13px;color:#9CA3AF;margin-bottom:12px;">{{ $user->email }}</div>
            @if($user->company_name)
            <div style="font-size:13px;color:#6B7280;margin-bottom:12px;">{{ $user->company_name }}</div>
            @endif
            <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-bottom:20px;">
                @if($user->role === 'admin')
                <span style="background:#FEF3C7;color:#92400E;font-size:12px;font-weight:700;padding:4px 12px;border-radius:20px;">Admin</span>
                @endif
                @if($user->status === 'active')
                <span style="background:#D1FAE5;color:#065F46;font-size:12px;font-weight:700;padding:4px 12px;border-radius:20px;">Active</span>
                @elseif($user->status === 'suspended')
                <span style="background:#FEE2E2;color:#991B1B;font-size:12px;font-weight:700;padding:4px 12px;border-radius:20px;">Suspended</span>
                @endif
                <span style="background:#DBEAFE;color:#1E40AF;font-size:12px;font-weight:700;padding:4px 12px;border-radius:20px;">{{ $user->plan?->name ?? 'Free' }}</span>
            </div>
            <!-- Quick actions -->
            <div style="display:flex;flex-direction:column;gap:8px;">
                @if($user->status === 'active' && $user->role !== 'admin')
                <form method="POST" action="{{ route('admin.users.suspend', $user->id) }}">
                    @csrf
                    <button type="submit" class="btn w-100" style="background:#FEF3C7;color:#92400E;border:1px solid #FDE68A;font-size:13px;font-weight:700;padding:10px;border-radius:9px;" onclick="return confirm('Suspend {{ $user->name }}?')">
                        <i class="bi bi-slash-circle me-2"></i>Suspend User
                    </button>
                </form>
                @elseif($user->status === 'suspended')
                <form method="POST" action="{{ route('admin.users.activate', $user->id) }}">
                    @csrf
                    <button type="submit" class="btn w-100" style="background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0;font-size:13px;font-weight:700;padding:10px;border-radius:9px;">
                        <i class="bi bi-check-circle me-2"></i>Activate User
                    </button>
                </form>
                @endif
            </div>
        </div>

        <!-- Stats -->
        <div class="alb-card">
            <h3 class="alb-card-title mb-3"><i class="bi bi-bar-chart me-2" style="color:#E31837;"></i>Usage Stats</h3>
            @php
            $userStats = [
                ['Imports', $user->productImports->count(), 'bi-collection', '#E31837'],
                ['AI Generations', $user->aiGenerations->count(), 'bi-cpu', '#8B5CF6'],
                ['Completed', $user->aiGenerations->where('status','completed')->count(), 'bi-check-circle', '#10B981'],
                ['Exports', $user->exports->count(), 'bi-download', '#3B82F6'],
                ['Listings Used', $user->listings_used, 'bi-list-check', '#F59E0B'],
                ['AI Gens Used', $user->ai_generations_used, 'bi-lightning', '#EC4899'],
            ];
            @endphp
            <div style="display:flex;flex-direction:column;gap:10px;">
                @foreach($userStats as [$label, $value, $icon, $color])
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;background:#F9FAFB;border-radius:8px;">
                    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:#6B7280;">
                        <i class="bi {{ $icon }}" style="color:{{ $color }};"></i>{{ $label }}
                    </div>
                    <strong style="font-size:14px;color:#111827;">{{ $value }}</strong>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Right: Edit form + Activity -->
    <div class="col-lg-8 fade-in-up fade-in-up-delay-1">
        <!-- Edit form -->
        <div class="alb-card mb-4">
            <h3 class="alb-card-title mb-4"><i class="bi bi-pencil me-2" style="color:#E31837;"></i>Edit User</h3>
            <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="alb-label">Full Name</label>
                        <input type="text" name="name" class="alb-input" value="{{ $user->name }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">Email</label>
                        <input type="email" name="email" class="alb-input" value="{{ $user->email }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="alb-label">Status</label>
                        <select name="status" class="alb-input">
                            @foreach(['active','suspended','pending'] as $s)
                            <option value="{{ $s }}" {{ $user->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="alb-label">Role</label>
                        <select name="role" class="alb-input">
                            <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>User</option>
                            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="alb-label">Plan</label>
                        <select name="plan_id" class="alb-input">
                            <option value="">No Plan</option>
                            @foreach(\App\Models\Plan::where('is_active',true)->get() as $p)
                            <option value="{{ $p->id }}" {{ $user->plan_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="alb-label">Admin Notes</label>
                        <textarea name="notes" class="alb-input alb-textarea" style="min-height:70px;" placeholder="Internal notes about this user...">{{ $user->notes }}</textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn-alb-primary btn">
                            <i class="bi bi-save me-2"></i>Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Recent Listings -->
        <div class="alb-card">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-collection me-2" style="color:#E31837;"></i>Recent Imports</h3>
                <span style="font-size:12.5px;color:#9CA3AF;">{{ $user->productImports->count() }} total</span>
            </div>
            @if($user->productImports->isEmpty())
            <div style="text-align:center;padding:24px;color:#9CA3AF;font-size:13px;">No imports yet.</div>
            @else
            <table class="alb-table">
                <thead><tr><th>Product</th><th>Status</th><th>Brand</th><th>Date</th></tr></thead>
                <tbody>
                    @foreach($user->productImports->take(8) as $imp)
                    <tr>
                        <td>
                            <div style="font-size:12.5px;font-weight:600;color:#111827;max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                {{ $imp->original_title ?? 'Importing...' }}
                            </div>
                            @if($imp->asin)
                            <div style="font-size:11px;color:#9CA3AF;font-family:monospace;">{{ $imp->asin }}</div>
                            @endif
                        </td>
                        <td>{!! $imp->status_badge !!}</td>
                        <td style="font-size:12.5px;color:#6B7280;">{{ $imp->target_brand_name }}</td>
                        <td style="font-size:12px;color:#9CA3AF;">{{ $imp->created_at->format('M d, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</div>
@endsection
