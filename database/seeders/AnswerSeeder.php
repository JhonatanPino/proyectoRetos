<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Challenge;
use Illuminate\Database\Seeder;

class AnswerSeeder extends Seeder
{
    public function run()
    {
        Challenge::all()->each(function ($challenge) {
            // 4 respuestas por challenge
            Answer::factory()->count(4)->create([
                'challenge_id' => $challenge->id
            ])->each(function ($answer, $index) {
                // SOLO LA PRIMERA es correcta
                if ($index === 0) {
                    $answer->update(['is_correct' => true]);
                }
            });
        });
    }
}
