<?php

namespace App\Http\Controllers;

use App\Models\FundSubmissionItem;
use App\Models\ReportExpense;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateDocumentController extends Controller
{
    public function submission(FundSubmissionItem $fundSubmissionItem): StreamedResponse
    {
        Gate::authorize('download', $fundSubmissionItem);

        abort_unless(Storage::disk('local')->exists($fundSubmissionItem->supporting_document), 404);

        return Storage::disk('local')->download(
            $fundSubmissionItem->supporting_document,
            'bukti-pengajuan-'.$fundSubmissionItem->id.'.'.pathinfo($fundSubmissionItem->supporting_document, PATHINFO_EXTENSION),
        );
    }

    public function expense(ReportExpense $reportExpense): StreamedResponse
    {
        Gate::authorize('download', $reportExpense);

        abort_unless(Storage::disk('local')->exists($reportExpense->receipt_path), 404);

        return Storage::disk('local')->download(
            $reportExpense->receipt_path,
            'bukti-pengeluaran-'.$reportExpense->id.'.'.pathinfo($reportExpense->receipt_path, PATHINFO_EXTENSION),
        );
    }
}
