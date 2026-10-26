<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class AnnouncementRead extends Model
{
    use SoftDeletes;

    protected $table = 'announcement_reads';
    protected $fillable = ['announcement_id', 'employee_id'];
}
