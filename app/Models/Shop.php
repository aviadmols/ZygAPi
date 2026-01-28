<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(ShopMember::class);
    }

    public function automations(): HasMany
    {
        return $this->hasMany(Automation::class);
    }

    public function shopifyIntegration(): HasMany
    {
        return $this->hasMany(IntegrationShopify::class);
    }

    public function rechargeIntegration(): HasMany
    {
        return $this->hasMany(IntegrationRecharge::class);
    }

    public function openrouterIntegration(): HasMany
    {
        return $this->hasMany(IntegrationOpenrouter::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(Run::class);
    }

    public function domainLogs(): HasMany
    {
        return $this->hasMany(DomainLog::class);
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }
}
