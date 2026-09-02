<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Table `collections`. Nommé CollectionCatalogue (et non `Collection`) pour
 * ne jamais entrer en collision avec Illuminate\Support\Collection, déjà
 * importé dans plusieurs fichiers de l'application (ex. App\Mail\RecapJournalier).
 */
class CollectionCatalogue extends Model
{
    protected $table = 'collections';

    protected $fillable = [
        'nom',
        'slug',
        'description',
        'image',
        'verset_requis',
        'modeles_disponibles',
        'ordre',
        'active',
    ];

    protected $casts = [
        'verset_requis' => 'boolean',
        'modeles_disponibles' => 'array',
        'active' => 'boolean',
        'ordre' => 'integer',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'collection_id');
    }

    public function scopeActives(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Garde-fou admin : une collection contenant des articles ne peut pas
     * être supprimée, seulement désactivée.
     */
    public function estSupprimable(): bool
    {
        return ! $this->articles()->exists();
    }
}
