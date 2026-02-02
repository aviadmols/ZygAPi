<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaggingRuleLog extends Model
{
    protected $fillable = [
        'tagging_rule_id',
        'order_id',
        'order_number',
        'tags_applied',
        'success',
        'error_message',
        'source',
    ];

    protected $casts = [
        'tags_applied' => 'array',
        'success' => 'boolean',
    ];

    public function taggingRule(): BelongsTo
    {
        return $this->belongsTo(TaggingRule::class);
    }
}
