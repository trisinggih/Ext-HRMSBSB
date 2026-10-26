<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class CompanyNumbering extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'type', 'format', 'counter'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
