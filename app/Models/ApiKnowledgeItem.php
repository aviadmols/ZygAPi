<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKnowledgeItem extends Model
{
    public const PROVIDER_SHOPIFY = 'shopify';
    public const PROVIDER_RECHARGE = 'recharge';
    public const PROVIDER_INTERNAL = 'internal';

    protected $fillable = [
        'provider',
        'title',
        'content',
        'tags',
        'json_examples',
    ];

    protected $casts = [
        'tags' => 'array',
        'json_examples' => 'array',
    ];
}
