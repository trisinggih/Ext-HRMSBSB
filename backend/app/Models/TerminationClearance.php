<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerminationClearance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'termination_id', 'item', 'is_returned', 'note', 'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'is_returned' => 'boolean',
            'returned_at' => 'datetime',
        ];
    }

    public function termination(): BelongsTo
    {
        return $this->belongsTo(EmployeeTermination::class, 'termination_id');
    }
}
