<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    protected $fillable = [
        'user_id', 'service', 'endpoint', 'method', 'status_code',
        'request_data', 'response_data', 'response_time_ms', 'success',
        'error_message', 'ip_address',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'success' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }
}
