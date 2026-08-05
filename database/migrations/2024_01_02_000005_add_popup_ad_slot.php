<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add popup_left slot if it doesn't exist
        if (!DB::table('ad_settings')->where('slot_key', 'popup_left')->exists()) {
            DB::table('ad_settings')->insert([
                'provider'   => 'google',
                'slot_key'   => 'popup_left',
                'label'      => 'Popup Banner — Left Side',
                'is_enabled' => false,
                'placement'  => 'popup',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('ad_settings')->where('slot_key', 'popup_left')->delete();
    }
};
