<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'seuil_alerte',
    ];

    protected $casts = [
        'disponible' => 'boolean',
        'stock' => 'integer',
        'seuil_alerte' => 'integer',
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
     * Pour une collection au stock géré, `disponible` n'est plus une case à
     * cocher : elle est entièrement dérivée de la présence de stock, à
     * chaque sauvegarde, quel que soit le point d'entrée (édition en ligne
     * dans « Mon stock », entrée de stock, tinker...). La gérante ne peut
     * plus la forcer — seul le stock enregistré décide. Les collections
     * fabriquées à la demande (ex. My verse, `gere_stock = false`) gardent
     * le contrôle manuel d'origine : il n'y a pas de stock dont dériver quoi
     * que ce soit.
     */
    protected static function booted(): void
    {
        static::saving(function (ArticleVariante $variante) {
            if ($variante->article?->gere_stock !== false) {
                $variante->disponible = $variante->stock !== null && $variante->stock > 0;
            }
        });
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

    /**
     * Décrémente le stock suivi d'une quantité vendue. Ne fait rien si le
     * stock n'est pas suivi (NULL — V2 §3.4 : NULL veut dire « non suivi »,
     * on se rabat alors sur le booléen `disponible`). Ne descend jamais en
     * dessous de zéro : le système avertit (journal, rattaché à la
     * commande à l'origine de la vente), il n'interdit jamais une vente
     * (V2 §8.1 — « Il n'interdit jamais une vente. Il avertit. »).
     */
    public function decrementerStock(int $quantite, Commande $commande): void
    {
        if ($this->stock === null) {
            return;
        }

        $nouveauStock = $this->stock - $quantite;

        if ($nouveauStock < 0) {
            CommandeJournal::consigner($commande, 'stock_negatif_evite', [
                'article_variante_id' => $this->id,
                'stock_avant' => $this->stock,
                'quantite_demandee' => $quantite,
            ]);
        }

        $this->update(['stock' => max(0, $nouveauStock)]);
    }

    /**
     * Restitue un stock suivi (annulation d'une commande validée).
     */
    public function restituerStock(int $quantite): void
    {
        if ($this->stock === null) {
            return;
        }

        $this->increment('stock', $quantite);
    }

    public function scopeStockFaible(Builder $query): Builder
    {
        return $query->whereNotNull('stock')
            ->whereColumn('stock', '<=', 'seuil_alerte');
    }

    public function scopeRupture(Builder $query): Builder
    {
        return $query->where('stock', 0);
    }

    /**
     * Vrai si la variante peut être vendue maintenant : en vente ET (stock
     * suivi > 0, OU stock non suivi — NULL, seulement pertinent pour une
     * collection fabriquée à la demande où `disponible` reste manuel — OU
     * la collection ne gère pas le stock, ex. My verse : une rupture n'a
     * alors aucun sens). Pour une collection au stock géré, `disponible`
     * est déjà dérivé du stock à la sauvegarde (voir booted() ci-dessus),
     * donc en pratique cette méthode s'y réduit à `disponible`. Distinct de
     * `disponible` seul : sert à bloquer la sélection d'une variante en
     * rupture dans le compositeur de commande, là où `disponible` gate déjà
     * la visibilité du catalogue public.
     */
    public function estAchetable(): bool
    {
        if (! $this->disponible) {
            return false;
        }

        return $this->stock === null || $this->stock > 0 || $this->article?->gere_stock === false;
    }

    public function scopeAchetable(Builder $query): Builder
    {
        return $query->where('disponible', true)
            ->where(fn (Builder $q) => $q->whereNull('stock')
                ->orWhere('stock', '>', 0)
                ->orWhereHas('article.collection', fn (Builder $qc) => $qc->where('gere_stock', false)));
    }
}
