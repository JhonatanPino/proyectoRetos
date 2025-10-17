<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id(); // id (PK, INT, AUTO_INCREMENT)
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade'); // FK a Categories
            $table->string('name'); // VARCHAR
            $table->string('description', 250)->unique(); // VARCHAR(250)
            $table->integer('score_value'); // INT - Puntos que otorga el reto
            $table->timestamps();
            
            // Índice para mejorar consultas por categoría
            $table->index('category_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('challenges');
    }
};
