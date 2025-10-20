<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ChallengeResource;

class AnswerResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $request->user();
        $showCorrect = $user && $user->role === 'admin';

        return [
            'id' => $this->id,
            'challenge_id' => $this->challenge_id,
            'description' => $this->description,
            'is_correct' => $this->when($showCorrect, (bool) $this->is_correct),
            'is_correct_label' => $this->when($showCorrect, $this->is_correct_label ?? null),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
