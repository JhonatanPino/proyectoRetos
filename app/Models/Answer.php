<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;

    // Campos que se pueden llenar masivamente
    protected $fillable = [
        'challenge_id',
        'description',
        'is_correct'
    ];

    // Casting
    protected $casts = [
        'is_correct' => 'boolean',
    ];

    // RELACIONES
    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }

    // SCOPES ÚTILES
    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    public function scopeForChallenge($query, $challengeId)
    {
        return $query->where('challenge_id', $challengeId);
    }

    // ACCESOR
    public function getIsCorrectLabelAttribute()
    {
        return $this->is_correct ? '✅ Correcta' : '❌ Incorrecta';
    }

    // RELACIÓN CON USER ANSWERS
    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class, 'selected_answer_id');
    }
}