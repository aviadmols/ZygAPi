<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CustomEndpoint extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'platform',
        'prompt',
        'input_params',
        'test_return_values',
        'php_code',
        'webhook_token',
        'is_active',
    ];

    protected $casts = [
        'input_params' => 'array',
        'test_return_values' => 'array',
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CustomEndpointLog::class);
    }

    public static function generateSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 0;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }
}
