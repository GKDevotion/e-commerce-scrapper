<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->unique(); // google_analytics, google_tag_manager, microsoft_clarity, facebook_pixel, hotjar, mixpanel, custom
            $table->string('label', 100);
            $table->string('icon_url', 255)->nullable();
            $table->text('tracking_id')->nullable();   // GA4 measurement ID, GTM ID, Clarity ID etc
            $table->text('head_code')->nullable();     // raw <script> for <head>
            $table->text('body_code')->nullable();     // raw <noscript> for after <body>
            $table->boolean('is_enabled')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('analytics_settings')->insert([
            [
                'provider'   => 'google_analytics',
                'label'      => 'Google Analytics 4',
                'is_enabled' => false,
                'sort_order' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'provider'   => 'google_tag_manager',
                'label'      => 'Google Tag Manager',
                'is_enabled' => false,
                'sort_order' => 2,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'provider'   => 'microsoft_clarity',
                'label'      => 'Microsoft Clarity',
                'is_enabled' => false,
                'sort_order' => 3,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'provider'   => 'google_search_console',
                'label'      => 'Google Search Console',
                'is_enabled' => false,
                'sort_order' => 4,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'provider'   => 'facebook_pixel',
                'label'      => 'Facebook Pixel',
                'is_enabled' => false,
                'sort_order' => 5,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'provider'   => 'hotjar',
                'label'      => 'Hotjar',
                'is_enabled' => false,
                'sort_order' => 6,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'provider'   => 'custom',
                'label'      => 'Custom Script',
                'is_enabled' => false,
                'sort_order' => 7,
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_settings');
    }
};
