<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use App\Models\User;
use App\Models\UserAnswer;
use Illuminate\Database\Seeder;
use App\Models\Challenge;

class UserAnswerSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        User::all()->each(function ($user) use ($faker) {
            $challenges = Challenge::inRandomOrder()->take(rand(3, 8))->get();

            foreach ($challenges as $challenge) {
                $answers = $challenge->answers;
                if ($answers->isEmpty()) continue;

                $selectedAnswer = $answers->random();

                UserAnswer::create([
                    'user_id' => $user->id,
                    'challenge_id' => $challenge->id,
                    'selected_answer_id' => $selectedAnswer->id,
                    'is_correct_submission' => $selectedAnswer->is_correct,
                    'submitted_at' => $faker->dateTimeBetween('-20 days', 'now'),
                ]);
            }
        });
    }
}