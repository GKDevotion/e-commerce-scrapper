@extends('layouts.app')
@section('title','Analytics & Tracking')
@section('page-title','Analytics & Tracking')

@section('content')
<div class="alb-card mb-4 fade-in-up" style="background:#EFF6FF;border:1px solid #BFDBFE;padding:16px 20px;">
    <div style="font-size:13.5px;color:#1E40AF;">
        <i class="bi bi-info-circle me-1"></i>
        Enable a provider, enter its tracking ID (for standard snippets) <strong>or</strong> paste a custom script.
        If both are set, the custom script takes priority. Changes are live immediately — no deploy needed.
    </div>
</div>

@php
$providerMeta = [
    'google_analytics'   => ['icon'=>'📊', 'color'=>'#E37400', 'bg'=>'#FFF8EE', 'placeholder'=>'G-XXXXXXXXXX', 'hint'=>'Find in GA4 → Admin → Data Streams → Measurement ID'],
    'google_tag_manager' => ['icon'=>'🏷️', 'color'=>'#4285F4', 'bg'=>'#EFF6FF', 'placeholder'=>'GTM-XXXXXXX',  'hint'=>'Find in GTM → Admin → Container ID'],
    'microsoft_clarity'  => ['icon'=>'🔬', 'color'=>'#0078D4', 'bg'=>'#EFF6FF', 'placeholder'=>'xxxxxxxxxx',   'hint'=>'Find in Clarity → Settings → Overview → Project ID'],
    'google_search_console'=>['icon'=>'🔍','color'=>'#34A853', 'bg'=>'#F0FDF4', 'placeholder'=>'Paste verification meta tag content only', 'hint'=>'Google Search Console → Settings → Ownership verification → HTML tag'],
    'facebook_pixel'     => ['icon'=>'📘', 'color'=>'#1877F2', 'bg'=>'#EFF6FF', 'placeholder'=>'000000000000000', 'hint'=>'Find in Meta Events Manager → Data Sources → Pixel ID'],
    'hotjar'             => ['icon'=>'🔥', 'color'=>'#FF3C00', 'bg'=>'#FFF5F5', 'placeholder'=>'0000000',       'hint'=>'Find in Hotjar → Settings → Sites & Organizations → Site ID'],
    'custom'             => ['icon'=>'⚙️', 'color'=>'#6B7280', 'bg'=>'#F9FAFB', 'placeholder'=>'',              'hint'=>'Paste any custom tracking script (head and/or body)'],
];
@endphp

<div class="row g-4">
@foreach($analytics as $item)
@php $meta = $providerMeta[$item->provider] ?? ['icon'=>'📌','color'=>'#374151','bg'=>'#F9FAFB','placeholder'=>'Tracking ID','hint'=>'']; @endphp
<div class="col-12">
    <div class="alb-card fade-in-up" style="border-left:4px solid {{ $item->is_enabled ? $meta['color'] : '#E5E7EB' }};padding:0;overflow:hidden;">
        <form method="POST" action="{{ route('admin.analytics.settings.update', $item->id) }}">
            @csrf

            {{-- Header --}}
            <div style="padding:18px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;background:{{ $item->is_enabled ? $meta['bg'] : 'white' }};">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:48px;height:48px;background:{{ $meta['bg'] }};border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;border:1.5px solid {{ $meta['color'] }}22;">
                        {{ $meta['icon'] }}
                    </div>
                    <div>
                        <div style="font-family:'Sora',sans-serif;font-size:15px;font-weight:700;color:#111827;">{{ $item->label }}</div>
                        <div style="font-size:12px;color:#9CA3AF;">{{ $meta['hint'] }}</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    @if($item->is_enabled)
                    <span style="background:#D1FAE5;color:#065F46;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;">
                        <i class="bi bi-check-circle me-1"></i>Active
                    </span>
                    @else
                    <span style="background:#F3F4F6;color:#9CA3AF;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;">Inactive</span>
                    @endif
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13.5px;font-weight:600;color:#374151;">
                        <input type="checkbox" name="is_enabled" value="1" {{ $item->is_enabled ? 'checked' : '' }}
                            style="width:18px;height:18px;accent-color:{{ $meta['color'] }};">
                        Enable
                    </label>
                </div>
            </div>

            {{-- Fields --}}
            <div style="padding:18px 20px;border-top:1px solid #F3F4F6;">
                <div class="row g-3">
                    {{-- Tracking ID (not shown for custom) --}}
                    @if($item->provider !== 'custom')
                    <div class="col-md-6">
                        <label class="alb-label">
                            Tracking ID
                            @if($item->provider === 'google_search_console')
                            <span style="color:#9CA3AF;font-weight:400;">(verification content value only)</span>
                            @endif
                        </label>
                        <input type="text" name="tracking_id" class="alb-input"
                            value="{{ $item->tracking_id }}"
                            placeholder="{{ $meta['placeholder'] }}"
                            style="font-family:monospace;font-size:13px;">
                        <div style="font-size:11.5px;color:#9CA3AF;margin-top:4px;">
                            Standard snippet generated automatically from this ID.
                        </div>
                    </div>
                    @endif

                    {{-- Custom head code --}}
                    <div class="{{ $item->provider === 'custom' ? 'col-12' : 'col-md-6' }}">
                        <label class="alb-label">
                            Custom &lt;head&gt; Script
                            <span style="color:#9CA3AF;font-weight:400;">(optional — overrides tracking ID)</span>
                        </label>
                        <textarea name="head_code" class="alb-input"
                            style="min-height:90px;font-family:monospace;font-size:12px;"
                            placeholder="&lt;script&gt;...&lt;/script&gt;">{{ $item->head_code }}</textarea>
                    </div>

                    {{-- Body code (GTM / custom) --}}
                    @if(in_array($item->provider, ['google_tag_manager', 'custom']))
                    <div class="col-12">
                        <label class="alb-label">
                            Custom &lt;body&gt; Script
                            <span style="color:#9CA3AF;font-weight:400;">(placed immediately after &lt;body&gt; tag)</span>
                        </label>
                        <textarea name="body_code" class="alb-input"
                            style="min-height:70px;font-family:monospace;font-size:12px;"
                            placeholder="&lt;noscript&gt;...&lt;/noscript&gt;">{{ $item->body_code }}</textarea>
                    </div>
                    @endif
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn-alb-primary btn" style="font-size:13px;padding:9px 22px;">
                        <i class="bi bi-save me-1"></i>Save {{ $item->label }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endforeach
</div>
@endsection
