<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ChallengeResource;

class AnswerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'challenge_id' => $this->challenge_id,
            'description' => $this->description,
            'is_correct' => (bool) ($this->is_correct ?? false),
            'is_correct_label' => $this->is_correct_label ?? null,
            'challenge' => $this->whenLoaded('challenge', fn() => new ChallengeResource($this->challenge)),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
