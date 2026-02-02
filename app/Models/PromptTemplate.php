<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromptTemplate extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'content',
        'description',
    ];

    /**
     * Get prompt by slug (cached for same request).
     */
    public static function getBySlug(string $slug): ?string
    {
        $template = static::where('slug', $slug)->first();

        return $template?->content;
    }
}
