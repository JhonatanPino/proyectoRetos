<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAnswer extends Model
{
    use HasFactory;

    // Campos que se pueden llenar masivamente
    protected $fillable = [
        'user_id',
        'challenge_id',
        'selected_answer_id',
        'is_correct_submission',
        'submitted_at'
    ];

    // Casting
    protected $casts = [
        'is_correct_submission' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    // RELACIONES
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }

    public function selectedAnswer()
    {
        return $this->belongsTo(Answer::class, 'selected_answer_id');
    }

    // SCOPES ÚTILES
    public function scopeCorrect($query)
    {
        return $query->where('is_correct_submission', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ACCESORES
    public function getStatusLabelAttribute()
    {
        return $this->is_correct_submission ? '✅ Correcta' : '❌ Incorrecta';
    }
}
