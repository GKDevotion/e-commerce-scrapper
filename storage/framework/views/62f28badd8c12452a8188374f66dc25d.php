<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Amazon Listing Builder — AI-Powered Listing Generator for Sellers</title>
    <meta name="description" content="Import any Amazon product URL and generate unique, branded AI listings in seconds. Powered by GPT-4o.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sora:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --red: #E31837; --red-dark: #b01028; --black: #0a0a0a; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #0a0a0a; color: white; overflow-x: hidden; }

        /* NAV */
        nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            padding: 16px 48px;
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(10,10,10,0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .nav-logo-icon { width: 36px; height: 36px; background: var(--red); border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .nav-logo-text { font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700; color: white; line-height: 1.2; }
        .nav-logo-text span { color: var(--red); }
        .nav-links { display: flex; align-items: center; gap: 32px; }
        .nav-links a { color: rgba(255,255,255,0.65); text-decoration: none; font-size: 14px; font-weight: 500; transition: color 0.15s; }
        .nav-links a:hover { color: white; }
        .nav-cta { display: flex; gap: 10px; }
        .btn-nav-login { color: rgba(255,255,255,0.8); text-decoration: none; font-size: 14px; font-weight: 600; padding: 9px 20px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15); transition: all 0.15s; }
        .btn-nav-login:hover { border-color: rgba(255,255,255,0.4); color: white; }
        .btn-nav-signup { background: var(--red); color: white; text-decoration: none; font-size: 14px; font-weight: 700; padding: 9px 22px; border-radius: 8px; transition: all 0.15s; }
        .btn-nav-signup:hover { background: var(--red-dark); color: white; }

        /* HERO */
        .hero {
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            text-align: center;
            padding: 120px 20px 80px;
            position: relative;
            overflow: hidden;
        }
        .hero-bg {
            position: absolute; inset: 0; pointer-events: none;
            background: radial-gradient(ellipse 800px 600px at 50% 40%, rgba(227,24,55,0.12) 0%, transparent 70%);
        }
        .hero-grid {
            position: absolute; inset: 0;
            background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(227,24,55,0.15); border: 1px solid rgba(227,24,55,0.3);
            border-radius: 99px; padding: 6px 16px; margin-bottom: 28px;
            font-size: 13px; font-weight: 600; color: #ff6b6b;
            animation: fadeInUp 0.6s ease both;
        }
        .hero-title {
            font-family: 'Sora', sans-serif;
            font-size: clamp(40px, 7vw, 80px);
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: -0.02em;
            margin-bottom: 24px;
            animation: fadeInUp 0.6s ease 0.1s both;
        }
        .hero-title .highlight { color: var(--red); }
        .hero-subtitle {
            font-size: clamp(16px, 2vw, 20px);
            color: rgba(255,255,255,0.55);
            max-width: 580px; margin: 0 auto 40px;
            line-height: 1.65;
            animation: fadeInUp 0.6s ease 0.2s both;
        }
        .hero-cta { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; animation: fadeInUp 0.6s ease 0.3s both; }
        .btn-hero-primary {
            background: var(--red); color: white; text-decoration: none;
            font-size: 16px; font-weight: 700; padding: 15px 34px;
            border-radius: 12px; font-family: 'Sora', sans-serif;
            transition: all 0.15s; display: flex; align-items: center; gap: 8px;
        }
        .btn-hero-primary:hover { background: var(--red-dark); color: white; transform: translateY(-2px); box-shadow: 0 12px 40px rgba(227,24,55,0.4); }
        .btn-hero-secondary {
            background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.8);
            border: 1px solid rgba(255,255,255,0.15); text-decoration: none;
            font-size: 16px; font-weight: 600; padding: 15px 34px;
            border-radius: 12px; transition: all 0.15s;
        }
        .btn-hero-secondary:hover { background: rgba(255,255,255,0.1); color: white; }
        .hero-social-proof {
            margin-top: 48px;
            animation: fadeInUp 0.6s ease 0.4s both;
            color: rgba(255,255,255,0.35); font-size: 13.5px;
        }
        .hero-stats { display: flex; justify-content: center; gap: 40px; margin-top: 16px; flex-wrap: wrap; }
        .hero-stat-val { font-family: 'Sora', sans-serif; font-size: 24px; font-weight: 800; color: white; }
        .hero-stat-lbl { font-size: 12px; color: rgba(255,255,255,0.4); }

        /* DEMO PREVIEW */
        .demo-section { padding: 60px 20px; }
        .demo-window {
            max-width: 900px; margin: 0 auto;
            background: #111;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 40px 120px rgba(0,0,0,0.6);
        }
        .demo-topbar {
            background: #1a1a1a;
            padding: 12px 16px;
            display: flex; align-items: center; gap: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .demo-dot { width: 12px; height: 12px; border-radius: 50%; }
        .demo-content { padding: 24px; }

        /* HOW IT WORKS */
        .section-dark { padding: 100px 20px; }
        .section-label { font-size: 12px; font-weight: 800; letter-spacing: 0.1em; text-transform: uppercase; color: var(--red); margin-bottom: 12px; }
        .section-title { font-family: 'Sora', sans-serif; font-size: clamp(28px, 4vw, 44px); font-weight: 800; margin-bottom: 16px; }
        .section-subtitle { font-size: 17px; color: rgba(255,255,255,0.5); max-width: 500px; margin: 0 auto; line-height: 1.6; }

        .step-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px; padding: 32px;
            position: relative;
            transition: all 0.3s;
        }
        .step-card:hover { background: rgba(255,255,255,0.05); border-color: rgba(227,24,55,0.3); transform: translateY(-4px); }
        .step-number {
            width: 44px; height: 44px; border-radius: 12px;
            background: rgba(227,24,55,0.15); border: 1px solid rgba(227,24,55,0.3);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 800; color: var(--red);
            margin-bottom: 16px;
        }
        .step-title { font-family: 'Sora', sans-serif; font-size: 17px; font-weight: 700; margin-bottom: 8px; }
        .step-desc { font-size: 13.5px; color: rgba(255,255,255,0.5); line-height: 1.6; }

        /* FEATURES */
        .feature-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px; padding: 28px;
            transition: all 0.2s;
        }
        .feature-card:hover { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.12); }
        .feature-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 16px; }

        /* PRICING */
        .pricing-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 18px; padding: 32px;
            position: relative;
            transition: all 0.2s;
        }
        .pricing-card:hover { transform: translateY(-4px); }
        .pricing-card.featured { border-color: var(--red); background: rgba(227,24,55,0.05); }

        /* FOOTER */
        footer { border-top: 1px solid rgba(255,255,255,0.06); padding: 60px 48px 40px; }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* Floating animation */
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        .float { animation: float 4s ease-in-out infinite; }

        @media (max-width: 768px) {
            nav { padding: 14px 20px; }
            .nav-links { display: none; }
            footer { padding: 40px 20px; }
        }
    </style>
</head>
<body>

<!-- Nav -->
<nav>
    <a href="/" class="nav-logo">
        <div class="nav-logo-icon"><i class="bi bi-robot"></i></div>
        <div class="nav-logo-text">Amazon<br><span>Listing Builder</span></div>
    </a>
    <div class="nav-links">
        <a href="#how-it-works">How It Works</a>
        <a href="#features">Features</a>
        <a href="#pricing">Pricing</a>
    </div>
    <div class="nav-cta">
        <a href="<?php echo e(route('login')); ?>" class="btn-nav-login">Sign In</a>
        <a href="<?php echo e(route('register')); ?>" class="btn-nav-signup">Get Started Free →</a>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-grid"></div>
    <div style="position:relative;z-index:1;max-width:800px;">
        <div class="hero-badge">
            <i class="bi bi-stars"></i>
            Powered by GPT-4o + Amazon SP-API
        </div>
        <h1 class="hero-title">
            Generate Unique<br>
            <span class="highlight">Amazon Listings</span><br>
            with AI in Seconds
        </h1>
        <p class="hero-subtitle">
            Import any Amazon product URL, enter your brand name, and let our AI generate a completely unique, SEO-optimized listing — ready to publish.
        </p>
        <div class="hero-cta">
            <a href="<?php echo e(route('register')); ?>" class="btn-hero-primary">
                <i class="bi bi-rocket-takeoff"></i> Start for Free
            </a>
            <a href="<?php echo e(route('login')); ?>" class="btn-hero-secondary">
                Sign In →
            </a>
        </div>
        <div class="hero-social-proof">
            Trusted by Amazon sellers worldwide
            <div class="hero-stats">
                <div style="text-align:center;">
                    <div class="hero-stat-val">10,000+</div>
                    <div class="hero-stat-lbl">Listings Generated</div>
                </div>
                <div style="text-align:center;">
                    <div class="hero-stat-val">500+</div>
                    <div class="hero-stat-lbl">Active Sellers</div>
                </div>
                <div style="text-align:center;">
                    <div class="hero-stat-val">15+</div>
                    <div class="hero-stat-lbl">Marketplaces</div>
                </div>
                <div style="text-align:center;">
                    <div class="hero-stat-val">99.9%</div>
                    <div class="hero-stat-lbl">Unique Content</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Demo Preview -->
<div class="demo-section">
    <div class="demo-window float">
        <div class="demo-topbar">
            <div class="demo-dot" style="background:#FF5F57;"></div>
            <div class="demo-dot" style="background:#FEBC2E;"></div>
            <div class="demo-dot" style="background:#28C840;"></div>
            <div style="flex:1;background:#111;border-radius:6px;height:26px;margin-left:10px;display:flex;align-items:center;padding:0 12px;">
                <span style="font-size:12px;color:rgba(255,255,255,0.3);">amazon.com/dp/B0XXXXXXXXX</span>
            </div>
        </div>
        <div class="demo-content">
            <div class="row g-3">
                <div class="col-6">
                    <div style="background:#1a1a1a;border:1px solid rgba(255,255,255,0.07);border-radius:10px;padding:16px;height:100%;">
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:rgba(255,255,255,0.3);letter-spacing:0.08em;margin-bottom:10px;">Original Listing</div>
                        <div style="font-size:12.5px;color:rgba(255,255,255,0.6);line-height:1.5;margin-bottom:10px;">Contoso 32oz Insulated Water Bottle — Stainless Steel, BPA Free, Keep Cold 24hr</div>
                        <div style="font-size:11px;color:rgba(255,255,255,0.3);">Brand: Contoso</div>
                        <div style="margin-top:10px;display:flex;flex-direction:column;gap:5px;">
                            <?php $__currentLoopData = ['Keeps drinks cold for 24 hours or hot for 12 hours','Premium 18/8 stainless steel construction','BPA-free and eco-friendly materials']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div style="font-size:11px;color:rgba(255,255,255,0.4);display:flex;gap:6px;"><span style="color:rgba(255,255,255,0.2);">•</span><?php echo e($b); ?></div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div style="background:rgba(227,24,55,0.08);border:1px solid rgba(227,24,55,0.3);border-radius:10px;padding:16px;height:100%;position:relative;overflow:hidden;">
                        <div style="position:absolute;top:8px;right:8px;background:var(--red);color:white;font-size:9px;font-weight:700;padding:2px 8px;border-radius:20px;">AI Generated ✦</div>
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:rgba(227,24,55,0.7);letter-spacing:0.08em;margin-bottom:10px;">PrimeCraft Listing</div>
                        <div style="font-size:12.5px;color:rgba(255,255,255,0.85);line-height:1.5;margin-bottom:10px;font-weight:500;">PrimeCraft HydroMax 32oz Vacuum-Sealed Water Bottle — Triple-Wall Insulation, Non-Toxic, 24-Hour Cold Retention</div>
                        <div style="font-size:11px;color:rgba(227,24,55,0.7);">Brand: PrimeCraft</div>
                        <div style="margin-top:10px;display:flex;flex-direction:column;gap:5px;">
                            <?php $__currentLoopData = ['ADVANCED INSULATION: Triple-wall vacuum technology maintains beverage temperature','PREMIUM BUILD: Food-grade 18/8 stainless steel, completely BPA-free','ECO-CONSCIOUS: Reusable design reduces single-use plastic waste']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div style="font-size:11px;color:rgba(255,255,255,0.7);display:flex;gap:6px;"><span style="color:var(--red);">✓</span><?php echo e($b); ?></div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- How It Works -->
<section class="section-dark text-center" id="how-it-works">
    <div class="container">
        <div class="section-label">How It Works</div>
        <h2 class="section-title">From URL to Listing in 4 Steps</h2>
        <p class="section-subtitle">No copywriting experience needed. Just paste, click, and export.</p>
        <div class="row g-4 mt-5 text-start">
            <?php
            $steps = [
                ['01', 'Paste Amazon URL', 'Enter any Amazon product link — .com, .in, .co.uk, .de and 10+ more marketplaces supported.', 'bi-link-45deg'],
                ['02', 'Enter Your Brand', 'Add your brand name and manufacturer. Our AI will weave it throughout the entire listing naturally.', 'bi-tag'],
                ['03', 'AI Generates Content', 'GPT-4o analyzes the product, rewrites everything from scratch — title, 5 bullets, description, SEO keywords.', 'bi-cpu'],
                ['04', 'Export or Publish', 'Download CSV, Excel, Amazon Flat File, JSON, or PDF. Or publish directly via Amazon SP-API.', 'bi-cloud-upload'],
            ];
            ?>
            <?php $__currentLoopData = $steps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$num, $title, $desc, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="col-md-6 col-lg-3">
                <div class="step-card">
                    <div class="step-number"><?php echo e($num); ?></div>
                    <i class="bi <?php echo e($icon); ?>" style="font-size:28px;color:var(--red);margin-bottom:14px;display:block;"></i>
                    <div class="step-title"><?php echo e($title); ?></div>
                    <div class="step-desc"><?php echo e($desc); ?></div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>

<!-- Features -->
<section class="section-dark text-center" id="features" style="background:rgba(255,255,255,0.01);">
    <div class="container">
        <div class="section-label">Features</div>
        <h2 class="section-title">Everything You Need to Dominate Amazon</h2>
        <div class="row g-4 mt-5 text-start">
            <?php
            $features = [
                ['bi-robot', '#E31837', '#3D0012', 'AI-Powered Generation', 'GPT-4o generates completely unique titles, bullet points, descriptions, search terms, and A+ content.'],
                ['bi-shield-check', '#10B981', '#022C22', 'Copyright-Safe', 'Our AI rewrites all content from scratch. Never copies competitor text or trademarked claims.'],
                ['bi-cloud-download', '#3B82F6', '#0C1A3D', 'Amazon Scraper', 'Extracts product title, bullets, specs, images, category and attributes from any Amazon marketplace.'],
                ['bi-tags', '#F59E0B', '#3D2500', 'Brand Replacement', 'Automatically replaces all original brand and manufacturer references with your brand throughout.'],
                ['bi-file-earmark-arrow-down', '#8B5CF6', '#1E0A3C', 'Multi-Format Export', 'Export to CSV, Excel, Amazon Flat File, JSON, or PDF. Ready for any workflow or tool.'],
                ['bi-broadcast', '#06B6D4', '#022B38', 'SP-API Publishing', 'Publish listings directly to Amazon Seller Central via official SP-API (Pro & Enterprise plans).'],
                ['bi-bar-chart', '#EC4899', '#3D0026', 'Analytics Dashboard', 'Track usage, token spend, listing performance, and generation history over time.'],
                ['bi-people', '#14B8A6', '#022C29', 'Team & Agency', 'Multi-user accounts, shared workspaces, and agency dashboards for managing multiple sellers.'],
            ];
            ?>
            <?php $__currentLoopData = $features; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$icon, $color, $bg, $title, $desc]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon" style="background:<?php echo e($bg); ?>;">
                        <i class="bi <?php echo e($icon); ?>" style="color:<?php echo e($color); ?>;"></i>
                    </div>
                    <div style="font-family:'Sora',sans-serif;font-size:15px;font-weight:700;margin-bottom:8px;"><?php echo e($title); ?></div>
                    <div style="font-size:13px;color:rgba(255,255,255,0.45);line-height:1.6;"><?php echo e($desc); ?></div>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>

<!-- Pricing Preview -->
<section class="section-dark text-center" id="pricing">
    <div class="container">
        <div class="section-label">Pricing</div>
        <h2 class="section-title">Simple, Transparent Pricing</h2>
        <p class="section-subtitle">Start free. Upgrade when you're ready to scale.</p>
        <div class="row g-4 mt-5 justify-content-center">
            <?php
            $pricingPlans = [
                ['Free', '$0', '/month', '5 listings', 'CSV & JSON export', 'Side-by-side view', false],
                ['Pro', '$79', '/month', '200 listings', 'All export formats', 'Amazon SP-API publish', true],
                ['Enterprise', '$199', '/month', 'Unlimited', 'Bulk import', 'Team accounts + API', false],
            ];
            ?>
            <?php $__currentLoopData = $pricingPlans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$name, $price, $per, $feat1, $feat2, $feat3, $featured]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="col-md-4 col-lg-3">
                <div class="pricing-card <?php echo e($featured ? 'featured' : ''); ?>">
                    <?php if($featured): ?>
                    <div style="background:var(--red);color:white;font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;padding:5px;border-radius:6px;margin-bottom:16px;text-align:center;">Most Popular</div>
                    <?php endif; ?>
                    <div style="font-family:'Sora',sans-serif;font-size:17px;font-weight:800;margin-bottom:6px;"><?php echo e($name); ?></div>
                    <div style="display:flex;align-items:baseline;gap:4px;margin-bottom:20px;">
                        <span style="font-family:'Sora',sans-serif;font-size:36px;font-weight:900;"><?php echo e($price); ?></span>
                        <span style="color:rgba(255,255,255,0.4);font-size:13px;"><?php echo e($per); ?></span>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:24px;">
                        <?php $__currentLoopData = [$feat1, $feat2, $feat3]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:rgba(255,255,255,0.6);">
                            <i class="bi bi-check-circle-fill" style="color:var(--red);font-size:14px;flex-shrink:0;"></i><?php echo e($f); ?>

                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <a href="<?php echo e(route('register')); ?>" style="display:block;text-align:center;padding:12px;border-radius:9px;font-size:14px;font-weight:700;text-decoration:none;background:<?php echo e($featured ? 'var(--red)' : 'rgba(255,255,255,0.07)'); ?>;color:white;border:1px solid <?php echo e($featured ? 'transparent' : 'rgba(255,255,255,0.1)'); ?>;transition:all 0.15s;" onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                        Get Started
                    </a>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div style="margin-top:32px;">
            <a href="<?php echo e(route('billing.plans')); ?>" style="color:rgba(255,255,255,0.4);font-size:14px;text-decoration:none;">View full pricing comparison →</a>
        </div>
    </div>
</section>

<!-- CTA -->
<section style="padding:100px 20px;text-align:center;background:radial-gradient(ellipse 700px 400px at 50% 50%, rgba(227,24,55,0.15) 0%, transparent 70%);">
    <div class="section-label">Get Started Today</div>
    <h2 class="section-title" style="max-width:520px;margin:0 auto 20px;">Ready to Build Better Listings?</h2>
    <p style="color:rgba(255,255,255,0.45);font-size:16px;margin-bottom:36px;">5 free listings. No credit card. No commitment.</p>
    <a href="<?php echo e(route('register')); ?>" class="btn-hero-primary" style="display:inline-flex;">
        <i class="bi bi-rocket-takeoff"></i> Create Free Account
    </a>
</section>

<!-- Footer -->
<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="nav-logo" style="margin-bottom:16px;">
                    <div class="nav-logo-icon"><i class="bi bi-robot"></i></div>
                    <div class="nav-logo-text">Amazon<br><span>Listing Builder</span></div>
                </div>
                <p style="font-size:13.5px;color:rgba(255,255,255,0.35);line-height:1.6;max-width:280px;">AI-powered Amazon listing generator for sellers who want to scale faster.</p>
            </div>
            <div class="col-md-2 mb-4">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.3);margin-bottom:14px;">Product</div>
                <?php $__currentLoopData = ['Features', 'Pricing', 'How It Works', 'API Docs']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="margin-bottom:8px;"><a href="#" style="font-size:13.5px;color:rgba(255,255,255,0.45);text-decoration:none;transition:color 0.15s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.45)'"><?php echo e($link); ?></a></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <div class="col-md-2 mb-4">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.3);margin-bottom:14px;">Account</div>
                <?php $__currentLoopData = ['Sign In', 'Register', 'Dashboard', 'Billing']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="margin-bottom:8px;"><a href="#" style="font-size:13.5px;color:rgba(255,255,255,0.45);text-decoration:none;transition:color 0.15s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.45)'"><?php echo e($link); ?></a></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <div class="col-md-4 mb-4">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.3);margin-bottom:14px;">Coming Soon</div>
                <?php $__currentLoopData = ['Flipkart Generator', 'eBay Listing Builder', 'WooCommerce Import', 'Bulk URL Processing', 'Agency Dashboard']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="margin-bottom:6px;display:flex;align-items:center;gap:8px;">
                    <span style="width:6px;height:6px;border-radius:50%;background:var(--red);display:inline-block;flex-shrink:0;"></span>
                    <span style="font-size:13px;color:rgba(255,255,255,0.35);"><?php echo e($f); ?></span>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <div style="border-top:1px solid rgba(255,255,255,0.06);padding-top:24px;margin-top:32px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
            <div style="font-size:13px;color:rgba(255,255,255,0.25);">© <?php echo e(date('Y')); ?> Amazon Listing Builder. All rights reserved.</div>
            <div style="display:flex;gap:20px;">
                <?php $__currentLoopData = ['Privacy Policy', 'Terms of Service', 'Contact']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="#" style="font-size:12.5px;color:rgba(255,255,255,0.3);text-decoration:none;transition:color 0.15s;" onmouseover="this.style.color='white'" onmouseout="this.style.color='rgba(255,255,255,0.3)'"><?php echo e($l); ?></a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php /**PATH S:\Devotion\www\laravel\amazon-ai-listing-builder\resources\views/welcome.blade.php ENDPATH**/ ?>