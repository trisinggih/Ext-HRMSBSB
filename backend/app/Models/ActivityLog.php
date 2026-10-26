<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'actor_type',
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function actor()
    {
        return $this->morphTo();
    }

    public function subject()
    {
        return $this->morphTo();
    }
}
