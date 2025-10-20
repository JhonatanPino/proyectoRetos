<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = [
        'challenge_id',
        'description',
        'is_correct'
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }

    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    public function scopeForChallenge($query, $challengeId)
    {
        return $query->where('challenge_id', $challengeId);
    }

    public function getIsCorrectLabelAttribute()
    {
        return $this->is_correct ? '✅ Correcta' : '❌ Incorrecta';
    }

    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class, 'selected_answer_id');
    }
}