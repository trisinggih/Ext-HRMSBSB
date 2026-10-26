<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

/**
 * Model untuk tabel cms_products — katalog produk Company Profile.
 */
class CmsProduct extends Model
{
    use SoftDeletes;

    protected $table = 'cms_products';

    protected $fillable = [
        'name',
        'category',
        'categoryLabel',
        'badge',
        'highlight',
        'specLabel',
        'specValue',
        'description',
        'images',
        'specs',
        'compatibility',
        'marketplaceLinks',
        'is_active',
    ];

    protected $casts = [
        'images'           => 'array',
        'specs'            => 'array',
        'compatibility'    => 'array',
        'marketplaceLinks' => 'array',
        'is_active'        => 'boolean',
    ];
}
