<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfills the `platform` column for existing rows where it is NULL
 * by detecting the platform from the stored amazon_url value.
 *
 * Safe to run multiple times (only touches NULL rows).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('product_imports', 'platform')) {
            return; // Column doesn't exist yet — 000007 will handle it
        }

        // Flipkart
        DB::table('product_imports')
            ->whereNull('platform')
            ->where('amazon_url', 'like', '%flipkart.com%')
            ->update(['platform' => 'flipkart']);

        // Meesho
        DB::table('product_imports')
            ->whereNull('platform')
            ->where('amazon_url', 'like', '%meesho.com%')
            ->update(['platform' => 'meesho']);

        // Everything else defaults to Amazon
        DB::table('product_imports')
            ->whereNull('platform')
            ->update(['platform' => 'amazon']);
    }

    public function down(): void
    {
        // Non-destructive — no rollback needed
    }
};
