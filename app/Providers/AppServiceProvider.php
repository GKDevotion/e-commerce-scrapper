<?php

namespace App\Providers;

use App\Services\AI\AiGenerationService;
use App\Services\Amazon\AmazonSpApiService;
use App\Services\Export\ExportService;
use App\Services\Scraper\AmazonScraperService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind services as singletons
        $this->app->singleton(AmazonScraperService::class);
        $this->app->singleton(\App\Services\Scraper\PlaywrightScraperService::class);
        $this->app->singleton(AiGenerationService::class);
        $this->app->singleton(AmazonSpApiService::class);
        $this->app->singleton(ExportService::class);
        $this->app->singleton(\App\Services\Export\ImageDownloadService::class);
    }

    public function boot(): void
    {
        // Enforce HTTPS in production
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Strict model behavior
        Model::shouldBeStrict(!app()->isProduction());

        // Rate limiters
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('scraper', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('ai-generation', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Blade directives
        \Illuminate\Support\Facades\Blade::directive('money', function ($expression) {
            return "<?php echo '$' . number_format({$expression}, 2); ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('adminOnly', function () {
            return "<?php if(auth()->check() && auth()->user()->isAdmin()): ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('endAdminOnly', function () {
            return "<?php endif; ?>";
        });
    }
}
