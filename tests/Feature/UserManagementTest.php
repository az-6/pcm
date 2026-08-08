<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

test('super admin can create and edit fictional users', function (): void {
    $superAdmin = User::where('email', 'superadmin@demo.test')->firstOrFail();

    Livewire::actingAs($superAdmin)
        ->test('pages::users.index')
        ->call('openCreate')
        ->set('name', 'Bima Santoso')
        ->set('email', 'bima@demo.test')
        ->set('globalRole', 'pimpinan')
        ->set('password', 'secret123')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'bima@demo.test')->firstOrFail();

    expect($user->name)->toBe('Bima Santoso')
        ->and($user->global_role)->toBe('pimpinan')
        ->and(Hash::check('secret123', $user->password))->toBeTrue();

    Livewire::actingAs($superAdmin)
        ->test('pages::users.index')
        ->call('edit', $user->id)
        ->set('name', 'Bima Santoso Updated')
        ->set('globalRole', 'none')
        ->set('password', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->refresh()->name)->toBe('Bima Santoso Updated')
        ->and($user->global_role)->toBeNull()
        ->and(Hash::check('secret123', $user->password))->toBeTrue();
});

test('only super admins can access user management', function (): void {
    $member = User::where('email', 'anggota@demo.test')->firstOrFail();

    $this->actingAs($member)->get(route('users.index'))->assertForbidden();
});

test('read only mode blocks user mutations', function (): void {
    config()->set('app.demo_read_only', true);
    $superAdmin = User::where('email', 'superadmin@demo.test')->firstOrFail();

    Livewire::actingAs($superAdmin)
        ->test('pages::users.index')
        ->call('openCreate')
        ->assertForbidden();
});

test('super admin cannot remove their own access', function (): void {
    $superAdmin = User::where('email', 'superadmin@demo.test')->firstOrFail();

    Livewire::actingAs($superAdmin)
        ->test('pages::users.index')
        ->call('edit', $superAdmin->id)
        ->set('globalRole', 'none')
        ->set('password', '')
        ->call('save')
        ->assertHasErrors('globalRole');

    expect($superAdmin->refresh()->global_role)->toBe('super-admin');
});
