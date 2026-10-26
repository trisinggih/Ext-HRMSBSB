<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionRequest extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'employee_id', 'type', 'date', 'end_date', 'start_time', 'end_time',
        'reason', 'attachment', 'status',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
    ];

    protected $appends = ['start_date'];

    public function getDateAttribute($value)
    {
        if (!$value) return null;
        return $value instanceof \Carbon\Carbon ? $value->format('Y-m-d') : substr((string)$value, 0, 10);
    }

    public function getStartDateAttribute()
    {
        $val = $this->attributes['date'] ?? null;
        if (!$val) return null;
        return $val instanceof \Carbon\Carbon ? $val->format('Y-m-d') : substr((string)$val, 0, 10);
    }

    public function getEndDateAttribute($value)
    {
        $val = $value ?: ($this->attributes['date'] ?? null);
        if (!$val) return null;
        return $val instanceof \Carbon\Carbon ? $val->format('Y-m-d') : substr((string)$val, 0, 10);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approval()
    {
        return $this->hasOne(ApprovalRequest::class, 'module_id', 'id')
            ->where('module', 'permission');
    }
}
