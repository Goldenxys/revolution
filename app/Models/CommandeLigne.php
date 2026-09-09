<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandeLigne extends Model
{
    protected $fillable = [
        'commande_id',
        'article_id',
        'taille_id',
        'couleur_id',
        'article_nom',
        'taille_libelle',
        'couleur_nom',
        'quantite',
        'prix_unitaire',
        'verset',
        'modele',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix_unitaire' => 'integer',
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function taille(): BelongsTo
    {
        return $this->belongsTo(Taille::class);
    }

    public function couleur(): BelongsTo
    {
        return $this->belongsTo(Couleur::class);
    }

    public function getSousTotalAttribute(): int
    {
        return $this->prix_unitaire * $this->quantite;
    }
}
