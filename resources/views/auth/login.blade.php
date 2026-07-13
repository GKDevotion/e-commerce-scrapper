@extends('layouts.auth')
@section('title', 'Sign In')

@section('content')
<div class="auth-form-title">Welcome back</div>
<div class="auth-form-subtitle">Sign in to your Amazon Listing Builder account</div>

@if($errors->any())
<div style="background:#7F1D1D;border:1px solid #EF4444;border-radius:10px;padding:12px 16px;margin-bottom:20px;color:#FCA5A5;font-size:13px;">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    {{ $errors->first() }}
</div>
@endif

@if(session('status'))
<div style="background:#064E3B;border:1px solid #10B981;border-radius:10px;padding:12px 16px;margin-bottom:20px;color:#6EE7B7;font-size:13px;">
    <i class="bi bi-check-circle-fill me-2"></i>
    {{ session('status') }}
</div>
@endif

<form method="POST" action="{{ route('login.post') }}" id="loginForm">
    @csrf
    <div class="alb-form-group">
        <label class="alb-label" for="email">Email Address</label>
        <input type="email" name="email" id="email" class="alb-input @error('email') is-invalid @enderror"
            placeholder="you@example.com" value="{{ old('email') }}" required autocomplete="email" autofocus>
    </div>

    <div class="alb-form-group">
        <label class="alb-label" for="password">
            Password
            <a href="{{ route('password.request') }}" class="auth-link float-end" style="font-size:12px;">Forgot?</a>
        </label>
        <div style="position:relative;">
            <input type="password" name="password" id="password" class="alb-input @error('password') is-invalid @enderror"
                placeholder="Your password" required autocomplete="current-password">
            <button type="button" onclick="togglePassword('password')" 
                style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:#6B7280;cursor:pointer;font-size:16px;">
                <i class="bi bi-eye" id="passwordEye"></i>
            </button>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label" for="remember">Remember me</label>
        </div>
    </div>

    <button type="submit" class="btn-auth" id="submitBtn">
        <span id="btnText"><i class="bi bi-box-arrow-in-right me-2"></i>Sign In</span>
        <span id="btnLoading" style="display:none;">
            <span class="spinner-border spinner-border-sm me-2"></span>Signing in...
        </span>
    </button>
</form>

<div class="auth-footer mt-4">
    Don't have an account? <a href="{{ route('register') }}" class="auth-link">Create one free</a>
</div>

@push('scripts')
<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    const icon = document.getElementById(id + 'Eye');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

document.getElementById('loginForm').addEventListener('submit', function() {
    document.getElementById('btnText').style.display = 'none';
    document.getElementById('btnLoading').style.display = 'inline-flex';
    document.getElementById('submitBtn').disabled = true;
});
</script>
@endpush
@endsection
