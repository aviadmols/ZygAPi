<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Run extends Model
{
    public const MODE_DRY_RUN = 'dry_run';
    public const MODE_EXECUTE = 'execute';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PARTIAL = 'partial';

    protected $fillable = [
        'shop_id',
        'automation_id',
        'mode',
        'status',
        'trigger_type',
        'trigger_payload',
        'external_order_id',
        'order_number',
        'external_subscription_id',
        'customer_id',
        'idempotency_key',
        'execution_snapshot_json',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'trigger_payload' => 'array',
        'execution_snapshot_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RunStep::class);
    }

    public function domainLogs(): HasMany
    {
        return $this->hasMany(DomainLog::class);
    }

    public function isDryRun(): bool
    {
        return $this->mode === self::MODE_DRY_RUN;
    }
}
