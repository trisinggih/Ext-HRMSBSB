<?php

namespace App\Http\Resources\Face;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'model_version' => $this->model_version,
            'is_active' => $this->is_active,
            'has_face_image' => !empty($this->face_image),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
