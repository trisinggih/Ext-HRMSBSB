<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class CheckPoint extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'client_name',
        'purpose',
        'latitude',
        'longitude',
        'address',
        'photo_path',
        'notes',
        'photos',
    ];

    protected $casts = [
        'photos' => 'array',
    ];

    protected $appends = [
        'photo_url',
        'photos_urls',
    ];

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) {
            return null;
        }

        if (filter_var($this->photo_path, FILTER_VALIDATE_URL)) {
            return $this->photo_path;
        }

        return asset('storage/' . ltrim($this->photo_path, '/'));
    }

    public function getPhotosUrlsAttribute(): array
    {
        $urls = [];
        $photos = $this->photos;

        if (is_array($photos) && count($photos) > 0) {
            foreach ($photos as $p) {
                if (filter_var($p, FILTER_VALIDATE_URL)) {
                    $urls[] = $p;
                } else {
                    $urls[] = asset('storage/' . ltrim($p, '/'));
                }
            }
        } elseif ($this->photo_url) {
            $urls[] = $this->photo_url;
        }

        return $urls;
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
