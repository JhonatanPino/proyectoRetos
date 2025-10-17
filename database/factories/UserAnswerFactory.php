<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Challenge;
use App\Models\Answer;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserAnswerFactory extends Factory
{
    public function definition()
    {
        // intentar obtener un challenge existente, si no crear uno
        $challenge = Challenge::inRandomOrder()->first() ?? Challenge::factory()->create();

        // asegurarnos de que el challenge tenga respuestas (si no, crearlas)
        $allAnswers = $challenge->answers()->get();
        if ($allAnswers->isEmpty()) {
            // crear 4 respuestas, una correcta
            $created = Answer::factory()->count(4)->state(function () use ($challenge) {
                return ['challenge_id' => $challenge->id];
            })->create();

            // marcar aleatoriamente una como correcta
            $created->random()->update(['is_correct' => true]);

            $allAnswers = $challenge->answers()->get();
        }

        $selectedAnswerId = $this->faker->randomElement($allAnswers->pluck('id')->toArray());
        $isCorrect = (bool) Answer::find($selectedAnswerId)->is_correct;

        return [
            'user_id' => User::factory(),
            'challenge_id' => $challenge->id,
            'selected_answer_id' => $selectedAnswerId,
            'is_correct_submission' => $isCorrect,
            'submitted_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}