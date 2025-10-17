<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'password',
        'score',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    // Mutator: siempre hashea la contraseña al asignarla
    protected function password(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if (! $value) {
                    return null;
                }

                // Si ya es un hash válido y no necesita rehash, devolver tal cual
                if (! Hash::needsRehash($value)) {
                    return $value;
                }

                return Hash::make($value);
            }
        );
    }

    // JWTSubject methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // SCOPES ÚTILES
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeUsers($query)
    {
        return $query->where('role', 'user');
    }

    public function scopeByScore($query, $minScore = 0)
    {
        return $query->where('score', '>=', $minScore);
    }

    // ACCESORES
    public function getTotalCompletedAttribute()
    {
        // contar retos distintos donde el usuario tiene una sumisión correcta
        return $this->userAnswers()
                    ->where('is_correct_submission', true)
                    ->pluck('challenge_id')
                    ->unique()
                    ->count();
    }

    public function getTotalScoreAttribute()
    {
        // sumar score_value de los retos que el usuario tiene correctamente respondidos
        $challengeIds = $this->userAnswers()
                             ->where('is_correct_submission', true)
                             ->pluck('challenge_id')
                             ->unique()
                             ->toArray();

        return Challenge::whereIn('id', $challengeIds)->sum('score_value');
    }

    public function getProgressAttribute()
    {
        $total = Challenge::count();
        $completed = $this->total_completed;
        return $total > 0 ? round(($completed / $total) * 100, 1) : 0;
    }

    // MÉTODOS ÚTILES
    public function completeChallenge(Challenge $challenge)
    {
        // Comprobar si ya tiene una sumisión correcta para este challenge
        $already = $this->userAnswers()
                        ->where('challenge_id', $challenge->id)
                        ->where('is_correct_submission', true)
                        ->exists();

        if ($already) {
            return $this->fresh();
        }

        // Si no existe una sumisión correcta, incrementar score directamente.
        // Nota: no se crea registro pivot aquí; si quieres guardar "completado" crea la tabla user_challenges
        $this->increment('score', $challenge->score_value);
        return $this->fresh();
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    // RELACIÓN CON UserAnswer
    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }

    // MÉTODO CLAVE: Enviar respuesta
    public function submitAnswer(Challenge $challenge, Answer $selectedAnswer)
    {
    // Prevenir duplicados
    if ($this->userAnswers()->where('challenge_id', $challenge->id)->exists()) {
        return ['success' => false, 'message' => 'Ya respondiste este reto'];
    }

    $isCorrect = $selectedAnswer->is_correct;
    
    $userAnswer = UserAnswer::create([
        'user_id' => $this->id,
        'challenge_id' => $challenge->id,
        'selected_answer_id' => $selectedAnswer->id,
        'is_correct_submission' => $isCorrect,
        'submitted_at' => now()
    ]);

    // SUMAR PUNTOS si es correcta
    if ($isCorrect) {
        $this->increment('score', $challenge->score_value);
    }

    return [
        'success' => true, 
        'is_correct' => $isCorrect,
        'score_earned' => $isCorrect ? $challenge->score_value : 0,
        'user_answer' => $userAnswer
    ];
}
}



