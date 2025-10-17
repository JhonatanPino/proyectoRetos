<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            CategorySeeder::class,
            ChallengeSeeder::class,
            AnswerSeeder::class,
            UserSeeder::class,
            UserAnswerSeeder::class,
        ]);
    }
}