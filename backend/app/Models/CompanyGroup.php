<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class CompanyGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'logo',
        'favicon',
        'is_active',
    ];

    public function companies()
    {
        return $this->hasMany(Company::class, 'group_id');
    }
}
