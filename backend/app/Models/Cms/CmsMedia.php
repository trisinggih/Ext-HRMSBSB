<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class CmsMedia extends Model
{
    use SoftDeletes;

    protected $table = 'cms_media';

    protected $fillable = ['filename', 'path', 'mime', 'size'];
}
