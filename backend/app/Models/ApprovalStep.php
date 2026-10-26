<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStep extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'workflow_id', 'approver_type', 'approver_id', 'order', 'is_optional',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_optional' => 'boolean',
    ];

    protected $appends = ['approver_name'];

    public function getApproverNameAttribute()
    {
        if ($this->approver_type === 'supervisor') {
            return 'Atasan Langsung';
        } elseif ($this->approver_type === 'hr') {
            return 'HRD';
        } elseif ($this->approverEmployee) {
            return $this->approverEmployee->name;
        }
        return 'System';
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }

    public function approverEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approver_id');
    }
}
