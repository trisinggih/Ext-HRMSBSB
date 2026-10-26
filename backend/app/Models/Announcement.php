<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'body',
        'audience',
        'audience_id',
        'published_at',
        'expires_at',
        'attachment_type',
        'attachment_path'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function reads()
    {
        return $this->hasMany(\App\Models\AnnouncementRead::class, 'announcement_id', 'id');
    }
}
