<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — Amazon Listing Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --alb-red: #E31837;
            --alb-red-dark: #b01028;
            --alb-black: #0d0d0d;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0d0d0d;
            min-height: 100vh;
            display: flex;
        }

        /* Left panel - brand */
        .auth-left {
            width: 420px;
            flex-shrink: 0;
            background: var(--alb-red);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px;
            overflow: hidden;
        }
        .auth-left::before {
            content: '';
            position: absolute;
            top: -100px; right: -100px;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
            pointer-events: none;
        }
        .auth-left::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -60px;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: rgba(0,0,0,0.15);
            pointer-events: none;
        }

        .auth-logo {
            display: flex; align-items: center; gap: 12px;
            position: relative; z-index: 1;
        }
        .auth-logo-icon {
            width: 46px; height: 46px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; color: white;
        }
        .auth-logo-text {
            font-family: 'Sora', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: white;
            line-height: 1.2;
        }

        .auth-tagline {
            position: relative; z-index: 1;
        }
        .auth-tagline h2 {
            font-family: 'Sora', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: white;
            line-height: 1.2;
            margin-bottom: 16px;
        }
        .auth-tagline p {
            color: rgba(255,255,255,0.75);
            font-size: 14px;
            line-height: 1.6;
        }

        .auth-features {
            position: relative; z-index: 1;
        }
        .auth-feature {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 0;
            color: rgba(255,255,255,0.85);
            font-size: 13.5px;
        }
        .auth-feature i { color: white; font-size: 16px; }

        /* Right panel - form */
        .auth-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background: #111827;
        }
        .auth-form-box {
            width: 100%;
            max-width: 440px;
        }

        .auth-form-title {
            font-family: 'Sora', sans-serif;
            font-size: 26px;
            font-weight: 700;
            color: white;
            margin-bottom: 6px;
        }
        .auth-form-subtitle {
            color: #9CA3AF;
            font-size: 14px;
            margin-bottom: 32px;
        }

        .alb-label { font-size: 13px; font-weight: 600; color: #9CA3AF; margin-bottom: 6px; display: block; }
        .alb-input {
            width: 100%;
            padding: 12px 16px;
            background: #1F2937;
            border: 1.5px solid #374151;
            border-radius: 10px;
            font-size: 14px;
            color: white;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .alb-input::placeholder { color: #6B7280; }
        .alb-input:focus { outline: none; border-color: var(--alb-red); box-shadow: 0 0 0 3px rgba(227,24,55,0.15); }
        .alb-form-group { margin-bottom: 18px; }

        .btn-auth {
            width: 100%;
            background: var(--alb-red);
            color: white;
            border: none;
            padding: 13px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s;
            font-family: 'Sora', sans-serif;
            letter-spacing: 0.02em;
        }
        .btn-auth:hover { background: var(--alb-red-dark); transform: translateY(-1px); box-shadow: 0 8px 24px rgba(227,24,55,0.35); }
        .btn-auth:active { transform: translateY(0); }

        .auth-divider {
            display: flex; align-items: center; gap: 12px;
            margin: 24px 0;
            color: #6B7280;
            font-size: 12px;
        }
        .auth-divider::before, .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #1F2937;
        }

        .auth-link { color: var(--alb-red); text-decoration: none; font-weight: 600; }
        .auth-link:hover { text-decoration: underline; }

        .auth-footer {
            text-align: center;
            color: #6B7280;
            font-size: 13px;
            margin-top: 24px;
        }

        .form-check-input:checked { background-color: var(--alb-red); border-color: var(--alb-red); }
        .form-check-label { color: #9CA3AF; font-size: 13px; }

        .is-invalid { border-color: #EF4444 !important; }
        .invalid-feedback { color: #EF4444; font-size: 12px; }

        .input-group-text {
            background: #1F2937;
            border: 1.5px solid #374151;
            color: #6B7280;
        }

        /* Animated background dots */
        .auth-bg-dots {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }
        .dot {
            position: absolute;
            border-radius: 50%;
            background: rgba(227,24,55,0.04);
            animation: floatDot linear infinite;
        }
        @keyframes floatDot {
            0% { transform: translateY(100vh) scale(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 0.5; }
            100% { transform: translateY(-20vh) scale(1); opacity: 0; }
        }

        @media (max-width: 768px) {
            .auth-left { display: none; }
            body { background: #111827; }
        }
    </style>
</head>
<body>

<div class="auth-bg-dots" aria-hidden="true">
    @for($i = 0; $i < 8; $i++)
    <div class="dot" style="
        width: {{ rand(40, 120) }}px;
        height: {{ rand(40, 120) }}px;
        left: {{ rand(0, 100) }}%;
        animation-duration: {{ rand(15, 30) }}s;
        animation-delay: {{ rand(0, 15) }}s;
    "></div>
    @endfor
</div>

<div class="auth-left">
    <div class="auth-logo">
        <div class="auth-logo-icon"><i class="bi bi-robot"></i></div>
        <div class="auth-logo-text">Amazon<br>Listing Builder</div>
    </div>

    <div class="auth-tagline">
        <h2>AI-Powered Listings That Sell</h2>
        <p>Import any Amazon product URL and generate unique, branded listings in seconds. Powered by GPT-4o for maximum conversion.</p>
    </div>

    <div class="auth-features">
        <div class="auth-feature"><i class="bi bi-check-circle-fill"></i> Scrape & analyze Amazon products</div>
        <div class="auth-feature"><i class="bi bi-check-circle-fill"></i> Generate unique AI content</div>
        <div class="auth-feature"><i class="bi bi-check-circle-fill"></i> Replace brand & manufacturer</div>
        <div class="auth-feature"><i class="bi bi-check-circle-fill"></i> Export to CSV, Excel, Amazon Flat File</div>
        <div class="auth-feature"><i class="bi bi-check-circle-fill"></i> Publish directly to Amazon SP-API</div>
    </div>
</div>

<div class="auth-right">
    <div class="auth-form-box" style="position:relative;z-index:1;">
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
