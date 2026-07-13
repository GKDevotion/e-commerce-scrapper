<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Amazon Listing Builder</title>
    <meta name="description" content="AI-powered Amazon product listing generator for sellers">

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --alb-red: #E31837;
            --alb-red-dark: #b01028;
            --alb-red-light: #ff4d6d;
            --alb-black: #0d0d0d;
            --alb-dark: #111827;
            --alb-gray: #6B7280;
            --alb-light: #F9FAFB;
            --alb-border: #E5E7EB;
            --alb-sidebar-width: 260px;
            --alb-topbar-height: 64px;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #F4F6F9;
            color: #111827;
            margin: 0;
        }

        /* ===== SIDEBAR ===== */
        .alb-sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--alb-sidebar-width);
            height: 100vh;
            background: var(--alb-black);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: transform 0.3s ease;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .alb-sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 20px 20px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            text-decoration: none;
        }
        .alb-sidebar-logo .logo-icon {
            width: 38px; height: 38px;
            background: var(--alb-red);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: white; flex-shrink: 0;
        }
        .alb-sidebar-logo .logo-text {
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 14px;
            line-height: 1.2;
            color: white;
        }
        .alb-sidebar-logo .logo-text span { color: var(--alb-red); }

        .alb-nav-section {
            padding: 12px 0;
        }
        .alb-nav-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.3);
            padding: 8px 20px 4px;
        }
        .alb-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            border-radius: 0;
            transition: all 0.15s;
            position: relative;
        }
        .alb-nav-item:hover {
            color: white;
            background: rgba(255,255,255,0.06);
        }
        .alb-nav-item.active {
            color: white;
            background: rgba(227,24,55,0.2);
        }
        .alb-nav-item.active::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 3px;
            background: var(--alb-red);
            border-radius: 0 2px 2px 0;
        }
        .alb-nav-item i { font-size: 16px; width: 20px; text-align: center; }

        .alb-sidebar-footer {
            margin-top: auto;
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        .alb-user-mini {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none; padding: 8px 10px;
            border-radius: 10px;
            transition: background 0.15s;
        }
        .alb-user-mini:hover { background: rgba(255,255,255,0.06); }
        .alb-user-mini img {
            width: 34px; height: 34px;
            border-radius: 50%; object-fit: cover;
        }
        .alb-user-mini .info { flex: 1; min-width: 0; }
        .alb-user-mini .name { font-size: 12.5px; font-weight: 600; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .alb-user-mini .plan { font-size: 11px; color: rgba(255,255,255,0.45); }

        /* ===== TOPBAR ===== */
        .alb-topbar {
            position: fixed;
            top: 0;
            left: var(--alb-sidebar-width);
            right: 0;
            height: var(--alb-topbar-height);
            background: white;
            border-bottom: 1px solid var(--alb-border);
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 16px;
            z-index: 999;
        }
        .alb-topbar .page-title {
            font-family: 'Sora', sans-serif;
            font-size: 17px;
            font-weight: 700;
            color: var(--alb-black);
            flex: 1;
        }
        .alb-topbar .topbar-btn {
            background: var(--alb-red);
            color: white;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: flex; align-items: center; gap: 6px;
            transition: background 0.15s;
        }
        .alb-topbar .topbar-btn:hover { background: var(--alb-red-dark); color: white; }

        /* ===== MAIN CONTENT ===== */
        .alb-main {
            margin-left: var(--alb-sidebar-width);
            padding-top: var(--alb-topbar-height);
            min-height: 100vh;
        }
        .alb-content {
            padding: 28px 28px;
        }

        /* ===== CARDS ===== */
        .alb-card {
            background: white;
            border-radius: 14px;
            border: 1px solid var(--alb-border);
            padding: 24px;
            transition: box-shadow 0.2s;
        }
        .alb-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
        .alb-card-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px;
        }
        .alb-card-title {
            font-family: 'Sora', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: var(--alb-black);
            margin: 0;
        }

        /* ===== STAT CARDS ===== */
        .alb-stat {
            background: white;
            border-radius: 14px;
            border: 1px solid var(--alb-border);
            padding: 22px 24px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }
        .alb-stat-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .alb-stat-icon.red { background: #FEE2E8; color: var(--alb-red); }
        .alb-stat-icon.blue { background: #EFF6FF; color: #3B82F6; }
        .alb-stat-icon.green { background: #ECFDF5; color: #10B981; }
        .alb-stat-icon.orange { background: #FFF7ED; color: #F97316; }
        .alb-stat-icon.purple { background: #F5F3FF; color: #8B5CF6; }
        .alb-stat-value {
            font-family: 'Sora', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--alb-black);
            line-height: 1;
            margin-bottom: 4px;
        }
        .alb-stat-label {
            font-size: 12.5px;
            color: var(--alb-gray);
            font-weight: 500;
        }

        /* ===== BUTTONS ===== */
        .btn-alb-primary {
            background: var(--alb-red);
            color: white;
            border: none;
            font-weight: 600;
            font-size: 13.5px;
            padding: 10px 22px;
            border-radius: 9px;
            transition: all 0.15s;
        }
        .btn-alb-primary:hover { background: var(--alb-red-dark); color: white; transform: translateY(-1px); }
        .btn-alb-outline {
            border: 1.5px solid var(--alb-border);
            color: #374151;
            background: white;
            font-weight: 600;
            font-size: 13.5px;
            padding: 9px 22px;
            border-radius: 9px;
            transition: all 0.15s;
        }
        .btn-alb-outline:hover { border-color: var(--alb-red); color: var(--alb-red); }

        /* ===== BADGES ===== */
        .badge-status-completed { background: #D1FAE5; color: #065F46; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; }
        .badge-status-pending { background: #FEF3C7; color: #92400E; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; }
        .badge-status-failed { background: #FEE2E2; color: #991B1B; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; }
        .badge-status-processing { background: #DBEAFE; color: #1E40AF; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; }

        /* ===== FORMS ===== */
        .alb-form-group { margin-bottom: 18px; }
        .alb-label { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; display: block; }
        .alb-input {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--alb-border);
            border-radius: 9px;
            font-size: 14px;
            color: var(--alb-black);
            transition: border-color 0.15s, box-shadow 0.15s;
            background: white;
        }
        .alb-input:focus {
            outline: none;
            border-color: var(--alb-red);
            box-shadow: 0 0 0 3px rgba(227,24,55,0.1);
        }
        .alb-textarea { min-height: 100px; resize: vertical; }

        /* ===== TABLES ===== */
        .alb-table { width: 100%; border-collapse: collapse; }
        .alb-table th {
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--alb-gray);
            padding: 10px 16px;
            background: var(--alb-light);
            border-bottom: 1px solid var(--alb-border);
            text-align: left;
        }
        .alb-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #F3F4F6;
            font-size: 13.5px;
            color: #374151;
            vertical-align: middle;
        }
        .alb-table tr:last-child td { border-bottom: none; }
        .alb-table tr:hover td { background: #FAFAFA; }

        /* ===== ALERTS ===== */
        .alb-alert {
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .alb-alert.success { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
        .alb-alert.error { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
        .alb-alert.warning { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
        .alb-alert.info { background: #DBEAFE; color: #1E40AF; border: 1px solid #BFDBFE; }

        /* ===== USAGE BAR ===== */
        .usage-bar-track {
            height: 6px;
            background: #F3F4F6;
            border-radius: 99px;
            overflow: hidden;
        }
        .usage-bar-fill {
            height: 100%;
            background: var(--alb-red);
            border-radius: 99px;
            transition: width 0.5s;
        }

        /* ===== MOBILE ===== */
        @media (max-width: 991px) {
            .alb-sidebar { transform: translateX(-100%); }
            .alb-sidebar.open { transform: translateX(0); }
            .alb-main { margin-left: 0; }
            .alb-topbar { left: 0; }
            .alb-content { padding: 20px 16px; }
        }

        /* ===== DARK MODE ===== */
        [data-bs-theme="dark"] body { background: #0f1117; color: #e5e7eb; }
        [data-bs-theme="dark"] .alb-topbar { background: #1a1d24; border-color: #2d3139; }
        [data-bs-theme="dark"] .alb-card, [data-bs-theme="dark"] .alb-stat { background: #1a1d24; border-color: #2d3139; }
        [data-bs-theme="dark"] .alb-input { background: #1a1d24; border-color: #2d3139; color: #e5e7eb; }
        [data-bs-theme="dark"] .alb-table th { background: #1a1d24; }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in-up { animation: fadeInUp 0.35s ease both; }
        .fade-in-up-delay-1 { animation-delay: 0.07s; }
        .fade-in-up-delay-2 { animation-delay: 0.14s; }
        .fade-in-up-delay-3 { animation-delay: 0.21s; }
        .fade-in-up-delay-4 { animation-delay: 0.28s; }

        @keyframes spin { to { transform: rotate(360deg); } }
        .spin { animation: spin 1s linear infinite; }

        /* ===== SIDEBAR OVERLAY ===== */
        .sidebar-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .sidebar-overlay.show { display: block; }
    </style>
    @stack('styles')
</head>
<body>

<!-- Sidebar Overlay (Mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<aside class="alb-sidebar" id="sidebar">
    <a href="{{ route('dashboard') }}" class="alb-sidebar-logo">
        <div class="logo-icon"><i class="bi bi-robot"></i></div>
        <div class="logo-text">Amazon<br><span>Listing Builder</span></div>
    </a>

    <nav class="alb-nav-section flex-grow-1">
        <a href="{{ route('dashboard') }}" class="alb-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>

        <div class="alb-nav-label">Listings</div>
        <a href="{{ route('listings.create') }}" class="alb-nav-item {{ request()->routeIs('listings.create') ? 'active' : '' }}">
            <i class="bi bi-plus-circle-fill"></i> New Listing
        </a>
        <a href="{{ route('listings.index') }}" class="alb-nav-item {{ request()->routeIs('listings.index') ? 'active' : '' }}">
            <i class="bi bi-collection-fill"></i> My Listings
        </a>

        <div class="alb-nav-label">Account</div>
        <a href="{{ route('billing.plans') }}" class="alb-nav-item {{ request()->routeIs('billing.*') ? 'active' : '' }}">
            <i class="bi bi-credit-card-fill"></i> Plans & Billing
        </a>
        <a href="{{ route('profile.index') }}" class="alb-nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <i class="bi bi-person-fill"></i> Profile
        </a>

        @if(auth()->user()->isAdmin())
        <div class="alb-nav-label">Admin</div>
        <a href="{{ route('admin.dashboard') }}" class="alb-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Admin Dashboard
        </a>
        <a href="{{ route('admin.users') }}" class="alb-nav-item {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
            <i class="bi bi-people-fill"></i> Users
        </a>
        <a href="{{ route('admin.plans') }}" class="alb-nav-item {{ request()->routeIs('admin.plans*') ? 'active' : '' }}">
            <i class="bi bi-layers-fill"></i> Plans
        </a>
        <a href="{{ route('admin.ai-settings') }}" class="alb-nav-item {{ request()->routeIs('admin.ai*') ? 'active' : '' }}">
            <i class="bi bi-cpu-fill"></i> AI Settings
        </a>
        <a href="{{ route('admin.analytics') }}" class="alb-nav-item {{ request()->routeIs('admin.analytics') ? 'active' : '' }}">
            <i class="bi bi-bar-chart-fill"></i> Analytics
        </a>
        @endif
    </nav>

    <div class="alb-sidebar-footer">
        <!-- Usage -->
        @php $user = auth()->user(); @endphp
        <div style="background:rgba(255,255,255,0.05);border-radius:10px;padding:12px;margin-bottom:12px;">
            <div style="display:flex;justify-content:space-between;font-size:11px;color:rgba(255,255,255,0.5);margin-bottom:6px;">
                <span>Listings Used</span>
                <span style="color:rgba(255,255,255,0.7);">{{ $user->listings_used }} / {{ $user->plan?->listings_limit_display ?? '5' }}</span>
            </div>
            <div class="usage-bar-track">
                <div class="usage-bar-fill" style="width:{{ $user->getUsagePercentage() }}%"></div>
            </div>
            @if($user->plan)
            <div style="font-size:10px;color:rgba(255,255,255,0.35);margin-top:6px;">{{ $user->plan->name }} Plan</div>
            @endif
        </div>

        <a href="{{ route('profile.index') }}" class="alb-user-mini">
            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}">
            <div class="info">
                <div class="name">{{ $user->name }}</div>
                <div class="plan">{{ $user->plan?->name ?? 'Free' }}</div>
            </div>
            <i class="bi bi-three-dots-vertical" style="color:rgba(255,255,255,0.4);font-size:14px;"></i>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button type="submit" style="width:100%;background:rgba(255,255,255,0.06);border:none;color:rgba(255,255,255,0.5);padding:9px;border-radius:8px;font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all 0.15s;" onmouseover="this.style.background='rgba(227,24,55,0.2)';this.style.color='#ff6b80'" onmouseout="this.style.background='rgba(255,255,255,0.06)';this.style.color='rgba(255,255,255,0.5)'">
                <i class="bi bi-box-arrow-left"></i> Sign Out
            </button>
        </form>
    </div>
</aside>

<!-- Topbar -->
<header class="alb-topbar">
    <button class="d-lg-none btn btn-sm" onclick="openSidebar()" style="border:none;color:#6B7280;font-size:18px;padding:4px 8px;">
        <i class="bi bi-list"></i>
    </button>
    <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
    <div class="d-flex align-items-center gap-2">
        @yield('topbar-actions')
        <!-- Dark mode toggle -->
        <button onclick="toggleDarkMode()" style="border:none;background:none;color:#6B7280;font-size:18px;padding:6px;border-radius:8px;cursor:pointer;" title="Toggle Dark Mode">
            <i class="bi bi-moon-fill" id="darkModeIcon"></i>
        </button>
        <a href="{{ route('listings.create') }}" class="topbar-btn d-none d-sm-flex">
            <i class="bi bi-plus-lg"></i> New Listing
        </a>
    </div>
</header>

<!-- Main -->
<main class="alb-main">
    <div class="alb-content">
        <!-- Flash Messages -->
        @if(session('success'))
        <div class="alb-alert success fade-in-up">
            <i class="bi bi-check-circle-fill"></i>
            <div>{{ session('success') }}</div>
        </div>
        @endif
        @if(session('error'))
        <div class="alb-alert error fade-in-up">
            <i class="bi bi-x-circle-fill"></i>
            <div>{{ session('error') }}</div>
        </div>
        @endif
        @if(session('warning'))
        <div class="alb-alert warning fade-in-up">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>{{ session('warning') }}</div>
        </div>
        @endif

        @yield('content')
    </div>
</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('show');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
}

function toggleDarkMode() {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-bs-theme') === 'dark';
    html.setAttribute('data-bs-theme', isDark ? 'light' : 'dark');
    document.getElementById('darkModeIcon').className = isDark ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
    localStorage.setItem('albTheme', isDark ? 'light' : 'dark');
}

// Restore theme
(function() {
    const saved = localStorage.getItem('albTheme');
    if (saved === 'dark') {
        document.documentElement.setAttribute('data-bs-theme', 'dark');
        const icon = document.getElementById('darkModeIcon');
        if (icon) icon.className = 'bi bi-sun-fill';
    }
})();

// Auto-dismiss alerts
setTimeout(() => {
    document.querySelectorAll('.alb-alert').forEach(el => {
        el.style.transition = 'opacity 0.4s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    });
}, 5000);
</script>

@stack('scripts')
</body>
</html>
