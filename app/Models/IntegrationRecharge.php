<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class IntegrationRecharge extends Model
{
    protected $table = 'integration_recharge';

    protected $fillable = [
        'shop_id',
        'base_url',
        'access_token',
        'webhook_secret',
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

    public function getAccessTokenAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getWebhookSecretAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function setWebhookSecretAttribute($value): void
    {
        $this->attributes['webhook_secret'] = $value ? Crypt::encryptString($value) : null;
    }
}
