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
          ${{ number_format($amount,2) }}<span style="font-size:15px;font-weight:400;color:#9CA3AF;">/{{ $billingCycle==='yearly'?'year':'month' }}</span>
        </div>
      </div>

      <div style="margin-bottom:20px;">
        <label class="alb-label">Card Details</label>
        <div id="card-element" style="background:#F9FAFB;border:1.5px solid #E5E7EB;border-radius:10px;padding:14px 16px;"></div>
        <div id="card-errors" style="color:#EF4444;font-size:12.5px;margin-top:6px;min-height:18px;"></div>
      </div>

      <div style="background:#F9FAFB;border-radius:10px;padding:12px 16px;margin-bottom:20px;font-size:13px;color:#6B7280;">
        <i class="bi bi-person-circle me-1"></i>Subscribing as <strong style="color:#374151;">{{ $user->email }}</strong>
      </div>

      <button id="stripeBtn" class="btn-alb-primary btn w-100 py-3" style="font-size:16px;font-family:'Sora',sans-serif;">
        <span id="stripeText"><i class="bi bi-lock-fill me-2"></i>Pay ${{ number_format($amount,2) }}</span>
        <span id="stripeLoad" style="display:none;"><span class="spinner-border spinner-border-sm me-2"></span>Processing...</span>
      </button>

      <div style="text-align:center;margin-top:12px;font-size:12px;color:#9CA3AF;">
        <i class="bi bi-shield-check me-1"></i>Secured by Stripe · 256-bit SSL
      </div>

      <form id="stripeForm" method="POST" action="{{ route('billing.stripe.verify') }}" style="display:none;">
        @csrf
        <input type="hidden" name="subscription_id" value="{{ $subscription->id }}">
        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
        <input type="hidden" name="payment_intent_id" id="stripe_pi">
      </form>
    </div>
  </div>
</div>
@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
const stripe   = Stripe("{{ $publishableKey }}");
const elements = stripe.elements();
const card     = elements.create('card', {
  style: { base: { fontSize:'15px', color:'#111827', fontFamily:'Inter,sans-serif', '::placeholder':{ color:'#9CA3AF' } }, invalid:{ color:'#EF4444' } }
});
card.mount('#card-element');
card.on('change', function(e){ document.getElementById('card-errors').textContent = e.error ? e.error.message : ''; });

document.getElementById('stripeBtn').addEventListener('click', async function() {
  this.disabled = true;
  document.getElementById('stripeText').style.display = 'none';
  document.getElementById('stripeLoad').style.display  = 'inline-flex';

  const res = await fetch("{{ route('billing.stripe.intent') }}", {
    method: 'POST',
    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':"{{ csrf_token() }}" },
    body: JSON.stringify({ subscription_id: {{ $subscription->id }}, amount_cents: {{ $amountCents }} })
  });
  const { client_secret, error: serverErr } = await res.json();

  if (serverErr) {
    document.getElementById('card-errors').textContent = serverErr;
    document.getElementById('stripeText').style.display = 'inline'; document.getElementById('stripeLoad').style.display = 'none';
    this.disabled = false; return;
  }

  const { paymentIntent, error } = await stripe.confirmCardPayment(client_secret, {
    payment_method: { card: card, billing_details: { email:"{{ $user->email }}" } }
  });

  if (error) {
    document.getElementById('card-errors').textContent = error.message;
    document.getElementById('stripeText').style.display = 'inline'; document.getElementById('stripeLoad').style.display = 'none';
    this.disabled = false;
  } else if (paymentIntent.status === 'succeeded') {
    document.getElementById('stripe_pi').value = paymentIntent.id;
    document.getElementById('stripeForm').submit();
  }
});
</script>
@endpush
@endsection
