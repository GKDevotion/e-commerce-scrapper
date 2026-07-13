<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Welcome</title>
<style>body{margin:0;padding:0;background:#F4F6F9;font-family:Inter,-apple-system,sans-serif;}.wrap{max-width:560px;margin:40px auto;background:white;border-radius:14px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.06);}.hdr{background:linear-gradient(135deg,#E31837,#b01028);padding:28px 36px;text-align:center;}.hdr-t{font-size:16px;font-weight:700;color:white;margin:0;}.body{padding:36px;}h2{font-size:22px;font-weight:700;color:#111827;margin:0 0 12px;}p{font-size:14.5px;color:#6B7280;line-height:1.7;margin:0 0 14px;}.step{display:flex;gap:12px;margin-bottom:12px;}.si{width:34px;height:34px;background:#FEE2E8;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:16px;line-height:34px;text-align:center;}.st{font-size:14px;color:#374151;line-height:1.55;padding-top:6px;}.btn{display:inline-block;background:#E31837;color:white;text-decoration:none;font-size:15px;font-weight:700;padding:13px 30px;border-radius:10px;margin:20px 0;}.ftr{background:#F9FAFB;padding:20px 36px;text-align:center;border-top:1px solid #F3F4F6;}.ftr p{font-size:12px;color:#9CA3AF;margin:3px 0;}.ftr a{color:#E31837;text-decoration:none;}</style>
</head><body>
<div class="wrap">
  <div class="hdr"><div class="hdr-t">🤖 Amazon Listing Builder</div></div>
  <div class="body">
    <h2>Welcome, {{ $user->name }}! 👋</h2>
    <p>Your account is ready. You're on the <strong>Free plan</strong> with 5 listing slots to get started.</p>
    <div class="step"><div class="si">🔗</div><div class="st"><strong>Step 1:</strong> Paste any Amazon product URL into the importer.</div></div>
    <div class="step"><div class="si">🤖</div><div class="st"><strong>Step 2:</strong> Generate with AI or create the listing manually.</div></div>
    <div class="step"><div class="si">📥</div><div class="st"><strong>Step 3:</strong> Export as CSV, Excel, Amazon Flat File, JSON, or PDF.</div></div>
    <a href="{{ config('app.url') }}/dashboard" class="btn">Go to Dashboard →</a>
    <p style="font-size:13px;">Questions? Reply to this email anytime.</p>
  </div>
  <div class="ftr">
    <p>© {{ date('Y') }} Amazon Listing Builder</p>
    <p><a href="{{ config('app.url') }}/dashboard">Dashboard</a> · <a href="{{ config('app.url') }}/billing/plans">Upgrade Plan</a></p>
  </div>
</div></body></html>
