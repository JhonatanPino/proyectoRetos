<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserAnswerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'challenge_id' => $this->challenge_id,
            'selected_answer_id' => $this->selected_answer_id,
            'is_correct_submission' => (bool) ($this->is_correct_submission ?? false),
            'user' => $this->whenLoaded('user', fn() => new UserResource($this->user)),
            'challenge' => $this->whenLoaded('challenge', fn() => new ChallengeResource($this->challenge)),
            'selected_answer' => $this->whenLoaded('selectedAnswer', fn() => new AnswerResource($this->selectedAnswer)),
            'submitted_at' => $this->submitted_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
