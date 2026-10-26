<?php

namespace App\Http\Resources\Cms;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CmsSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'page_id' => $this->page_id,
            'key' => $this->key,
            'title' => $this->title,
            'content' => $this->content,
            'image_url' => $this->image_url,
            'sort_order' => $this->sort_order,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
