<?php $__env->startSection('title', 'Sign In'); ?>

<?php $__env->startSection('content'); ?>
<div class="auth-form-title">Welcome back</div>
<div class="auth-form-subtitle">Sign in to your Amazon Listing Builder account</div>

<?php if($errors->any()): ?>
<div style="background:#7F1D1D;border:1px solid #EF4444;border-radius:10px;padding:12px 16px;margin-bottom:20px;color:#FCA5A5;font-size:13px;">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <?php echo e($errors->first()); ?>

</div>
<?php endif; ?>

<?php if(session('status')): ?>
<div style="background:#064E3B;border:1px solid #10B981;border-radius:10px;padding:12px 16px;margin-bottom:20px;color:#6EE7B7;font-size:13px;">
    <i class="bi bi-check-circle-fill me-2"></i>
    <?php echo e(session('status')); ?>

</div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('login.post')); ?>" id="loginForm">
    <?php echo csrf_field(); ?>
    <div class="alb-form-group">
        <label class="alb-label" for="email">Email Address</label>
        <input type="email" name="email" id="email" class="alb-input <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
            placeholder="you@example.com" value="<?php echo e(old('email')); ?>" required autocomplete="email" autofocus>
    </div>

    <div class="alb-form-group">
        <label class="alb-label" for="password">
            Password
            <a href="<?php echo e(route('password.request')); ?>" class="auth-link float-end" style="font-size:12px;">Forgot?</a>
        </label>
        <div style="position:relative;">
            <input type="password" name="password" id="password" class="alb-input <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
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
    Don't have an account? <a href="<?php echo e(route('register')); ?>" class="auth-link">Create one free</a>
</div>

<?php $__env->startPush('scripts'); ?>
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
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH S:\Devotion\www\laravel\amazon-ai-listing-builder\resources\views/auth/login.blade.php ENDPATH**/ ?>