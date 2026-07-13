<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('code'); ?> — <?php echo $__env->yieldContent('title'); ?> | Amazon Listing Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Sora:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0a0a;
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        .bg-glow {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 600px; height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(227,24,55,0.08) 0%, transparent 70%);
            pointer-events: none;
        }
        .error-code {
            font-family: 'Sora', sans-serif;
            font-size: clamp(80px, 15vw, 160px);
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(135deg, #E31837, #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 16px;
        }
        .error-icon {
            font-size: 48px;
            color: #E31837;
            display: block;
            margin-bottom: 20px;
        }
        h1 {
            font-family: 'Sora', sans-serif;
            font-size: clamp(22px, 4vw, 32px);
            font-weight: 800;
            margin-bottom: 12px;
        }
        p {
            font-size: 16px;
            color: rgba(255,255,255,0.5);
            max-width: 440px;
            line-height: 1.6;
            margin: 0 auto 32px;
        }
        .btn-home {
            background: #E31837;
            color: white;
            text-decoration: none;
            font-size: 15px;
            font-weight: 700;
            padding: 13px 30px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.15s;
            font-family: 'Sora', sans-serif;
        }
        .btn-home:hover { background: #b01028; color: white; transform: translateY(-2px); }
        .btn-back {
            color: rgba(255,255,255,0.4);
            text-decoration: none;
            font-size: 14px;
            margin-left: 16px;
            transition: color 0.15s;
        }
        .btn-back:hover { color: white; }
    </style>
</head>
<body>
    <div class="bg-glow"></div>
    <div style="position:relative;z-index:1;">
        <div class="error-code"><?php echo $__env->yieldContent('code'); ?></div>
        <i class="bi <?php echo $__env->yieldContent('icon'); ?> error-icon"></i>
        <h1><?php echo $__env->yieldContent('title'); ?></h1>
        <p><?php echo $__env->yieldContent('description'); ?></p>
        <div>
            <a href="<?php echo e(url('/dashboard')); ?>" class="btn-home">
                <i class="bi bi-house"></i> Go to Dashboard
            </a>
            <a href="javascript:history.back()" class="btn-back">← Go back</a>
        </div>
    </div>
</body>
</html>
<?php /**PATH S:\Devotion\www\laravel\amazon-ai-listing-builder\resources\views/errors/layout.blade.php ENDPATH**/ ?>