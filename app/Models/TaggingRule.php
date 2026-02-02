<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaggingRule extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'description',
        'rules_json',
        'tags_template',
        'php_rule',
        'is_active',
        'overwrite_existing_tags',
    ];

    protected $casts = [
        'rules_json' => 'array',
        'is_active' => 'boolean',
        'overwrite_existing_tags' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function orderProcessingJobs(): HasMany
    {
        return $this->hasMany(OrderProcessingJob::class, 'rule_id');
    }
}
