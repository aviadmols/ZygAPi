<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key (cached).
     * Safe when settings table is missing (e.g. migration not run).
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        try {
            $cacheKey = 'setting.' . $key;

            return Cache::remember($cacheKey, 300, function () use ($key, $default) {
                $row = static::where('key', $key)->first();

                return $row ? $row->value : $default;
            });
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Set a setting value.
     * Safe when settings table is missing.
     */
    public static function set(string $key, ?string $value): void
    {
        try {
            static::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
            Cache::forget('setting.' . $key);
        } catch (\Throwable $e) {
            // Table may not exist yet
        }
    }
}
