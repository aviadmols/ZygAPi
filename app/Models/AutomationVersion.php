<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationVersion extends Model
{
    protected $fillable = [
        'automation_id',
        'version',
        'definition_json',
        'created_by_user_id',
    ];

    protected $casts = [
        'definition_json' => 'array',
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
