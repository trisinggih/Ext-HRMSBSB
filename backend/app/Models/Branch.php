<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'company_id', 'name', 'code', 'address', 'province', 'city', 'postal_code',
        'latitude', 'longitude', 'attendance_radius', 'phone', 'email', 'logo', 'is_active',
    ];

    protected $appends = ['logo_url'];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'attendance_radius' => 'integer',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Dapatkan path relatif file logo yang efektif di storage disk 'public'.
     */
    public function getEffectiveLogoDiskPathAttribute(): ?string
    {
        if ($this->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->logo)) {
            return $this->logo;
        }

        if ($this->company) {
            if ($this->company->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->company->logo)) {
                return $this->company->logo;
            }
            if ($this->company->group && $this->company->group->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->company->group->logo)) {
                return $this->company->group->logo;
            }
        }

        $firstGroup = CompanyGroup::first();
        if ($firstGroup && $firstGroup->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($firstGroup->logo)) {
            return $firstGroup->logo;
        }

        $groupLogos = glob(storage_path('app/public/group-logos/*.{png,jpg,jpeg,svg,webp}'), GLOB_BRACE);
        if (!empty($groupLogos)) {
            return 'group-logos/' . basename($groupLogos[0]);
        }

        $branchLogos = glob(storage_path('app/public/branch-logos/*.{png,jpg,jpeg,svg,webp}'), GLOB_BRACE);
        if (!empty($branchLogos)) {
            return 'branch-logos/' . basename($branchLogos[0]);
        }

        return null;
    }

    /**
     * Dapatkan URL penuh dari logo yang efektif.
     */
    public function getEffectiveLogoUrlAttribute(): ?string
    {
        if ($this->logo && (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://'))) {
            return $this->logo;
        }

        $diskPath = $this->effective_logo_disk_path;
        if ($diskPath) {
            return asset('storage/' . $diskPath);
        }

        return null;
    }

    /**
     * Dapatkan base64 Data URI dari logo untuk DomPDF.
     */
    public function getEffectiveLogoBase64Attribute(): ?string
    {
        $diskPath = $this->effective_logo_disk_path;
        if (!$diskPath) {
            return null;
        }

        $fullPath = storage_path('app/public/' . $diskPath);
        if (!file_exists($fullPath)) {
            return null;
        }

        $mime = mime_content_type($fullPath) ?: 'image/png';
        $data = base64_encode(file_get_contents($fullPath));
        return 'data:' . $mime . ';base64,' . $data;
    }

    /**
     * Dapatkan URL logo spesifik cabang.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo) {
            if (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://')) {
                return $this->logo;
            }
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($this->logo)) {
                return asset('storage/' . $this->logo);
            }
        }
        return null;
    }
}
