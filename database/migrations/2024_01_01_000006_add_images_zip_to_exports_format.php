<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Widen the format enum to include images_zip.
        // Using raw DDL since changing an enum's allowed values isn't
        // supported by Laravel's fluent Blueprint::enum() modify path.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE exports MODIFY format ENUM('csv', 'excel', 'amazon_flat_file', 'json', 'pdf', 'images_zip') NOT NULL");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE exports MODIFY format ENUM('csv', 'excel', 'amazon_flat_file', 'json', 'pdf') NOT NULL");
        }
    }
};
