<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition()
    {
        $names = [
            'Programación', 'Diseño', 'Seguridad', 'Base de Datos',
            'Frontend', 'Backend', 'DevOps', 'Testing', 'UX/UI', 'Mobile'
        ];
        
        return [
            'name' => $this->faker->unique()->randomElement($names)
        ];
    }
}
