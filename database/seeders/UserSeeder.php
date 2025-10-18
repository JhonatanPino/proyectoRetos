<?php

namespace Database\Seeders;

use App\Models\Challenge;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Admin principal
        $admin = User::create([
            'username' => 'admin',
            'password' => bcrypt('admin123'),
            'role' => 'admin'
        ]);

+        // Crear 20 usuarios forzando el role 'user' (evita que la factory genere admins)
+        User::factory()->count(20)->state(['role' => 'user'])->create();

        // Completar algunos challenges aleatorios
        User::all()->each(function ($user) {
            $challenges = Challenge::inRandomOrder()->take(
                rand(0, 10)
            )->get();
            
            foreach ($challenges as $challenge) {
                $user->completeChallenge($challenge);
            }
        });
    }
}