<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyApprovalLine extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'company_id',
        'module',
        'level',
        'approver_type',
        'specific_user_id',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function specificUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'specific_user_id');
    }
}
