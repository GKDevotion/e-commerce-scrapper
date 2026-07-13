@extends('layouts.auth')
@section('title', 'Forgot Password')

@section('content')
<div class="auth-form-title">Forgot Password</div>
<div class="auth-form-subtitle">Enter your email and we'll send you a reset link.</div>

@if(session('status'))
<div style="background:#064E3B;border:1px solid #10B981;border-radius:10px;padding:12px 16px;margin-bottom:20px;color:#6EE7B7;font-size:13px;">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('status') }}
</div>
@endif

@if($errors->any())
<div style="background:#7F1D1D;border:1px solid #EF4444;border-radius:10px;padding:12px 16px;margin-bottom:20px;color:#FCA5A5;font-size:13px;">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="alb-form-group">
        <label class="alb-label">Email Address</label>
        <input type="email" name="email" class="alb-input" placeholder="you@example.com"
            value="{{ old('email') }}" required autofocus>
    </div>
    <button type="submit" class="btn-auth">
        <i class="bi bi-envelope me-2"></i>Send Reset Link
    </button>
</form>

<div class="auth-footer mt-4">
    <a href="{{ route('login') }}" class="auth-link">← Back to Sign In</a>
</div>
@endsection
