<?php

use App\Models\WorkProgram;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detail Program Kerja')] class extends Component {
    public WorkProgram $workProgram;

    public function mount(WorkProgram $workProgram): void
    {
        Gate::authorize('view', $workProgram);

        $this->workProgram = $workProgram->load([
            'majelis',
            'creator',
            'submissions' => fn ($query) => $query->with(['submitter', 'items', 'approvals.reviewer'])->latest(),
            'reports' => fn ($query) => $query->with(['submitter', 'expenses', 'approvals.reviewer'])->latest(),
        ]);
    }
};
?>

<div class="space-y-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:button variant="ghost" size="sm" :href="route('programs.index')" wire:navigate icon="arrow-left">
                Kembali ke program
            </flux:button>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <flux:heading size="xl" level="1">{{ $workProgram->name }}</flux:heading>
                <flux:badge :color="$workProgram->status->color()">{{ $workProgram->status->label() }}</flux:badge>
            </div>
            <flux:text class="mt-2 text-zinc-500">{{ $workProgram->code }} · {{ $workProgram->majelis->name }}</flux:text>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('submissions.index')" wire:navigate icon="banknotes">Pengajuan dana</flux:button>
            <flux:button :href="route('reports.index')" wire:navigate icon="document-chart-bar">Pelaporan</flux:button>
        </div>
    </div>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        <flux:card class="!p-5">
            <flux:text class="text-xs uppercase tracking-wide text-zinc-500">Anggaran program</flux:text>
            <flux:heading size="lg" class="mt-2">Rp {{ number_format((float) $workProgram->budget, 0, ',', '.') }}</flux:heading>
        </flux:card>
        <flux:card class="!p-5">
            <flux:text class="text-xs uppercase tracking-wide text-zinc-500">Total pengajuan</flux:text>
            <flux:heading size="lg" class="mt-2">Rp {{ number_format((float) $workProgram->submissions->sum('amount'), 0, ',', '.') }}</flux:heading>
            <flux:text class="mt-1 text-xs text-zinc-500">{{ $workProgram->submissions->count() }} pengajuan</flux:text>
        </flux:card>
        <flux:card class="!p-5">
            <flux:text class="text-xs uppercase tracking-wide text-zinc-500">Total realisasi</flux:text>
            <flux:heading size="lg" class="mt-2">Rp {{ number_format((float) $workProgram->reports->sum('realized_amount'), 0, ',', '.') }}</flux:heading>
            <flux:text class="mt-1 text-xs text-zinc-500">{{ $workProgram->reports->count() }} laporan</flux:text>
        </flux:card>
        <flux:card class="!p-5">
            <flux:text class="text-xs uppercase tracking-wide text-zinc-500">Periode</flux:text>
            <flux:heading size="base" class="mt-2">{{ $workProgram->starts_on->format('d M Y') }}</flux:heading>
            <flux:text class="mt-1 text-xs text-zinc-500">s.d. {{ $workProgram->ends_on->format('d M Y') }}</flux:text>
        </flux:card>
    </div>

    <flux:card class="!p-6">
        <div class="grid gap-6 lg:grid-cols-[1fr_0.42fr]">
            <div>
                <flux:heading size="lg">Ringkasan program</flux:heading>
                <flux:text class="mt-3 leading-7 text-zinc-600 dark:text-zinc-300">
                    {{ $workProgram->description ?: 'Belum ada deskripsi program.' }}
                </flux:text>
            </div>
            <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-1">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-zinc-500">Majelis</dt>
                    <dd class="mt-1 font-medium">{{ $workProgram->majelis->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-zinc-500">Dibuat oleh</dt>
                    <dd class="mt-1 font-medium">{{ $workProgram->creator->name }}</dd>
                </div>
            </dl>
        </div>
    </flux:card>

    <section class="space-y-5">
        <div class="flex items-end justify-between gap-4">
            <div>
                <flux:heading size="lg" level="2">Pengajuan dana</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500">Rincian kebutuhan anggaran yang terhubung ke program ini.</flux:text>
            </div>
            <flux:badge color="zinc">{{ $workProgram->submissions->count() }}</flux:badge>
        </div>

        @forelse($workProgram->submissions as $submission)
            <flux:card wire:key="submission-{{ $submission->id }}" class="!p-0 overflow-hidden">
                <div class="border-b border-zinc-200 p-6 dark:border-zinc-700">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <flux:text class="font-mono text-xs text-zinc-500">{{ $submission->reference }}</flux:text>
                            <flux:heading size="lg" class="mt-2">{{ $submission->title }}</flux:heading>
                            <flux:text class="mt-2 text-sm text-zinc-500">Diajukan oleh {{ $submission->submitter->name }}</flux:text>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-semibold">Rp {{ number_format((float) $submission->amount, 0, ',', '.') }}</span>
                            <flux:badge :color="$submission->status->color()">{{ $submission->status->label() }}</flux:badge>
                        </div>
                    </div>
                    <flux:text class="mt-4 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $submission->purpose }}</flux:text>
                </div>
                <div class="grid gap-6 p-6 lg:grid-cols-[1fr_0.42fr]">
                    <div class="space-y-3">
                        @foreach($submission->items as $item)
                            <div wire:key="submission-item-{{ $item->id }}" class="flex items-center justify-between gap-4 rounded-lg bg-zinc-50 px-4 py-3 text-sm dark:bg-zinc-900">
                                <div>
                                    <div class="font-medium">{{ $item->description }}</div>
                                    <div class="mt-1 text-xs text-zinc-500">{{ $item->quantity }} × Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</div>
                                    @if($item->supporting_document)
                                        <a href="{{ route('documents.submission-items', $item) }}" class="mt-2 inline-flex text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">Unduh bukti pendukung</a>
                                    @endif
                                </div>
                                <span class="shrink-0 font-medium">Rp {{ number_format((float) $item->total(), 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div>
                        <flux:text class="text-xs uppercase tracking-wide text-zinc-500">Riwayat persetujuan</flux:text>
                        <div class="mt-3 space-y-3">
                            @forelse($submission->approvals as $approval)
                                <div wire:key="submission-approval-{{ $approval->id }}" class="text-sm">
                                    <div class="font-medium">{{ str($approval->decision)->replace('-', ' ')->title() }}</div>
                                    <div class="mt-1 text-xs text-zinc-500">{{ $approval->reviewer->name }} · {{ $approval->decided_at->format('d M Y H:i') }}</div>
                                    @if($approval->note)<div class="mt-1 text-xs text-zinc-600 dark:text-zinc-300">{{ $approval->note }}</div>@endif
                                </div>
                            @empty
                                <flux:text class="text-sm text-zinc-500">Belum ada keputusan.</flux:text>
                            @endforelse
                        </div>
                    </div>
                </div>
            </flux:card>
        @empty
            <flux:card class="!p-6 text-center">
                <flux:heading size="base">Belum ada pengajuan dana</flux:heading>
                <flux:text class="mt-2 text-sm text-zinc-500">Pengajuan yang dibuat untuk program ini akan muncul di sini.</flux:text>
            </flux:card>
        @endforelse
    </section>

    <section class="space-y-5">
        <div class="flex items-end justify-between gap-4">
            <div>
                <flux:heading size="lg" level="2">Laporan realisasi</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500">Pengeluaran dan hasil pelaksanaan program.</flux:text>
            </div>
            <flux:badge color="zinc">{{ $workProgram->reports->count() }}</flux:badge>
        </div>

        @forelse($workProgram->reports as $report)
            <flux:card wire:key="report-{{ $report->id }}" class="!p-0 overflow-hidden">
                <div class="grid xl:grid-cols-[1fr_0.42fr]">
                    <div class="p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <flux:text class="font-mono text-xs text-zinc-500">{{ $report->reference }}</flux:text>
                                <flux:heading size="lg" class="mt-2">{{ $report->title }}</flux:heading>
                                <flux:text class="mt-2 text-sm text-zinc-500">Diajukan oleh {{ $report->submitter->name }}</flux:text>
                            </div>
                            <flux:badge :color="$report->status->color()">{{ $report->status->label() }}</flux:badge>
                        </div>
                        <flux:text class="mt-5 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $report->summary }}</flux:text>
                        <div class="mt-5 space-y-3">
                            @foreach($report->expenses as $expense)
                                <div wire:key="report-expense-{{ $expense->id }}" class="flex items-center justify-between gap-4 rounded-lg bg-zinc-50 px-4 py-3 text-sm dark:bg-zinc-900">
                                    <div>
                                        <div class="font-medium">{{ $expense->description }}</div>
                                        <div class="mt-1 text-xs text-zinc-500">{{ $expense->spent_on->format('d M Y') }}</div>
                                        @if($expense->receipt_path)
                                            <a href="{{ route('documents.report-expenses', $expense) }}" class="mt-2 inline-flex text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">Unduh bukti pengeluaran</a>
                                        @endif
                                    </div>
                                    <span class="shrink-0 font-medium">Rp {{ number_format((float) $expense->amount, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="border-t border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-900/60 xl:border-l xl:border-t-0">
                        <flux:text class="text-xs uppercase tracking-wide text-zinc-500">Total realisasi</flux:text>
                        <flux:heading size="xl" class="mt-2">Rp {{ number_format((float) $report->realized_amount, 0, ',', '.') }}</flux:heading>
                        <flux:text class="mt-8 text-xs uppercase tracking-wide text-zinc-500">Riwayat persetujuan</flux:text>
                        <div class="mt-3 space-y-3">
                            @forelse($report->approvals as $approval)
                                <div wire:key="report-approval-{{ $approval->id }}" class="text-sm">
                                    <div class="font-medium">{{ str($approval->decision)->replace('-', ' ')->title() }}</div>
                                    <div class="mt-1 text-xs text-zinc-500">{{ $approval->reviewer->name }} · {{ $approval->decided_at->format('d M Y H:i') }}</div>
                                    @if($approval->note)<div class="mt-1 text-xs text-zinc-600 dark:text-zinc-300">{{ $approval->note }}</div>@endif
                                </div>
                            @empty
                                <flux:text class="text-sm text-zinc-500">Belum ada keputusan.</flux:text>
                            @endforelse
                        </div>
                    </div>
                </div>
            </flux:card>
        @empty
            <flux:card class="!p-6 text-center">
                <flux:heading size="base">Belum ada laporan realisasi</flux:heading>
                <flux:text class="mt-2 text-sm text-zinc-500">Laporan yang dibuat untuk program ini akan muncul di sini.</flux:text>
            </flux:card>
        @endforelse
    </section>
</div>
