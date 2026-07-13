@extends('layouts.app')
@section('title','Payments')
@section('page-title','Payments')
@section('content')
<div class="row g-3 mb-4 fade-in-up">
  <div class="col-md-4">
    <div class="alb-stat">
      <div class="alb-stat-icon green"><i class="bi bi-currency-dollar"></i></div>
      <div><div class="alb-stat-value">${{ number_format($totalRevenue,2) }}</div><div class="alb-stat-label">Total Revenue</div></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="alb-stat">
      <div class="alb-stat-icon blue"><i class="bi bi-calendar-month"></i></div>
      <div><div class="alb-stat-value">${{ number_format($monthRevenue,2) }}</div><div class="alb-stat-label">This Month</div></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="alb-stat">
      <div class="alb-stat-icon red"><i class="bi bi-receipt"></i></div>
      <div><div class="alb-stat-value">{{ $payments->total() }}</div><div class="alb-stat-label">Transactions</div></div>
    </div>
  </div>
</div>

<div class="alb-card mb-4 fade-in-up" style="padding:14px 20px;">
  <form method="GET" class="d-flex gap-3 flex-wrap align-items-center">
    <select name="status" class="alb-input" style="width:auto;padding:8px 14px;">
      <option value="">All Status</option>
      @foreach(['success','failed','pending','refunded'] as $s)
      <option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
      @endforeach
    </select>
    <select name="gateway" class="alb-input" style="width:auto;padding:8px 14px;">
      <option value="">All Gateways</option>
      @foreach(['razorpay','stripe','manual'] as $g)
      <option value="{{ $g }}" {{ request('gateway')===$g?'selected':'' }}>{{ ucfirst($g) }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn-alb-primary btn" style="padding:9px 20px;">Filter</button>
    <a href="{{ route('admin.payments') }}" class="btn-alb-outline btn" style="padding:9px 16px;">Clear</a>
  </form>
</div>

<div class="alb-card fade-in-up" style="padding:0;overflow:hidden;">
  <div style="overflow-x:auto;">
    <table class="alb-table" style="min-width:800px;">
      <thead><tr><th>Invoice</th><th>User</th><th>Plan</th><th>Amount</th><th>Gateway</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
        @forelse($payments as $payment)
        @php
          $statusColors = ['success'=>['#D1FAE5','#065F46'],'failed'=>['#FEE2E2','#991B1B'],'pending'=>['#FEF3C7','#92400E'],'refunded'=>['#EDE9FE','#5B21B6']];
          $sc = $statusColors[$payment->status] ?? ['#F3F4F6','#6B7280'];
        @endphp
        <tr>
          <td style="font-size:12px;font-family:monospace;color:#6B7280;">{{ $payment->invoice_number ?? Str::limit($payment->gateway_payment_id, 16) }}</td>
          <td>
            <div style="font-size:13px;font-weight:600;">{{ $payment->user?->name }}</div>
            <div style="font-size:11px;color:#9CA3AF;">{{ $payment->user?->email }}</div>
          </td>
          <td style="font-size:13px;">{{ $payment->plan?->name ?? '—' }}</td>
          <td style="font-size:14px;font-weight:700;color:#111827;">{{ $payment->currency==='INR'?'₹':'$' }}{{ number_format($payment->amount,2) }}</td>
          <td><span style="background:#F3F4F6;font-size:12px;font-weight:600;padding:3px 9px;border-radius:6px;">{{ ucfirst($payment->gateway) }}</span></td>
          <td><span style="background:{{ $sc[0] }};color:{{ $sc[1] }};font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:20px;">{{ ucfirst($payment->status) }}</span></td>
          <td style="font-size:12px;color:#9CA3AF;white-space:nowrap;">{{ $payment->created_at->format('M d, Y') }}</td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:40px;color:#9CA3AF;">No payments yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($payments->hasPages())
  <div style="padding:14px 20px;border-top:1px solid #F3F4F6;">{{ $payments->links('pagination::bootstrap-5') }}</div>
  @endif
</div>
@endsection
