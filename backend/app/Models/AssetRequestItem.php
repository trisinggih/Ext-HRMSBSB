<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_request_id',
        'item_name',
        'item_size',
        'quantity',
        'placement',
        'unit_price',
        'estimated_price',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'estimated_price' => 'decimal:2',
    ];

    public function assetRequest()
    {
        return $this->belongsTo(AssetRequest::class);
    }
}
