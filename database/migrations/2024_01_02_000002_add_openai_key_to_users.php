<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // OpenAI personal key — if null, user gets manual mode only
            if (!Schema::hasColumn('users', 'openai_api_key')) {
                $table->string('openai_api_key', 200)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'openai_model')) {
                $table->string('openai_model', 50)->default('gpt-4o')->after('openai_api_key');
            }
            // Remove listings_used cap — now unlimited
            // (column stays for tracking/analytics, just not enforced)
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['openai_api_key', 'openai_model']);
        });
    }
};
