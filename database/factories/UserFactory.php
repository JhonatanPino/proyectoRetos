<?php

namespace Database\Factories;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition()
    {
        return [
            'username' => fake()->unique()->userName(),
            'password' => Hash::make('password'),
            'score' => 0,
            'role' => fake()->randomElement(['admin', 'user']),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin()
    {
        return $this->state(fn() => ['role' => 'admin']);
    }

}
