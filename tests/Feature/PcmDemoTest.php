<?php

use App\Enums\SubmissionStatus;
use App\Models\FundSubmission;
use App\Models\Majelis;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

test('seeded demo accounts can access the dashboard and core pages', function (string $route): void {
    $user = User::where('email', 'superadmin@demo.test')->firstOrFail();

    $this->actingAs($user)->get(route($route))->assertOk();
})->with(['dashboard', 'majelis.index', 'programs.index', 'submissions.index', 'reports.index']);

test('members only see their assigned majelis', function (): void {
    $user = User::where('email', 'anggota@demo.test')->firstOrFail();
    $economy = Majelis::where('code', 'EKO')->firstOrFail();
    $health = Majelis::create(['name' => 'Majelis Kesehatan', 'code' => 'KES', 'is_active' => true]);

    Livewire::actingAs($user)
        ->test('pages::majelis.index')
        ->assertSee($economy->name)
        ->assertDontSee($health->name);
});

test('pimpinan can process and approve a funding submission', function (): void {
    $user = User::where('email', 'pimpinan@demo.test')->firstOrFail();
    $submission = FundSubmission::where('reference', 'SUB-2026-0001')->firstOrFail();

    Livewire::actingAs($user)
        ->test('pages::submissions.index')
        ->call('process', $submission->id)
        ->call('decide', $submission->id, 'approved');

    expect($submission->refresh()->status)->toBe(SubmissionStatus::Approved);
    expect($submission->approvals()->where('decision', 'approved')->exists())->toBeTrue();
});

test('anggota cannot approve a funding submission', function (): void {
    $user = User::where('email', 'anggota@demo.test')->firstOrFail();
    $submission = FundSubmission::where('reference', 'SUB-2026-0001')->firstOrFail();

    Livewire::actingAs($user)
        ->test('pages::submissions.index')
        ->call('decide', $submission->id, 'approved')
        ->assertForbidden();
});
