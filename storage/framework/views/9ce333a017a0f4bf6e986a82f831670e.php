<?php $__env->startSection('title', 'Analytics'); ?>
<?php $__env->startSection('page-title', 'Analytics'); ?>

<?php $__env->startSection('content'); ?>
<div class="row g-4">

    <!-- API Usage by Service -->
    <div class="col-lg-6 fade-in-up">
        <div class="alb-card">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-cloud me-2" style="color:#E31837;"></i>API Usage by Service</h3>
            </div>
            <?php if($apiLogs->isEmpty()): ?>
            <div style="text-align:center;padding:32px;color:#9CA3AF;font-size:13px;">No API logs yet.</div>
            <?php else: ?>
            <table class="alb-table">
                <thead>
                    <tr><th>Service</th><th>Total Calls</th><th>Avg Response</th><th>Health</th></tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $apiLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <?php
                                $serviceIcons = ['openai' => ['bi-cpu','#8B5CF6'], 'amazon' => ['bi-amazon','#FF9900'], 'scraper' => ['bi-cloud-download','#3B82F6'], 'razorpay' => ['bi-credit-card','#3395FF'], 'stripe' => ['bi-stripe','#6772E5']];
                                $si = $serviceIcons[$log->service] ?? ['bi-gear','#9CA3AF'];
                                ?>
                                <i class="bi <?php echo e($si[0]); ?>" style="color:<?php echo e($si[1]); ?>;font-size:18px;"></i>
                                <span style="font-size:13.5px;font-weight:600;color:#111827;text-transform:capitalize;"><?php echo e($log->service); ?></span>
                            </div>
                        </td>
                        <td style="font-size:13.5px;font-weight:700;"><?php echo e(number_format($log->count)); ?></td>
                        <td style="font-size:13px;color:#6B7280;"><?php echo e(number_format($log->avg_time)); ?>ms</td>
                        <td>
                            <?php if($log->avg_time < 1000): ?>
                            <span style="background:#D1FAE5;color:#065F46;font-size:11px;font-weight:700;padding:3px 8px;border-radius:20px;">Fast</span>
                            <?php elseif($log->avg_time < 3000): ?>
                            <span style="background:#FEF3C7;color:#92400E;font-size:11px;font-weight:700;padding:3px 8px;border-radius:20px;">OK</span>
                            <?php else: ?>
                            <span style="background:#FEE2E2;color:#991B1B;font-size:11px;font-weight:700;padding:3px 8px;border-radius:20px;">Slow</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Revenue by Month -->
    <div class="col-lg-6 fade-in-up fade-in-up-delay-1">
        <div class="alb-card">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-currency-dollar me-2" style="color:#10B981;"></i>Revenue by Month</h3>
            </div>
            <div style="height:240px;position:relative;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Users -->
    <div class="col-12 fade-in-up fade-in-up-delay-2">
        <div class="alb-card">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-trophy me-2" style="color:#F59E0B;"></i>Top Users by AI Generations</h3>
            </div>
            <?php if($topUsers->isEmpty()): ?>
            <div style="text-align:center;padding:32px;color:#9CA3AF;">No data available yet.</div>
            <?php else: ?>
            <div class="row g-3">
                <?php $__currentLoopData = $topUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $topUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-md-6 col-lg-4">
                    <div style="display:flex;align-items:center;gap:12px;padding:14px;background:#F9FAFB;border-radius:10px;">
                        <div style="width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0;
                            background:<?php echo e($i === 0 ? '#FEF3C7' : ($i === 1 ? '#F3F4F6' : '#FFF7ED')); ?>;
                            color:<?php echo e($i === 0 ? '#92400E' : ($i === 1 ? '#374151' : '#C2410C')); ?>;">
                            <?php echo e($i + 1); ?>

                        </div>
                        <img src="<?php echo e($topUser->avatar_url); ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;" alt="">
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13px;font-weight:700;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($topUser->name); ?></div>
                            <div style="font-size:11.5px;color:#9CA3AF;"><?php echo e($topUser->plan?->name ?? 'Free'); ?></div>
                        </div>
                        <div style="text-align:right;flex-shrink:0;">
                            <div style="font-family:'Sora',sans-serif;font-size:18px;font-weight:800;color:#E31837;"><?php echo e($topUser->ai_generations_count); ?></div>
                            <div style="font-size:11px;color:#9CA3AF;">gens</div>
                        </div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const revData = <?php echo json_encode($revenueByMonth, 15, 512) ?>;
const labels = revData.map(r => {
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return months[r.month - 1] + ' ' + r.year;
});
const values = revData.map(r => parseFloat(r.total));

new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: labels.length ? labels : ['No data'],
        datasets: [{
            label: 'Revenue ($)',
            data: values.length ? values : [0],
            backgroundColor: 'rgba(227,24,55,0.7)',
            borderColor: '#E31837',
            borderWidth: 1,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#F3F4F6' }, ticks: { callback: v => '$' + v } },
            x: { grid: { display: false } }
        }
    }
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH S:\Devotion\www\laravel\amazon-ai-listing-builder\resources\views/admin/analytics.blade.php ENDPATH**/ ?>