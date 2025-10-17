<?php

namespace Database\Factories;

use App\Models\Challenge;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnswerFactory extends Factory
{
    public function definition()
    {
        return [
            'challenge_id' => Challenge::factory(),
            'description' => $this->faker->sentence(6), // Respuesta más larga
            'is_correct' => $this->faker->boolean(20), // Solo 20% correctas
        ];
    }

    // Estado para respuesta CORRECTA
    public function correct()
    {
        return $this->state(fn() => ['is_correct' => true]);
    }
}
