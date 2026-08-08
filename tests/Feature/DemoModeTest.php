<?php

use App\Models\FundSubmission;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

test('authenticated pages load successfully in demo mode', function (): void {
    $user = User::where('email', 'superadmin@demo.test')->firstOrFail();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful();
});

test('read only mode blocks domain mutations and hides review actions', function (): void {
    config()->set('app.demo_read_only', true);
    $pimpinan = User::where('email', 'pimpinan@demo.test')->firstOrFail();
    $submission = FundSubmission::where('reference', 'SUB-2026-0001')->firstOrFail();

    Livewire::actingAs($pimpinan)
        ->test('pages::submissions.index')
        ->assertDontSee('Terima')
        ->call('decide', $submission->id, 'approved')
        ->assertForbidden();

    expect($submission->refresh()->status->value)->toBe('submitted');
});

test('demo reset is guarded outside safe environments', function (): void {
    $this->app->detectEnvironment(fn (): string => 'production');

    $this->artisan('demo:reset')
        ->expectsOutputToContain('Demo reset is restricted')
        ->assertFailed();
});
