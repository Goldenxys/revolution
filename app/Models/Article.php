<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'collection_id',
        'type_article_id',
        'nom',
        'slug',
        'prix',
        'description',
        'photo',
        'ordre',
        'active',
    ];

    protected $casts = [
        'prix' => 'integer',
        'active' => 'boolean',
        'ordre' => 'integer',
    ];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(CollectionCatalogue::class, 'collection_id');
    }

    public function typeArticle(): BelongsTo
    {
        return $this->belongsTo(TypeArticle::class);
    }

    public function variantes(): HasMany
    {
        return $this->hasMany(ArticleVariante::class);
    }

    /**
     * Dès qu'un article rejoint une collection au stock géré, toutes ses
     * combinaisons taille×couleur existent immédiatement dans « Mon stock »
     * — plus besoin de les créer au clic dans la grille « Disponibilité »,
     * qui n'a plus vocation qu'à afficher l'état (dérivé du stock, voir
     * ArticleVariante::booted()). Les collections fabriquées à la demande
     * (My verse) gardent leur fonctionnement manuel d'origine.
     */
    protected static function booted(): void
    {
        static::created(function (Article $article) {
            if ($article->gere_stock) {
                $article->genererVariantesInitiales();
            }
        });
    }

    /**
     * Crée toutes les variantes taille×couleur possibles pour cet article,
     * sans stock (donc non disponibles tant que rien n'est enregistré).
     * Idempotente (firstOrCreate) : ne duplique jamais une combinaison déjà
     * présente.
     */
    public function genererVariantesInitiales(): void
    {
        $tailles = $this->gere_tailles ? Taille::query()->actives()->pluck('id') : collect([null]);
        $couleurs = $this->gere_couleurs ? Couleur::query()->actives()->pluck('id') : collect([null]);

        foreach ($tailles as $tailleId) {
            foreach ($couleurs as $couleurId) {
                $this->variantes()->firstOrCreate([
                    'taille_id' => $tailleId,
                    'couleur_id' => $couleurId,
                ]);
            }
        }
    }

    public function getGereTaillesAttribute(): bool
    {
        return (bool) $this->typeArticle?->gere_tailles;
    }

    public function getGereCouleursAttribute(): bool
    {
        return (bool) $this->typeArticle?->gere_couleurs;
    }

    /**
     * Faux pour une collection fabriquée à la demande (ex. My verse) : la
     * gérante compose taille/couleur librement au compositeur, sans jamais
     * être bloquée par une rupture qui n'a pas de sens pour un article sans
     * pièces en réserve. Vrai par défaut (collection absente ou sans
     * préférence explicite) pour ne rien changer au comportement des
     * collections stockées.
     */
    public function getGereStockAttribute(): bool
    {
        return $this->collection?->gere_stock ?? true;
    }

    public function estEpuise(): bool
    {
        return ! $this->variantes()->where('disponible', true)->exists();
    }

    /**
     * @return array{disponibles: int, total: int}
     */
    public function ratioDisponibilite(): array
    {
        return [
            'disponibles' => $this->variantes()->where('disponible', true)->count(),
            'total' => $this->variantes()->count(),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Taille>
     */
    public function taillesDisponibles(?int $couleurId = null): \Illuminate\Support\Collection
    {
        return Taille::query()
            ->whereIn('id', $this->variantes()
                ->where('disponible', true)
                ->when($couleurId, fn ($query) => $query->where('couleur_id', $couleurId))
                ->pluck('taille_id')
                ->filter())
            ->orderBy('ordre')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Couleur>
     */
    public function couleursDisponibles(?int $tailleId = null): \Illuminate\Support\Collection
    {
        return Couleur::query()
            ->whereIn('id', $this->variantes()
                ->where('disponible', true)
                ->when($tailleId, fn ($query) => $query->where('taille_id', $tailleId))
                ->pluck('couleur_id')
                ->filter())
            ->orderBy('ordre')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Taille>
     */
    public function taillesAchetables(?int $couleurId = null): \Illuminate\Support\Collection
    {
        return Taille::query()
            ->whereIn('id', $this->variantes()
                ->achetable()
                ->when($couleurId, fn ($query) => $query->where('couleur_id', $couleurId))
                ->pluck('taille_id')
                ->filter())
            ->orderBy('ordre')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Couleur>
     */
    public function couleursAchetables(?int $tailleId = null): \Illuminate\Support\Collection
    {
        return Couleur::query()
            ->whereIn('id', $this->variantes()
                ->achetable()
                ->when($tailleId, fn ($query) => $query->where('taille_id', $tailleId))
                ->pluck('couleur_id')
                ->filter())
            ->orderBy('ordre')
            ->get();
    }

    /**
     * Un article actif avec au moins une variante disponible : c'est la
     * seule condition de visibilité publique, un article épuisé disparaît
     * de lui-même sans que la gérante ait à le désactiver.
     */
    public function scopeVisiblesPublic(Builder $query): Builder
    {
        return $query->where('active', true)
            ->whereHas('variantes', fn ($q) => $q->where('disponible', true));
    }
}
