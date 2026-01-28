<?php

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;

class ShopPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Shop $shop): bool
    {
        return $shop->members()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Shop $shop): bool
    {
        $member = $shop->members()->where('user_id', $user->id)->first();
        return $member && ($member->isAdmin() || $member->isOwner());
    }

    public function delete(User $user, Shop $shop): bool
    {
        $member = $shop->members()->where('user_id', $user->id)->first();
        return $member && $member->isOwner();
    }

    public function manageMembers(User $user, Shop $shop): bool
    {
        $member = $shop->members()->where('user_id', $user->id)->first();
        return $member && ($member->isAdmin() || $member->isOwner());
    }
}
