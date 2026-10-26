<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use SoftDeletes;

    protected $fillable = ['code', 'province_code', 'name'];
    public $timestamps = false;
}
