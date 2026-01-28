<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainLog extends Model
{
    public const OBJECT_TYPE_ORDER = 'order';
    public const OBJECT_TYPE_SUBSCRIPTION = 'subscription';

    protected $fillable = [
        'shop_id',
        'object_type',
        'object_external_id',
        'object_display',
        'action_type',
        'run_id',
        'run_step_id',
        'status',
        'message',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class);
    }

    public function runStep(): BelongsTo
    {
        return $this->belongsTo(RunStep::class);
    }
}
