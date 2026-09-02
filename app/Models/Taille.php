<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Taille extends Model
{
    protected $fillable = [
        'libelle',
        'ordre',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'ordre' => 'integer',
    ];

    public function variantes(): HasMany
    {
        return $this->hasMany(ArticleVariante::class);
    }

    public function scopeActives(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Garde-fou admin : une taille référencée par une variante d'article ne
     * peut pas être supprimée (contrainte restrictOnDelete en base), elle
     * est seulement désactivable.
     */
    public function estSupprimable(): bool
    {
        return ! $this->variantes()->exists();
    }
}
