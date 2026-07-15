<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_imports', function (Blueprint $table) {
            $table->enum('platform', ['amazon', 'flipkart', 'meesho'])
                  ->default('amazon')
                  ->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_imports', function (Blueprint $table) {
            $table->dropColumn('platform');
        });
    }
};
