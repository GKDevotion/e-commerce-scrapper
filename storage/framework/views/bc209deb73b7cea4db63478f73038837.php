<?php $__env->startSection('title', 'Plans & Billing'); ?>
<?php $__env->startSection('page-title', 'Plans & Billing'); ?>

<?php $__env->startSection('content'); ?>
<?php
$currentPlan = auth()->user()->plan;
$plans = \App\Models\Plan::where('is_active', true)->orderBy('sort_order')->get();
?>

<div class="text-center mb-5 fade-in-up">
    <h2 style="font-family:'Sora',sans-serif;font-size:28px;font-weight:800;color:#111827;margin-bottom:10px;">
        Choose Your Plan
    </h2>
    <p style="color:#6B7280;font-size:15px;max-width:480px;margin:0 auto;">
        Scale your Amazon listing business with AI. Upgrade anytime, cancel whenever.
    </p>
    <!-- Billing toggle -->
    <div style="display:inline-flex;align-items:center;gap:12px;margin-top:20px;background:#F3F4F6;border-radius:99px;padding:4px 6px;">
        <button id="btnMonthly" onclick="setBilling('monthly')" style="padding:8px 20px;border-radius:99px;font-size:13.5px;font-weight:700;border:none;cursor:pointer;background:#E31837;color:white;transition:all 0.15s;">Monthly</button>
        <button id="btnYearly" onclick="setBilling('yearly')" style="padding:8px 20px;border-radius:99px;font-size:13.5px;font-weight:700;border:none;cursor:pointer;background:transparent;color:#6B7280;transition:all 0.15s;">
            Yearly <span style="background:#D1FAE5;color:#065F46;font-size:10px;font-weight:700;padding:2px 6px;border-radius:20px;margin-left:4px;">Save 20%</span>
        </button>
    </div>
</div>

<div class="row g-4 justify-content-center fade-in-up fade-in-up-delay-1">
    <?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
    $isCurrent = $currentPlan?->id === $plan->id;
    $isFeatured = $plan->is_featured;
    ?>
    <div class="col-md-6 col-lg-3">
        <div style="
            background:white;
            border-radius:16px;
            border:<?php echo e($isFeatured ? '2px solid #E31837' : '1.5px solid #E5E7EB'); ?>;
            position:relative;
            height:100%;
            display:flex;
            flex-direction:column;
            overflow:hidden;
            transition:transform 0.2s,box-shadow 0.2s;
        " onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 40px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='none'">

            <?php if($isFeatured): ?>
            <div style="background:linear-gradient(135deg,#E31837,#b01028);color:white;text-align:center;padding:8px;font-size:11.5px;font-weight:800;letter-spacing:0.05em;text-transform:uppercase;">
                ⭐ Most Popular
            </div>
            <?php endif; ?>

            <div style="padding:28px 24px;flex:1;display:flex;flex-direction:column;">
                <!-- Plan name -->
                <div style="font-family:'Sora',sans-serif;font-size:18px;font-weight:800;color:#111827;margin-bottom:4px;"><?php echo e($plan->name); ?></div>
                <div style="font-size:13px;color:#6B7280;margin-bottom:20px;min-height:36px;"><?php echo e($plan->description); ?></div>

                <!-- Price -->
                <div style="margin-bottom:24px;">
                    <div id="price-monthly-<?php echo e($plan->id); ?>" style="display:flex;align-items:baseline;gap:4px;">
                        <span style="font-family:'Sora',sans-serif;font-size:36px;font-weight:800;color:#111827;">
                            <?php if($plan->price_monthly == 0): ?> Free
                            <?php else: ?> $<?php echo e(number_format($plan->price_monthly, 0)); ?>

                            <?php endif; ?>
                        </span>
                        <?php if($plan->price_monthly > 0): ?>
                        <span style="color:#9CA3AF;font-size:14px;">/month</span>
                        <?php endif; ?>
                    </div>
                    <div id="price-yearly-<?php echo e($plan->id); ?>" style="display:none;align-items:baseline;gap:4px;">
                        <span style="font-family:'Sora',sans-serif;font-size:36px;font-weight:800;color:#111827;">
                            <?php if($plan->price_yearly == 0): ?> Free
                            <?php else: ?> $<?php echo e(number_format($plan->price_yearly / 12, 0)); ?>

                            <?php endif; ?>
                        </span>
                        <?php if($plan->price_yearly > 0): ?>
                        <span style="color:#9CA3AF;font-size:14px;">/month</span>
                        <?php endif; ?>
                    </div>
                    <?php if($plan->price_yearly > 0): ?>
                    <div id="yearly-note-<?php echo e($plan->id); ?>" style="display:none;font-size:12px;color:#10B981;font-weight:600;margin-top:4px;">
                        Billed $<?php echo e(number_format($plan->price_yearly, 0)); ?>/year
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Features -->
                <div style="flex:1;margin-bottom:24px;display:flex;flex-direction:column;gap:8px;">
                    <?php
                    $features = [
                        [($plan->listings_limit === -1 ? 'Unlimited' : $plan->listings_limit) . ' AI-generated listings', true],
                        [($plan->ai_generations_limit === -1 ? 'Unlimited' : $plan->ai_generations_limit) . ' AI generations/month', true],
                        ['CSV & JSON export', true],
                        ['Excel & PDF export', $plan->slug !== 'free'],
                        ['Amazon Flat File', in_array($plan->slug, ['pro','enterprise'])],
                        ['Amazon SP-API publish', $plan->amazon_publish],
                        ['Bulk URL import', $plan->bulk_import],
                        ['Team accounts', $plan->team_accounts],
                        ['Priority support', $plan->priority_support],
                        ['API access', $plan->api_access],
                    ];
                    ?>
                    <?php $__currentLoopData = $features; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$feat, $included]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:<?php echo e($included ? '#374151' : '#CBD5E1'); ?>;">
                        <i class="bi <?php echo e($included ? 'bi-check-circle-fill' : 'bi-x-circle'); ?>" style="color:<?php echo e($included ? '#10B981' : '#D1D5DB'); ?>;flex-shrink:0;font-size:15px;"></i>
                        <?php echo e($feat); ?>

                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <!-- CTA -->
                <?php if($isCurrent): ?>
                <div style="text-align:center;padding:12px;background:#F9FAFB;border-radius:10px;font-size:13.5px;font-weight:700;color:#6B7280;border:1.5px solid #E5E7EB;">
                    <i class="bi bi-check-circle-fill me-2" style="color:#10B981;"></i>Current Plan
                </div>
                <?php elseif($plan->isFree()): ?>
                <div style="text-align:center;padding:12px;background:#F9FAFB;border-radius:10px;font-size:13.5px;font-weight:600;color:#9CA3AF;">
                    Default Plan
                </div>
                <?php else: ?>
                <form method="POST" action="<?php echo e(route('billing.subscribe', $plan->id)); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="billing_cycle" id="cycle-<?php echo e($plan->id); ?>" value="monthly">
                    <button type="submit" style="
                        width:100%;
                        background:<?php echo e($isFeatured ? 'linear-gradient(135deg,#E31837,#b01028)' : 'white'); ?>;
                        color:<?php echo e($isFeatured ? 'white' : '#E31837'); ?>;
                        border:<?php echo e($isFeatured ? 'none' : '2px solid #E31837'); ?>;
                        padding:13px;
                        border-radius:10px;
                        font-size:14px;
                        font-weight:700;
                        font-family:'Sora',sans-serif;
                        cursor:pointer;
                        transition:all 0.15s;
                    " onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                        Get <?php echo e($plan->name); ?>

                        <?php if($currentPlan && $plan->price_monthly > ($currentPlan->price_monthly ?? 0)): ?>
                        — Upgrade
                        <?php elseif($currentPlan && $plan->price_monthly < ($currentPlan->price_monthly ?? 0)): ?>
                        — Downgrade
                        <?php endif; ?>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<!-- FAQ -->
<div class="row g-4 mt-5 fade-in-up">
    <div class="col-12">
        <h3 style="font-family:'Sora',sans-serif;font-size:20px;font-weight:700;text-align:center;margin-bottom:32px;">Frequently Asked Questions</h3>
    </div>
    <?php
    $faqs = [
        ['Can I cancel anytime?', 'Yes, cancel anytime from your account settings. You keep access until the end of your billing period.'],
        ['Is the generated content truly unique?', 'Yes! Our AI rewrites all content from scratch, preserving only factual product data. No copied text.'],
        ['What payment methods do you accept?', 'We accept all major cards via Stripe, and UPI/NetBanking via Razorpay for Indian sellers.'],
        ['Does it work for all Amazon marketplaces?', 'Yes — Amazon.com, Amazon.in, Amazon.co.uk, Amazon.de, and 10+ other marketplaces.'],
    ];
    ?>
    <?php $__currentLoopData = $faqs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$q, $a]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="col-md-6">
        <div class="alb-card">
            <div style="font-size:14px;font-weight:700;color:#111827;margin-bottom:8px;"><?php echo e($q); ?></div>
            <div style="font-size:13.5px;color:#6B7280;line-height:1.6;"><?php echo e($a); ?></div>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
let billingMode = 'monthly';
function setBilling(mode) {
    billingMode = mode;
    document.getElementById('btnMonthly').style.background = mode === 'monthly' ? '#E31837' : 'transparent';
    document.getElementById('btnMonthly').style.color = mode === 'monthly' ? 'white' : '#6B7280';
    document.getElementById('btnYearly').style.background = mode === 'yearly' ? '#E31837' : 'transparent';
    document.getElementById('btnYearly').style.color = mode === 'yearly' ? 'white' : '#6B7280';

    document.querySelectorAll('[id^="price-monthly-"]').forEach(el => el.style.display = mode === 'monthly' ? 'flex' : 'none');
    document.querySelectorAll('[id^="price-yearly-"]').forEach(el => el.style.display = mode === 'yearly' ? 'flex' : 'none');
    document.querySelectorAll('[id^="yearly-note-"]').forEach(el => el.style.display = mode === 'yearly' ? 'block' : 'none');
    document.querySelectorAll('[id^="cycle-"]').forEach(el => el.value = mode);
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH S:\Devotion\www\laravel\amazon-ai-listing-builder\resources\views/billing/plans.blade.php ENDPATH**/ ?>