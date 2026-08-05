@extends('layouts.app')
@section('title', 'Profile Settings')
@section('page-title', 'Profile Settings')

@section('content')
@php $user = auth()->user(); @endphp
<div class="row g-4">

    <!-- Profile Info -->
    <div class="col-lg-7 fade-in-up">
        <div class="alb-card">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-person me-2" style="color:#d09226;"></i>Profile Information</h3>
            </div>
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" data-warn-unsaved>
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-12">
                        <label class="alb-label">Profile Picture</label>
                        <div class="d-flex align-items-center gap-3">
                            <img src="{{ $user->avatar_url }}" id="avatarPreview" style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid #FEE2E8;">
                            <div>
                                <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display:none" onchange="if(this.files[0]){var r=new FileReader();r.onload=function(e){document.getElementById('avatarPreview').src=e.target.result;};r.readAsDataURL(this.files[0]);}">
                                <button type="button" onclick="document.getElementById('avatarInput').click()" class="btn-alb-outline btn" style="font-size:12.5px;padding:7px 16px;"><i class="bi bi-camera me-1"></i>Change Photo</button>
                                <div style="font-size:11.5px;color:#9CA3AF;margin-top:4px;">JPG/PNG/WebP · max 2MB</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">Full Name</label>
                        <input type="text" name="name" class="alb-input" value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">Email Address</label>
                        <input type="email" name="email" class="alb-input" value="{{ old('email', $user->email) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">Company Name</label>
                        <input type="text" name="company_name" class="alb-input" value="{{ old('company_name', $user->company_name) }}" placeholder="Optional">
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">Phone Number</label>
                        <input type="text" name="phone" class="alb-input" value="{{ old('phone', $user->phone) }}" placeholder="+91 9999999999">
                    </div>
                    <div class="col-12" style="border-top:1px solid #F3F4F6;padding-top:16px;margin-top:4px;">
                        <div style="font-size:12.5px;font-weight:700;color:#374151;margin-bottom:12px;">
                            <i class="bi bi-tag me-1" style="color:#d09226;"></i>Default Brand Defaults (pre-fills new imports)
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">Default Brand Name</label>
                        <input type="text" name="default_brand" class="alb-input" value="{{ old('default_brand', $user->default_brand) }}" placeholder="Your brand name">
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">Default Manufacturer</label>
                        <input type="text" name="default_manufacturer" class="alb-input" value="{{ old('default_manufacturer', $user->default_manufacturer) }}" placeholder="Your manufacturer">
                    </div>

                    {{-- OpenAI API Key section --}}
                    <div class="col-12">
                        <div style="border-top:1.5px solid #F3F4F6;padding-top:20px;margin-top:8px;">
                            <div style="font-family:'Sora',sans-serif;font-size:14px;font-weight:700;color:#111827;margin-bottom:6px;">
                                <i class="bi bi-cpu me-2" style="color:#d09226;"></i>AI Settings
                            </div>
                            <div style="font-size:12.5px;color:#6B7280;margin-bottom:14px;line-height:1.6;">
                                Add your personal OpenAI API key to enable AI-powered listing generation.
                                Without it you'll be in <strong>manual mode</strong> — you can still create listings by editing scraped data yourself.
                                <a href="https://platform.openai.com/api-keys" target="_blank" style="color:#d09226;">Get your API key →</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <label class="alb-label">
                            OpenAI API Key
                            @if($user->hasOpenAiKey())
                            <span style="background:#D1FAE5;color:#065F46;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:6px;">Active ✓</span>
                            @else
                            <span style="background:#FEF3C7;color:#92400E;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:6px;">Not Set</span>
                            @endif
                        </label>
                        <input type="password" name="openai_api_key" class="alb-input"
                            placeholder="{{ $user->hasOpenAiKey() ? 'sk-proj-••••••••••••••••••••' : 'sk-proj-...' }}"
                            value=""
                            autocomplete="new-password">
                        <div style="font-size:11.5px;color:#9CA3AF;margin-top:5px;">
                            Leave blank to keep your existing key. Your key is encrypted and never exposed.
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="alb-label">AI Model</label>
                        <select name="openai_model" class="alb-input">
                            @foreach(['gpt-4o','gpt-4o-mini','gpt-4-turbo','gpt-3.5-turbo'] as $m)
                            <option value="{{ $m }}" {{ ($user->openai_model ?: 'gpt-4o') === $m ? 'selected' : '' }}>
                                {{ $m }}{{ $m === 'gpt-4o' ? ' (recommended)' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    @if($user->hasOpenAiKey())
                    <div class="col-12">
                        <div style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:12px 16px;font-size:12.5px;color:#14532D;">
                            <i class="bi bi-cpu me-1" style="color:#10B981;"></i>
                            AI generation is <strong>active</strong> using your personal key ({{ $user->openai_model ?: 'gpt-4o' }}).
                            Costs are billed directly to your OpenAI account.
                        </div>
                    </div>
                    @endif

                    <div class="col-12">
                        <button type="submit" class="btn-alb-primary btn">
                            <i class="bi bi-save me-2"></i>Save Profile
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Change Password -->
        <div class="alb-card mt-4">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-shield-lock me-2" style="color:#d09226;"></i>Change Password</h3>
            </div>
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-12">
                        <label class="alb-label">Current Password</label>
                        <input type="password" name="current_password" class="alb-input" required>
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">New Password</label>
                        <input type="password" name="password" class="alb-input" required>
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="alb-input" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn-alb-primary btn">
                            <i class="bi bi-key me-2"></i>Update Password
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-lg-5 fade-in-up fade-in-up-delay-1">
        <!-- Account Summary -->
        <div class="alb-card mb-4">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-person-badge me-2" style="color:#d09226;"></i>Account Summary</h3>
            </div>
            <div style="text-align:center;padding:16px 0 24px;">
                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #FEE2E8;margin-bottom:12px;">
                <div style="font-family:'Sora',sans-serif;font-size:18px;font-weight:700;color:#111827;">{{ $user->name }}</div>
                <div style="font-size:13px;color:#9CA3AF;margin-bottom:8px;">{{ $user->email }}</div>
                <span style="background:{{ $user->isAdmin() ? '#FEF3C7' : '#FEE2E8' }};color:{{ $user->isAdmin() ? '#92400E' : '#d09226' }};font-size:12px;font-weight:700;padding:4px 14px;border-radius:20px;">
                    {{ $user->isAdmin() ? 'Administrator' : ($user->plan?->name ?? 'Free Plan') }}
                </span>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;border-top:1px solid #F3F4F6;padding-top:16px;">
                @php
                $accountInfo = [
                    ['Member Since', $user->created_at->format('M d, Y'), 'bi-calendar'],
                    ['Last Login', $user->last_login_at?->format('M d, Y H:i') ?? 'Now', 'bi-clock'],
                    ['Listings Generated', $user->ai_generations_used, 'bi-cpu'],
                    ['Plan', $user->plan?->name ?? 'Free', 'bi-layers'],
                ];
                @endphp
                @foreach($accountInfo as [$label, $value, $icon])
                <div style="display:flex;align-items:center;justify-content:space-between;font-size:13px;">
                    <span style="color:#9CA3AF;display:flex;align-items:center;gap:6px;">
                        <i class="bi {{ $icon }}"></i> {{ $label }}
                    </span>
                    <strong style="color:#374151;">{{ $value }}</strong>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Current Plan -->
        <div class="alb-card mb-4">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-credit-card me-2" style="color:#d09226;"></i>Current Plan</h3>
                <a href="{{ route('billing.plans') }}" style="font-size:13px;color:#d09226;text-decoration:none;font-weight:600;">Change →</a>
            </div>
            @if($user->plan)
            <div style="background:#F9FAFB;border-radius:10px;padding:16px;margin-bottom:12px;">
                <div style="font-family:'Sora',sans-serif;font-size:18px;font-weight:800;color:#111827;margin-bottom:4px;">{{ $user->plan->name }}</div>
                <div style="font-size:13px;color:#6B7280;">
                    @if($user->plan->price_monthly > 0)
                    ${{ number_format($user->plan->price_monthly, 2) }}/month
                    @else
                    Free Forever
                    @endif
                </div>
            </div>
            @endif
            <div style="margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;color:#6B7280;margin-bottom:6px;">
                    <span>Listings Used</span>
                    <strong style="color:#111827;">{{ $user->listings_used }} / {{ $user->plan?->listings_limit_display ?? '5' }}</strong>
                </div>
                <div class="usage-bar-track">
                    <div class="usage-bar-fill" style="width:{{ $user->getUsagePercentage() }}%;"></div>
                </div>
            </div>
            <a href="{{ route('billing.plans') }}" style="display:block;text-align:center;background:#d09226;color:white;border-radius:9px;padding:11px;font-size:13.5px;font-weight:700;text-decoration:none;transition:background 0.15s;" onmouseover="this.style.background='#b01028'" onmouseout="this.style.background='#d09226'">
                <i class="bi bi-arrow-up-circle me-2"></i>Upgrade Plan
            </a>
        </div>

        <!-- Danger Zone -->
        <div class="alb-card" style="border-color:#FCA5A5;">
            <h3 class="alb-card-title mb-3" style="color:#EF4444;"><i class="bi bi-exclamation-triangle me-2"></i>Danger Zone</h3>
            <p style="font-size:13px;color:#6B7280;margin-bottom:16px;">Once you delete your account, all of your data will be permanently removed. This action cannot be undone.</p>
            <button type="button" class="btn" style="background:#d09226;color:#EF4444;border:1.5px solid #FCA5A5;font-size:13px;font-weight:700;padding:10px 20px;border-radius:9px;" onclick="if(confirm('Are you absolutely sure? This will permanently delete your account and all data.')) alert('Contact support to delete your account.')">
                <i class="bi bi-trash me-2"></i>Delete Account
            </button>
        </div>
    </div>
</div>
@endsection
