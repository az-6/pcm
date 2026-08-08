<?php

use App\Models\FundSubmission;
use App\Models\Majelis;
use App\Models\ProgramReport;
use App\Models\User;
use App\Models\WorkProgram;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

test('domain visibility scopes match seeded role boundaries', function (): void {
    $member = User::where('email', 'anggota@demo.test')->firstOrFail();
    $superAdmin = User::where('email', 'superadmin@demo.test')->firstOrFail();
    $hiddenMajelis = Majelis::create(['name' => 'Majelis Kesehatan', 'code' => 'KES', 'is_active' => true]);
    WorkProgram::factory()->create([
        'majelis_id' => $hiddenMajelis->id,
        'created_by' => $superAdmin->id,
        'name' => 'Klinik Sehat Warga',
        'code' => 'KES-2026-01',
        'description' => 'Program kesehatan fiktif.',
        'starts_on' => '2026-01-01',
        'ends_on' => '2026-12-31',
        'status' => 'active',
        'budget' => 10000000,
    ]);

    expect(Majelis::visibleTo($member)->pluck('code')->all())->toEqualCanonicalizing(['DIK', 'EKO'])
        ->and(WorkProgram::visibleTo($member)->whereHas('majelis', fn ($query) => $query->where('code', 'KES'))->exists())->toBeFalse()
        ->and(Majelis::visibleTo($superAdmin)->where('code', 'KES')->exists())->toBeTrue();
});

test('policies allow review only for global reviewers', function (): void {
    $submission = FundSubmission::where('reference', 'SUB-2026-0001')->firstOrFail();
    $report = ProgramReport::where('reference', 'RPT-2026-0001')->firstOrFail();
    $pimpinan = User::where('email', 'pimpinan@demo.test')->firstOrFail();
    $member = User::where('email', 'anggota@demo.test')->firstOrFail();

    expect($pimpinan->can('review', $submission))->toBeTrue()
        ->and($pimpinan->can('review', $report))->toBeTrue()
        ->and($member->can('review', $submission))->toBeFalse()
        ->and($member->can('review', $report))->toBeFalse();
});
