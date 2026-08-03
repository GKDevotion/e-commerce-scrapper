@extends('layouts.app')
@section('title','Ad Settings')
@section('page-title','Advertisement Settings')
@section('content')
<div class="alb-card mb-4 fade-in-up" style="background:#EFF6FF;border:1px solid #BFDBFE;padding:16px 20px;">
    <div style="font-size:13.5px;color:#1E40AF;">
        <i class="bi bi-info-circle me-1"></i>
        Paste your Google AdSense or Facebook Audience Network ad unit code into each slot.
        Enable only the slots you want active. Changes take effect immediately (cache cleared on save).
    </div>
</div>

@foreach($ads->groupBy('provider') as $provider => $slots)
<div class="alb-card mb-4 fade-in-up">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid #F3F4F6;">
        @if($provider === 'google')
        <div style="width:40px;height:40px;background:#FEF3C7;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;">🅖</div>
        <div>
            <div style="font-family:'Sora',sans-serif;font-size:16px;font-weight:700;color:#111827;">Google AdSense</div>
            <div style="font-size:12px;color:#9CA3AF;">Add your AdSense publisher ID and ad unit codes</div>
        </div>
        @else
        <div style="width:40px;height:40px;background:#EFF6FF;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;">f</div>
        <div>
            <div style="font-family:'Sora',sans-serif;font-size:16px;font-weight:700;color:#111827;">Facebook Audience Network</div>
            <div style="font-size:12px;color:#9CA3AF;">Add your Facebook placement codes</div>
        </div>
        @endif
    </div>

    <div class="row g-4">
        @foreach($slots as $ad)
        <div class="col-md-6">
            <form method="POST" action="{{ route('admin.ads.update', $ad->id) }}">
                @csrf
                <div style="border:1.5px solid {{ $ad->is_enabled ? '#10B981' : '#E5E7EB' }};border-radius:12px;padding:16px;transition:border-color .15s;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <div style="font-size:13.5px;font-weight:700;color:#111827;">{{ $ad->label }}</div>
                            <div style="font-size:11.5px;color:#9CA3AF;">Placement: {{ $ad->placement }}</div>
                        </div>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:#374151;">
                            <input type="checkbox" name="is_enabled" value="1" {{ $ad->is_enabled ? 'checked' : '' }}
                                style="width:16px;height:16px;accent-color:#E31837;">
                            Enabled
                        </label>
                    </div>
                    <textarea name="ad_code" class="alb-input" style="min-height:100px;font-family:monospace;font-size:12px;"
                        placeholder="Paste ad unit code here...">{{ $ad->ad_code }}</textarea>
                    <button type="submit" class="btn-alb-primary btn w-100 mt-2" style="font-size:13px;padding:9px;">
                        <i class="bi bi-save me-1"></i>Save Slot
                    </button>
                </div>
            </form>
        </div>
        @endforeach
    </div>
</div>
@endforeach
@endsection
