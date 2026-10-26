<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecruitmentJob extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'title', 'company_id', 'branch_id', 'department_id', 'position_id',
        'vacancy_quota', 'description', 'requirements', 'salary_min', 'salary_max',
        'status', 'application_deadline', 'created_by',
    ];

    protected $casts = [
        'vacancy_quota' => 'integer',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'application_deadline' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Position::class);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(Test::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CandidateApplication::class);
    }
}
