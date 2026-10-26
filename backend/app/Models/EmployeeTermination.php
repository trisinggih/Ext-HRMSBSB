<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeTermination extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id', 'type', 'termination_date', 'reason', 'status',
    ];

    protected function casts(): array
    {
        return [
            'termination_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function clearances(): HasMany
    {
        return $this->hasMany(TerminationClearance::class, 'termination_id');
    }
}
