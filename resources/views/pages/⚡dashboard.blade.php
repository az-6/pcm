<?php

use App\Models\FundSubmission;
use App\Models\Majelis;
use App\Models\ProgramReport;
use App\Models\WorkProgram;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    #[Computed]
    public function user()
    {
        return Auth::user();
    }

    #[Computed]
    public function stats(): array
    {
        $user = $this->user;
        return [
            'majelis' => Majelis::query()->visibleTo($user)->count(),
            'programs' => WorkProgram::query()->visibleTo($user)->count(),
            'submissions' => FundSubmission::query()->visibleTo($user)->count(),
            'reports' => ProgramReport::query()->visibleTo($user)->count(),
        ];
    }

    #[Computed]
    public function pendingSubmissions()
    {
        return FundSubmission::query()
            ->with(['workProgram.majelis', 'submitter'])
            ->whereIn('status', ['submitted', 'processing'])
            ->visibleTo($this->user)
            ->latest('submitted_at')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function activePrograms()
    {
        return WorkProgram::query()
            ->with('majelis')
            ->visibleTo($this->user)
            ->where('status', 'active')
            ->orderBy('ends_on')
            ->limit(4)
            ->get();
    }
}; ?>

<div class="space-y-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ now()->format('l, d F Y') }}</flux:text>
                <flux:heading size="xl" level="1" class="mt-1">Selamat datang, {{ $this->user->name }}</flux:heading>
                <flux:text class="mt-2 max-w-2xl text-zinc-600 dark:text-zinc-300">{{ $this->user->roleLabel() }} · Demo workspace dengan data fiktif untuk PCM Sukajadi.@if($this->user->global_role === null) Tampilan dan aksi dibatasi pada Majelis tempat Anda terdaftar.@endif</flux:text>
            </div>
            <flux:badge color="green" icon="sparkles">Portfolio demo</flux:badge>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([
                ['label' => 'Majelis terjangkau', 'value' => $this->stats['majelis'], 'icon' => 'building-office-2'],
                ['label' => 'Program kerja', 'value' => $this->stats['programs'], 'icon' => 'clipboard-document-list'],
                ['label' => 'Pengajuan dana', 'value' => $this->stats['submissions'], 'icon' => 'banknotes'],
                ['label' => 'Laporan realisasi', 'value' => $this->stats['reports'], 'icon' => 'document-chart-bar'],
            ] as $stat)
                <flux:card class="!p-5">
                    <div class="flex items-start justify-between"><flux:text class="text-sm text-zinc-500">{{ $stat['label'] }}</flux:text><flux:icon :name="$stat['icon']" class="size-5 text-zinc-400" /></div>
                    <flux:heading size="xl" class="mt-4">{{ $stat['value'] }}</flux:heading>
                    <flux:text class="mt-1 text-xs text-zinc-500">Data demo aktif</flux:text>
                </flux:card>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <flux:card class="!p-0">
                <div class="flex items-center justify-between border-b border-zinc-200 p-5 dark:border-zinc-700"><div><flux:heading size="lg">Antrian pengajuan</flux:heading><flux:text class="text-sm text-zinc-500">Pengajuan yang memerlukan perhatian</flux:text></div><flux:button variant="ghost" size="sm" :href="route('submissions.index')" wire:navigate>Lihat semua</flux:button></div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->pendingSubmissions as $submission)
                        <a href="{{ route('submissions.index') }}" wire:navigate class="flex items-center justify-between gap-4 p-5 transition hover:bg-zinc-50 dark:hover:bg-zinc-800/60"><div class="min-w-0"><flux:text class="font-medium">{{ $submission->title }}</flux:text><flux:text class="mt-1 truncate text-xs text-zinc-500">{{ $submission->reference }} · {{ $submission->workProgram->majelis->name }}</flux:text></div><div class="shrink-0 text-right"><flux:badge :color="$submission->status->color()" size="sm">{{ $submission->status->label() }}</flux:badge><flux:text class="mt-1 text-xs text-zinc-500">Rp {{ number_format((float) $submission->amount, 0, ',', '.') }}</flux:text></div></a>
                    @empty
                        <div class="p-8 text-center"><flux:text class="text-sm text-zinc-500">Tidak ada antrian saat ini.</flux:text></div>
                    @endforelse
                </div>
            </flux:card>

            <flux:card class="!p-0">
                <div class="border-b border-zinc-200 p-5 dark:border-zinc-700"><flux:heading size="lg">Program aktif</flux:heading><flux:text class="text-sm text-zinc-500">Program yang sedang berjalan</flux:text></div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($this->activePrograms as $program)
                        <div class="p-5"><div class="flex items-start justify-between gap-3"><flux:text class="font-medium">{{ $program->name }}</flux:text><flux:badge color="blue" size="sm">Aktif</flux:badge></div><flux:text class="mt-1 text-xs text-zinc-500">{{ $program->majelis->name }} · berakhir {{ $program->ends_on->format('d M Y') }}</flux:text><div class="mt-4 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"><div class="h-full w-2/3 rounded-full bg-blue-500"></div></div></div>
                    @empty
                        <div class="p-8 text-center"><flux:text class="text-sm text-zinc-500">Belum ada program aktif.</flux:text></div>
                    @endforelse
                </div>
            </flux:card>
        </div>
</div>
