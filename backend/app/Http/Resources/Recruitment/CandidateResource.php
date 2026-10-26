<?php

namespace App\Http\Resources\Recruitment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date?->toDateString(),
            'gender' => $this->gender,
            'address' => $this->address,
            'photo' => $this->photo ? asset('storage/' . $this->photo) : null,
            'ktp_number' => $this->ktp_number,
            'source' => $this->source,
            'status' => $this->status,
            'applied_date' => $this->applied_date?->toDateString(),
            'applications' => CandidateApplicationResource::collection($this->whenLoaded('applications')),
            'documents' => CandidateDocumentResource::collection($this->whenLoaded('documents')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
