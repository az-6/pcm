<?php

use App\Enums\SubmissionStatus;
use App\Models\Approval;
use App\Models\FundSubmission;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pengajuan Dana')] class extends Component {
    use WithFileUploads;

    public string $status = 'all';
    public bool $showCreate = false;
    public ?int $work_program_id = null;
    public string $title = '';
    public string $purpose = '';
    public array $items = [['description' => '', 'quantity' => 1, 'unit_price' => '', 'supporting_document' => null]];
    public string $reviewerNote = '';

    private function ensureDemoIsWritable(): void
    {
        abort_if(config('app.demo_read_only'), 403, 'Demo publik hanya-baca.');
    }

    public function addItem(): void
    {
        $this->items[] = ['description' => '', 'quantity' => 1, 'unit_price' => '', 'supporting_document' => null];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    #[Computed]
    public function availablePrograms()
    {
        return \App\Models\WorkProgram::query()->visibleTo(Auth::user())->orderBy('name')->get();
    }

    public function createSubmission(): void
    {
        $this->ensureDemoIsWritable();
        $this->validate([
            'work_program_id' => ['required', 'integer', Rule::exists('work_programs', 'id')->whereIn('majelis_id', Auth::user()->accessibleMajelisIds())],
            'title' => ['required', 'string', 'min:4', 'max:120'],
            'purpose' => ['required', 'string', 'min:10', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.description' => ['required', 'string', 'max:160'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.supporting_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $program = \App\Models\WorkProgram::findOrFail($this->work_program_id);
        abort_unless(Auth::user()->canManagePrograms($program->majelis_id), 403);
        $amount = collect($this->items)->sum(fn (array $item): float => (int) $item['quantity'] * (float) $item['unit_price']);
        $submission = FundSubmission::create(['work_program_id' => $program->id, 'submitted_by' => Auth::id(), 'reference' => 'SUB-'.now()->format('YmdHis'), 'title' => $this->title, 'purpose' => $this->purpose, 'amount' => $amount, 'status' => SubmissionStatus::Draft]);
        foreach ($this->items as $item) {
            $path = $item['supporting_document']?->store('demo/submissions');
            $submission->items()->create(['description' => $item['description'], 'quantity' => $item['quantity'], 'unit_price' => $item['unit_price'], 'supporting_document' => $path]);
        }
        $this->reset(['showCreate', 'work_program_id', 'title', 'purpose', 'items']);
        $this->items = [['description' => '', 'quantity' => 1, 'unit_price' => '', 'supporting_document' => null]];
        Flux::toast(variant: 'success', text: 'Pengajuan dibuat sebagai draft.');
    }

    public function submitDraft(int $submissionId): void
    {
        $this->ensureDemoIsWritable();
        $submission = FundSubmission::with('workProgram')->findOrFail($submissionId);
        abort_unless($submission->submitted_by === Auth::id() || Auth::user()->canReview(), 403);
        abort_unless($submission->status === SubmissionStatus::Draft, 422);
        $submission->update(['status' => SubmissionStatus::Submitted, 'submitted_at' => now()]);
        Flux::toast(variant: 'success', text: 'Pengajuan dikirim untuk diproses.');
    }

    #[Computed]
    public function submissions()
    {
        return FundSubmission::query()->with(['workProgram.majelis', 'submitter', 'items'])
            ->visibleTo(Auth::user())
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->latest()->get();
    }

    public function process(int $submissionId): void
    {
        $this->ensureDemoIsWritable();
        abort_unless(Auth::user()->canReview(), 403);
        $submission = FundSubmission::findOrFail($submissionId);
        abort_unless(in_array($submission->status, [SubmissionStatus::Submitted, SubmissionStatus::Processing], true), 422);
        $submission->update(['status' => SubmissionStatus::Processing]);
        Flux::toast(variant: 'success', text: 'Pengajuan dipindahkan ke tahap proses.');
    }

    public function decide(int $submissionId, string $decision): void
    {
        $this->ensureDemoIsWritable();
        abort_unless(Auth::user()->canReview(), 403);
        abort_unless(in_array($decision, ['approved', 'rejected'], true), 422);
        $this->validate(['reviewerNote' => ['nullable', 'string', 'max:1000']]);
        $submission = FundSubmission::findOrFail($submissionId);
        $submission->update(['status' => $decision, 'reviewer_note' => $this->reviewerNote ?: null, 'reviewed_at' => now()]);
        Approval::create(['approvable_type' => FundSubmission::class, 'approvable_id' => $submission->id, 'reviewer_id' => Auth::id(), 'decision' => $decision, 'note' => $this->reviewerNote ?: null, 'decided_at' => now()]);
        $this->reset('reviewerNote');
        Flux::toast(variant: $decision === 'approved' ? 'success' : 'danger', text: $decision === 'approved' ? 'Pengajuan diterima.' : 'Pengajuan ditolak.');
    }
}; ?>

<div class="space-y-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><flux:heading size="xl" level="1">Pengajuan Dana</flux:heading><flux:text class="mt-2 text-zinc-500">Workflow: Diajukan → Diproses → Diterima atau Ditolak.</flux:text></div><div class="flex flex-col gap-3 sm:flex-row"><flux:select wire:model.live="status" class="w-full sm:w-52"><flux:select.option value="all">Semua status</flux:select.option>@foreach(SubmissionStatus::cases() as $case)<flux:select.option :value="$case->value">{{ $case->label() }}</flux:select.option>@endforeach</flux:select>@if(!config('app.demo_read_only') && Auth::user()->majelisMemberships()->whereIn('role', ['admin-majelis', 'ketua-bidang'])->exists())<flux:button variant="primary" wire:click="$set('showCreate', true)" icon="plus">Buat pengajuan</flux:button>@endif</div></div>
        @if($showCreate)
            <flux:card class="!p-6"><div class="flex items-center justify-between"><div><flux:heading size="lg">Pengajuan dana baru</flux:heading><flux:text class="mt-1 text-sm text-zinc-500">Tambahkan satu atau beberapa item dan bukti pendukung opsional.</flux:text></div><flux:button variant="ghost" size="sm" wire:click="$set('showCreate', false)">Tutup</flux:button></div><form wire:submit="createSubmission" class="mt-6 space-y-6"><div class="grid gap-5 md:grid-cols-2"><flux:select wire:model="work_program_id" label="Program kerja" required><flux:select.option value="">Pilih program</flux:select.option>@foreach($this->availablePrograms as $program)<flux:select.option :value="$program->id">{{ $program->name }}</flux:select.option>@endforeach</flux:select><flux:input wire:model="title" label="Judul pengajuan" required /><flux:textarea wire:model="purpose" label="Tujuan penggunaan" class="md:col-span-2" rows="3" required /></div><div class="space-y-3"><div class="flex items-center justify-between"><flux:heading size="base">Item anggaran</flux:heading><flux:button type="button" size="sm" wire:click="addItem" icon="plus">Tambah item</flux:button></div>@foreach($items as $index => $item)<div wire:key="new-item-{{ $index }}" class="grid gap-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700 md:grid-cols-[1fr_7rem_10rem_12rem_auto]"><flux:input wire:model="items.{{ $index }}.description" label="Deskripsi" required /><flux:input wire:model="items.{{ $index }}.quantity" label="Jumlah" type="number" min="1" required /><flux:input wire:model="items.{{ $index }}.unit_price" label="Harga satuan" type="number" min="0" required /><flux:input wire:model="items.{{ $index }}.supporting_document" label="Bukti" type="file" accept=".pdf,.jpg,.jpeg,.png" /><flux:button type="button" size="sm" variant="ghost" wire:click="removeItem({{ $index }})" icon="trash">Hapus</flux:button></div>@endforeach</div><div class="flex justify-end"><flux:button type="submit" variant="primary">Simpan draft</flux:button></div></form></flux:card>
        @endif
        <div class="grid gap-5 xl:grid-cols-2">
            @foreach($this->submissions as $submission)
                <flux:card class="!p-0 overflow-hidden">
                    <div class="border-b border-zinc-200 p-6 dark:border-zinc-700"><div class="flex items-start justify-between gap-4"><div><flux:text class="font-mono text-xs text-zinc-500">{{ $submission->reference }}</flux:text><flux:heading size="lg" class="mt-2">{{ $submission->title }}</flux:heading><flux:text class="mt-2 text-sm text-zinc-500">{{ $submission->workProgram->name }} · {{ $submission->workProgram->majelis->name }}</flux:text></div><flux:badge :color="$submission->status->color()">{{ $submission->status->label() }}</flux:badge></div></div>
                    <div class="p-6"><flux:text class="text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $submission->purpose }}</flux:text><div class="mt-5 space-y-3">@foreach($submission->items as $item)<div class="flex justify-between gap-4 text-sm"><span class="text-zinc-500">{{ $item->description }} × {{ $item->quantity }}@if($item->supporting_document)<a href="{{ route('documents.submission-items', $item) }}" class="mt-1 block text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">Unduh bukti</a>@endif</span><span class="font-medium">Rp {{ number_format((float) $item->total(), 0, ',', '.') }}</span></div>@endforeach</div>@if($submission->reviewer_note)<div class="mt-4 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-900"><span class="font-medium">Catatan reviewer:</span> {{ $submission->reviewer_note }}</div>@endif<div class="mt-5 flex flex-col gap-4 border-t border-zinc-200 pt-5 dark:border-zinc-700 sm:flex-row sm:items-end sm:justify-between"><div><flux:text class="text-xs text-zinc-500">Total pengajuan</flux:text><flux:heading size="lg">Rp {{ number_format((float) $submission->amount, 0, ',', '.') }}</flux:heading></div>@if(!config('app.demo_read_only'))<div class="flex flex-col gap-2 sm:items-end">@if(Auth::user()->canReview() && !in_array($submission->status, [SubmissionStatus::Approved, SubmissionStatus::Rejected], true))<flux:input wire:model="reviewerNote" label="Catatan keputusan (opsional)" class="w-full sm:w-80" />@endif<div class="flex flex-wrap justify-end gap-2">@if($submission->status === SubmissionStatus::Draft && $submission->submitted_by === Auth::id())<flux:button size="sm" wire:click="submitDraft({{ $submission->id }})">Kirim pengajuan</flux:button>@endif @if(Auth::user()->canReview() && !in_array($submission->status, [SubmissionStatus::Approved, SubmissionStatus::Rejected], true))@if($submission->status === SubmissionStatus::Submitted)<flux:button size="sm" wire:click="process({{ $submission->id }})">Proses</flux:button>@endif<flux:button size="sm" variant="danger" wire:click="decide({{ $submission->id }}, 'rejected')">Tolak</flux:button><flux:button size="sm" variant="primary" wire:click="decide({{ $submission->id }}, 'approved')">Terima</flux:button>@endif</div></div>@endif</div></div>
                </flux:card>
            @endforeach
        </div>
</div>
