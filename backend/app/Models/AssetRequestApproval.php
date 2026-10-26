<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetRequestApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_request_id',
        'level',
        'role_name',
        'approver_id',
        'status',
        'notes',
        'action_date',
    ];

    protected $casts = [
        'level' => 'integer',
        'action_date' => 'datetime',
    ];

    public function assetRequest()
    {
        return $this->belongsTo(AssetRequest::class);
    }

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }
}
