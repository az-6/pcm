<?php

use App\Enums\WorkProgramStatus;
use App\Models\WorkProgram;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Program Kerja')] class extends Component {
    public string $status = 'all';
    public bool $showCreate = false;
    public ?int $majelis_id = null;
    public string $name = '';
    public string $code = '';
    public string $description = '';
    public string $starts_on = '';
    public string $ends_on = '';
    public string $budget = '';

    private function ensureDemoIsWritable(): void
    {
        abort_if(config('app.demo_read_only'), 403, 'Demo publik hanya-baca.');
    }

    #[Computed]
    public function accessibleMajelis()
    {
        return \App\Models\Majelis::query()->visibleTo(Auth::user())->orderBy('name')->get();
    }

    public function createProgram(): void
    {
        $this->ensureDemoIsWritable();
        $this->validate([
            'majelis_id' => ['required', 'integer', Rule::in(Auth::user()->accessibleMajelisIds())],
            'name' => ['required', 'string', 'min:4', 'max:120'],
            'code' => ['required', 'string', 'max:40', 'unique:work_programs,code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'budget' => ['required', 'numeric', 'min:0'],
        ]);

        abort_unless(Auth::user()->canManagePrograms((int) $this->majelis_id), 403);
        \App\Models\WorkProgram::create([
            'majelis_id' => $this->majelis_id,
            'created_by' => Auth::id(),
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'description' => $this->description,
            'starts_on' => $this->starts_on,
            'ends_on' => $this->ends_on,
            'status' => WorkProgramStatus::Draft,
            'budget' => $this->budget,
        ]);

        $this->reset(['showCreate', 'majelis_id', 'name', 'code', 'description', 'starts_on', 'ends_on', 'budget']);
        Flux::toast(variant: 'success', text: 'Program kerja berhasil dibuat sebagai draft.');
    }

    #[Computed]
    public function programs()
    {
        return WorkProgram::query()->with(['majelis', 'creator'])->withCount(['submissions', 'reports'])
            ->visibleTo(Auth::user())
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->orderByDesc('starts_on')->get();
    }
}; ?>

<div class="space-y-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><flux:heading size="xl" level="1">Program Kerja</flux:heading><flux:text class="mt-2 text-zinc-500">Periode, anggaran, status, dan aktivitas tiap program.</flux:text></div><div class="flex flex-col gap-3 sm:flex-row"><flux:select wire:model.live="status" class="w-full sm:w-52"><flux:select.option value="all">Semua status</flux:select.option>@foreach(WorkProgramStatus::cases() as $case)<flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>@endforeach</flux:select>@if(!config('app.demo_read_only') && (Auth::user()->hasGlobalRole('super-admin') || Auth::user()->majelisMemberships()->whereIn('role', ['admin-majelis', 'ketua-bidang'])->exists()))<flux:button variant="primary" wire:click="$set('showCreate', true)" icon="plus">Tambah program</flux:button>@endif</div></div>
        @if($showCreate)
            <flux:card class="!p-6"><div class="flex items-center justify-between"><div><flux:heading size="lg">Program kerja baru</flux:heading><flux:text class="mt-1 text-sm text-zinc-500">Simpan sebagai draft sebelum dipublikasikan.</flux:text></div><flux:button variant="ghost" size="sm" wire:click="$set('showCreate', false)">Tutup</flux:button></div><form wire:submit="createProgram" class="mt-6 grid gap-5 md:grid-cols-2"><flux:select wire:model="majelis_id" label="Majelis" required><flux:select.option value="">Pilih Majelis</flux:select.option>@foreach($this->accessibleMajelis as $majelis)<flux:select.option :value="$majelis->id">{{ $majelis->name }}</flux:select.option>@endforeach</flux:select><flux:input wire:model="code" label="Kode program" placeholder="DIK-2026-03" required /><flux:input wire:model="name" label="Nama program" class="md:col-span-2" required /><flux:textarea wire:model="description" label="Deskripsi" class="md:col-span-2" rows="3" /><flux:input wire:model="starts_on" label="Mulai" type="date" required /><flux:input wire:model="ends_on" label="Selesai" type="date" required /><flux:input wire:model="budget" label="Anggaran (Rp)" type="number" min="0" required /><div class="flex items-end justify-end"><flux:button type="submit" variant="primary">Simpan draft</flux:button></div></form></flux:card>
        @endif
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700"><thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-900"><tr><th class="px-5 py-3">Program</th><th class="px-5 py-3">Majelis</th><th class="px-5 py-3">Periode</th><th class="px-5 py-3">Anggaran</th><th class="px-5 py-3">Aktivitas</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"><span class="sr-only">Aksi</span></th></tr></thead><tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">@foreach($this->programs as $program)<tr wire:key="program-{{ $program->id }}" class="bg-white dark:bg-zinc-800"><td class="px-5 py-4"><div class="font-medium">{{ $program->name }}</div><div class="mt-1 text-xs text-zinc-500">{{ $program->code }}</div></td><td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ $program->majelis->name }}</td><td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ $program->starts_on->format('d M Y') }}<br><span class="text-xs text-zinc-500">s.d. {{ $program->ends_on->format('d M Y') }}</span></td><td class="px-5 py-4 font-medium">Rp {{ number_format((float) $program->budget, 0, ',', '.') }}</td><td class="px-5 py-4 text-zinc-600 dark:text-zinc-300">{{ $program->submissions_count }} pengajuan<br><span class="text-xs text-zinc-500">{{ $program->reports_count }} laporan</span></td><td class="px-5 py-4"><flux:badge :color="$program->status->color()">{{ $program->status->label() }}</flux:badge></td><td class="px-5 py-4 text-right"><flux:button size="sm" variant="ghost" :href="route('programs.show', $program)" wire:navigate>Lihat detail</flux:button></td></tr>@endforeach</tbody></table></div></div>
</div>
