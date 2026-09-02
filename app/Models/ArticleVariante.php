<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleVariante extends Model
{
    protected $table = 'article_variantes';

    protected $fillable = [
        'article_id',
        'taille_id',
        'couleur_id',
        'disponible',
        'stock',
    ];

    protected $casts = [
        'disponible' => 'boolean',
        'stock' => 'integer',
    ];

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

    /**
     * Centralise la vérification d'unicité applicative (article, taille,
     * couleur) : un index unique classique ne protège pas des doublons ici
     * car MySQL/Postgres traitent deux NULL comme différents, or taille_id
     * et couleur_id sont NULL dès que le type d'article ne gère pas cet
     * attribut.
     */
    public static function existeDeja(int $articleId, ?int $tailleId, ?int $couleurId, ?int $exceptId = null): bool
    {
        return static::query()
            ->where('article_id', $articleId)
            ->where('taille_id', $tailleId)
            ->where('couleur_id', $couleurId)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();
    }
}
