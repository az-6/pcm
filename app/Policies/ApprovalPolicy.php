<?php

namespace App\Policies;

use App\Models\Approval;
use App\Models\User;

class ApprovalPolicy
{
    public function view(User $user, Approval $approval): bool
    {
        return $approval->approvable && $user->can('view', $approval->approvable);
    }

    public function create(User $user): bool
    {
        return $user->canReview();
    }
}
