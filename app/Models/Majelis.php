<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Majelis extends Model
{
    /** @use HasFactory<\Database\Factories\MajelisFactory> */
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @param  Builder<Majelis>  $query
     * @return Builder<Majelis>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->whereIn('id', $user->accessibleMajelisIds());
    }

    /** @return BelongsToMany<User, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'majelis_memberships')
            ->withPivot(['id', 'role', 'position', 'is_active'])
            ->withTimestamps();
    }

    /** @return HasMany<MajelisMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(MajelisMembership::class);
    }

    /** @return HasMany<WorkProgram, $this> */
    public function workPrograms(): HasMany
    {
        return $this->hasMany(WorkProgram::class);
    }
}
