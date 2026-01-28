<?php

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Automation extends Model
{
    use BelongsToShop;
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const TRIGGER_WEBHOOK = 'webhook';
    public const TRIGGER_SCHEDULE = 'schedule';
    public const TRIGGER_MANUAL = 'manual';
    public const TRIGGER_PLAYGROUND = 'playground';

    protected $fillable = [
        'shop_id',
        'name',
        'status',
        'trigger_type',
        'trigger_config',
        'steps',
        'version',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'steps' => 'array',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AutomationVersion::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(Run::class);
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
