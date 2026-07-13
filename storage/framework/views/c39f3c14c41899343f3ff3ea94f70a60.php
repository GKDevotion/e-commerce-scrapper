<?php $__env->startSection('title', 'User Management'); ?>
<?php $__env->startSection('page-title', 'User Management'); ?>

<?php $__env->startSection('topbar-actions'); ?>
<a href="<?php echo e(route('admin.dashboard')); ?>" class="topbar-btn" style="background:#374151;">
    <i class="bi bi-arrow-left"></i> Admin
</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<!-- Filters -->
<div class="alb-card mb-4 fade-in-up" style="padding:14px 20px;">
    <form method="GET" action="<?php echo e(route('admin.users')); ?>" class="d-flex align-items-center gap-3 flex-wrap">
        <div style="position:relative;flex:1;min-width:200px;">
            <i class="bi bi-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9CA3AF;"></i>
            <input type="text" name="search" class="alb-input" style="padding-left:36px;padding-top:8px;padding-bottom:8px;" placeholder="Search name, email, company..." value="<?php echo e(request('search')); ?>">
        </div>
        <select name="status" class="alb-input" style="width:auto;padding:8px 14px;">
            <option value="">All Status</option>
            <option value="active" <?php echo e(request('status') === 'active' ? 'selected' : ''); ?>>Active</option>
            <option value="suspended" <?php echo e(request('status') === 'suspended' ? 'selected' : ''); ?>>Suspended</option>
            <option value="pending" <?php echo e(request('status') === 'pending' ? 'selected' : ''); ?>>Pending</option>
        </select>
        <select name="plan_id" class="alb-input" style="width:auto;padding:8px 14px;">
            <option value="">All Plans</option>
            <?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($plan->id); ?>" <?php echo e(request('plan_id') == $plan->id ? 'selected' : ''); ?>><?php echo e($plan->name); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button type="submit" class="btn-alb-primary btn" style="padding:9px 20px;">
            <i class="bi bi-funnel me-1"></i>Filter
        </button>
        <?php if(request()->hasAny(['search','status','plan_id'])): ?>
        <a href="<?php echo e(route('admin.users')); ?>" class="btn-alb-outline btn" style="padding:9px 16px;">Clear</a>
        <?php endif; ?>
        <span style="font-size:13px;color:#9CA3AF;white-space:nowrap;"><?php echo e($users->total()); ?> users</span>
    </form>
</div>

<!-- Users Table -->
<div class="alb-card fade-in-up fade-in-up-delay-1" style="padding:0;overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="alb-table" style="min-width:900px;">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Plan</th>
                    <th>Usage</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <img src="<?php echo e($user->avatar_url); ?>" style="width:38px;height:38px;border-radius:50%;object-fit:cover;" alt="">
                            <div>
                                <div style="font-size:13.5px;font-weight:700;color:#111827;">
                                    <?php echo e($user->name); ?>

                                    <?php if($user->role === 'admin'): ?>
                                    <span style="background:#FEF3C7;color:#92400E;font-size:10px;font-weight:700;padding:2px 6px;border-radius:4px;margin-left:4px;">ADMIN</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:12px;color:#9CA3AF;"><?php echo e($user->email); ?></div>
                                <?php if($user->company_name): ?>
                                <div style="font-size:11px;color:#CBD5E1;"><?php echo e($user->company_name); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span style="font-size:12.5px;font-weight:600;color:#374151;"><?php echo e($user->plan?->name ?? 'Free'); ?></span>
                    </td>
                    <td>
                        <div style="font-size:12px;color:#6B7280;">
                            <?php echo e($user->listings_used); ?> listings
                        </div>
                        <div style="font-size:11px;color:#CBD5E1;"><?php echo e($user->ai_generations_used); ?> AI gens</div>
                    </td>
                    <td>
                        <?php if($user->status === 'active'): ?>
                        <span style="background:#D1FAE5;color:#065F46;font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:20px;">Active</span>
                        <?php elseif($user->status === 'suspended'): ?>
                        <span style="background:#FEE2E2;color:#991B1B;font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:20px;">Suspended</span>
                        <?php else: ?>
                        <span style="background:#FEF3C7;color:#92400E;font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:20px;"><?php echo e(ucfirst($user->status)); ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12.5px;color:#6B7280;"><?php echo e($user->created_at->format('M d, Y')); ?></td>
                    <td style="font-size:12px;color:#9CA3AF;">
                        <?php echo e($user->last_login_at?->diffForHumans() ?? 'Never'); ?>

                    </td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;">
                            <a href="<?php echo e(route('admin.users.show', $user->id)); ?>" style="background:#F3F4F6;border:none;color:#374151;padding:6px 12px;border-radius:7px;font-size:12px;font-weight:600;text-decoration:none;transition:background 0.15s;" onmouseover="this.style.background='#E5E7EB'" onmouseout="this.style.background='#F3F4F6'">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if($user->status === 'active' && $user->role !== 'admin'): ?>
                            <form method="POST" action="<?php echo e(route('admin.users.suspend', $user->id)); ?>" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" style="background:#FEF3C7;border:none;color:#92400E;padding:6px 12px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;" onclick="return confirm('Suspend <?php echo e($user->name); ?>?')">
                                    <i class="bi bi-slash-circle"></i>
                                </button>
                            </form>
                            <?php elseif($user->status === 'suspended'): ?>
                            <form method="POST" action="<?php echo e(route('admin.users.activate', $user->id)); ?>" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" style="background:#D1FAE5;border:none;color:#065F46;padding:6px 12px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if(auth()->id() !== $user->id && $user->role !== 'admin'): ?>
                            <form method="POST" action="<?php echo e(route('admin.users.delete', $user->id)); ?>" class="d-inline">
                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                <button type="submit" style="background:#FEE2E2;border:none;color:#991B1B;padding:6px 12px;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;" onclick="return confirm('Permanently delete <?php echo e($user->name); ?>? This cannot be undone.')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:#9CA3AF;">
                        <i class="bi bi-people" style="font-size:32px;display:block;margin-bottom:10px;"></i>
                        No users found matching your filters.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if($users->hasPages()): ?>
    <div style="padding:16px 20px;border-top:1px solid #F3F4F6;">
        <?php echo e($users->links('pagination::bootstrap-5')); ?>

    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH S:\Devotion\www\laravel\amazon-ai-listing-builder\resources\views/admin/users/index.blade.php ENDPATH**/ ?>