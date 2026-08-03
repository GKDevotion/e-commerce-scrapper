<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);         // google, facebook
            $table->string('slot_key', 60)->unique(); // e.g. sidebar_top, listing_banner
            $table->string('label', 100);
            $table->text('ad_code')->nullable();     // raw HTML/JS embed code
            $table->boolean('is_enabled')->default(false);
            $table->string('placement', 60)->nullable(); // sidebar, header, content, footer
            $table->timestamps();
        });

        // Pre-populate slots
        \Illuminate\Support\Facades\DB::table('ad_settings')->insert([
            ['provider'=>'google',   'slot_key'=>'sidebar_top',      'label'=>'Sidebar Top (Google)',        'is_enabled'=>false, 'placement'=>'sidebar',  'created_at'=>now(),'updated_at'=>now()],
            ['provider'=>'google',   'slot_key'=>'content_top',      'label'=>'Content Top Banner (Google)', 'is_enabled'=>false, 'placement'=>'content',  'created_at'=>now(),'updated_at'=>now()],
            ['provider'=>'google',   'slot_key'=>'content_bottom',   'label'=>'Content Bottom (Google)',     'is_enabled'=>false, 'placement'=>'content',  'created_at'=>now(),'updated_at'=>now()],
            ['provider'=>'facebook', 'slot_key'=>'fb_sidebar',       'label'=>'Sidebar (Facebook)',          'is_enabled'=>false, 'placement'=>'sidebar',  'created_at'=>now(),'updated_at'=>now()],
            ['provider'=>'facebook', 'slot_key'=>'fb_content_top',   'label'=>'Content Top (Facebook)',      'is_enabled'=>false, 'placement'=>'content',  'created_at'=>now(),'updated_at'=>now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_settings');
    }
};
