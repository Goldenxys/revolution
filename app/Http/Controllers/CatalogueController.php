<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\CollectionCatalogue;
use App\Models\Couleur;
use App\Models\Taille;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class CatalogueController extends Controller
{
    public function catalogueJson(): JsonResponse
    {
        return response()->json($this->payload());
    }

    /**
     * Catalogue complet en une seule structure (collections actives,
     * articles visibles avec leur grille de disponibilité, référentiels
     * tailles et couleurs) : à ~40-50 articles, ça tient largement en
     * quelques dizaines de Ko, et un seul aller-retour réseau évite la
     * classe de bugs « la couleur choisie n'est plus dispo mais on ne l'a
     * su qu'après plusieurs fetchs successifs ». La disponibilité reste de
     * toute façon revérifiée côté serveur à la soumission (voir
     * StoreCommandeCatalogueRequest) : la fraîcheur de cette donnée n'a
     * donc besoin d'être qu'indicative.
     *
     * Utilisé à la fois par catalogueJson() (rafraîchissement en JS) et par
     * CommandeCatalogueController::creer() (injection initiale côté serveur,
     * pour éviter un flash de contenu vide au premier chargement).
     */
    public function payload(): array
    {
        $tailles = Taille::query()->actives()->orderBy('ordre')->get(['id', 'libelle']);
        $couleurs = Couleur::query()->actives()->orderBy('ordre')->get(['id', 'nom', 'code_hex']);

        $collections = CollectionCatalogue::query()
            ->actives()
            ->orderBy('ordre')
            ->get(['id', 'slug', 'nom', 'description', 'image', 'verset_requis', 'modeles_disponibles']);

        $articles = Article::query()
            ->visiblesPublic()
            ->with(['typeArticle:id,nom,gere_tailles,gere_couleurs', 'variantes' => fn ($q) => $q->where('disponible', true)])
            ->orderBy('ordre')
            ->get(['id', 'collection_id', 'type_article_id', 'nom', 'prix', 'photo'])
            ->map(fn (Article $article) => [
                'id' => $article->id,
                'collection_id' => $article->collection_id,
                'nom' => $article->nom,
                'prix' => $article->prix,
                'photo' => $article->photo ? Storage::disk('public')->url($article->photo) : null,
                'gere_tailles' => $article->gere_tailles,
                'gere_couleurs' => $article->gere_couleurs,
                'variantes' => $article->variantes->map(fn ($v) => [
                    'taille_id' => $v->taille_id,
                    'couleur_id' => $v->couleur_id,
                ])->values(),
            ])
            ->values();

        return [
            'collections' => $collections,
            'articles' => $articles,
            'tailles' => $tailles,
            'couleurs' => $couleurs,
            'communes' => config('revolution.communes'),
        ];
    }
}
