<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CategoryResource;

class ChallengeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? null,
            'description' => $this->description ?? null,
            'category_id' => $this->category_id ?? null,
            'category' => $this->whenLoaded('category', fn() => new CategoryResource($this->category)),
            'score_value' => isset($this->score_value) ? (int) $this->score_value : null,
            'full_name' => $this->when($this->relationLoaded('category') && $this->category, fn() => $this->full_name),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
