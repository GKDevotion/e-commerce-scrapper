<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'status', 'billing_cycle', 'payment_gateway',
        'gateway_subscription_id', 'gateway_customer_id', 'amount', 'currency',
        'trial_ends_at', 'current_period_start', 'current_period_end',
        'cancelled_at', 'cancellation_reason',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function plan() { return $this->belongsTo(Plan::class); }

    public function isActive(): bool { return $this->status === 'active'; }
    public function isOnTrial(): bool { return $this->status === 'trial' && $this->trial_ends_at?->isFuture(); }
}
