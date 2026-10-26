<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'inventory_number',
        'employee_id',
        'name',
        'quantity',
        'unit',
        'serial_number',
        'type',
        'status',
        'purchase_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function assetType()
    {
        return $this->belongsTo(AssetType::class, 'type');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
