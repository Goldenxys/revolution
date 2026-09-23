<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\Couleur;
use App\Models\Taille;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Grille de disponibilité taille×couleur d'un article — pur affichage.
 * `disponible` est entièrement dérivée par ArticleVariante::booted() (du
 * stock pour une collection qui le gère, toujours vraie sinon) : la
 * gérante n'a plus de case à cocher ici, cet onglet n'est d'ailleurs
 * montré par ArticleResource que pour les collections au stock géré (les
 * autres, fabriquées à la demande, n'ont rien à y afficher). Embarqué via
 * resources/views/filament/forms/components/matrice-disponibilite.blade.php.
 *
 * Quand le type d'article ne gère pas les tailles et/ou pas les couleurs
 * (gere_tailles/gere_couleurs sur TypeArticle), la dimension correspondante
 * se réduit à une unique entrée « sans objet » (taille_id ou couleur_id
 * NULL).
 */
class MatriceDisponibiliteArticle extends Component
{
    public Article $article;

    /** @var array<string, bool> clé "{tailleId|x}_{couleurId|x}" => disponible */
    public array $etat = [];

    public bool $gereTailles = true;

    public bool $gereCouleurs = true;

    public function mount(Article $article): void
    {
        $this->article = $article;
        $this->gereTailles = $article->gere_tailles;
        $this->gereCouleurs = $article->gere_couleurs;
        $this->chargerEtat();
    }

    /**
     * @return Collection<int, Taille|null>
     */
    public function getTaillesProperty(): Collection
    {
        return $this->gereTailles
            ? Taille::query()->actives()->orderBy('ordre')->get()
            : collect([null]);
    }

    /**
     * @return Collection<int, Couleur|null>
     */
    public function getCouleursProperty(): Collection
    {
        return $this->gereCouleurs
            ? Couleur::query()->actives()->orderBy('ordre')->get()
            : collect([null]);
    }

    public function getEpuiseProperty(): bool
    {
        return $this->article->fresh()->estEpuise();
    }

    private function cle(?int $tailleId, ?int $couleurId): string
    {
        return ($tailleId ?? 'x').'_'.($couleurId ?? 'x');
    }

    private function chargerEtat(): void
    {
        $this->etat = [];

        foreach ($this->article->variantes as $variante) {
            $this->etat[$this->cle($variante->taille_id, $variante->couleur_id)] = $variante->disponible;
        }
    }

    public function estCoche(?int $tailleId, ?int $couleurId): bool
    {
        return $this->etat[$this->cle($tailleId, $couleurId)] ?? false;
    }

    public function render()
    {
        return view('livewire.matrice-disponibilite-article');
    }
}
