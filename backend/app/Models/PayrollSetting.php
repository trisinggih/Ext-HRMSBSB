<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollSetting extends Model
{
    use SoftDeletes;

    protected $fillable = ['key', 'value', 'description'];

    /**
     * Ambil nilai config, beri default bila tidak ada. Nilai dari TABEL (payroll_settings).
     */
    public static function value(string $key, mixed $default = null): mixed
    {
        $row = static::where('key', $key)->first();

        return $row ? $row->value : $default;
    }
}
