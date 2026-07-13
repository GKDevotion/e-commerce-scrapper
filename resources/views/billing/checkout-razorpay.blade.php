@extends('layouts.app')
@section('title','Checkout — '.$plan->name)
@section('page-title','Checkout')
@section('content')
<div class="row justify-content-center">
  <div class="col-lg-5 col-md-7">
    <div class="alb-card fade-in-up">
      <div style="text-align:center;padding:20px 0 24px;border-bottom:1px solid #F3F4F6;margin-bottom:24px;">
        <div style="width:60px;height:60px;background:#FEE2E8;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:26px;color:#E31837;">
          <i class="bi bi-credit-card"></i>
        </div>
        <div style="font-family:'Sora',sans-serif;font-size:22px;font-weight:800;color:#111827;margin-bottom:4px;">{{ $plan->name }} Plan</div>
        <div style="font-size:13px;color:#6B7280;margin-bottom:12px;">{{ ucfirst($billingCycle) }} subscription</div>
        <div style="font-family:'Sora',sans-serif;font-size:34px;font-weight:900;color:#E31837;">
          ₹{{ number_format($amount,0) }}<span style="font-size:15px;font-weight:400;color:#9CA3AF;">/{{ $billingCycle==='yearly'?'year':'month' }}</span>
        </div>
      </div>

      <div style="margin-bottom:20px;">
        @foreach(array_filter([$plan->listings_limit_display.' listings/month', $plan->amazon_publish?'Amazon SP-API publish':null, $plan->bulk_import?'Bulk import':null, $plan->priority_support?'Priority support':null]) as $feat)
        <div style="display:flex;align-items:center;gap:10px;font-size:13.5px;color:#374151;margin-bottom:8px;">
          <i class="bi bi-check-circle-fill" style="color:#10B981;"></i> {{ $feat }}
        </div>
        @endforeach
      </div>

      <div style="background:#F9FAFB;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#6B7280;">
        <i class="bi bi-person-circle me-1"></i>Subscribing as <strong style="color:#374151;">{{ $user->email }}</strong>
      </div>

      <button id="rzpBtn" class="btn-alb-primary btn w-100 py-3" style="font-size:16px;font-family:'Sora',sans-serif;">
        <i class="bi bi-lock-fill me-2"></i>Pay ₹{{ number_format($amount,0) }} Securely
      </button>

      <div style="text-align:center;margin-top:12px;font-size:12px;color:#9CA3AF;">
        <i class="bi bi-shield-check me-1"></i>Secured by Razorpay · 256-bit SSL
      </div>

      <form id="rzpForm" method="POST" action="{{ route('billing.razorpay.verify') }}" style="display:none;">
        @csrf
        <input type="hidden" name="subscription_id" value="{{ $subscription->id }}">
        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
        <input type="hidden" name="razorpay_payment_id" id="rzp_pid">
        <input type="hidden" name="razorpay_order_id" id="rzp_oid">
        <input type="hidden" name="razorpay_signature" id="rzp_sig">
      </form>
    </div>
  </div>
</div>
@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var rzp = new Razorpay({
  key: "{{ $keyId }}",
  amount: {{ $amountPaise }},
  currency: "{{ $currency }}",
  name: "Amazon Listing Builder",
  description: "{{ $plan->name }} Plan — {{ ucfirst($billingCycle) }}",
  prefill: { name: "{{ $user->name }}", email: "{{ $user->email }}", contact: "{{ $user->phone ?? '' }}" },
  notes: { user_id: "{{ $user->id }}", plan_id: "{{ $plan->id }}", subscription_id: "{{ $subscription->id }}" },
  theme: { color: "#E31837" },
  handler: function(r) {
    document.getElementById('rzp_pid').value = r.razorpay_payment_id;
    document.getElementById('rzp_oid').value = r.razorpay_order_id || '';
    document.getElementById('rzp_sig').value  = r.razorpay_signature || '';
    document.getElementById('rzpForm').submit();
  },
  modal: { ondismiss: function() { document.getElementById('rzpBtn').disabled=false; document.getElementById('rzpBtn').innerHTML='<i class="bi bi-lock-fill me-2"></i>Pay ₹{{ number_format($amount,0) }} Securely'; } }
});
document.getElementById('rzpBtn').addEventListener('click', function() {
  this.disabled = true;
  this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Opening...';
  rzp.open();
});
</script>
@endpush
@endsection
