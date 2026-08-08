<?php

namespace App\Policies;

use App\Models\FundSubmissionItem;
use App\Models\User;

class FundSubmissionItemPolicy
{
    public function view(User $user, FundSubmissionItem $fundSubmissionItem): bool
    {
        return $user->can('view', $fundSubmissionItem->submission);
    }

    public function download(User $user, FundSubmissionItem $fundSubmissionItem): bool
    {
        return filled($fundSubmissionItem->supporting_document) && $this->view($user, $fundSubmissionItem);
    }
}
