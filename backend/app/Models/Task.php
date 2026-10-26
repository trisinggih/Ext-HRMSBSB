<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'creator_id', 'title', 'description', 'due_date', 'priority',
        'progress', 'status', 'attachment', 'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public const STATUS_TODO = 'todo';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_REVIEW = 'review';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const TRANSITIONS = [
        self::STATUS_TODO => [self::STATUS_IN_PROGRESS, self::STATUS_REVIEW, self::STATUS_DONE, self::STATUS_CANCELLED],
        self::STATUS_IN_PROGRESS => [self::STATUS_REVIEW, self::STATUS_DONE, self::STATUS_CANCELLED],
        self::STATUS_REVIEW => [self::STATUS_IN_PROGRESS, self::STATUS_DONE, self::STATUS_CANCELLED],
        self::STATUS_DONE => [self::STATUS_TODO, self::STATUS_IN_PROGRESS],
        self::STATUS_CANCELLED => [self::STATUS_TODO], // reopen
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'creator_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'task_assignments')
            ->withPivot('role');
    }

    public function assigneesOfRole(string $role)
    {
        return $this->assignees()->wherePivot('role', $role);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }
}
