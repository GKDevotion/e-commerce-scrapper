@extends('layouts.app')
@section('title','Checkout — '.$plan->name)
@section('page-title','Checkout')
@section('content')
<div class="row justify-content-center">
  <div class="col-lg-6 col-md-8">
    <div class="alb-card fade-in-up" style="text-align:center;padding:48px 32px;">
      <div style="width:72px;height:72px;background:#FEF3C7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:32px;color:#F59E0B;">
        <i class="bi bi-gear"></i>
      </div>
      <h3 style="font-family:'Sora',sans-serif;font-size:20px;font-weight:700;margin-bottom:10px;">Payment Gateway Not Configured</h3>
      <p style="color:#6B7280;font-size:14px;line-height:1.7;max-width:400px;margin:0 auto 24px;">
        You selected the <strong>{{ $plan->name }}</strong> plan ({{ ucfirst($billingCycle) }}, <strong>{{ $currency === 'INR' ? '₹' : '$' }}{{ number_format($amount,2) }}</strong>).<br>
        Add your gateway credentials to <code style="background:#F3F4F6;padding:2px 6px;border-radius:4px;">.env</code>:
      </p>
      <div style="background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;padding:16px 20px;text-align:left;margin-bottom:24px;">
        <div style="font-size:11.5px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px;">Razorpay (India)</div>
        <code style="font-size:12.5px;color:#374151;display:block;line-height:2;">RAZORPAY_KEY_ID=rzp_live_xxx<br>RAZORPAY_KEY_SECRET=xxx</code>
        <div style="font-size:11.5px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:.05em;margin:12px 0 8px;">Stripe (International)</div>
        <code style="font-size:12.5px;color:#374151;display:block;line-height:2;">STRIPE_KEY=pk_live_xxx<br>STRIPE_SECRET=sk_live_xxx</code>
      </div>
      <a href="{{ route('billing.plans') }}" class="btn-alb-outline btn"><i class="bi bi-arrow-left me-2"></i>Back to Plans</a>
    </div>
  </div>
</div>
@endsection
