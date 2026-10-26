<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CmsPage extends Model
{
    use SoftDeletes;

    protected $table = 'cms_pages';

    protected $fillable = [
        'slug', 'title', 'content', 'meta_title', 'meta_description', 'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(CmsSection::class, 'page_id');
    }
}
