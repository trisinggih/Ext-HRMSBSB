<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalRequest extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'workflow_id', 'requested_by', 'module', 'module_id',
        'payload', 'current_step', 'status',
    ];

    protected $casts = [
        'payload' => 'array',
        'current_step' => 'integer',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApprovalLog::class, 'approval_request_id');
    }
}
