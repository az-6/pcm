<?php

use App\Models\Majelis;
use App\Models\MajelisMembership;
use App\Models\User;
use App\Models\WorkProgram;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Majelis')] class extends Component {
    public bool $showMajelisForm = false;
    public bool $showMembershipForm = false;
    public ?int $editingMajelisId = null;
    public ?int $membershipMajelisId = null;
    public ?int $membershipUserId = null;
    public string $name = '';
    public string $code = '';
    public string $description = '';
    public bool $isActive = true;
    public string $membershipRole = 'anggota';
    public string $membershipPosition = '';

    #[Computed]
    public function majelis()
    {
        return Majelis::query()
            ->visibleTo(Auth::user())
            ->with(['members', 'workPrograms'])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function users()
    {
        return User::query()->orderBy('name')->get();
    }

    public function openCreateMajelis(): void
    {
        $this->ensureWritable();
        abort_unless(Auth::user()->hasGlobalRole('super-admin'), 403);
        $this->resetMajelisForm();
        $this->showMajelisForm = true;
    }

    public function editMajelis(int $majelisId): void
    {
        $this->ensureWritable();
        abort_unless(Auth::user()->hasGlobalRole('super-admin'), 403);
        $majelis = Majelis::findOrFail($majelisId);
        $this->editingMajelisId = $majelis->id;
        $this->name = $majelis->name;
        $this->code = $majelis->code;
        $this->description = $majelis->description ?? '';
        $this->isActive = $majelis->is_active;
        $this->showMajelisForm = true;
    }

    public function saveMajelis(): void
    {
        $this->ensureWritable();
        abort_unless(Auth::user()->hasGlobalRole('super-admin'), 403);
        $attributes = $this->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'code' => ['required', 'string', 'min:2', 'max:12', Rule::unique('majelis', 'code')->ignore($this->editingMajelisId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'isActive' => ['required', 'boolean'],
        ]);

        Majelis::updateOrCreate(
            ['id' => $this->editingMajelisId],
            ['name' => $attributes['name'], 'code' => strtoupper($attributes['code']), 'description' => $attributes['description'], 'is_active' => $attributes['isActive']],
        );

        $this->resetMajelisForm();
        Flux::toast(variant: 'success', text: 'Data Majelis disimpan.');
    }

    public function openMembership(int $majelisId): void
    {
        $this->ensureWritable();
        $majelis = Majelis::findOrFail($majelisId);
        abort_unless(Auth::user()->hasGlobalRole('super-admin') || Auth::user()->hasMajelisRole($majelis->id, 'admin-majelis'), 403);
        $this->membershipMajelisId = $majelis->id;
        $this->membershipUserId = null;
        $this->membershipRole = 'anggota';
        $this->membershipPosition = '';
        $this->showMembershipForm = true;
    }

    public function saveMembership(): void
    {
        $this->ensureWritable();
        $majelis = Majelis::findOrFail($this->membershipMajelisId);
        abort_unless(Auth::user()->hasGlobalRole('super-admin') || Auth::user()->hasMajelisRole($majelis->id, 'admin-majelis'), 403);
        $attributes = $this->validate([
            'membershipUserId' => ['required', 'integer', Rule::exists('users', 'id')],
            'membershipRole' => ['required', Rule::in(['admin-majelis', 'ketua-bidang', 'anggota'])],
            'membershipPosition' => ['nullable', 'string', 'max:120'],
        ]);

        $alreadyMember = MajelisMembership::where('majelis_id', $majelis->id)
            ->where('user_id', $attributes['membershipUserId'])
            ->exists();

        if ($alreadyMember) {
            $this->addError('membershipUserId', 'Pengguna ini sudah terdaftar sebagai anggota/pengurus di majelis ini.');
            return;
        }

        MajelisMembership::create([
            'majelis_id' => $majelis->id,
            'user_id' => $attributes['membershipUserId'],
            'role' => $attributes['membershipRole'],
            'position' => $attributes['membershipPosition'],
            'is_active' => true,
        ]);

        $this->reset(['showMembershipForm', 'membershipMajelisId', 'membershipUserId', 'membershipRole', 'membershipPosition']);
        Flux::toast(variant: 'success', text: 'Keanggotaan Majelis disimpan.');
    }

    public function deleteMembership(int $membershipId): void
    {
        $this->ensureWritable();
        $membership = MajelisMembership::findOrFail($membershipId);
        abort_unless(Auth::user()->hasGlobalRole('super-admin') || Auth::user()->hasMajelisRole($membership->majelis_id, 'admin-majelis'), 403);

        $hasAssignedPrograms = WorkProgram::where('majelis_id', $membership->majelis_id)
            ->where('created_by', $membership->user_id)
            ->exists();

        if ($hasAssignedPrograms) {
            Flux::toast(variant: 'danger', text: 'Anggota tidak dapat dihapus karena memiliki program kerja yang ditugaskan.');
            return;
        }

        $membership->delete();
        Flux::toast(variant: 'success', text: 'Keanggotaan berhasil dihapus.');
    }

    private function resetMajelisForm(): void
    {
        $this->reset(['showMajelisForm', 'editingMajelisId', 'name', 'code', 'description']);
        $this->isActive = true;
    }

    private function ensureWritable(): void
    {
        abort_if(config('app.demo_read_only'), 403, 'Demo publik hanya-baca.');
    }
}; ?>

<div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="xl" level="1">Manajemen Majelis</flux:heading>
            <flux:text class="mt-2 max-w-2xl text-zinc-500">Struktur organisasi, keanggotaan, dan peran spesifik di setiap Majelis.</flux:text>
        </div>
        @if(!config('app.demo_read_only') && Auth::user()->hasGlobalRole('super-admin'))
            <flux:button variant="primary" wire:click="openCreateMajelis" icon="plus">Tambah Majelis</flux:button>
        @endif
    </div>

    @if($showMajelisForm)
        <flux:card class="!p-6">
            <div class="flex items-center justify-between gap-4"><flux:heading size="lg">{{ $editingMajelisId ? 'Edit Majelis' : 'Majelis baru' }}</flux:heading><flux:button variant="ghost" size="sm" wire:click="$set('showMajelisForm', false)">Tutup</flux:button></div>
            <form wire:submit="saveMajelis" class="mt-6 grid gap-5 md:grid-cols-2">
                <flux:input wire:model="name" label="Nama Majelis" required />
                <flux:input wire:model="code" label="Kode" required />
                <flux:textarea wire:model="description" label="Deskripsi" rows="3" class="md:col-span-2" />
                <flux:switch wire:model="isActive" label="Majelis aktif" />
                <div class="flex justify-end md:col-span-2"><flux:button type="submit" variant="primary">Simpan Majelis</flux:button></div>
            </form>
        </flux:card>
    @endif

    @if($showMembershipForm)
        <flux:card class="!p-6">
            <div class="flex items-center justify-between gap-4"><flux:heading size="lg">Tambah keanggotaan</flux:heading><flux:button variant="ghost" size="sm" wire:click="$set('showMembershipForm', false)">Tutup</flux:button></div>
            <form wire:submit="saveMembership" class="mt-6 grid gap-5 md:grid-cols-2">
                <flux:select wire:model="membershipUserId" label="Pengguna" required><flux:select.option value="">Pilih pengguna</flux:select.option>@foreach($this->users as $user)<flux:select.option :value="$user->id">{{ $user->name }} · {{ $user->email }}</flux:select.option>@endforeach</flux:select>
                <flux:select wire:model="membershipRole" label="Peran" required><flux:select.option value="admin-majelis">Admin Majelis</flux:select.option><flux:select.option value="ketua-bidang">Ketua Bidang</flux:select.option><flux:select.option value="anggota">Anggota</flux:select.option></flux:select>
                <flux:input wire:model="membershipPosition" label="Jabatan/posisi" class="md:col-span-2" />
                <div class="flex justify-end md:col-span-2"><flux:button type="submit" variant="primary">Simpan keanggotaan</flux:button></div>
            </form>
        </flux:card>
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        @foreach($this->majelis as $majelis)
            <flux:card wire:key="majelis-{{ $majelis->id }}" class="!p-0 overflow-hidden">
                <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                    <div class="flex items-start justify-between gap-4"><div><flux:badge size="sm" color="blue">{{ $majelis->code }}</flux:badge><flux:heading size="lg" class="mt-3">{{ $majelis->name }}</flux:heading><flux:text class="mt-2 text-sm text-zinc-500">{{ $majelis->description }}</flux:text></div><flux:badge :color="$majelis->is_active ? 'green' : 'zinc'">{{ $majelis->is_active ? 'Aktif' : 'Nonaktif' }}</flux:badge></div>
                    @if(!config('app.demo_read_only'))<div class="mt-4 flex flex-wrap gap-2">@if(Auth::user()->hasGlobalRole('super-admin'))<flux:button size="sm" wire:click="editMajelis({{ $majelis->id }})">Edit</flux:button>@endif @if(Auth::user()->hasGlobalRole('super-admin') || Auth::user()->hasMajelisRole($majelis->id, 'admin-majelis'))<flux:button size="sm" wire:click="openMembership({{ $majelis->id }})" icon="user-plus">Tambah anggota</flux:button>@endif</div>@endif
                </div>
                <div class="grid grid-cols-2 divide-x divide-zinc-200 border-b border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700"><div class="p-4"><flux:text class="text-xs text-zinc-500">Anggota aktif</flux:text><flux:heading size="lg" class="mt-1">{{ $majelis->members->where('pivot.is_active', true)->count() }}</flux:heading></div><div class="p-4"><flux:text class="text-xs text-zinc-500">Program kerja</flux:text><flux:heading size="lg" class="mt-1">{{ $majelis->workPrograms->count() }}</flux:heading></div></div>
                <div class="p-5">
                    <flux:text class="mb-3 text-xs font-medium uppercase tracking-wide text-zinc-500">Keanggotaan</flux:text>
                    <div class="space-y-3">
                        @foreach($majelis->members as $member)
                            @php
                                $hasAssignedPrograms = WorkProgram::where('majelis_id', $majelis->id)
                                    ->where('created_by', $member->id)
                                    ->exists();
                            @endphp
                            <div wire:key="member-{{ $majelis->id }}-{{ $member->id }}-{{ $member->pivot->role }}" class="flex items-center justify-between gap-4 {{ $member->pivot->is_active ? '' : 'opacity-50' }}">
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="sm" :name="$member->name" :initials="$member->initials()" />
                                    <div>
                                        <flux:text class="text-sm font-medium">{{ $member->name }}</flux:text>
                                        <flux:text class="text-xs text-zinc-500">{{ $member->pivot->position ?: 'Tanpa posisi' }}</flux:text>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <flux:badge size="sm">{{ str($member->pivot->role)->replace('-', ' ')->title() }}</flux:badge>
                                    @if(!config('app.demo_read_only') && (Auth::user()->hasGlobalRole('super-admin') || Auth::user()->hasMajelisRole($majelis->id, 'admin-majelis')))
                                        <flux:button 
                                            size="sm" 
                                            variant="ghost" 
                                            class="text-red-600 hover:text-red-700 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40 disabled:opacity-40 disabled:cursor-not-allowed" 
                                            wire:click="deleteMembership({{ $member->pivot->id }})"
                                            wire:confirm="Apakah Anda yakin ingin menghapus pengguna ini dari keanggotaan majelis?"
                                            :disabled="$hasAssignedPrograms"
                                            :title="$hasAssignedPrograms ? 'Tidak dapat dihapus karena memiliki program kerja yang ditugaskan' : 'Hapus keanggotaan'"
                                        >
                                            Hapus
                                        </flux:button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </flux:card>
        @endforeach
    </div>
</div>
