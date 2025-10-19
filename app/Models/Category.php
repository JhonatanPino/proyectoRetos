<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    // Campos que se pueden llenar masivamente
    protected $fillable = [
        'name'
    ];

    // Relación: Una Category tiene muchos Challenges
    public function challenges()
    {
        return $this->hasMany(Challenge::class);
    }

    // Scope útil: Buscar por nombre
    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    // Accesor útil: Nombre con conteo de challenges
    public function getWithCountAttribute()
    {
        return $this->name . ' (' . $this->challenges()->count() . ' retos)';
    }

    // Mutator: Convertir a uppercase automáticamente
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucfirst(strtolower($value));
    }

}
