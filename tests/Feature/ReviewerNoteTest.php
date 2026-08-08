<?php

use App\Models\FundSubmission;
use App\Models\ProgramReport;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

test('review decisions retain notes in the record and approval history', function (): void {
    $reviewer = User::where('email', 'pimpinan@demo.test')->firstOrFail();
    $submission = FundSubmission::where('reference', 'SUB-2026-0001')->firstOrFail();
    $report = ProgramReport::where('reference', 'RPT-2026-0001')->firstOrFail();

    Livewire::actingAs($reviewer)
        ->test('pages::submissions.index')
        ->set('reviewerNote', 'Anggaran sesuai dengan kebutuhan program.')
        ->call('decide', $submission->id, 'approved');

    Livewire::actingAs($reviewer)
        ->test('pages::reports.index')
        ->set('reviewerNote', 'Bukti realisasi sudah lengkap.')
        ->call('decide', $report->id, 'approved');

    expect($submission->refresh()->reviewer_note)->toBe('Anggaran sesuai dengan kebutuhan program.')
        ->and($submission->approvals()->where('note', 'Anggaran sesuai dengan kebutuhan program.')->exists())->toBeTrue()
        ->and($report->refresh()->reviewer_note)->toBe('Bukti realisasi sudah lengkap.')
        ->and($report->approvals()->where('note', 'Bukti realisasi sudah lengkap.')->exists())->toBeTrue();
});
