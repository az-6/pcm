<?php

namespace App\Policies;

use App\Models\ReportExpense;
use App\Models\User;

class ReportExpensePolicy
{
    public function view(User $user, ReportExpense $reportExpense): bool
    {
        return $user->can('view', $reportExpense->report);
    }

    public function download(User $user, ReportExpense $reportExpense): bool
    {
        return filled($reportExpense->receipt_path) && $this->view($user, $reportExpense);
    }
}
