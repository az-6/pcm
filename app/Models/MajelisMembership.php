<?php

namespace App\Models;

use Database\Factories\MajelisMembershipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MajelisMembership extends Model
{
    /** @use HasFactory<MajelisMembershipFactory> */
    use HasFactory;

    protected $fillable = ['majelis_id', 'user_id', 'role', 'position', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsTo<Majelis, $this> */
    public function majelis(): BelongsTo
    {
        return $this->belongsTo(Majelis::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
