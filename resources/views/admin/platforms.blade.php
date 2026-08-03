@extends('layouts.app')
@section('title','Platform Settings')
@section('page-title','Platform Settings')
@section('content')
<div class="row g-4 fade-in-up">
    @foreach($platforms as $platform)
    <div class="col-md-4">
        <div class="alb-card" style="border-top:4px solid {{ $platform->color }};padding:24px;">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:48px;height:48px;background:{{ $platform->color }}22;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;color:{{ $platform->color }};">
                        <i class="{{ $platform->icon }}"></i>
                    </div>
                    <div>
                        <div style="font-family:'Sora',sans-serif;font-size:16px;font-weight:700;color:#111827;">{{ $platform->label }}</div>
                        <div style="font-size:12px;color:#9CA3AF;">{{ $platform->key }}</div>
                    </div>
                </div>
                <span style="background:{{ $platform->is_enabled ? '#D1FAE5' : '#F3F4F6' }};color:{{ $platform->is_enabled ? '#065F46' : '#9CA3AF' }};font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;">
                    {{ $platform->is_enabled ? 'Enabled' : 'Disabled' }}
                </span>
            </div>
            <form method="POST" action="{{ route('admin.platforms.update', $platform->id) }}">
                @csrf
                <div class="d-flex gap-2">
                    @if($platform->is_enabled)
                    <button type="submit" name="is_enabled" value="0" class="btn w-100"
                        style="border:1.5px solid #EF4444;color:#EF4444;background:white;border-radius:9px;padding:10px;font-size:13px;font-weight:700;"
                        onclick="return confirm('Disable {{ $platform->label }}? Users will not see this platform option.')">
                        <i class="bi bi-toggle-off me-1"></i>Disable
                    </button>
                    @else
                    <button type="submit" name="is_enabled" value="1" class="btn w-100"
                        style="background:#10B981;color:white;border:none;border-radius:9px;padding:10px;font-size:13px;font-weight:700;">
                        <i class="bi bi-toggle-on me-1"></i>Enable
                    </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
    @endforeach
</div>
<div class="alb-card mt-4 fade-in-up" style="background:#FEF3C7;border:1px solid #FDE68A;padding:16px 20px;">
    <div style="font-size:13.5px;color:#92400E;">
        <i class="bi bi-info-circle me-1"></i>
        <strong>Note:</strong> Disabling a platform hides it from the "New Listing" form immediately.
        Existing listings from that platform are unaffected.
    </div>
</div>
@endsection
