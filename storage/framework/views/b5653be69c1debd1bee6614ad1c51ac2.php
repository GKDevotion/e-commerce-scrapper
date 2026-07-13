<?php $__env->startSection('title', 'Profile Settings'); ?>
<?php $__env->startSection('page-title', 'Profile Settings'); ?>

<?php $__env->startSection('content'); ?>
<?php $user = auth()->user(); ?>
<div class="row g-4">

    <!-- Profile Info -->
    <div class="col-lg-7 fade-in-up">
        <div class="alb-card">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-person me-2" style="color:#E31837;"></i>Profile Information</h3>
            </div>
            <form method="POST" action="<?php echo e(route('profile.update')); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="alb-label">Full Name</label>
                        <input type="text" name="name" class="alb-input" value="<?php echo e(old('name', $user->name)); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">Email Address</label>
                        <input type="email" name="email" class="alb-input" value="<?php echo e(old('email', $user->email)); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">Company Name</label>
                        <input type="text" name="company_name" class="alb-input" value="<?php echo e(old('company_name', $user->company_name)); ?>" placeholder="Optional">
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">Phone Number</label>
                        <input type="text" name="phone" class="alb-input" value="<?php echo e(old('phone', $user->phone)); ?>" placeholder="+91 9999999999">
                    </div>
                    <div class="col-12" style="border-top:1px solid #F3F4F6;padding-top:16px;margin-top:4px;">
                        <div style="font-size:12.5px;font-weight:700;color:#374151;margin-bottom:12px;">
                            <i class="bi bi-tag me-1" style="color:#E31837;"></i>Default Brand Defaults (pre-fills new imports)
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">Default Brand Name</label>
                        <input type="text" name="default_brand" class="alb-input" value="<?php echo e(old('default_brand', $user->default_brand)); ?>" placeholder="Your brand name">
                    </div>
                    <div class="col-md-6">
                        <label class="alb-label">Default Manufacturer</label>
                        <input type="text" name="default_manufacturer" class="alb-input" value="<?php echo e(old('default_manufacturer', $user->default_manufacturer)); ?>" placeholder="Your manufacturer">
                    </div>
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
                <h3 class="alb-card-title"><i class="bi bi-shield-lock me-2" style="color:#E31837;"></i>Change Password</h3>
            </div>
            <form method="POST" action="<?php echo e(route('profile.password')); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
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
                <h3 class="alb-card-title"><i class="bi bi-person-badge me-2" style="color:#E31837;"></i>Account Summary</h3>
            </div>
            <div style="text-align:center;padding:16px 0 24px;">
                <img src="<?php echo e($user->avatar_url); ?>" alt="<?php echo e($user->name); ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #FEE2E8;margin-bottom:12px;">
                <div style="font-family:'Sora',sans-serif;font-size:18px;font-weight:700;color:#111827;"><?php echo e($user->name); ?></div>
                <div style="font-size:13px;color:#9CA3AF;margin-bottom:8px;"><?php echo e($user->email); ?></div>
                <span style="background:<?php echo e($user->isAdmin() ? '#FEF3C7' : '#FEE2E8'); ?>;color:<?php echo e($user->isAdmin() ? '#92400E' : '#E31837'); ?>;font-size:12px;font-weight:700;padding:4px 14px;border-radius:20px;">
                    <?php echo e($user->isAdmin() ? 'Administrator' : ($user->plan?->name ?? 'Free Plan')); ?>

                </span>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;border-top:1px solid #F3F4F6;padding-top:16px;">
                <?php
                $accountInfo = [
                    ['Member Since', $user->created_at->format('M d, Y'), 'bi-calendar'],
                    ['Last Login', $user->last_login_at?->format('M d, Y H:i') ?? 'Now', 'bi-clock'],
                    ['Listings Generated', $user->ai_generations_used, 'bi-cpu'],
                    ['Plan', $user->plan?->name ?? 'Free', 'bi-layers'],
                ];
                ?>
                <?php $__currentLoopData = $accountInfo; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label, $value, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="display:flex;align-items:center;justify-content:space-between;font-size:13px;">
                    <span style="color:#9CA3AF;display:flex;align-items:center;gap:6px;">
                        <i class="bi <?php echo e($icon); ?>"></i> <?php echo e($label); ?>

                    </span>
                    <strong style="color:#374151;"><?php echo e($value); ?></strong>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <!-- Current Plan -->
        <div class="alb-card mb-4">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-credit-card me-2" style="color:#E31837;"></i>Current Plan</h3>
                <a href="<?php echo e(route('billing.plans')); ?>" style="font-size:13px;color:#E31837;text-decoration:none;font-weight:600;">Change →</a>
            </div>
            <?php if($user->plan): ?>
            <div style="background:#F9FAFB;border-radius:10px;padding:16px;margin-bottom:12px;">
                <div style="font-family:'Sora',sans-serif;font-size:18px;font-weight:800;color:#111827;margin-bottom:4px;"><?php echo e($user->plan->name); ?></div>
                <div style="font-size:13px;color:#6B7280;">
                    <?php if($user->plan->price_monthly > 0): ?>
                    $<?php echo e(number_format($user->plan->price_monthly, 2)); ?>/month
                    <?php else: ?>
                    Free Forever
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <div style="margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;color:#6B7280;margin-bottom:6px;">
                    <span>Listings Used</span>
                    <strong style="color:#111827;"><?php echo e($user->listings_used); ?> / <?php echo e($user->plan?->listings_limit_display ?? '5'); ?></strong>
                </div>
                <div class="usage-bar-track">
                    <div class="usage-bar-fill" style="width:<?php echo e($user->getUsagePercentage()); ?>%;"></div>
                </div>
            </div>
            <a href="<?php echo e(route('billing.plans')); ?>" style="display:block;text-align:center;background:#E31837;color:white;border-radius:9px;padding:11px;font-size:13.5px;font-weight:700;text-decoration:none;transition:background 0.15s;" onmouseover="this.style.background='#b01028'" onmouseout="this.style.background='#E31837'">
                <i class="bi bi-arrow-up-circle me-2"></i>Upgrade Plan
            </a>
        </div>

        <!-- Danger Zone -->
        <div class="alb-card" style="border-color:#FCA5A5;">
            <h3 class="alb-card-title mb-3" style="color:#EF4444;"><i class="bi bi-exclamation-triangle me-2"></i>Danger Zone</h3>
            <p style="font-size:13px;color:#6B7280;margin-bottom:16px;">Once you delete your account, all of your data will be permanently removed. This action cannot be undone.</p>
            <button type="button" class="btn" style="background:#FEE2E2;color:#EF4444;border:1.5px solid #FCA5A5;font-size:13px;font-weight:700;padding:10px 20px;border-radius:9px;" onclick="if(confirm('Are you absolutely sure? This will permanently delete your account and all data.')) alert('Contact support to delete your account.')">
                <i class="bi bi-trash me-2"></i>Delete Account
            </button>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH S:\Devotion\www\laravel\amazon-ai-listing-builder\resources\views/dashboard/profile.blade.php ENDPATH**/ ?>