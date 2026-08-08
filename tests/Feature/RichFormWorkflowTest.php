<?php

use App\Models\FundSubmission;
use App\Models\ProgramReport;
use App\Models\User;
use App\Models\WorkProgram;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    Storage::fake('local');
});

test('admin majelis can create a multi item funding draft with private evidence', function (): void {
    $user = User::where('email', 'admin.majelis@demo.test')->firstOrFail();
    $program = WorkProgram::where('code', 'DIK-2026-01')->firstOrFail();

    Livewire::actingAs($user)
        ->test('pages::submissions.index')
        ->set('work_program_id', $program->id)
        ->set('title', 'Pengajuan perlengkapan kelas')
        ->set('purpose', 'Mendukung dua kegiatan kelas dengan kebutuhan berbeda.')
        ->set('items.0.description', 'Papan tulis')
        ->set('items.0.quantity', 2)
        ->set('items.0.unit_price', '750000')
        ->set('items.0.supporting_document', UploadedFile::fake()->create('quotation.pdf', 100, 'application/pdf'))
        ->call('addItem')
        ->set('items.1.description', 'Perlengkapan peserta')
        ->set('items.1.quantity', 10)
        ->set('items.1.unit_price', '50000')
        ->call('createSubmission')
        ->assertHasNoErrors();

    $submission = FundSubmission::where('title', 'Pengajuan perlengkapan kelas')->with('items')->firstOrFail();

    expect($submission->items)->toHaveCount(2)
        ->and($submission->amount)->toBe('2000000.00')
        ->and($submission->items->first()->supporting_document)->not->toBeNull();
    Storage::disk('local')->assertExists($submission->items->first()->supporting_document);
});

test('admin majelis can create a multi expense report with private receipt', function (): void {
    $user = User::where('email', 'admin.majelis@demo.test')->firstOrFail();
    $program = WorkProgram::where('code', 'EKO-2026-01')->firstOrFail();

    Livewire::actingAs($user)
        ->test('pages::reports.index')
        ->set('work_program_id', $program->id)
        ->set('title', 'Laporan realisasi tahap dua')
        ->set('summary', 'Dua kegiatan fiktif selesai dengan bukti pengeluaran privat.')
        ->set('expenses.0.description', 'Sewa ruangan')
        ->set('expenses.0.amount', '1500000')
        ->set('expenses.0.spent_on', '2026-04-01')
        ->set('expenses.0.receipt', UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'))
        ->call('addExpense')
        ->set('expenses.1.description', 'Materi peserta')
        ->set('expenses.1.amount', '500000')
        ->set('expenses.1.spent_on', '2026-04-02')
        ->call('createReport')
        ->assertHasNoErrors();

    $report = ProgramReport::where('title', 'Laporan realisasi tahap dua')->with('expenses')->firstOrFail();

    expect($report->expenses)->toHaveCount(2)
        ->and($report->realized_amount)->toBe('2000000.00')
        ->and($report->expenses->first()->receipt_path)->not->toBeNull();
    Storage::disk('local')->assertExists($report->expenses->first()->receipt_path);
});
