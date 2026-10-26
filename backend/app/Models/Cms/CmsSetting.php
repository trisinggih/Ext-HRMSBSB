<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class CmsSetting extends Model
{
    use SoftDeletes;

    protected $table = 'cms_settings';

    protected $fillable = ['key', 'value', 'group', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
