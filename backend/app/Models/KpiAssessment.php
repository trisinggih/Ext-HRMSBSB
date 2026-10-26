<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiAssessment extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'transaction_number', 'period_id', 'employee_id', 'template_id', 'reviewer_id',
        'status', 'final_score', 'reviewer_notes', 'reviewed_at', 'approved_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($assessment) {
            if (empty($assessment->transaction_number)) {
                $year = date('Y');
                $month = date('m');
                $periodId = str_pad((string)($assessment->period_id ?? 1), 2, '0', STR_PAD_LEFT);
                $maxId = static::max('id') ?? 0;
                $seq = str_pad((string)($maxId + 1), 4, '0', STR_PAD_LEFT);
                $assessment->transaction_number = "KPI-{$year}-{$month}-{$periodId}-{$seq}";
            }
        });
    }

    protected $casts = [
        'final_score' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(KpiPeriod::class, 'period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTemplate::class, 'template_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewer_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(KpiAssessmentScore::class, 'assessment_id');
    }

    /**
     * Hitung final_score berdasarkan skor per indikator (proporsional dari bobot).
     */
    public function calculateFinalScore(): float
    {
        $scores = $this->scores()->with('templateIndicator')->get();
        if ($scores->isEmpty()) return 0;

        $totalWeight = $scores->sum(fn ($s) => $s->templateIndicator->weight ?? 0);
        if ($totalWeight == 0) return 0;

        $weightedSum = $scores->sum(fn ($s) => ($s->raw_score ?? 0) * ($s->templateIndicator->weight ?? 0));
        return round($weightedSum / $totalWeight, 2);
    }
}
