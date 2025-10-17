<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            'Programación',
            'Diseño Web',
            'Seguridad Informática',
            'Base de Datos',
            'Frontend Development',
            'Backend Development',
            'DevOps',
            'Testing QA',
            'UX/UI Design',
            'Desarrollo Mobile'
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}