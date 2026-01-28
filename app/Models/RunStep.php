<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RunStep extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'run_id',
        'step_id',
        'step_name',
        'status',
        'started_at',
        'finished_at',
        'input',
        'output',
        'error',
        'http_request',
        'http_response',
        'simulation_diff',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'error' => 'array',
        'http_request' => 'array',
        'http_response' => 'array',
        'simulation_diff' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class);
    }
}
