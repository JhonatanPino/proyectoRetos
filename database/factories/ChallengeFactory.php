<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChallengeFactory extends Factory
{
    public function definition()
    {
        return [
            'category_id' => Category::factory(),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(2),
            'score_value' => $this->faker->numberBetween(10, 100),
        ];
    }
}
