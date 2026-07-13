@extends('layouts.app')
@section('title', isset($plan) ? 'Edit Plan' : 'Create Plan')
@section('page-title', isset($plan) ? 'Edit Plan: ' . $plan->name : 'Create New Plan')

@section('topbar-actions')
<a href="{{ route('admin.plans') }}" class="topbar-btn" style="background:#374151;">
    <i class="bi bi-arrow-left"></i> All Plans
</a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="alb-card fade-in-up">
            <h3 class="alb-card-title mb-4">
                <i class="bi bi-layers me-2" style="color:#E31837;"></i>
                {{ isset($plan) ? 'Edit Plan' : 'Create New Plan' }}
            </h3>

            <form method="POST" action="{{ isset($plan) ? route('admin.plans.update', $plan->id) : route('admin.plans.store') }}">
                @csrf
                @if(isset($plan)) @method('PUT') @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="alb-label">Plan Name <span style="color:#E31837;">*</span></label>
                        <input type="text" name="name" class="alb-input" value="{{ old('name', $plan->name ?? '') }}" required placeholder="e.g. Pro">
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">Slug (unique) <span style="color:#E31837;">*</span></label>
                        <input type="text" name="slug" class="alb-input" value="{{ old('slug', $plan->slug ?? '') }}" required placeholder="e.g. pro" {{ isset($plan) ? 'readonly' : '' }}>
                    </div>
                    <div class="col-12">
                        <label class="alb-label">Description</label>
                        <textarea name="description" class="alb-input alb-textarea" style="min-height:70px;" placeholder="Plan description shown to users">{{ old('description', $plan->description ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">Monthly Price (USD)</label>
                        <input type="number" name="price_monthly" class="alb-input" value="{{ old('price_monthly', $plan->price_monthly ?? 0) }}" min="0" step="0.01">
                        <div style="font-size:12px;color:#9CA3AF;margin-top:4px;">Set 0 for free plan</div>
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">Yearly Price (USD)</label>
                        <input type="number" name="price_yearly" class="alb-input" value="{{ old('price_yearly', $plan->price_yearly ?? 0) }}" min="0" step="0.01">
                    </div>
                    <div class="col-md-4">
                        <label class="alb-label">Listings Limit</label>
                        <input type="number" name="listings_limit" class="alb-input" value="{{ old('listings_limit', $plan->listings_limit ?? 5) }}" min="-1">
                        <div style="font-size:12px;color:#9CA3AF;margin-top:4px;">-1 = unlimited</div>
                    </div>
                    <div class="col-md-4">
                        <label class="alb-label">AI Generations Limit</label>
                        <input type="number" name="ai_generations_limit" class="alb-input" value="{{ old('ai_generations_limit', $plan->ai_generations_limit ?? 5) }}" min="-1">
                    </div>
                    <div class="col-md-4">
                        <label class="alb-label">Exports Limit</label>
                        <input type="number" name="exports_limit" class="alb-input" value="{{ old('exports_limit', $plan->exports_limit ?? 10) }}" min="-1">
                    </div>

                    <!-- Feature Toggles -->
                    <div class="col-12" style="border-top:1px solid #F3F4F6;padding-top:20px;margin-top:4px;">
                        <div style="font-size:13px;font-weight:700;color:#374151;margin-bottom:14px;">Feature Access</div>
                        <div class="row g-3">
                            @php
                            $toggles = [
                                ['amazon_publish', 'Amazon SP-API Publish', 'Publish listings directly to Amazon Seller Central'],
                                ['bulk_import', 'Bulk URL Import', 'Import multiple Amazon URLs at once'],
                                ['team_accounts', 'Team Accounts', 'Multiple users per account'],
                                ['priority_support', 'Priority Support', 'Faster support response time'],
                                ['api_access', 'API Access', 'REST API for programmatic access'],
                                ['is_featured', 'Featured Plan', 'Highlight this plan on the pricing page'],
                                ['is_active', 'Active Plan', 'Show this plan to users'],
                            ];
                            @endphp
                            @foreach($toggles as [$field, $label, $desc])
                            <div class="col-md-6">
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#F9FAFB;border-radius:10px;border:1.5px solid #E5E7EB;">
                                    <div>
                                        <div style="font-size:13.5px;font-weight:600;color:#111827;">{{ $label }}</div>
                                        <div style="font-size:12px;color:#9CA3AF;">{{ $desc }}</div>
                                    </div>
                                    <label style="position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0;">
                                        <input type="checkbox" name="{{ $field }}" value="1"
                                            {{ old($field, $plan?->{$field} ?? ($field === 'is_active')) ? 'checked' : '' }}
                                            style="opacity:0;width:0;height:0;" id="toggle_{{ $field }}"
                                            onchange="updateToggle('{{ $field }}')">
                                        <span id="toggle_display_{{ $field }}" style="
                                            position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;
                                            border-radius:24px;transition:0.15s;
                                            background:{{ old($field, $plan?->{$field} ?? ($field === 'is_active')) ? '#E31837' : '#E5E7EB' }};
                                        ">
                                            <span style="position:absolute;content:'';height:18px;width:18px;left:3px;bottom:3px;border-radius:50%;background:white;transition:0.15s;
                                                transform:{{ old($field, $plan?->{$field} ?? ($field === 'is_active')) ? 'translateX(20px)' : 'translateX(0)' }};display:block;"></span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-12" style="display:flex;gap:10px;padding-top:8px;">
                        <button type="submit" class="btn-alb-primary btn">
                            <i class="bi bi-save me-2"></i>{{ isset($plan) ? 'Update Plan' : 'Create Plan' }}
                        </button>
                        <a href="{{ route('admin.plans') }}" class="btn-alb-outline btn">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function updateToggle(field) {
    const cb = document.getElementById('toggle_' + field);
    const display = document.getElementById('toggle_display_' + field);
    const thumb = display.querySelector('span');
    if (cb.checked) {
        display.style.background = '#E31837';
        thumb.style.transform = 'translateX(20px)';
    } else {
        display.style.background = '#E5E7EB';
        thumb.style.transform = 'translateX(0)';
    }
}
// Make the whole toggle label clickable
document.querySelectorAll('[id^="toggle_display_"]').forEach(el => {
    el.addEventListener('click', function() {
        const field = this.id.replace('toggle_display_', '');
        const cb = document.getElementById('toggle_' + field);
        cb.checked = !cb.checked;
        updateToggle(field);
    });
});
</script>
@endpush
@endsection
