<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = [
        'name',
        'shopify_store_url',
        'shopify_access_token',
        'recharge_access_token',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function taggingRules(): HasMany
    {
        return $this->hasMany(TaggingRule::class);
    }

    public function orderProcessingJobs(): HasMany
    {
        return $this->hasMany(OrderProcessingJob::class);
    }

    public function aiConversations(): HasMany
    {
        return $this->hasMany(AiConversation::class);
    }
}
