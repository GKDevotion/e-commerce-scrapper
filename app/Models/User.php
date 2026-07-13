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

    public function canGenerateListing(): bool
    {
        if (!$this->plan) return $this->listings_used < 5;
        $limit = $this->plan->listings_limit;
        if ($limit === -1) return true;
        return $this->listings_used < $limit;
    }

    public function getRemainingListings(): int|string
    {
        if (!$this->plan) return max(0, 5 - $this->listings_used);
        $limit = $this->plan->listings_limit;
        if ($limit === -1) return 'Unlimited';
        return max(0, $limit - $this->listings_used);
    }

    public function getUsagePercentage(): int
    {
        if (!$this->plan) {
            return min(100, (int)(($this->listings_used / 5) * 100));
        }
        $limit = $this->plan->listings_limit;
        if ($limit === -1) return 0;
        return min(100, (int)(($this->listings_used / $limit) * 100));
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=E31837&color=fff&size=128';
    }
}
