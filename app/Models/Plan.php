<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'price_monthly', 'price_yearly',
        'currency', 'listings_limit', 'ai_generations_limit', 'exports_limit',
        'amazon_publish', 'bulk_import', 'team_accounts', 'priority_support',
        'api_access', 'features', 'razorpay_plan_id', 'stripe_price_id',
        'is_active', 'is_featured', 'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'amazon_publish' => 'boolean',
        'bulk_import' => 'boolean',
        'team_accounts' => 'boolean',
        'priority_support' => 'boolean',
        'api_access' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function isFree(): bool
    {
        return $this->price_monthly == 0;
    }

    public function getFormattedPriceAttribute(): string
    {
        if ($this->price_monthly == 0) return 'Free';
        return '$' . number_format($this->price_monthly, 2) . '/mo';
    }

    public function getListingsLimitDisplayAttribute(): string
    {
        return $this->listings_limit === -1 ? 'Unlimited' : (string)$this->listings_limit;
    }
}
