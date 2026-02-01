<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProcessingJob extends Model
{
    protected $fillable = [
        'store_id',
        'rule_id',
        'order_ids',
        'status',
        'progress',
        'total_orders',
        'processed_orders',
        'failed_orders',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'order_ids' => 'array',
        'progress' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(TaggingRule::class, 'rule_id');
    }
}
