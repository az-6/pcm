<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ProgramReport extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramReportFactory> */
    use HasFactory;

    protected $fillable = ['work_program_id', 'submitted_by', 'reference', 'title', 'summary', 'realized_amount', 'status', 'reviewer_note', 'submitted_at', 'reviewed_at'];

    protected function casts(): array { return ['status' => ReportStatus::class, 'realized_amount' => 'decimal:2', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime']; }

    /**
     * @param  Builder<ProgramReport>  $query
     * @return Builder<ProgramReport>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereHas(
            'workProgram',
            fn (Builder $programs): Builder => $programs->whereIn('majelis_id', $user->accessibleMajelisIds()),
        );
    }

    /** @return BelongsTo<WorkProgram, $this> */
    public function workProgram(): BelongsTo { return $this->belongsTo(WorkProgram::class); }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }

    /** @return HasMany<ReportExpense, $this> */
    public function expenses(): HasMany { return $this->hasMany(ReportExpense::class); }

    /** @return MorphMany<Approval, $this> */
    public function approvals(): MorphMany { return $this->morphMany(Approval::class, 'approvable'); }
}
