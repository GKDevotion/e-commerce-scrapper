<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AnalyticsSetting extends Model
{
    protected $fillable = [
        'provider', 'label', 'icon_url',
        'tracking_id', 'head_code', 'body_code',
        'is_enabled', 'sort_order',
    ];

    protected $casts = ['is_enabled' => 'boolean'];

    /** All enabled analytics providers — cached 10 min */
    public static function enabled(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('analytics_enabled', 600, fn() =>
            static::query()->where('is_enabled', true)->orderBy('sort_order')->get()
        );
    }

    protected static function booted(): void
    {
        static::saved(fn()   => Cache::forget('analytics_enabled'));
        static::deleted(fn() => Cache::forget('analytics_enabled'));
    }

    /**
     * Generate the <head> script tag for this provider.
     * Uses tracking_id to build the canonical snippet if head_code is blank.
     */
    public function renderHeadCode(): string
    {
        if ($this->head_code) return $this->head_code;

        $id = htmlspecialchars($this->tracking_id ?? '', ENT_QUOTES);
        if (!$id) return '';

        return match ($this->provider) {
            'google_analytics' => <<<HTML
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id={$id}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{$id}');
</script>
HTML,
            'google_tag_manager' => <<<HTML
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{$id}');</script>
HTML,
            'microsoft_clarity' => <<<HTML
<!-- Microsoft Clarity -->
<script type="text/javascript">
    (function(c,l,a,r,i,t,y){
        c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
        t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
        y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "{$id}");
</script>
HTML,
            'google_search_console' => <<<HTML
<!-- Google Search Console Verification -->
<meta name="google-site-verification" content="{$id}" />
HTML,
            'facebook_pixel' => <<<HTML
<!-- Facebook Pixel -->
<script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '{$id}');
  fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
  src="https://www.facebook.com/tr?id={$id}&ev=PageView&noscript=1"/></noscript>
HTML,
            'hotjar' => <<<HTML
<!-- Hotjar -->
<script>
    (function(h,o,t,j,a,r){
        h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
        h._hjSettings={hjid:'{$id}',hjsv:6};
        a=o.getElementsByTagName('head')[0];
        r=o.createElement('script');r.async=1;
        r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
        a.appendChild(r);
    })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
</script>
HTML,
            default => '',
        };
    }

    /**
     * Generate the <body> noscript tag (GTM only needs this).
     */
    public function renderBodyCode(): string
    {
        if ($this->body_code) return $this->body_code;

        $id = htmlspecialchars($this->tracking_id ?? '', ENT_QUOTES);
        if (!$id) return '';

        return match ($this->provider) {
            'google_tag_manager' => <<<HTML
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={$id}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
HTML,
            default => '',
        };
    }
}
