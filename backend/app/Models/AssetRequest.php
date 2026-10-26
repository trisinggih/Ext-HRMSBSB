<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'department_id',
        'manager_id',
        'submission_month',
        'request_type',
        'requester_id',
        'for_employee_id',
        'for_who',
        'item_name',
        'brand',
        'model',
        'item_size',
        'placement',
        'asset_type_id',
        'purpose',
        'quantity',
        'estimated_price',
        'specifications',
        'notes',
        'status',
        'acknowledged_by',
        'acknowledged_at',
        'approved_by',
        'approved_at',
        'admin_notes',
        'purchased_at',
        'asset_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'estimated_price' => 'decimal:2',
        'purchased_at' => 'datetime',
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

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function requester()
    {
        return $this->belongsTo(Employee::class, 'requester_id');
    }

    public function forEmployee()
    {
        return $this->belongsTo(Employee::class, 'for_employee_id');
    }

    public function assetType()
    {
        return $this->belongsTo(AssetType::class, 'asset_type_id');
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

    public function items()
    {
        return $this->hasMany(AssetRequestItem::class);
    }

    public function approvals()
    {
        return $this->hasMany(AssetRequestApproval::class)->orderBy('level', 'asc');
    }
}
