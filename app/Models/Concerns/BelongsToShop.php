<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToShop
{
    public function scopeForShop(Builder $query, int $shopId): Builder
    {
        return $query->where('shop_id', $shopId);
    }

    public function scopeForShops(Builder $query, array $shopIds): Builder
    {
        return $query->whereIn('shop_id', $shopIds);
    }
}
