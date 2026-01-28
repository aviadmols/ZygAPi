<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class IntegrationOpenrouter extends Model
{
    protected $table = 'integration_openrouter';

    protected $fillable = [
        'shop_id',
        'api_key',
        'default_model',
        'status',
        'last_tested_at',
    ];

    protected $casts = [
        'last_tested_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function getApiKeyAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setApiKeyAttribute($value): void
    {
        $this->attributes['api_key'] = $value ? Crypt::encryptString($value) : null;
    }
}
