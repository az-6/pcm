<?php

use App\Models\Majelis;
use App\Models\MajelisMembership;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

test('super admin can create and edit majelis', function (): void {
    $superAdmin = User::where('email', 'superadmin@demo.test')->firstOrFail();

    Livewire::actingAs($superAdmin)
        ->test('pages::majelis.index')
        ->call('openCreateMajelis')
        ->set('name', 'Majelis Kesehatan')
        ->set('code', 'kes')
        ->set('description', 'Program kesehatan fiktif.')
        ->call('saveMajelis')
        ->assertHasNoErrors();

    $majelis = Majelis::where('code', 'KES')->firstOrFail();

    Livewire::actingAs($superAdmin)
        ->test('pages::majelis.index')
        ->call('editMajelis', $majelis->id)
        ->set('name', 'Majelis Kesehatan Warga')
        ->call('saveMajelis')
        ->assertHasNoErrors();

    expect($majelis->refresh()->name)->toBe('Majelis Kesehatan Warga');
});

test('admin majelis can manage memberships only in their majelis', function (): void {
    $admin = User::where('email', 'admin.majelis@demo.test')->firstOrFail();
    $member = User::factory()->create();
    $education = Majelis::where('code', 'DIK')->firstOrFail();
    $outside = Majelis::create(['name' => 'Majelis Kesehatan', 'code' => 'KES', 'is_active' => true]);

    Livewire::actingAs($admin)
        ->test('pages::majelis.index')
        ->call('openMembership', $education->id)
        ->set('membershipUserId', $member->id)
        ->set('membershipRole', 'anggota')
        ->set('membershipPosition', 'Relawan')
        ->call('saveMembership')
        ->assertHasNoErrors();

    expect(MajelisMembership::where('majelis_id', $education->id)->where('user_id', $member->id)->exists())->toBeTrue();

    Livewire::actingAs($admin)
        ->test('pages::majelis.index')
        ->call('openMembership', $outside->id)
        ->assertForbidden();
});

test('cannot add duplicate membership for the same user in a majelis', function (): void {
    $admin = User::where('email', 'admin.majelis@demo.test')->firstOrFail();
    $education = Majelis::where('code', 'DIK')->firstOrFail();
    $existingMember = $education->members()->firstOrFail();

    Livewire::actingAs($admin)
        ->test('pages::majelis.index')
        ->call('openMembership', $education->id)
        ->set('membershipUserId', $existingMember->id)
        ->set('membershipRole', 'anggota')
        ->call('saveMembership')
        ->assertHasErrors(['membershipUserId']);
});

test('can delete membership if no work programs are assigned to user', function (): void {
    $superAdmin = User::where('email', 'superadmin@demo.test')->firstOrFail();
    $education = Majelis::where('code', 'DIK')->firstOrFail();
    $newUser = User::factory()->create();

    $membership = MajelisMembership::create([
        'majelis_id' => $education->id,
        'user_id' => $newUser->id,
        'role' => 'anggota',
        'is_active' => true,
    ]);

    Livewire::actingAs($superAdmin)
        ->test('pages::majelis.index')
        ->call('deleteMembership', $membership->id);

    expect(MajelisMembership::where('id', $membership->id)->exists())->toBeFalse();
});

test('cannot delete membership if user has assigned work programs', function (): void {
    $superAdmin = User::where('email', 'superadmin@demo.test')->firstOrFail();
    $education = Majelis::where('code', 'DIK')->firstOrFail();
    $ketua = User::where('email', 'ketua.bidang@demo.test')->firstOrFail();
    $membership = MajelisMembership::where('majelis_id', $education->id)->where('user_id', $ketua->id)->firstOrFail();

    Livewire::actingAs($superAdmin)
        ->test('pages::majelis.index')
        ->call('deleteMembership', $membership->id);

    expect(MajelisMembership::where('id', $membership->id)->exists())->toBeTrue();
});
