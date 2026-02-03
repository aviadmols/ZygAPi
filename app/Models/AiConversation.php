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
        $value = parent::getAttribute($key);
        
        // If type is null or empty and key is 'type', return default
        if ($key === 'type' && (empty($value) || is_null($value))) {
            return 'tags';
        }
        
        return $value;
    }

    /**
     * Check if type column exists in database
     */
    protected static function typeColumnExists(): bool
    {
        try {
            $connection = (new static)->getConnection();
            $schema = $connection->getSchemaBuilder();
            return $schema->hasColumn((new static)->getTable(), 'type');
        } catch (\Throwable $e) {
            return false;
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
