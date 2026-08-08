<?php

namespace App\Policies;

use App\Models\MajelisMembership;
use App\Models\User;

class MajelisMembershipPolicy
{
    public function view(User $user, MajelisMembership $membership): bool
    {
        return in_array($membership->majelis_id, $user->accessibleMajelisIds(), true);
    }

    public function create(User $user, int $majelisId): bool
    {
        return $user->hasGlobalRole('super-admin') || $user->hasMajelisRole($majelisId, 'admin-majelis');
    }

    public function update(User $user, MajelisMembership $membership): bool
    {
        return $this->create($user, $membership->majelis_id);
    }
}
