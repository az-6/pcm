<?php

use App\Enums\ReportStatus;
use App\Models\Approval;
use App\Models\ProgramReport;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pelaporan')] class extends Component {
    use WithFileUploads;

    public bool $showCreate = false;
    public ?int $work_program_id = null;
    public string $title = '';
    public string $summary = '';
    public array $expenses = [['description' => '', 'amount' => '', 'spent_on' => '', 'receipt' => null]];
    public string $reviewerNote = '';

    private function ensureDemoIsWritable(): void
    {
        abort_if(config('app.demo_read_only'), 403, 'Demo publik hanya-baca.');
    }

    public function addExpense(): void
    {
        $this->expenses[] = ['description' => '', 'amount' => '', 'spent_on' => '', 'receipt' => null];
    }

    public function removeExpense(int $index): void
    {
        if (count($this->expenses) > 1) {
            unset($this->expenses[$index]);
            $this->expenses = array_values($this->expenses);
        }
    }

    #[Computed]
    public function availablePrograms()
    {
        return \App\Models\WorkProgram::query()->visibleTo(Auth::user())->orderBy('name')->get();
    }

    public function createReport(): void
    {
        $this->ensureDemoIsWritable();
        $this->validate([
            'work_program_id' => ['required', 'integer', Rule::exists('work_programs', 'id')->whereIn('majelis_id', Auth::user()->accessibleMajelisIds())],
            'title' => ['required', 'string', 'min:4', 'max:120'],
            'summary' => ['required', 'string', 'min:10', 'max:2000'],
            'expenses' => ['required', 'array', 'min:1', 'max:10'],
            'expenses.*.description' => ['required', 'string', 'max:160'],
            'expenses.*.amount' => ['required', 'numeric', 'min:0'],
            'expenses.*.spent_on' => ['required', 'date'],
            'expenses.*.receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $program = \App\Models\WorkProgram::findOrFail($this->work_program_id);
        abort_unless(Auth::user()->canManagePrograms($program->majelis_id), 403);
        $realizedAmount = collect($this->expenses)->sum(fn (array $expense): float => (float) $expense['amount']);
        $report = ProgramReport::create(['work_program_id' => $program->id, 'submitted_by' => Auth::id(), 'reference' => 'RPT-'.now()->format('YmdHis'), 'title' => $this->title, 'summary' => $this->summary, 'realized_amount' => $realizedAmount, 'status' => ReportStatus::Draft]);
        foreach ($this->expenses as $expense) {
            $path = $expense['receipt']?->store('demo/reports');
            $report->expenses()->create(['description' => $expense['description'], 'amount' => $expense['amount'], 'spent_on' => $expense['spent_on'], 'receipt_path' => $path]);
        }
        $this->reset(['showCreate', 'work_program_id', 'title', 'summary', 'expenses']);
        $this->expenses = [['description' => '', 'amount' => '', 'spent_on' => '', 'receipt' => null]];
        Flux::toast(variant: 'success', text: 'Laporan dibuat sebagai draft.');
    }

    public function submitDraft(int $reportId): void
    {
        $this->ensureDemoIsWritable();
        $report = ProgramReport::with('workProgram')->findOrFail($reportId);
        abort_unless($report->submitted_by === Auth::id() || Auth::user()->canReview(), 403);
        abort_unless($report->status === ReportStatus::Draft, 422);
        $report->update(['status' => ReportStatus::Submitted, 'submitted_at' => now()]);
        Flux::toast(variant: 'success', text: 'Laporan dikirim untuk disetujui.');
    }
    #[Computed]
    public function reports()
    {
        return ProgramReport::query()->with(['workProgram.majelis', 'submitter', 'expenses'])
            ->visibleTo(Auth::user())
            ->latest()->get();
    }

    public function decide(int $reportId, string $decision): void
    {
        $this->ensureDemoIsWritable();
        abort_unless(Auth::user()->canReview(), 403);
        abort_unless(in_array($decision, ['approved', 'rejected'], true), 422);
        $this->validate(['reviewerNote' => ['nullable', 'string', 'max:1000']]);
        $report = ProgramReport::findOrFail($reportId);
        $report->update(['status' => $decision, 'reviewer_note' => $this->reviewerNote ?: null, 'reviewed_at' => now()]);
        Approval::create(['approvable_type' => ProgramReport::class, 'approvable_id' => $report->id, 'reviewer_id' => Auth::id(), 'decision' => $decision, 'note' => $this->reviewerNote ?: null, 'decided_at' => now()]);
        $this->reset('reviewerNote');
        Flux::toast(variant: $decision === 'approved' ? 'success' : 'danger', text: $decision === 'approved' ? 'Laporan disetujui.' : 'Laporan ditolak.');
    }
}; ?>

<div class="space-y-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><flux:heading size="xl" level="1">Pelaporan Realisasi</flux:heading><flux:text class="mt-2 text-zinc-500">Rincian pengeluaran, bukti, dan persetujuan laporan program kerja.</flux:text></div>@if(!config('app.demo_read_only') && Auth::user()->majelisMemberships()->whereIn('role', ['admin-majelis', 'ketua-bidang'])->exists())<flux:button variant="primary" wire:click="$set('showCreate', true)" icon="plus">Buat laporan</flux:button>@endif</div>
        @if($showCreate)
            <flux:card class="!p-6"><div class="flex items-center justify-between"><div><flux:heading size="lg">Laporan realisasi baru</flux:heading><flux:text class="mt-1 text-sm text-zinc-500">Masukkan satu atau beberapa pengeluaran dan bukti opsional.</flux:text></div><flux:button variant="ghost" size="sm" wire:click="$set('showCreate', false)">Tutup</flux:button></div><form wire:submit="createReport" class="mt-6 space-y-6"><div class="grid gap-5 md:grid-cols-2"><flux:select wire:model="work_program_id" label="Program kerja" required><flux:select.option value="">Pilih program</flux:select.option>@foreach($this->availablePrograms as $program)<flux:select.option :value="$program->id">{{ $program->name }}</flux:select.option>@endforeach</flux:select><flux:input wire:model="title" label="Judul laporan" required /><flux:textarea wire:model="summary" label="Ringkasan realisasi" class="md:col-span-2" rows="3" required /></div><div class="space-y-3"><div class="flex items-center justify-between"><flux:heading size="base">Rincian pengeluaran</flux:heading><flux:button type="button" size="sm" wire:click="addExpense" icon="plus">Tambah pengeluaran</flux:button></div>@foreach($expenses as $index => $expense)<div wire:key="new-expense-{{ $index }}" class="grid gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700 md:grid-cols-[1fr_10rem_10rem_12rem_auto]"><flux:input wire:model="expenses.{{ $index }}.description" label="Deskripsi" required /><flux:input wire:model="expenses.{{ $index }}.amount" label="Nominal" type="number" min="0" required /><flux:input wire:model="expenses.{{ $index }}.spent_on" label="Tanggal" type="date" required /><flux:input wire:model="expenses.{{ $index }}.receipt" label="Bukti" type="file" accept=".pdf,.jpg,.jpeg,.png" /><flux:button type="button" size="sm" variant="ghost" wire:click="removeExpense({{ $index }})" icon="trash">Hapus</flux:button></div>@endforeach</div><div class="flex justify-end"><flux:button type="submit" variant="primary">Simpan draft</flux:button></div></form></flux:card>
        @endif
        <div class="space-y-5">
            @foreach($this->reports as $report)
                <flux:card class="!p-0 overflow-hidden"><div class="grid xl:grid-cols-[1fr_0.55fr]"><div class="p-6"><div class="flex items-start justify-between gap-4"><div><flux:text class="font-mono text-xs text-zinc-500">{{ $report->reference }}</flux:text><flux:heading size="lg" class="mt-2">{{ $report->title }}</flux:heading><flux:text class="mt-2 text-sm text-zinc-500">{{ $report->workProgram->name }} · {{ $report->workProgram->majelis->name }}</flux:text></div><flux:badge :color="$report->status->color()">{{ $report->status->label() }}</flux:badge></div><flux:text class="mt-5 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $report->summary }}</flux:text><div class="mt-5 space-y-3">@foreach($report->expenses as $expense)<div class="flex items-center justify-between gap-4 rounded-lg bg-zinc-50 px-4 py-3 text-sm dark:bg-zinc-900"><div><div>{{ $expense->description }}</div><div class="mt-1 text-xs text-zinc-500">{{ $expense->spent_on->format('d M Y') }} · Bukti tersimpan privat</div>@if($expense->receipt_path)<a href="{{ route('documents.report-expenses', $expense) }}" class="mt-1 inline-flex text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">Unduh bukti</a>@endif</div><span class="font-medium">Rp {{ number_format((float) $expense->amount, 0, ',', '.') }}</span></div>@endforeach</div></div><div class="border-t border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-900/60 xl:border-l xl:border-t-0"><flux:text class="text-xs text-zinc-500">Total realisasi</flux:text><flux:heading size="xl" class="mt-1">Rp {{ number_format((float) $report->realized_amount, 0, ',', '.') }}</flux:heading><flux:text class="mt-2 text-xs text-zinc-500">Diajukan oleh {{ $report->submitter->name }}</flux:text>@if($report->reviewer_note)<div class="mt-4 rounded-lg bg-white p-3 text-sm dark:bg-zinc-800"><span class="font-medium">Catatan reviewer:</span> {{ $report->reviewer_note }}</div>@endif @if(!config('app.demo_read_only'))<div class="mt-8 grid gap-2">@if(Auth::user()->canReview() && $report->status === ReportStatus::Submitted)<flux:input wire:model="reviewerNote" label="Catatan keputusan (opsional)" />@endif @if($report->status === ReportStatus::Draft && $report->submitted_by === Auth::id())<flux:button wire:click="submitDraft({{ $report->id }})">Kirim laporan</flux:button>@endif @if(Auth::user()->canReview() && $report->status === ReportStatus::Submitted)<flux:button variant="primary" wire:click="decide({{ $report->id }}, 'approved')">Setujui laporan</flux:button><flux:button variant="danger" wire:click="decide({{ $report->id }}, 'rejected')">Tolak laporan</flux:button>@endif</div>@endif</div></div></flux:card>
            @endforeach
        </div>
</div>
