@extends('layouts.auth')
@section('title', 'Create Account')

@section('content')
<div class="auth-form-title">Get started free</div>
<div class="auth-form-subtitle">Create your Amazon Listing Builder account — no credit card required</div>

@if($errors->any())
<div style="background:#7F1D1D;border:1px solid #EF4444;border-radius:10px;padding:12px 16px;margin-bottom:20px;color:#FCA5A5;font-size:13px;">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('register.post') }}" id="registerForm">
    @csrf

    <div class="row g-3 mb-3">
        <div class="col-12">
            <label class="alb-label">Full Name *</label>
            <input type="text" name="name" class="alb-input @error('name') is-invalid @enderror"
                placeholder="John Smith" value="{{ old('name') }}" required autofocus>
        </div>
        <div class="col-12">
            <label class="alb-label">Email Address *</label>
            <input type="email" name="email" class="alb-input @error('email') is-invalid @enderror"
                placeholder="you@example.com" value="{{ old('email') }}" required>
        </div>
        <div class="col-12">
            <label class="alb-label">Company Name</label>
            <input type="text" name="company_name" class="alb-input"
                placeholder="My Store (optional)" value="{{ old('company_name') }}">
        </div>
        <div class="col-12">
            <label class="alb-label">Password *</label>
            <div style="position:relative;">
                <input type="password" name="password" id="password" class="alb-input @error('password') is-invalid @enderror"
                    placeholder="Min. 8 characters" required>
                <button type="button" onclick="togglePass('password')"
                    style="position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:#6B7280;cursor:pointer;">
                    <i class="bi bi-eye" id="passEye"></i>
                </button>
            </div>
        </div>
        <div class="col-12">
            <label class="alb-label">Confirm Password *</label>
            <input type="password" name="password_confirmation" class="alb-input" placeholder="Repeat password" required>
        </div>
    </div>

    <div class="form-check mb-4">
        <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" name="terms" id="terms" required>
        <label class="form-check-label" for="terms">
            I agree to the <a href="#" class="auth-link">Terms of Service</a> and <a href="#" class="auth-link">Privacy Policy</a>
        </label>
    </div>

    <button type="submit" class="btn-auth" id="submitBtn">
        <span id="btnText"><i class="bi bi-person-plus me-2"></i>Create Free Account</span>
        <span id="btnLoading" style="display:none;">
            <span class="spinner-border spinner-border-sm me-2"></span>Creating account...
        </span>
    </button>

    <!-- Free plan features -->
    <div style="margin-top:20px;padding:14px;background:rgba(227,24,55,0.08);border:1px solid rgba(227,24,55,0.2);border-radius:10px;">
        <div style="font-size:12px;font-weight:700;color:var(--alb-red);margin-bottom:8px;text-transform:uppercase;letter-spacing:0.05em;">Free Plan Includes</div>
        <div style="display:grid;gap:5px;">
            @php $freePlan = $plans->where('slug','free')->first(); @endphp
            <div style="font-size:12.5px;color:#9CA3AF;display:flex;align-items:center;gap:8px;">
                <i class="bi bi-check text-success"></i> 5 AI-generated listings/month
            </div>
            <div style="font-size:12.5px;color:#9CA3AF;display:flex;align-items:center;gap:8px;">
                <i class="bi bi-check text-success"></i> CSV & JSON export
            </div>
            <div style="font-size:12.5px;color:#9CA3AF;display:flex;align-items:center;gap:8px;">
                <i class="bi bi-check text-success"></i> Side-by-side comparison
            </div>
        </div>
    </div>
</form>

<div class="auth-footer mt-4">
    Already have an account? <a href="{{ route('login') }}" class="auth-link">Sign in</a>
</div>

@push('scripts')
<script>
function togglePass(id) {
    const input = document.getElementById(id);
    const eye = document.getElementById('passEye');
    if (input.type === 'password') { input.type = 'text'; eye.className = 'bi bi-eye-slash'; }
    else { input.type = 'password'; eye.className = 'bi bi-eye'; }
}
document.getElementById('registerForm').addEventListener('submit', function() {
    document.getElementById('btnText').style.display = 'none';
    document.getElementById('btnLoading').style.display = 'inline-flex';
    document.getElementById('submitBtn').disabled = true;
});
</script>
@endpush
@endsection
