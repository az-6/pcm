<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FundSubmission extends Model
{
    /** @use HasFactory<\Database\Factories\FundSubmissionFactory> */
    use HasFactory;

    protected $fillable = ['work_program_id', 'submitted_by', 'reference', 'title', 'purpose', 'amount', 'status', 'reviewer_note', 'submitted_at', 'reviewed_at'];

    protected function casts(): array { return ['status' => SubmissionStatus::class, 'amount' => 'decimal:2', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime']; }

    /**
     * @param  Builder<FundSubmission>  $query
     * @return Builder<FundSubmission>
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

    /** @return HasMany<FundSubmissionItem, $this> */
    public function items(): HasMany { return $this->hasMany(FundSubmissionItem::class); }

    /** @return MorphMany<Approval, $this> */
    public function approvals(): MorphMany { return $this->morphMany(Approval::class, 'approvable'); }
}
