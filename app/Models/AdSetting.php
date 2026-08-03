<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AdSetting extends Model
{
    protected $fillable = ['provider','slot_key','label','ad_code','is_enabled','placement'];
    protected $casts    = ['is_enabled' => 'boolean'];

    /** Get enabled ads for a placement slot — cached 10 min */
    public static function forPlacement(string $placement): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember("ads_{$placement}", 600, fn() =>
            static::query()->where('placement', $placement)->where('is_enabled', true)->get()
        );
    }

    public static function bySlot(string $slotKey): ?self
    {
        return static::where('slot_key', $slotKey)->first();
    }

    protected static function booted(): void
    {
        $clear = fn() => Cache::flush(); // simple — clear all on any ad change
        static::saved($clear);
        static::deleted($clear);
    }
}
