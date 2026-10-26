<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemSetting extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = ['key', 'value', 'description'];
}
