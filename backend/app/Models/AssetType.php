<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class AssetType extends Model
{
    use SoftDeletes;

    protected $fillable = ['company_id', 'code', 'name', 'description'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
