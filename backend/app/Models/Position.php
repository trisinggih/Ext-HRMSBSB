<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'company_id', 'branch_id', 'department_id', 'division_id', 'name', 'code', 'description', 'base_salary', 'is_active', 'level', 'parent_id',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'parent_id');
    }

    public function payrollComponents(): HasMany
    {
        return $this->hasMany(PositionPayrollComponent::class, 'position_id');
    }

    public function scopeWithoutExecutives($query)
    {
        return $query->whereNotIn('name', ['CEO', 'CFO'])
                     ->whereNotIn('code', ['CEO', 'CFO'])
                     ->where('name', 'not like', '%CEO%')
                     ->where('name', 'not like', '%CFO%')
                     ->where('code', 'not like', '%CEO%')
                     ->where('code', 'not like', '%CFO%');
    }
}

