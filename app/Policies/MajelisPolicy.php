<?php

namespace App\Policies;

use App\Models\Majelis;
use App\Models\User;

class MajelisPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Majelis $majelis): bool
    {
        return in_array($majelis->id, $user->accessibleMajelisIds(), true);
    }

    public function create(User $user): bool
    {
        return $user->hasGlobalRole('super-admin');
    }

    public function update(User $user, Majelis $majelis): bool
    {
        return $user->hasGlobalRole('super-admin');
    }
}
