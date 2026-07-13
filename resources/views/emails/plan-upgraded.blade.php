<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Plan Upgraded</title>
<style>body{margin:0;padding:0;background:#F4F6F9;font-family:Inter,-apple-system,sans-serif;}.wrap{max-width:560px;margin:40px auto;background:white;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.06);}.hdr{background:linear-gradient(135deg,#E31837,#b01028);padding:28px 36px;text-align:center;}.hdr-t{font-size:16px;font-weight:700;color:white;margin:0;}.body{padding:36px;}h2{font-size:22px;font-weight:700;color:#111827;margin:0 0 12px;}p{font-size:14.5px;color:#6B7280;line-height:1.7;margin:0 0 14px;}.plan-box{background:#FFF5F5;border:1.5px solid #FEE2E8;border-radius:12px;padding:20px 24px;margin:20px 0;}.plan-name{font-size:24px;font-weight:800;color:#E31837;margin:0 0 6px;}.plan-price{font-size:16px;color:#374151;margin:0;}.feat{font-size:13.5px;color:#374151;margin:6px 0;}.btn{display:inline-block;background:#E31837;color:white;text-decoration:none;font-size:15px;font-weight:700;padding:13px 30px;border-radius:10px;margin:20px 0;}.ftr{background:#F9FAFB;padding:20px 36px;text-align:center;border-top:1px solid #F3F4F6;}.ftr p{font-size:12px;color:#9CA3AF;margin:3px 0;}.ftr a{color:#E31837;text-decoration:none;}</style>
</head><body>
<div class="wrap">
  <div class="hdr"><div class="hdr-t">🤖 Amazon Listing Builder</div></div>
  <div class="body">
    <h2>You're upgraded! 🎉</h2>
    <p>Hi {{ $user->name }}, your subscription is now active.</p>
    <div class="plan-box">
      <div class="plan-name">{{ $plan->name }} Plan</div>
      <div class="plan-price">{{ ucfirst($billingCycle) }} · {{ $plan->currency === 'INR' ? '₹' : '$' }}{{ number_format($amount,2) }}</div>
    </div>
    <p><strong>What's unlocked:</strong></p>
    <div class="feat">✅ {{ $plan->listings_limit_display }} listing slots/month</div>
    @if($plan->amazon_publish)<div class="feat">✅ Amazon SP-API direct publishing</div>@endif
    @if($plan->bulk_import)<div class="feat">✅ Bulk URL import</div>@endif
    @if($plan->priority_support)<div class="feat">✅ Priority support</div>@endif
    @if($plan->api_access)<div class="feat">✅ REST API access</div>@endif
    <a href="{{ config('app.url') }}/dashboard" class="btn">Start Using Your Plan →</a>
    <p style="font-size:13px;">Manage at <a href="{{ config('app.url') }}/billing/subscription" style="color:#E31837;">Billing Settings</a>.</p>
  </div>
  <div class="ftr">
    <p>© {{ date('Y') }} Amazon Listing Builder</p>
    <p><a href="{{ config('app.url') }}/billing/subscription">Manage Subscription</a></p>
  </div>
</div></body></html>
