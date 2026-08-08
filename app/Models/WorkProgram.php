<?php

namespace App\Models;

use App\Enums\WorkProgramStatus;
use Database\Factories\WorkProgramFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkProgram extends Model
{
    /** @use HasFactory<WorkProgramFactory> */
    use HasFactory;

    protected $fillable = ['majelis_id', 'created_by', 'name', 'code', 'description', 'starts_on', 'ends_on', 'status', 'budget'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'status' => WorkProgramStatus::class, 'budget' => 'decimal:2'];
    }

    /**
     * @param  Builder<WorkProgram>  $query
     * @return Builder<WorkProgram>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereIn('majelis_id', $user->accessibleMajelisIds());
    }

    /** @return BelongsTo<Majelis, $this> */
    public function majelis(): BelongsTo
    {
        return $this->belongsTo(Majelis::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<FundSubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(FundSubmission::class);
    }

    /** @return HasMany<ProgramReport, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(ProgramReport::class);
    }
}
