<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Challenge;
use Illuminate\Database\Seeder;

class ChallengeSeeder extends Seeder
{
    public function run()
    {
        // Crear 20 challenges distribuidos en categorías
        Category::all()->each(function ($category) {
            Challenge::factory()
                ->count(5)
                ->create([
                    'category_id' => $category->id
                ]);
        });
    }
}
