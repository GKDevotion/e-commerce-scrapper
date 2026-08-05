<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'phone', 'company_name',
        'website', 'timezone', 'role', 'status', 'plan_id',
        'listings_used', 'ai_generations_used', 'amazon_seller_id',
        'default_brand', 'default_manufacturer', 'notes',
        'last_login_at', 'last_login_ip',
        'openai_api_key', 'openai_model',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latest();
    }

    public function productImports()
    {
        return $this->hasMany(ProductImport::class);
    }

    public function aiGenerations()
    {
        return $this->hasMany(AiGeneration::class);
    }

    public function exports()
    {
        return $this->hasMany(Export::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Helpers
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /** Unlimited listings — always true in Phase 2 */
    public function canGenerateListing(): bool
    {
        return true;
    }

    public function getRemainingListings(): int|string
    {
        return 'Unlimited';
    }

    public function getUsagePercentage(): int
    {
        return 0; // no limit, no bar
    }

    /** Has the user configured their personal OpenAI API key? */
    public function hasOpenAiKey(): bool
    {
        return !empty($this->openai_api_key);
    }

    /** Effective OpenAI key — user's own key first, fall back to system key */
    public function effectiveOpenAiKey(): ?string
    {
        return $this->openai_api_key ?: config('services.openai.api_key');
    }

    /** Effective model — user's preference or system default */
    public function effectiveOpenAiModel(): string
    {
        return $this->openai_model ?: config('services.openai.model', 'gpt-4o');
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=d09226&color=fff&size=128';
    }
}
