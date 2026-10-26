<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetApprovalSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'approval_category',
        'company_id',
        'branch_id',
        'department_id',
        'request_type',
        'mengetahui_type',
        'mengetahui_employee_id',
        'mengetahui_title',
        'mengetahui_2_type',
        'mengetahui_2_employee_id',
        'mengetahui_2_title',
        'disetujui_type',
        'disetujui_employee_id',
        'disetujui_title',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function mengetahuiEmployee()
    {
        return $this->belongsTo(Employee::class, 'mengetahui_employee_id');
    }

    public function mengetahui2Employee()
    {
        return $this->belongsTo(Employee::class, 'mengetahui_2_employee_id');
    }

    public function disetujuiEmployee()
    {
        return $this->belongsTo(Employee::class, 'disetujui_employee_id');
    }

    public function stages()
    {
        return $this->hasMany(AssetApprovalSettingStage::class, 'asset_approval_setting_id')->orderBy('level');
    }
}
