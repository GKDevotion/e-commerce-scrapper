<?php $__env->startSection('title', 'AI Generated Listing'); ?>
<?php $__env->startSection('page-title', 'Listing Comparison'); ?>

<?php $__env->startSection('topbar-actions'); ?>
<a href="<?php echo e(route('listings.create')); ?>" class="topbar-btn">
    <i class="bi bi-plus-lg"></i> New
</a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $import = $generation->productImport;
    $originalBullets = $import->original_bullet_points ?? [];
    $generatedBullets = $generation->generated_bullet_points ?? [];
?>

<!-- Header row -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 fade-in-up">
    <div>
        <div style="font-size:12.5px;color:#9CA3AF;margin-bottom:4px;">
            <a href="<?php echo e(route('listings.index')); ?>" style="color:#9CA3AF;text-decoration:none;">My Listings</a>
            <span class="mx-1">›</span>
            <a href="<?php echo e(route('listings.show', $import->id)); ?>" style="color:#9CA3AF;text-decoration:none;">Import #<?php echo e($import->id); ?></a>
            <span class="mx-1">›</span>
            <span style="color:#111827;">Generated Listing</span>
        </div>
        <h2 style="font-family:'Sora',sans-serif;font-size:18px;font-weight:700;color:#111827;margin:0;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <?php echo e(Str::limit($generation->generated_title ?? 'Listing', 60)); ?>

            <?php if($generation->isManual()): ?>
            <span style="background:#FEF3C7;color:#92400E;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;white-space:nowrap;">
                <i class="bi bi-pencil me-1"></i>Manual
            </span>
            <?php else: ?>
            <span style="background:#FEE2E8;color:#E31837;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;white-space:nowrap;">
                <i class="bi bi-stars me-1"></i>AI Generated
            </span>
            <?php endif; ?>
        </h2>
        <div style="font-size:12px;color:#9CA3AF;margin-top:4px;">
            Brand: <strong style="color:#E31837;"><?php echo e($generation->brand_name); ?></strong> •
            <?php echo e($generation->isManual() ? 'Created' : 'Generated'); ?> <?php echo e($generation->generated_at?->diffForHumans()); ?>

            <?php if($generation->isAi() && $generation->total_tokens): ?>
            • <?php echo e(number_format($generation->total_tokens)); ?> tokens
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <form method="POST" action="<?php echo e(route('generations.favorite', $generation->id)); ?>" class="d-inline">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn-alb-outline btn" style="<?php echo e($generation->is_favorite ? 'color:#F59E0B;border-color:#F59E0B;' : ''); ?>">
                <i class="bi <?php echo e($generation->is_favorite ? 'bi-star-fill' : 'bi-star'); ?>"></i>
                <?php echo e($generation->is_favorite ? 'Saved' : 'Save'); ?>

            </button>
        </form>
        <a href="<?php echo e(route('generations.edit', $generation->id)); ?>" class="btn-alb-outline btn">
            <i class="bi bi-pencil-square"></i> Edit
        </a>
        <?php if($generation->isAi()): ?>
        <form method="POST" action="<?php echo e(route('generations.generate', $import->id)); ?>" class="d-inline">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn-alb-outline btn">
                <i class="bi bi-arrow-clockwise"></i> Regenerate
            </button>
        </form>
        <?php endif; ?>
        <button type="button" class="btn-alb-primary btn" onclick="document.getElementById('exportPanel').scrollIntoView({behavior:'smooth'})">
            <i class="bi bi-download me-1"></i> Export
        </button>
    </div>
</div>

<!-- Comparison Toggle -->
<div class="alb-card mb-4 fade-in-up fade-in-up-delay-1" style="padding:12px 16px;">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span style="font-size:13px;font-weight:600;color:#374151;">View:</span>
        <button onclick="setView('split')" id="btn-split" class="btn btn-sm" style="font-size:12.5px;font-weight:600;border-radius:6px;padding:5px 14px;background:#E31837;color:white;border:none;">
            Side-by-Side
        </button>
        <button onclick="setView('generated')" id="btn-generated" class="btn btn-sm" style="font-size:12.5px;font-weight:600;border-radius:6px;padding:5px 14px;background:#F3F4F6;color:#374151;border:none;">
            Generated Only
        </button>
        <button onclick="setView('original')" id="btn-original" class="btn btn-sm" style="font-size:12.5px;font-weight:600;border-radius:6px;padding:5px 14px;background:#F3F4F6;color:#374151;border:none;">
            Original Only
        </button>
        <div class="ms-auto" style="font-size:12px;color:#9CA3AF;">
            <i class="bi bi-info-circle me-1"></i>Click any field to copy it
        </div>
    </div>
</div>

<!-- COMPARISON LAYOUT -->
<div id="comparisonLayout" class="row g-0 fade-in-up">

    <!-- Original Column -->
    <div id="originalCol" class="col-md-6" style="padding-right:2px;">
        <div style="background:#F9FAFB;border:1.5px solid #E5E7EB;border-radius:14px 0 0 14px;height:100%;padding:0;">
            <div style="background:#374151;color:white;padding:14px 20px;border-radius:12px 0 0 0;display:flex;align-items:center;gap:8px;">
                <i class="bi bi-amazon" style="font-size:16px;"></i>
                <span style="font-size:13px;font-weight:700;">Original Amazon Listing</span>
                <?php if($import->original_brand): ?>
                <span style="background:rgba(255,255,255,0.15);font-size:11px;padding:2px 8px;border-radius:20px;margin-left:auto;"><?php echo e($import->original_brand); ?></span>
                <?php endif; ?>
            </div>
            <div style="padding:20px;">
                <?php echo $__env->make('listings.partials.listing-column', [
                    'title' => $import->original_title,
                    'brand' => $import->original_brand,
                    'manufacturer' => $import->original_manufacturer,
                    'bullets' => $originalBullets,
                    'description' => $import->original_description,
                    'search_terms' => null,
                    'seo_keywords' => null,
                    'highlights' => null,
                    'type' => 'original'
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
    </div>

    <!-- Generated Column -->
    <div id="generatedCol" class="col-md-6" style="padding-left:2px;">
        <div style="background:white;border:1.5px solid #E31837;border-radius:0 14px 14px 0;height:100%;padding:0;">
            <div style="background:linear-gradient(135deg,#E31837,#b01028);color:white;padding:14px 20px;border-radius:0 12px 0 0;display:flex;align-items:center;gap:8px;">
                <i class="bi bi-stars" style="font-size:16px;"></i>
                <span style="font-size:13px;font-weight:700;">AI Generated Listing</span>
                <span style="background:rgba(255,255,255,0.2);font-size:11px;padding:2px 8px;border-radius:20px;margin-left:auto;"><?php echo e($generation->brand_name); ?></span>
            </div>
            <div style="padding:20px;">
                <?php echo $__env->make('listings.partials.listing-column', [
                    'title' => $generation->generated_title,
                    'brand' => $generation->brand_name,
                    'manufacturer' => $generation->manufacturer,
                    'bullets' => $generatedBullets,
                    'description' => $generation->generated_description,
                    'search_terms' => $generation->generated_search_terms,
                    'seo_keywords' => $generation->generated_seo_keywords,
                    'highlights' => $generation->generated_highlights,
                    'type' => 'generated'
                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
    </div>
</div>

<!-- A+ Content -->
<?php if($generation->generated_aplus_content): ?>
<div class="alb-card mt-4 fade-in-up">
    <div class="alb-card-header">
        <h3 class="alb-card-title"><i class="bi bi-layout-text-window me-2" style="color:#8B5CF6;"></i>Suggested A+ Content</h3>
        <button onclick="copyText('<?php echo e(addslashes($generation->generated_aplus_content)); ?>')" class="btn-alb-outline btn" style="font-size:12px;padding:6px 14px;">
            <i class="bi bi-copy me-1"></i>Copy
        </button>
    </div>
    <div style="background:#F5F3FF;border-radius:10px;padding:16px;font-size:13.5px;color:#374151;line-height:1.7;white-space:pre-wrap;"><?php echo e($generation->generated_aplus_content); ?></div>
</div>
<?php endif; ?>

<!-- Product Images -->
<?php $productImages = $import->original_images ?? []; ?>
<?php if(!empty($productImages)): ?>
<div class="alb-card mt-4 fade-in-up">
    <div class="alb-card-header">
        <h3 class="alb-card-title"><i class="bi bi-images me-2" style="color:#3B82F6;"></i>Product Images (<?php echo e(count($productImages)); ?>)</h3>
        <div class="d-flex gap-2">
            <a href="<?php echo e(route('generations.images', $generation->id)); ?>" class="btn-alb-outline btn" style="font-size:12.5px;padding:7px 16px;">
                <i class="bi bi-grid me-1"></i>View Gallery
            </a>
            <a href="<?php echo e(route('export.images.zip', $generation->id)); ?>" class="btn-alb-primary btn" style="font-size:12.5px;padding:8px 18px;">
                <i class="bi bi-file-earmark-zip me-1"></i>Download All as ZIP
            </a>
        </div>
    </div>
    <p style="font-size:13px;color:#6B7280;margin-bottom:18px;">
        Original images scraped from the Amazon listing, renamed to <strong><?php echo e(Str::slug($generation->brand_name ?: 'product')); ?>-<?php echo e($import->asin ?? 'product'); ?>-NN</strong> format. Hover an image to download it individually.
    </p>
    <div class="row g-3">
        <?php $__currentLoopData = $productImages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $imgUrl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-6 col-md-3 col-lg-2">
            <div style="position:relative;border-radius:10px;overflow:hidden;border:1.5px solid #E5E7EB;aspect-ratio:1;background:#F9FAFB;" class="image-card">
                <img src="<?php echo e($imgUrl); ?>" alt="Product image <?php echo e($i + 1); ?>" style="width:100%;height:100%;object-fit:cover;" onerror="this.parentElement.innerHTML='<div style=&quot;display:flex;align-items:center;justify-content:center;height:100%;color:#D1D5DB;font-size:24px;&quot;><i class=&quot;bi bi-image&quot;></i></div>'">
                <a href="<?php echo e(route('export.images.single', [$generation->id, $i])); ?>" class="image-download-overlay" title="Download image <?php echo e($i + 1); ?>">
                    <i class="bi bi-download"></i>
                </a>
                <span style="position:absolute;top:6px;left:6px;background:rgba(0,0,0,0.6);color:white;font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;"><?php echo e($i + 1); ?></span>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php else: ?>
<div class="alb-card mt-4 fade-in-up">
    <h3 class="alb-card-title mb-2"><i class="bi bi-images me-2" style="color:#9CA3AF;"></i>Product Images</h3>
    <div style="text-align:center;padding:24px;color:#9CA3AF;font-size:13px;">
        <i class="bi bi-image" style="font-size:28px;display:block;margin-bottom:8px;opacity:0.5;"></i>
        No images were found for this product during scraping.
    </div>
</div>
<?php endif; ?>

<!-- Export Panel -->
<div class="alb-card mt-4 fade-in-up" id="exportPanel">
    <div class="alb-card-header">
        <h3 class="alb-card-title"><i class="bi bi-download me-2" style="color:#E31837;"></i>Export Listing</h3>
    </div>
    <p style="font-size:14px;color:#6B7280;margin-bottom:20px;">Download your generated listing in your preferred format.</p>
    <div class="row g-3">
        <?php
        $formats = [
            ['csv', 'CSV File', 'bi-filetype-csv', '#10B981', 'Standard comma-separated values', 'Universal format for any tool'],
            ['excel', 'Excel (.xlsx)', 'bi-file-earmark-spreadsheet', '#1D6F42', 'Open in Microsoft Excel', 'Spreadsheet with formatting'],
            ['amazon_flat_file', 'Amazon Flat File', 'bi-amazon', '#FF9900', 'Ready for Seller Central upload', 'Tab-separated bulk upload'],
            ['json', 'JSON', 'bi-braces', '#3B82F6', 'Developer-friendly format', 'API integration ready'],
            ['pdf', 'PDF Report', 'bi-file-earmark-pdf', '#EF4444', 'Print-ready document', 'Share with team/clients'],
        ];
        ?>
        <?php $__currentLoopData = $formats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$fmt, $label, $icon, $color, $desc, $sub]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-6 col-md-4 col-lg-2">
            <form method="POST" action="<?php echo e(route('export', $generation->id)); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="format" value="<?php echo e($fmt); ?>">
                <button type="submit" style="width:100%;background:white;border:1.5px solid #E5E7EB;border-radius:12px;padding:16px 12px;cursor:pointer;text-align:center;transition:all 0.15s;" onmouseover="this.style.borderColor='<?php echo e($color); ?>';this.style.background='#FAFAFA'" onmouseout="this.style.borderColor='#E5E7EB';this.style.background='white'">
                    <i class="<?php echo e($icon); ?>" style="font-size:28px;color:<?php echo e($color); ?>;display:block;margin-bottom:8px;"></i>
                    <div style="font-size:12.5px;font-weight:700;color:#111827;margin-bottom:3px;"><?php echo e($label); ?></div>
                    <div style="font-size:11px;color:#9CA3AF;"><?php echo e($sub); ?></div>
                </button>
            </form>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        
        <?php if(!empty($import->original_images)): ?>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="<?php echo e(route('export.images.zip', $generation->id)); ?>" style="display:block;width:100%;background:white;border:1.5px solid #E5E7EB;border-radius:12px;padding:16px 12px;cursor:pointer;text-align:center;transition:all 0.15s;text-decoration:none;" onmouseover="this.style.borderColor='#3B82F6';this.style.background='#FAFAFA'" onmouseout="this.style.borderColor='#E5E7EB';this.style.background='white'">
                <i class="bi bi-file-earmark-zip" style="font-size:28px;color:#3B82F6;display:block;margin-bottom:8px;"></i>
                <div style="font-size:12.5px;font-weight:700;color:#111827;margin-bottom:3px;">Images ZIP</div>
                <div style="font-size:11px;color:#9CA3AF;"><?php echo e(count($import->original_images)); ?> image(s) packaged</div>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Copied Toast -->
<div id="copyToast" style="position:fixed;bottom:24px;right:24px;background:#111827;color:white;padding:12px 20px;border-radius:10px;font-size:13px;font-weight:600;display:none;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,0.3);">
    <i class="bi bi-check-circle me-2" style="color:#10B981;"></i>Copied to clipboard!
</div>

<?php $__env->startPush('styles'); ?>
<style>
.listing-field {
    cursor: pointer;
    transition: background 0.15s;
    border-radius: 8px;
    padding: 10px 12px;
    margin-bottom: 16px;
    border: 1.5px solid transparent;
}
.listing-field:hover { background: #FFF8F0; border-color: #FED7AA; }
.field-label {
    font-size: 10.5px; font-weight: 800; letter-spacing: 0.08em;
    text-transform: uppercase; color: #9CA3AF; margin-bottom: 6px;
    display: flex; align-items: center; gap: 6px;
}
.field-value { font-size: 13.5px; color: #374151; line-height: 1.6; }
.generated .field-value { color: #111827; }

.image-download-overlay {
    position: absolute;
    inset: 0;
    background: rgba(227,24,55,0.0);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 22px;
    opacity: 0;
    transition: all 0.2s;
    text-decoration: none;
}
.image-card:hover .image-download-overlay {
    background: rgba(227,24,55,0.55);
    opacity: 1;
}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function copyText(text) {
    navigator.clipboard.writeText(text).then(() => showToast());
}
function copyField(el) {
    const text = el.querySelector('.field-value')?.innerText || '';
    navigator.clipboard.writeText(text).then(() => showToast());
}
function showToast() {
    const toast = document.getElementById('copyToast');
    toast.style.display = 'flex';
    toast.style.alignItems = 'center';
    setTimeout(() => { toast.style.display = 'none'; }, 2000);
}

// Document click to copy
document.querySelectorAll('.listing-field').forEach(el => {
    el.addEventListener('click', () => copyField(el));
});

function setView(mode) {
    const orig = document.getElementById('originalCol');
    const gen = document.getElementById('generatedCol');
    const btnSplit = document.getElementById('btn-split');
    const btnGen = document.getElementById('btn-generated');
    const btnOrig = document.getElementById('btn-original');
    const layout = document.getElementById('comparisonLayout');

    [btnSplit, btnGen, btnOrig].forEach(b => {
        b.style.background = '#F3F4F6';
        b.style.color = '#374151';
    });

    if (mode === 'split') {
        orig.style.display = '';
        gen.style.display = '';
        orig.className = 'col-md-6';
        gen.className = 'col-md-6';
        orig.querySelector('div').style.borderRadius = '14px 0 0 14px';
        gen.querySelector('div').style.borderRadius = '0 14px 14px 0';
        btnSplit.style.background = '#E31837';
        btnSplit.style.color = 'white';
    } else if (mode === 'generated') {
        orig.style.display = 'none';
        gen.style.display = '';
        gen.className = 'col-12';
        gen.querySelector('div').style.borderRadius = '14px';
        btnGen.style.background = '#E31837';
        btnGen.style.color = 'white';
    } else {
        gen.style.display = 'none';
        orig.style.display = '';
        orig.className = 'col-12';
        orig.querySelector('div').style.borderRadius = '14px';
        btnOrig.style.background = '#E31837';
        btnOrig.style.color = 'white';
    }
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH S:\Devotion\www\laravel\amazon-ai-listing-builder\resources\views/listings/generation.blade.php ENDPATH**/ ?>