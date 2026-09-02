<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Couleur extends Model
{
    protected $fillable = [
        'nom',
        'code_hex',
        'ordre',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'ordre' => 'integer',
    ];

    public function scopeActives(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
