<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'birth_date', 'gender', 'address', 'photo',
        'ktp_number', 'source', 'status', 'applied_date',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'applied_date' => 'date',
    ];

    public function applications(): HasMany
    {
        return $this->hasMany(CandidateApplication::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CandidateDocument::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }
}
