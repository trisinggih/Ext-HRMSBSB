<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalLog extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'approval_request_id', 'approval_step_id', 'admin_id',
        'approver_name', 'action', 'note',
    ];

    public function approvalRequest(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(ApprovalStep::class, 'approval_step_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
