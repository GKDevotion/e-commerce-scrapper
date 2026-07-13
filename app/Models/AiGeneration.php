<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiGeneration extends Model
{
    protected $fillable = [
        'user_id', 'product_import_id', 'generation_method', 'generation_name', 'status',
        'generated_title', 'generated_bullet_points', 'generated_description',
        'generated_search_terms', 'generated_seo_keywords', 'generated_highlights',
        'generated_aplus_content', 'brand_name', 'manufacturer',
        'ai_model', 'prompt_tokens', 'completion_tokens', 'total_tokens', 'ai_cost',
        'prompt_used', 'generation_error', 'generated_at',
        'is_published', 'published_at', 'amazon_listing_id', 'is_favorite',
    ];

    protected $casts = [
        'generated_bullet_points' => 'array',
        'prompt_used' => 'array',
        'generated_at' => 'datetime',
        'published_at' => 'datetime',
        'is_published' => 'boolean',
        'is_favorite' => 'boolean',
        'ai_cost' => 'decimal:6',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function productImport()
    {
        return $this->belongsTo(ProductImport::class);
    }

    public function exports()
    {
        return $this->hasMany(Export::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending' => '<span class="badge bg-secondary">Pending</span>',
            'generating' => '<span class="badge bg-warning text-dark"><span class="spinner-border spinner-border-sm me-1"></span>Generating</span>',
            'completed' => '<span class="badge bg-success">Completed</span>',
            'failed' => '<span class="badge bg-danger">Failed</span>',
            default => '<span class="badge bg-secondary">' . ucfirst($this->status) . '</span>',
        };
    }

    public function getGenerationNameDisplayAttribute(): string
    {
        return $this->generation_name ?: ('Generation #' . $this->id);
    }

    public function isManual(): bool
    {
        return $this->generation_method === 'manual';
    }

    public function isAi(): bool
    {
        return $this->generation_method === 'ai';
    }
}
