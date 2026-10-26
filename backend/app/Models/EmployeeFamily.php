<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeFamily extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id', 'relation', 'name', 'birth_date', 'occupation', 'is_dependent', 'is_tax_dependent', 'phone',
        'domicile_address', 'residential_address'
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_dependent' => 'boolean',
            'is_tax_dependent' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
