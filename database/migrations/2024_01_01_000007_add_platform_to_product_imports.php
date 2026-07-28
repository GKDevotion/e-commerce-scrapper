<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `platform` column to product_imports for existing installations.
 * Safe to run even if the column already exists (fresh installs get it
 * from the base migration 000003).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('product_imports', 'platform')) {
            Schema::table('product_imports', function (Blueprint $table) {
                $table->enum('platform', ['amazon', 'flipkart', 'meesho'])
                      ->default('amazon')
                      ->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_imports', 'platform')) {
            Schema::table('product_imports', function (Blueprint $table) {
                $table->dropColumn('platform');
            });
        }
    }
};
