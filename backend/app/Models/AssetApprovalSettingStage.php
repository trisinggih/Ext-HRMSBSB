<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetApprovalSettingStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_approval_setting_id',
        'level',
        'stage_name',
        'employee_id',
        'role_title',
        'notes',
    ];

    public function setting()
    {
        return $this->belongsTo(AssetApprovalSetting::class, 'asset_approval_setting_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
