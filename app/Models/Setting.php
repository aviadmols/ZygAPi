<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key (cached).
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $cacheKey = 'setting.' . $key;

        return Cache::remember($cacheKey, 300, function () use ($key, $default) {
            $row = static::where('key', $key)->first();

            return $row ? $row->value : $default;
        });
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        Cache::forget('setting.' . $key);
    }
}
