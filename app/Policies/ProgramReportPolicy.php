<?php

namespace App\Policies;

use App\Models\ProgramReport;
use App\Models\User;

class ProgramReportPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProgramReport $programReport): bool
    {
        return $user->can('view', $programReport->workProgram);
    }

    public function create(User $user): bool
    {
        return $user->can('create', \App\Models\WorkProgram::class);
    }

    public function update(User $user, ProgramReport $programReport): bool
    {
        return $user->canReview()
            || ($programReport->submitted_by === $user->id && $user->canManagePrograms($programReport->workProgram->majelis_id));
    }

    public function review(User $user, ProgramReport $programReport): bool
    {
        return $user->canReview();
    }
}
