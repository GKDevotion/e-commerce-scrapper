<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    protected $fillable = [
        'user_id', 'ai_generation_id', 'format', 'file_path', 'file_name',
        'file_size', 'status', 'error', 'downloaded_at',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function aiGeneration() { return $this->belongsTo(AiGeneration::class); }

    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size) return 'N/A';
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        return round($size, 2) . ' ' . $units[$unit];
    }
}
