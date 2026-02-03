<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomEndpointLog extends Model
{
    protected $fillable = [
        'custom_endpoint_id',
        'source',
        'request_input',
        'response_data',
        'success',
        'error_message',
    ];

    protected $casts = [
        'request_input' => 'array',
        'response_data' => 'array',
        'success' => 'boolean',
    ];

    public function customEndpoint(): BelongsTo
    {
        return $this->belongsTo(CustomEndpoint::class);
    }
}
