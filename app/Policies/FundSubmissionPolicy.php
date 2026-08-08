<?php

namespace App\Policies;

use App\Models\FundSubmission;
use App\Models\User;

class FundSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FundSubmission $fundSubmission): bool
    {
        return $user->can('view', $fundSubmission->workProgram);
    }

    public function create(User $user): bool
    {
        return $user->can('create', \App\Models\WorkProgram::class);
    }

    public function update(User $user, FundSubmission $fundSubmission): bool
    {
        return $user->canReview()
            || ($fundSubmission->submitted_by === $user->id && $user->canManagePrograms($fundSubmission->workProgram->majelis_id));
    }

    public function review(User $user, FundSubmission $fundSubmission): bool
    {
        return $user->canReview();
    }
}
