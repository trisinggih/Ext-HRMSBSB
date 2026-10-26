<?php

namespace App\Http\Resources\Cms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CmsMediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'url' => $this->path ? asset('storage/' . $this->path) : null,
            'mime' => $this->mime,
            'size' => $this->size,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
