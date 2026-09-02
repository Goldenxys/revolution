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

    public function getGereTaillesAttribute(): bool
    {
        return (bool) $this->typeArticle?->gere_tailles;
    }

    public function getGereCouleursAttribute(): bool
    {
        return (bool) $this->typeArticle?->gere_couleurs;
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
