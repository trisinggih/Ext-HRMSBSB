<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EmployeeSignature extends Model
{
    use HasFactory;

    protected $table = 'employee_signatures';

    protected $fillable = [
        'employee_id',
        'signature_path',
        'strokes_data',
        'device_info',
        'ip_address',
    ];

    protected $appends = [
        'signature_url',
        'signature_base64',
    ];

    public function getSignatureUrlAttribute(): ?string
    {
        if (!$this->signature_path) {
            return null;
        }

        return asset(Storage::url($this->signature_path));
    }

    public function getSignatureBase64Attribute(): ?string
    {
        if (!$this->signature_path) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($this->signature_path);
        if (!file_exists($fullPath)) {
            $altPath = storage_path('app/public/' . $this->signature_path);
            if (file_exists($altPath)) {
                $fullPath = $altPath;
            } else {
                return null;
            }
        }

        $mime = @mime_content_type($fullPath) ?: 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
