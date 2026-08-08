<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Approval extends Model
{
    /** @use HasFactory<\Database\Factories\ApprovalFactory> */
    use HasFactory;

    protected $fillable = ['approvable_type', 'approvable_id', 'reviewer_id', 'decision', 'note', 'decided_at'];

    protected function casts(): array { return ['decided_at' => 'datetime']; }

    /** @return MorphTo<Model, $this> */
    public function approvable(): MorphTo { return $this->morphTo(); }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewer_id'); }
}
