<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_answers', function (Blueprint $table) {
            $table->id(); // id (PK, INT, AUTO_INCREMENT)
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // FK Users
            $table->foreignId('challenge_id')->constrained('challenges')->onDelete('cascade'); // FK Challenges
            $table->foreignId('selected_answer_id')->constrained('answers')->onDelete('cascade'); // FK Answers
            $table->boolean('is_correct_submission')->default(false); // BOOLEAN
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
            
            // CRUCIAL: Un usuario SOLO responde un challenge UNA vez
            $table->unique(['user_id', 'challenge_id']);
            $table->index(['user_id', 'is_correct_submission']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_answers');
    }
};
