<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImport extends Model
{
    protected $fillable = [
        'user_id', 'amazon_url', 'asin', 'status',
        'original_title', 'original_brand', 'original_manufacturer',
        'original_description', 'original_bullet_points', 'original_specifications',
        'original_images', 'original_category', 'original_attributes',
        'product_weight', 'product_dimensions', 'original_price', 'original_price_currency',
        'raw_scraped_data', 'target_brand_name', 'target_manufacturer', 'target_keywords',
        'scrape_error', 'scraped_at',
    ];

    protected $casts = [
        'original_bullet_points' => 'array',
        'original_specifications' => 'array',
        'original_images' => 'array',
        'original_attributes' => 'array',
        'raw_scraped_data' => 'array',
        'scraped_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function aiGenerations()
    {
        return $this->hasMany(AiGeneration::class);
    }

    public function latestGeneration()
    {
        return $this->hasOne(AiGeneration::class)->latest();
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending' => '<span class="badge bg-secondary">Pending</span>',
            'scraping' => '<span class="badge bg-warning text-dark">Scraping</span>',
            'scraped' => '<span class="badge bg-info">Scraped</span>',
            'processing' => '<span class="badge bg-primary">Processing</span>',
            'completed' => '<span class="badge bg-success">Completed</span>',
            'failed' => '<span class="badge bg-danger">Failed</span>',
            default => '<span class="badge bg-secondary">' . ucfirst($this->status) . '</span>',
        };
    }

    public function getPrimaryImageAttribute(): ?string
    {
        $images = $this->original_images;
        return is_array($images) && !empty($images) ? $images[0] : null;
    }
}
