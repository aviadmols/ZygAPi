<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConversation extends Model
{
    protected $fillable = [
        'store_id',
        'user_id',
        'type',
        'messages',
        'generated_rule_id',
    ];

    protected $attributes = [
        'type' => 'tags',
    ];

    /**
     * Get the value of an attribute, with fallback for missing columns
     */
    public function getAttribute($key)
    {
        try {
            return parent::getAttribute($key);
        } catch (\Throwable $e) {
            // If column doesn't exist, return default for 'type'
            if ($key === 'type') {
                return 'tags';
            }
            throw $e;
        }
    }

    protected $casts = [
        'messages' => 'array',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generatedRule(): BelongsTo
    {
        return $this->belongsTo(TaggingRule::class, 'generated_rule_id');
    }
}
