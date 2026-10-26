<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetRepair extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'department_id',
        'employee_id',
        'asset_id',
        'submission_date',
        'submission_month',
        'item_name',
        'brand_model',
        'asset_code',
        'quantity',
        'location',
        'condition',
        'condition_other',
        'damage_description',
        'repair_description',
        'pic_name',
        'operational_disrupted',
        'estimated_cost',
        'priority',
        'bank_account_info',
        'repair_date',
        'status',
        'acknowledged_by',
        'acknowledged_at',
        'approved_by',
        'approved_at',
        'admin_notes',
    ];

    protected $casts = [
        'submission_date' => 'date',
        'repair_date' => 'date',
        'operational_disrupted' => 'boolean',
        'estimated_cost' => 'decimal:2',
        'acknowledged_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(Employee::class, 'acknowledged_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }
}
