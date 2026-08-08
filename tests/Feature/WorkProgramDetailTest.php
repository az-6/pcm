<?php

use App\Models\Majelis;
use App\Models\User;
use App\Models\WorkProgram;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

test('authenticated users can view an accessible work program with its related activity', function (): void {
    $user = User::where('email', 'superadmin@demo.test')->firstOrFail();
    $program = WorkProgram::where('code', 'DIK-2026-01')->firstOrFail();

    $this->actingAs($user)
        ->get(route('programs.show', $program))
        ->assertOk()
        ->assertSee($program->name)
        ->assertSee('Pengadaan perangkat pelatihan')
        ->assertSee('Sewa perangkat dan proyektor');
});

test('majelis members can view programs assigned to their majelis', function (): void {
    $user = User::where('email', 'anggota@demo.test')->firstOrFail();
    $program = WorkProgram::where('code', 'DIK-2026-01')->firstOrFail();

    $this->actingAs($user)
        ->get(route('programs.show', $program))
        ->assertOk()
        ->assertSee($program->name);
});

test('members cannot view a work program outside their assigned majelis', function (): void {
    $user = User::where('email', 'anggota@demo.test')->firstOrFail();
    $unassignedMajelis = Majelis::create([
        'name' => 'Majelis Kesehatan',
        'code' => 'KES',
        'is_active' => true,
    ]);
    $program = WorkProgram::create([
        'majelis_id' => $unassignedMajelis->id,
        'created_by' => User::where('email', 'superadmin@demo.test')->firstOrFail()->id,
        'name' => 'Klinik Sehat Warga',
        'code' => 'KES-2026-01',
        'description' => 'Program kesehatan fiktif untuk pengujian akses.',
        'starts_on' => '2026-01-01',
        'ends_on' => '2026-12-31',
        'status' => 'active',
        'budget' => 10000000,
    ]);

    $this->actingAs($user)
        ->get(route('programs.show', $program))
        ->assertForbidden();
});

test('program detail livewire component loads related submissions and reports', function (): void {
    $user = User::where('email', 'superadmin@demo.test')->firstOrFail();
    $submissionProgram = WorkProgram::where('code', 'DIK-2026-01')->firstOrFail();
    $reportProgram = WorkProgram::where('code', 'EKO-2026-01')->firstOrFail();

    Livewire::actingAs($user)
        ->test('pages::programs.show', ['workProgram' => $submissionProgram])
        ->assertSee('Pengadaan perangkat pelatihan')
        ->assertSee('Modul dan konsumsi peserta');

    Livewire::actingAs($user)
        ->test('pages::programs.show', ['workProgram' => $reportProgram])
        ->assertSee('Laporan realisasi pendampingan UMKM tahap 1')
        ->assertSee('Lokakarya pencatatan keuangan');
});
