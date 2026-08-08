<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $global_role
 */
#[Fillable(['name', 'email', 'password', 'global_role'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /** @return BelongsToMany<Majelis, $this, \Illuminate\Database\Eloquent\Relations\Pivot, 'pivot'> */
    public function majelis(): BelongsToMany
    {
        return $this->belongsToMany(Majelis::class, 'majelis_memberships')
            ->withPivot(['id', 'role', 'position', 'is_active'])
            ->withTimestamps();
    }

    /** @return HasMany<MajelisMembership, $this> */
    public function majelisMemberships(): HasMany
    {
        return $this->hasMany(MajelisMembership::class);
    }

    public function hasGlobalRole(string ...$roles): bool
    {
        return in_array($this->global_role, $roles, true);
    }

    public function canReview(): bool
    {
        return $this->hasGlobalRole('super-admin', 'pimpinan');
    }

    public function roleLabel(): string
    {
        if ($this->global_role !== null) {
            return match ($this->global_role) {
                'super-admin' => 'Super Admin',
                'pimpinan' => 'Pimpinan',
                default => str($this->global_role)->replace('-', ' ')->title()->toString(),
            };
        }

        $role = $this->relationLoaded('majelisMemberships')
            ? $this->majelisMemberships->firstWhere('is_active', true)?->role
            : $this->majelisMemberships()->where('is_active', true)->value('role');

        return match ($role) {
            'admin-majelis' => 'Admin Majelis',
            'ketua-bidang' => 'Ketua Bidang',
            'anggota' => 'Anggota',
            default => 'Anggota Majelis',
        };
    }

    /** @return array<int> */
    public function accessibleMajelisIds(): array
    {
        if ($this->hasGlobalRole('super-admin', 'pimpinan')) {
            return Majelis::query()->pluck('id')->all();
        }

        return $this->majelisMemberships()
            ->where('is_active', true)
            ->pluck('majelis_id')
            ->all();
    }

    public function hasMajelisRole(int $majelisId, string ...$roles): bool
    {
        if ($this->hasGlobalRole('super-admin', 'pimpinan')) {
            return true;
        }

        return $this->majelisMemberships()
            ->where('majelis_id', $majelisId)
            ->where('is_active', true)
            ->whereIn('role', $roles)
            ->exists();
    }

    public function canManagePrograms(int $majelisId): bool
    {
        return $this->hasMajelisRole($majelisId, 'admin-majelis', 'ketua-bidang');
    }
}
