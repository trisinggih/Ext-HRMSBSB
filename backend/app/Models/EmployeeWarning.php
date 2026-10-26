<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeWarning extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id', 'warning_type', 'issue_date', 'reason', 'evidence', 'expired_date', 'status',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expired_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
