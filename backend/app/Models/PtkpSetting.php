<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

/**
 * Setting PTKP per status maritial/tanggungan, efektif berdasarkan tanggal.
 */
class PtkpSetting extends Model
{
    use SoftDeletes;

    protected $fillable = ['status', 'name', 'ptkp_amount', 'effective_from', 'is_active'];

    protected $casts = [
        'ptkp_amount' => 'float',
        'effective_from' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Ambil PTKP aktif untuk status tertentu pada tanggal tertentu.
     */
    public static function forStatus(string $status, string $date): ?self
    {
        return static::where('status', strtoupper($status))
            ->where('is_active', true)
            ->where('effective_from', '<=', $date)
            ->orderByDesc('effective_from')
            ->first();
    }
}
