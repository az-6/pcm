<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkProgram;

class WorkProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WorkProgram $workProgram): bool
    {
        return in_array($workProgram->majelis_id, $user->accessibleMajelisIds(), true);
    }

    public function create(User $user): bool
    {
        return $user->hasGlobalRole('super-admin')
            || $user->majelisMemberships()->where('is_active', true)->whereIn('role', ['admin-majelis', 'ketua-bidang'])->exists();
    }

    public function update(User $user, WorkProgram $workProgram): bool
    {
        return $user->canManagePrograms($workProgram->majelis_id);
    }
}
