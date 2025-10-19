<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id(); 
            $table->foreignId('challenge_id')->constrained('challenges')->onDelete('cascade');
            $table->string('description', 250)->unique();
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
            
            // Índices para performance
            $table->index(['challenge_id', 'is_correct']);
            $table->unique(['challenge_id', 'description']); // Evita respuestas duplicadas
        });
    }

    public function down()
    {
        Schema::dropIfExists('answers');
    }
};


