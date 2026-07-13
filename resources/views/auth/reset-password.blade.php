@extends('layouts.auth')
@section('title', 'Reset Password')

@section('content')
<div class="auth-form-title">Set New Password</div>
<div class="auth-form-subtitle">Enter your new password below.</div>

@if($errors->any())
<div style="background:#7F1D1D;border:1px solid #EF4444;border-radius:10px;padding:12px 16px;margin-bottom:20px;color:#FCA5A5;font-size:13px;">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="alb-form-group">
        <label class="alb-label">Email Address</label>
        <input type="email" name="email" class="alb-input" value="{{ old('email', $email) }}" required readonly>
    </div>

    <div class="alb-form-group">
        <label class="alb-label">New Password</label>
        <div style="position:relative;">
            <input type="password" name="password" id="password" class="alb-input" placeholder="Min. 8 characters" required autofocus>
            <button type="button" onclick="togglePassword('password')" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:#6B7280;cursor:pointer;">
                <i class="bi bi-eye" id="passwordEye"></i>
            </button>
        </div>
    </div>

    <div class="alb-form-group">
        <label class="alb-label">Confirm New Password</label>
        <input type="password" name="password_confirmation" class="alb-input" placeholder="Repeat new password" required>
    </div>

    <button type="submit" class="btn-auth">
        <i class="bi bi-key me-2"></i>Reset Password
    </button>
</form>

<div class="auth-footer mt-4">
    <a href="{{ route('login') }}" class="auth-link">← Back to Sign In</a>
</div>

@push('scripts')
<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    const eye = document.getElementById(id + 'Eye');
    input.type = input.type === 'password' ? 'text' : 'password';
    eye.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>
@endpush
@endsection
