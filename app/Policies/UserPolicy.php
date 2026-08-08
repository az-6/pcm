<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasGlobalRole('super-admin');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasGlobalRole('super-admin');
    }

    public function create(User $user): bool
    {
        return $user->hasGlobalRole('super-admin');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasGlobalRole('super-admin');
    }

    public function delete(User $user, User $model): bool
    {
        return false;
    }

    public function restore(User $user, User $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
