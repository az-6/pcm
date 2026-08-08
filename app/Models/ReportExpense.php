<?php

namespace App\Models;

use Database\Factories\ReportExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExpense extends Model
{
    /** @use HasFactory<ReportExpenseFactory> */
    use HasFactory;

    protected $fillable = ['program_report_id', 'description', 'amount', 'spent_on', 'receipt_path'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'spent_on' => 'date'];
    }

    /** @return BelongsTo<ProgramReport, $this> */
    public function report(): BelongsTo
    {
        return $this->belongsTo(ProgramReport::class, 'program_report_id');
    }
}
