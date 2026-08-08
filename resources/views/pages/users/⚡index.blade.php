<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pengguna')] class extends Component {
    public bool $showForm = false;
    public ?int $editingUserId = null;
    public string $name = '';
    public string $email = '';
    public string $globalRole = 'none';
    public string $password = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with(['majelisMemberships' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get();
    }

    public function openCreate(): void
    {
        $this->ensureWritable();
        Gate::authorize('create', User::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $userId): void
    {
        $this->ensureWritable();
        $user = User::findOrFail($userId);
        Gate::authorize('update', $user);

        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->globalRole = $user->global_role ?? 'none';
        $this->password = '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->ensureWritable();
        $user = $this->editingUserId ? User::findOrFail($this->editingUserId) : null;
        Gate::authorize($user ? 'update' : 'create', $user ?? User::class);

        $attributes = $this->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'globalRole' => ['required', Rule::in(['none', 'super-admin', 'pimpinan'])],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:72'],
        ]);

        if ($user && $user->is(Auth::user()) && $user->hasGlobalRole('super-admin') && $attributes['globalRole'] !== 'super-admin') {
            $this->addError('globalRole', 'Super Admin aktif tidak dapat menghapus perannya sendiri.');

            return;
        }

        $data = [
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'global_role' => $attributes['globalRole'] === 'none' ? null : $attributes['globalRole'],
        ];

        if (filled($attributes['password'])) {
            $data['password'] = Hash::make($attributes['password']);
        }

        if ($user) {
            $user->update($data);
        } else {
            User::create($data);
        }

        $this->resetForm();
        Flux::toast(variant: 'success', text: 'Data pengguna disimpan.');
    }

    private function resetForm(): void
    {
        $this->reset(['showForm', 'editingUserId', 'name', 'email', 'password']);
        $this->globalRole = 'none';
    }

    private function ensureWritable(): void
    {
        abort_if(config('app.demo_read_only'), 403, 'Demo publik hanya-baca.');
    }
}; ?>

<div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="xl" level="1">Manajemen Pengguna</flux:heading>
            <flux:text class="mt-2 max-w-2xl text-zinc-500">Kelola akun demo fiktif dan peran global untuk kebutuhan simulasi.</flux:text>
        </div>
        @if(!config('app.demo_read_only'))
            <flux:button variant="primary" wire:click="openCreate" icon="plus">Tambah pengguna</flux:button>
        @endif
    </div>

    @if($showForm)
        <flux:card class="!p-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <flux:heading size="lg">{{ $editingUserId ? 'Edit pengguna' : 'Pengguna baru' }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-500">Gunakan identitas fiktif untuk akun demonstrasi.</flux:text>
                </div>
                <flux:button variant="ghost" size="sm" wire:click="$set('showForm', false)">Tutup</flux:button>
            </div>
            <form wire:submit="save" class="mt-6 grid gap-5 md:grid-cols-2">
                <flux:input wire:model="name" label="Nama" required />
                <flux:input wire:model="email" label="Email" type="email" required />
                <flux:select wire:model="globalRole" label="Peran global" required>
                    <flux:select.option value="none">Tidak ada (peran Majelis)</flux:select.option>
                    <flux:select.option value="super-admin">Super Admin</flux:select.option>
                    <flux:select.option value="pimpinan">Pimpinan</flux:select.option>
                </flux:select>
                <flux:input wire:model="password" label="Password {{ $editingUserId ? '(opsional)' : '' }}" type="password" autocomplete="new-password" :required="!$editingUserId" />
                <div class="flex justify-end md:col-span-2">
                    <flux:button type="submit" variant="primary">Simpan pengguna</flux:button>
                </div>
            </form>
        </flux:card>
    @endif

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-900">
                    <tr><th class="px-5 py-3">Pengguna</th><th class="px-5 py-3">Peran global</th><th class="px-5 py-3">Majelis aktif</th><th class="px-5 py-3"><span class="sr-only">Aksi</span></th></tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->users as $user)
                        <tr wire:key="user-{{ $user->id }}" class="bg-white dark:bg-zinc-800">
                            <td class="px-5 py-4"><div class="flex items-center gap-3"><flux:avatar size="sm" :name="$user->name" :initials="$user->initials()" /><div><div class="font-medium">{{ $user->name }}</div><div class="text-xs text-zinc-500">{{ $user->email }}</div></div></div></td>
                            <td class="px-5 py-4"><flux:badge :color="$user->global_role ? 'blue' : 'zinc'">{{ $user->roleLabel() }}</flux:badge></td>
                            <td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ $user->majelisMemberships->count() }}</td>
                            <td class="px-5 py-4 text-right">@if(!config('app.demo_read_only'))<flux:button size="sm" variant="ghost" wire:click="edit({{ $user->id }})">Edit</flux:button>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
