<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();      // amazon, flipkart, meesho
            $table->string('label', 100);
            $table->boolean('is_enabled')->default(true);
            $table->string('icon', 100)->nullable();
            $table->string('color', 20)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed defaults — Amazon + Flipkart enabled, Meesho disabled
        DB::table('platform_settings')->insert([
            ['key'=>'amazon',   'label'=>'Amazon',   'is_enabled'=>true,  'icon'=>'bi-bag-check-fill', 'color'=>'#FF9900', 'sort_order'=>1, 'created_at'=>now(), 'updated_at'=>now()],
            ['key'=>'flipkart', 'label'=>'Flipkart', 'is_enabled'=>true,  'icon'=>'bi-cart-fill',      'color'=>'#2874F0', 'sort_order'=>2, 'created_at'=>now(), 'updated_at'=>now()],
            ['key'=>'meesho',   'label'=>'Meesho',   'is_enabled'=>false, 'icon'=>'bi-shop-window',    'color'=>'#F43397', 'sort_order'=>3, 'created_at'=>now(), 'updated_at'=>now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
