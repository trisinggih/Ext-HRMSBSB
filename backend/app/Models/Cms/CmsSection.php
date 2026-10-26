<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsSection extends Model
{
    use SoftDeletes;

    protected $table = 'cms_sections';

    protected $fillable = [
        'page_id', 'key', 'title', 'content', 'image_url', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'page_id');
    }
}
