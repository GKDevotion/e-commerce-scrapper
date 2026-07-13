@extends('layouts.app')
@section('title', 'My Subscription')
@section('page-title', 'Subscription')

@section('content')
@php $user = auth()->user(); @endphp

<div class="row g-4">
    <div class="col-lg-7 fade-in-up">

        <!-- Current Plan -->
        <div class="alb-card mb-4" style="background:linear-gradient(135deg,#111827,#1F2937);border:1.5px solid #374151;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px;">
                <div>
                    <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#9CA3AF;margin-bottom:8px;">Current Plan</div>
                    <div style="font-family:'Sora',sans-serif;font-size:28px;font-weight:800;color:white;margin-bottom:6px;">
                        {{ $user->plan?->name ?? 'Free' }}
                    </div>
                    @if($user->plan && $user->plan->price_monthly > 0)
                    <div style="font-size:14px;color:#9CA3AF;">${{ number_format($user->plan->price_monthly, 2) }}/month</div>
                    @else
                    <div style="font-size:14px;color:#9CA3AF;">Free forever</div>
                    @endif
                </div>
                <a href="{{ route('billing.plans') }}" style="background:var(--alb-red);color:white;text-decoration:none;font-size:13.5px;font-weight:700;padding:10px 22px;border-radius:9px;display:flex;align-items:center;gap:6px;transition:background 0.15s;" onmouseover="this.style.background='#b01028'" onmouseout="this.style.background='var(--alb-red)'">
                    <i class="bi bi-arrow-up-circle"></i>Upgrade
                </a>
            </div>

            @if($subscription && $subscription->current_period_end)
            <div style="margin-top:20px;padding-top:20px;border-top:1px solid rgba(255,255,255,0.08);">
                <div style="display:flex;justify-content:space-between;font-size:13px;flex-wrap:wrap;gap:10px;">
                    <div style="color:#9CA3AF;">
                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;color:#6B7280;">Billing Cycle</div>
                        <div style="color:white;font-weight:600;">{{ ucfirst($subscription->billing_cycle) }}</div>
                    </div>
                    <div style="color:#9CA3AF;">
                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;color:#6B7280;">Next Billing Date</div>
                        <div style="color:white;font-weight:600;">{{ $subscription->current_period_end->format('M d, Y') }}</div>
                    </div>
                    <div>
                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:4px;color:#6B7280;">Status</div>
                        <span style="background:#D1FAE5;color:#065F46;font-size:12px;font-weight:700;padding:4px 10px;border-radius:20px;">{{ ucfirst($subscription->status) }}</span>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Usage this period -->
        <div class="alb-card mb-4">
            <h3 class="alb-card-title mb-4"><i class="bi bi-activity me-2" style="color:#E31837;"></i>Usage This Period</h3>
            @php
            $usageItems = [
                ['Listings Generated', $user->listings_used, $user->plan?->listings_limit ?? 5, '#E31837'],
                ['AI Generations', $user->ai_generations_used, $user->plan?->ai_generations_limit ?? 5, '#8B5CF6'],
            ];
            @endphp
            @foreach($usageItems as [$label, $used, $limit, $color])
            @php $pct = $limit === -1 ? 0 : min(100, ($used / max(1, $limit)) * 100); @endphp
            <div style="margin-bottom:18px;">
                <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:6px;">
                    <span style="font-weight:600;color:#374151;">{{ $label }}</span>
                    <span style="color:#6B7280;">
                        {{ $used }} / {{ $limit === -1 ? 'Unlimited' : $limit }}
                    </span>
                </div>
                <div class="usage-bar-track">
                    <div class="usage-bar-fill" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Cancel -->
        @if($subscription && $subscription->isActive() && $user->plan?->price_monthly > 0)
        <div class="alb-card" style="border-color:#FCA5A5;">
            <h3 class="alb-card-title mb-2" style="color:#EF4444;"><i class="bi bi-x-circle me-2"></i>Cancel Subscription</h3>
            <p style="font-size:13.5px;color:#6B7280;margin-bottom:16px;">You'll keep access until {{ $subscription->current_period_end?->format('M d, Y') }}. After that you'll be moved to the Free plan.</p>
            <form method="POST" action="{{ route('billing.cancel') }}" onsubmit="return confirm('Are you sure you want to cancel your subscription?')">
                @csrf
                <div class="alb-form-group">
                    <label class="alb-label">Reason (optional)</label>
                    <select name="reason" class="alb-input">
                        <option value="">Select a reason...</option>
                        <option>Too expensive</option>
                        <option>Not using it enough</option>
                        <option>Missing features I need</option>
                        <option>Switching to another tool</option>
                        <option>Other</option>
                    </select>
                </div>
                <button type="submit" class="btn" style="background:#FEE2E2;color:#EF4444;border:1.5px solid #FCA5A5;font-size:13.5px;font-weight:700;padding:10px 22px;border-radius:9px;">
                    <i class="bi bi-x-circle me-2"></i>Cancel Subscription
                </button>
            </form>
        </div>
        @endif
    </div>

    <!-- Right: Payment History -->
    <div class="col-lg-5 fade-in-up fade-in-up-delay-1">
        <div class="alb-card">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-receipt me-2" style="color:#E31837;"></i>Payment History</h3>
            </div>
            @if($payments->isEmpty())
            <div style="text-align:center;padding:32px;color:#9CA3AF;">
                <i class="bi bi-credit-card" style="font-size:32px;display:block;margin-bottom:10px;opacity:0.4;"></i>
                No payments yet.
            </div>
            @else
            <div style="display:flex;flex-direction:column;gap:2px;">
                @foreach($payments as $payment)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px;border-radius:8px;background:#F9FAFB;margin-bottom:6px;">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#111827;">{{ $payment->plan?->name ?? 'Plan' }}</div>
                        <div style="font-size:11.5px;color:#9CA3AF;">{{ $payment->created_at->format('M d, Y') }} · {{ ucfirst($payment->gateway) }}</div>
                        @if($payment->invoice_number)
                        <div style="font-size:11px;color:#CBD5E1;font-family:monospace;">{{ $payment->invoice_number }}</div>
                        @endif
                    </div>
                    <div style="text-align:right;">
                        <div style="font-family:'Sora',sans-serif;font-size:16px;font-weight:700;color:#111827;">${{ number_format($payment->amount, 2) }}</div>
                        @if($payment->status === 'success')
                        <span style="background:#D1FAE5;color:#065F46;font-size:10.5px;font-weight:700;padding:2px 7px;border-radius:20px;">Paid</span>
                        @elseif($payment->status === 'failed')
                        <span style="background:#FEE2E2;color:#991B1B;font-size:10.5px;font-weight:700;padding:2px 7px;border-radius:20px;">Failed</span>
                        @else
                        <span style="background:#FEF3C7;color:#92400E;font-size:10.5px;font-weight:700;padding:2px 7px;border-radius:20px;">{{ ucfirst($payment->status) }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @if($payments->hasPages())
            <div style="margin-top:12px;">{{ $payments->links('pagination::bootstrap-5') }}</div>
            @endif
            @endif
        </div>
    </div>
</div>
@endsection
