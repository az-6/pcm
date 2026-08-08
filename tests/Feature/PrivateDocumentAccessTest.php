<?php

use App\Models\FundSubmissionItem;
use App\Models\Majelis;
use App\Models\ProgramReport;
use App\Models\ReportExpense;
use App\Models\User;
use App\Models\WorkProgram;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    Storage::fake('local');
});

test('authorized members can download a private submission document', function (): void {
    $user = User::where('email', 'anggota@demo.test')->firstOrFail();
    $item = FundSubmissionItem::firstOrFail();
    $item->update(['supporting_document' => 'demo/submissions/evidence.pdf']);
    Storage::disk('local')->put($item->supporting_document, 'fictional evidence');

    $this->actingAs($user)
        ->get(route('documents.submission-items', $item))
        ->assertSuccessful()
        ->assertDownload('bukti-pengajuan-'.$item->id.'.pdf');
});

test('members cannot download documents outside their assigned majelis', function (): void {
    $user = User::where('email', 'anggota@demo.test')->firstOrFail();
    $majelis = Majelis::create(['name' => 'Majelis Kesehatan', 'code' => 'KES', 'is_active' => true]);
    $program = WorkProgram::factory()->create([
        'majelis_id' => $majelis->id,
        'created_by' => User::where('email', 'superadmin@demo.test')->firstOrFail()->id,
        'name' => 'Klinik Sehat Warga',
        'code' => 'KES-2026-01',
        'description' => 'Program kesehatan fiktif untuk pengujian akses.',
        'starts_on' => '2026-01-01',
        'ends_on' => '2026-12-31',
        'status' => 'active',
        'budget' => 10000000,
    ]);
    $report = ProgramReport::factory()->create([
        'work_program_id' => $program->id,
        'submitted_by' => User::where('email', 'superadmin@demo.test')->firstOrFail()->id,
        'reference' => 'RPT-KES-0001',
        'title' => 'Laporan fiktif',
        'summary' => 'Ringkasan laporan fiktif.',
        'realized_amount' => 100,
        'status' => 'draft',
    ]);
    $expense = ReportExpense::factory()->create([
        'program_report_id' => $report->id,
        'description' => 'Bukti privat fiktif',
        'amount' => 100,
        'spent_on' => '2026-04-01',
        'receipt_path' => 'demo/reports/private.pdf',
    ]);
    Storage::disk('local')->put($expense->receipt_path, 'private fictional evidence');

    $this->actingAs($user)
        ->get(route('documents.report-expenses', $expense))
        ->assertForbidden();
});

test('missing private files return not found after authorization', function (): void {
    $user = User::where('email', 'superadmin@demo.test')->firstOrFail();
    $item = FundSubmissionItem::firstOrFail();
    $item->update(['supporting_document' => 'demo/submissions/missing.pdf']);

    $this->actingAs($user)
        ->get(route('documents.submission-items', $item))
        ->assertNotFound();
});
