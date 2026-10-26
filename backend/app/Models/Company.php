<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'group_id', 'parent_company_id', 'name', 'legal_name', 'npwp', 'email', 'phone', 'address',
        'kiosk_pin', 'city', 'province', 'postal_code', 'logo', 'is_active',
    ];

    public function group()
    {
        return $this->belongsTo(CompanyGroup::class, 'group_id');
    }

    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    public function childCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_company_id');
    }

    protected $casts = ['is_active' => 'boolean'];

    public function numberings(): HasMany
    {
        return $this->hasMany(CompanyNumbering::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
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

        if ($this->group && $this->group->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->group->logo)) {
            return $this->group->logo;
        }

        if ($this->group_id) {
            $group = CompanyGroup::find($this->group_id);
            if ($group && $group->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($group->logo)) {
                return $group->logo;
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

        $companyLogos = glob(storage_path('app/public/company-logos/*.{png,jpg,jpeg,svg,webp}'), GLOB_BRACE);
        if (!empty($companyLogos)) {
            return 'company-logos/' . basename($companyLogos[0]);
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
}
