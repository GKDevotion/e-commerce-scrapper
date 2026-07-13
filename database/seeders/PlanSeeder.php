<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Perfect to get started with AI listing generation',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'listings_limit' => 5,
                'ai_generations_limit' => 5,
                'exports_limit' => 10,
                'amazon_publish' => false,
                'bulk_import' => false,
                'team_accounts' => false,
                'priority_support' => false,
                'api_access' => false,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
                'features' => ['5 listings/month', 'CSV & JSON export', 'Basic AI generation', 'Side-by-side comparison'],
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Great for individual Amazon sellers scaling up',
                'price_monthly' => 29,
                'price_yearly' => 278,
                'listings_limit' => 50,
                'ai_generations_limit' => 50,
                'exports_limit' => 100,
                'amazon_publish' => false,
                'bulk_import' => false,
                'team_accounts' => false,
                'priority_support' => false,
                'api_access' => false,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 2,
                'features' => ['50 listings/month', 'All export formats', 'Priority AI queue', 'Email support'],
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'For power sellers and growing brands',
                'price_monthly' => 79,
                'price_yearly' => 758,
                'listings_limit' => 200,
                'ai_generations_limit' => 200,
                'exports_limit' => 500,
                'amazon_publish' => true,
                'bulk_import' => true,
                'team_accounts' => false,
                'priority_support' => true,
                'api_access' => false,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 3,
                'features' => ['200 listings/month', 'Amazon SP-API publish', 'Bulk URL import', 'Priority support', 'Advanced analytics'],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For agencies and large-scale operations',
                'price_monthly' => 199,
                'price_yearly' => 1910,
                'listings_limit' => -1,
                'ai_generations_limit' => -1,
                'exports_limit' => -1,
                'amazon_publish' => true,
                'bulk_import' => true,
                'team_accounts' => true,
                'priority_support' => true,
                'api_access' => true,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
                'features' => ['Unlimited listings', 'Team accounts', 'API access', 'Custom AI prompts', 'Dedicated support', 'Multi-marketplace'],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        $this->command?->info('✓ Plans seeded');
    }
}
