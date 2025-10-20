<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'score_value'
    ];

    protected $casts = [
        'score_value' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeWithScore($query, $minScore)
    {
        return $query->where('score_value', '>=', $minScore);
    }

    public function getFullNameAttribute()
    {
        return ($this->category?->name ?? 'Sin categoría') . ' - ' . $this->name;
    }

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

    public function userAnswers()
    {
        return $this->hasMany(UserAnswer::class);
    }

}
