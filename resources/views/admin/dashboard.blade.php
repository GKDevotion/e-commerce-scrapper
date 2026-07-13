@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('page-title', 'Admin Dashboard')

@section('content')
<!-- Stats Grid -->
<div class="row g-3 mb-4">
    @php
    $adminStats = [
        ['Total Users', $stats['total_users'], 'bi-people-fill', 'blue', route('admin.users')],
        ['Active Users', $stats['active_users'], 'bi-person-check-fill', 'green', route('admin.users') . '?status=active'],
        ['Total Listings', $stats['total_listings'], 'bi-collection-fill', 'red', '#'],
        ['AI Generations', $stats['total_generations'], 'bi-cpu-fill', 'purple', '#'],
        ['Monthly Revenue', '$' . number_format($stats['monthly_revenue'], 2), 'bi-currency-dollar', 'orange', '#'],
        ['Active Subs', $stats['active_subscriptions'], 'bi-credit-card-fill', 'green', '#'],
        ['Total API Calls', number_format($stats['total_api_calls']), 'bi-cloud-fill', 'blue', '#'],
        ['AI Cost', '$' . number_format($stats['total_ai_cost'], 2), 'bi-robot', 'red', '#'],
    ];
    @endphp
    @foreach($adminStats as [$label, $value, $icon, $color, $link])
    <div class="col-6 col-md-3 fade-in-up">
        <a href="{{ $link }}" style="text-decoration:none;">
            <div class="alb-stat">
                <div class="alb-stat-icon {{ $color }}"><i class="bi {{ $icon }}"></i></div>
                <div>
                    <div class="alb-stat-value" style="font-size:22px;">{{ $value }}</div>
                    <div class="alb-stat-label">{{ $label }}</div>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>

<div class="row g-4">
    <!-- Revenue Chart -->
    <div class="col-lg-8 fade-in-up">
        <div class="alb-card">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-bar-chart me-2" style="color:#E31837;"></i>New Registrations (30 days)</h3>
            </div>
            <div style="height:220px;position:relative;">
                <canvas id="growthChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Plan Distribution -->
    <div class="col-lg-4 fade-in-up">
        <div class="alb-card">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-pie-chart me-2" style="color:#E31837;"></i>Plan Distribution</h3>
            </div>
            @php $totalUsers = $planDistribution->sum('count'); @endphp
            @foreach($planDistribution as $dist)
            @php
            $plan = \App\Models\Plan::find($dist->plan_id);
            $pct = $totalUsers > 0 ? round(($dist->count / $totalUsers) * 100) : 0;
            $colors = ['#E31837','#3B82F6','#10B981','#8B5CF6','#F59E0B'];
            $ci = $loop->index % count($colors);
            @endphp
            <div style="margin-bottom:12px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                    <span style="font-weight:600;color:#374151;">{{ $plan?->name ?? 'No Plan' }}</span>
                    <span style="color:#6B7280;">{{ $dist->count }} users ({{ $pct }}%)</span>
                </div>
                <div style="height:6px;background:#F3F4F6;border-radius:99px;overflow:hidden;">
                    <div style="height:100%;width:{{ $pct }}%;background:{{ $colors[$ci] }};border-radius:99px;transition:width 0.5s;"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Recent Users -->
    <div class="col-lg-6 fade-in-up">
        <div class="alb-card">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-people me-2" style="color:#E31837;"></i>Recent Users</h3>
                <a href="{{ route('admin.users') }}" style="font-size:13px;color:#E31837;text-decoration:none;font-weight:600;">View all →</a>
            </div>
            <table class="alb-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentUsers as $u)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <img src="{{ $u->avatar_url }}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;" alt="">
                                <div>
                                    <div style="font-size:13px;font-weight:600;color:#111827;">{{ $u->name }}</div>
                                    <div style="font-size:11px;color:#9CA3AF;">{{ $u->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span style="font-size:12px;font-weight:600;color:#374151;">{{ $u->plan?->name ?? 'Free' }}</span></td>
                        <td>
                            @if($u->status === 'active')
                            <span style="background:#D1FAE5;color:#065F46;font-size:11px;font-weight:700;padding:3px 8px;border-radius:20px;">Active</span>
                            @elseif($u->status === 'suspended')
                            <span style="background:#FEE2E2;color:#991B1B;font-size:11px;font-weight:700;padding:3px 8px;border-radius:20px;">Suspended</span>
                            @else
                            <span style="background:#FEF3C7;color:#92400E;font-size:11px;font-weight:700;padding:3px 8px;border-radius:20px;">{{ ucfirst($u->status) }}</span>
                            @endif
                        </td>
                        <td style="font-size:12px;color:#9CA3AF;">{{ $u->created_at->format('M d') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="col-lg-6 fade-in-up">
        <div class="alb-card">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-credit-card me-2" style="color:#E31837;"></i>Recent Payments</h3>
                <div style="font-size:13px;font-weight:700;color:#10B981;">
                    Total: ${{ number_format($stats['total_revenue'], 2) }}
                </div>
            </div>
            @if($recentPayments->isEmpty())
            <div style="text-align:center;padding:24px;color:#9CA3AF;font-size:13px;">No payments yet</div>
            @else
            <table class="alb-table">
                <thead>
                    <tr><th>User</th><th>Plan</th><th>Amount</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach($recentPayments as $payment)
                    <tr>
                        <td>
                            <div style="font-size:13px;font-weight:600;">{{ $payment->user?->name }}</div>
                            <div style="font-size:11px;color:#9CA3AF;">{{ $payment->created_at->format('M d, Y') }}</div>
                        </td>
                        <td style="font-size:12.5px;">{{ $payment->plan?->name }}</td>
                        <td style="font-size:13px;font-weight:700;color:#111827;">${{ number_format($payment->amount, 2) }}</td>
                        <td>
                            @if($payment->status === 'success')
                            <span style="background:#D1FAE5;color:#065F46;font-size:11px;font-weight:700;padding:3px 8px;border-radius:20px;">Paid</span>
                            @else
                            <span style="background:#FEE2E2;color:#991B1B;font-size:11px;font-weight:700;padding:3px 8px;border-radius:20px;">{{ ucfirst($payment->status) }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-12 fade-in-up">
        <div class="alb-card">
            <h3 class="alb-card-title mb-4"><i class="bi bi-lightning me-2" style="color:#E31837;"></i>Quick Admin Actions</h3>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <a href="{{ route('admin.users') }}" style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:20px;background:#F9FAFB;border-radius:12px;text-decoration:none;color:#374151;border:1.5px solid #F3F4F6;transition:all 0.15s;" onmouseover="this.style.background='#FEE2E8';this.style.borderColor='#E31837'" onmouseout="this.style.background='#F9FAFB';this.style.borderColor='#F3F4F6'">
                        <i class="bi bi-people-fill" style="font-size:28px;color:#E31837;"></i>
                        <span style="font-size:13.5px;font-weight:700;">Manage Users</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="{{ route('admin.plans') }}" style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:20px;background:#F9FAFB;border-radius:12px;text-decoration:none;color:#374151;border:1.5px solid #F3F4F6;transition:all 0.15s;" onmouseover="this.style.background='#EFF6FF';this.style.borderColor='#3B82F6'" onmouseout="this.style.background='#F9FAFB';this.style.borderColor='#F3F4F6'">
                        <i class="bi bi-layers-fill" style="font-size:28px;color:#3B82F6;"></i>
                        <span style="font-size:13.5px;font-weight:700;">Manage Plans</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="{{ route('admin.ai-settings') }}" style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:20px;background:#F9FAFB;border-radius:12px;text-decoration:none;color:#374151;border:1.5px solid #F3F4F6;transition:all 0.15s;" onmouseover="this.style.background='#F5F3FF';this.style.borderColor='#8B5CF6'" onmouseout="this.style.background='#F9FAFB';this.style.borderColor='#F3F4F6'">
                        <i class="bi bi-cpu-fill" style="font-size:28px;color:#8B5CF6;"></i>
                        <span style="font-size:13.5px;font-weight:700;">AI Settings</span>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="{{ route('admin.analytics') }}" style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:20px;background:#F9FAFB;border-radius:12px;text-decoration:none;color:#374151;border:1.5px solid #F3F4F6;transition:all 0.15s;" onmouseover="this.style.background='#ECFDF5';this.style.borderColor='#10B981'" onmouseout="this.style.background='#F9FAFB';this.style.borderColor='#F3F4F6'">
                        <i class="bi bi-bar-chart-fill" style="font-size:28px;color:#10B981;"></i>
                        <span style="font-size:13.5px;font-weight:700;">Analytics</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Growth chart
const growthData = @json($userGrowth);
const listingData = @json($listingGrowth);

// Generate last 30 days labels
const labels = [];
for (let i = 29; i >= 0; i--) {
    const d = new Date();
    d.setDate(d.getDate() - i);
    labels.push(d.toISOString().split('T')[0]);
}

const userValues = labels.map(d => growthData[d] || 0);
const listingValues = labels.map(d => listingData[d] || 0);

new Chart(document.getElementById('growthChart'), {
    type: 'line',
    data: {
        labels: labels.map(l => {
            const d = new Date(l);
            return d.toLocaleDateString('en', {month:'short',day:'numeric'});
        }),
        datasets: [
            {
                label: 'New Users',
                data: userValues,
                borderColor: '#E31837',
                backgroundColor: 'rgba(227,24,55,0.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                borderWidth: 2,
            },
            {
                label: 'AI Generations',
                data: listingValues,
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59,130,246,0.06)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                borderWidth: 2,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top', labels: { font: { size: 12 }, boxWidth: 12 } } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#F3F4F6' }, ticks: { font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { font: { size: 11 }, maxTicksLimit: 10 } }
        }
    }
});
</script>
@endpush
@endsection
