<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    use HasFactory;

    // Campos que se pueden llenar masivamente
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'score_value'
    ];

    // Casting para score_value como entero
    protected $casts = [
        'score_value' => 'integer',
    ];

    // Relación: Cada Challenge pertenece a una Category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Scope útil: Challenges por categoría
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Scope útil: Challenges con cierto puntaje mínimo
    public function scopeWithScore($query, $minScore)
    {
        return $query->where('score_value', '>=', $minScore);
    }

    // Accesor útil: Nombre completo con categoría
    public function getFullNameAttribute()
    {
        return ($this->category?->name ?? 'Sin categoría') . ' - ' . $this->name;
    }

    // RELACIONES CON ANSWERS
    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function correctAnswer()
    {
        return $this->hasOne(Answer::class)->where('is_correct', true);
    }

    public function getCorrectAnswerIdAttribute()
    {
        return $this->correctAnswer?->id;
    }

    // Relación inversa: un Challenge tiene muchas UserAnswers (sumisiones)
    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }

}
