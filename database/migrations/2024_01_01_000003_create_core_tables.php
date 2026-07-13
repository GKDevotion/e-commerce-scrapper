<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Subscriptions
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['active', 'cancelled', 'expired', 'pending', 'trial'])->default('active');
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->enum('payment_gateway', ['razorpay', 'stripe', 'manual'])->nullable();
            $table->string('gateway_subscription_id')->nullable();
            $table->string('gateway_customer_id')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        // Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('gateway_payment_id')->unique();
            $table->enum('gateway', ['razorpay', 'stripe', 'manual']);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'success', 'failed', 'refunded']);
            $table->json('gateway_response')->nullable();
            $table->string('invoice_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        // Product Imports (raw scraped data)
        Schema::create('product_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('amazon_url', 2048);
            $table->string('asin', 20)->nullable();
            $table->enum('status', ['pending', 'scraping', 'scraped', 'processing', 'completed', 'failed'])->default('pending');
            // Raw scraped data
            $table->string('original_title')->nullable();
            $table->string('original_brand')->nullable();
            $table->string('original_manufacturer')->nullable();
            $table->text('original_description')->nullable();
            $table->json('original_bullet_points')->nullable();
            $table->json('original_specifications')->nullable();
            $table->json('original_images')->nullable();
            $table->string('original_category')->nullable();
            $table->json('original_attributes')->nullable();
            $table->string('product_weight')->nullable();
            $table->string('product_dimensions')->nullable();
            $table->decimal('original_price', 10, 2)->nullable();
            $table->string('original_price_currency', 3)->nullable();
            $table->json('raw_scraped_data')->nullable(); // Full raw data
            // User inputs
            $table->string('target_brand_name')->nullable();
            $table->string('target_manufacturer')->nullable();
            $table->text('target_keywords')->nullable();
            $table->text('scrape_error')->nullable();
            $table->timestamp('scraped_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index('asin');
        });

        // AI Generated content
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_import_id')->constrained()->cascadeOnDelete();
            $table->string('generation_name')->nullable(); // User given name
            $table->enum('status', ['pending', 'generating', 'completed', 'failed'])->default('pending');
            // Generated content
            $table->string('generated_title')->nullable();
            $table->json('generated_bullet_points')->nullable();
            $table->text('generated_description')->nullable();
            $table->text('generated_search_terms')->nullable();
            $table->text('generated_seo_keywords')->nullable();
            $table->text('generated_highlights')->nullable();
            $table->text('generated_aplus_content')->nullable();
            // Brand replacements applied
            $table->string('brand_name')->nullable();
            $table->string('manufacturer')->nullable();
            // AI metadata
            $table->string('ai_model')->nullable();
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->decimal('ai_cost', 10, 6)->default(0);
            $table->json('prompt_used')->nullable();
            $table->text('generation_error')->nullable();
            $table->timestamp('generated_at')->nullable();
            // Publishing
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->string('amazon_listing_id')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        // Exports
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_generation_id')->constrained()->cascadeOnDelete();
            $table->enum('format', ['csv', 'excel', 'amazon_flat_file', 'json', 'pdf']);
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->enum('status', ['pending', 'generating', 'completed', 'failed'])->default('pending');
            $table->text('error')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();
        });

        // API Logs
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service'); // openai, amazon, scraper, razorpay, stripe
            $table->string('endpoint')->nullable();
            $table->enum('method', ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])->nullable();
            $table->integer('status_code')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'service', 'created_at']);
        });

        // Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'action', 'created_at']);
        });

        // AI Prompt Templates (admin configurable)
        Schema::create('prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('system_prompt');
            $table->text('user_prompt_template');
            $table->string('ai_model')->default('gpt-4o');
            $table->integer('max_tokens')->default(4000);
            $table->decimal('temperature', 3, 2)->default(0.7);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_templates');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('api_logs');
        Schema::dropIfExists('exports');
        Schema::dropIfExists('ai_generations');
        Schema::dropIfExists('product_imports');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
    }
};
