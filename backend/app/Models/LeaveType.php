<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'name', 'code', 'category', 'default_days', 'is_paid',
        'is_carry_forward', 'max_carry_forward_days', 'is_active',
    ];

    protected $casts = [
        'default_days' => 'integer',
        'is_paid' => 'boolean',
        'is_carry_forward' => 'boolean',
        'max_carry_forward_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
