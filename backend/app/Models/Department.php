<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = ['branch_id', 'name', 'code', 'description', 'is_active', 'manager_id', 'parent_id', 'manager_position_id'];
    protected $casts = ['is_active' => 'boolean'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function managerPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'manager_position_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }
}
