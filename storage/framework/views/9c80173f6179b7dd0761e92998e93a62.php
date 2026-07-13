<?php $__env->startSection('title', 'Manage Plans'); ?>
<?php $__env->startSection('page-title', 'Subscription Plans'); ?>

<?php $__env->startSection('topbar-actions'); ?>
<a href="<?php echo e(route('admin.plans.create')); ?>" class="topbar-btn">
    <i class="bi bi-plus-lg"></i> New Plan
</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row g-4">
    <?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="col-md-6 col-lg-3 fade-in-up">
        <div class="alb-card h-100" style="border:<?php echo e($plan->is_featured ? '2px solid #E31837' : '1.5px solid #E5E7EB'); ?>;">
            <?php if($plan->is_featured): ?>
            <div style="background:#E31837;color:white;text-align:center;font-size:10.5px;font-weight:800;padding:5px;border-radius:8px 8px 0 0;margin:-24px -24px 16px;">FEATURED</div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
                <div>
                    <div style="font-family:'Sora',sans-serif;font-size:17px;font-weight:800;color:#111827;"><?php echo e($plan->name); ?></div>
                    <div style="font-size:12px;color:#9CA3AF;"><?php echo e($plan->slug); ?></div>
                </div>
                <div style="display:flex;gap:6px;">
                    <?php if($plan->is_active): ?>
                    <span style="background:#D1FAE5;color:#065F46;font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:20px;">Active</span>
                    <?php else: ?>
                    <span style="background:#F3F4F6;color:#9CA3AF;font-size:10.5px;font-weight:700;padding:3px 8px;border-radius:20px;">Inactive</span>
                    <?php endif; ?>
                </div>
            </div>

            <div style="font-family:'Sora',sans-serif;font-size:28px;font-weight:900;color:#111827;margin-bottom:16px;">
                <?php if($plan->price_monthly == 0): ?> Free
                <?php else: ?> $<?php echo e(number_format($plan->price_monthly, 0)); ?><span style="font-size:14px;font-weight:400;color:#9CA3AF;">/mo</span>
                <?php endif; ?>
            </div>

            <div style="display:flex;flex-direction:column;gap:7px;margin-bottom:20px;">
                <?php
                $planFeatureList = [
                    $plan->listings_limit_display . ' listings/month',
                    $plan->amazon_publish ? 'SP-API publish ✓' : 'SP-API publish ✗',
                    $plan->bulk_import ? 'Bulk import ✓' : null,
                    $plan->team_accounts ? 'Team accounts ✓' : null,
                    $plan->api_access ? 'API access ✓' : null,
                ];
                ?>
                <?php $__currentLoopData = array_filter($planFeatureList); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="font-size:12.5px;color:<?php echo e(str_ends_with($f, '✓') ? '#374151' : '#CBD5E1'); ?>;display:flex;gap:6px;">
                    <i class="bi <?php echo e(str_ends_with($f, '✓') ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted'); ?>" style="font-size:13px;margin-top:1px;flex-shrink:0;"></i>
                    <?php echo e(str_replace([' ✓', ' ✗'], '', $f)); ?>

                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <div style="border-top:1px solid #F3F4F6;padding-top:14px;display:flex;flex-direction:column;gap:8px;">
                <div style="font-size:12px;color:#9CA3AF;display:flex;justify-content:space-between;">
                    <span>Users on this plan:</span>
                    <strong style="color:#374151;"><?php echo e($plan->users->count()); ?></strong>
                </div>
                <a href="<?php echo e(route('admin.plans.edit', $plan->id)); ?>" class="btn-alb-primary btn text-center" style="font-size:13px;padding:9px;">
                    <i class="bi bi-pencil me-1"></i>Edit Plan
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH S:\Devotion\www\laravel\amazon-ai-listing-builder\resources\views/admin/plans/index.blade.php ENDPATH**/ ?>