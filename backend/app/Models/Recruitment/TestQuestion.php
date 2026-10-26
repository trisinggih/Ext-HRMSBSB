<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestQuestion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'test_id', 'question', 'option_a', 'option_b', 'option_c', 'option_d',
        'correct_answer', 'score',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }
}
