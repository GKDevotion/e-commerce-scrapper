<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes / Scheduled Tasks
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Scheduled Tasks ──────────────────────────────────────────────────────────

// Reset monthly listing usage on the 1st of each month
Schedule::call(function () {
    \App\Models\User::query()->update([
        'listings_used'       => 0,
        'ai_generations_used' => 0,
    ]);
    \Illuminate\Support\Facades\Log::info('Monthly usage counters reset.');
})->monthlyOn(1, '00:00')->name('reset-monthly-usage')->withoutOverlapping();

// Clean up old failed imports (older than 30 days)
Schedule::call(function () {
    $deleted = \App\Models\ProductImport::where('status', 'failed')
        ->where('created_at', '<', now()->subDays(30))
        ->count();
    \App\Models\ProductImport::where('status', 'failed')
        ->where('created_at', '<', now()->subDays(30))
        ->delete();
    \Illuminate\Support\Facades\Log::info("Cleaned up {$deleted} failed imports.");
})->daily()->name('clean-failed-imports')->withoutOverlapping();

// Clean up old export files (older than 7 days)
Schedule::call(function () {
    $exports = \App\Models\Export::where('created_at', '<', now()->subDays(7))
        ->where('status', 'completed')
        ->get();
    foreach ($exports as $export) {
        if ($export->file_path && \Illuminate\Support\Facades\Storage::exists($export->file_path)) {
            \Illuminate\Support\Facades\Storage::delete($export->file_path);
        }
        $export->delete();
    }
    \Illuminate\Support\Facades\Log::info("Cleaned up {$exports->count()} old export files.");
})->weekly()->name('clean-old-exports')->withoutOverlapping();

// Prune old API logs (keep last 30 days)
Schedule::call(function () {
    $deleted = \App\Models\ApiLog::where('created_at', '<', now()->subDays(30))->delete();
    \Illuminate\Support\Facades\Log::info("Pruned {$deleted} old API logs.");
})->daily()->name('prune-api-logs')->withoutOverlapping();

// Check and expire subscriptions
Schedule::call(function () {
    \App\Models\Subscription::where('status', 'active')
        ->where('current_period_end', '<', now())
        ->update(['status' => 'expired']);
})->hourly()->name('expire-subscriptions')->withoutOverlapping();
