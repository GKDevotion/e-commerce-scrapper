<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    protected $fillable = ['key','label','is_enabled','icon','color','sort_order'];
    protected $casts    = ['is_enabled' => 'boolean'];

    /** All platforms ordered by sort_order */
    public static function all($columns = ['*'])
    {
        return parent::query()->orderBy('sort_order')->get($columns);
    }

    /** Only enabled platforms — cached 5 min */
    public static function enabled(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('enabled_platforms', 300, fn() =>
            static::query()->where('is_enabled', true)->orderBy('sort_order')->get()
        );
    }

    /** Clear the cache whenever a record changes */
    protected static function booted(): void
    {
        static::saved(fn()   => Cache::forget('enabled_platforms'));
        static::deleted(fn() => Cache::forget('enabled_platforms'));
    }
}
