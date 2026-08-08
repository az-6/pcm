<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundSubmissionItem extends Model
{
    /** @use HasFactory<\Database\Factories\FundSubmissionItemFactory> */
    use HasFactory;

    protected $fillable = ['fund_submission_id', 'description', 'quantity', 'unit_price', 'supporting_document'];

    protected function casts(): array { return ['unit_price' => 'decimal:2']; }

    /** @return BelongsTo<FundSubmission, $this> */
    public function submission(): BelongsTo { return $this->belongsTo(FundSubmission::class, 'fund_submission_id'); }

    public function total(): string { return number_format((float) $this->unit_price * $this->quantity, 2, '.', ''); }
}
