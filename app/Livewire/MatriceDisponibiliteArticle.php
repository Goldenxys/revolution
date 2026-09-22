<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\ArticleVariante;
use App\Models\Couleur;
use App\Models\Taille;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Grille de disponibilité taille×couleur d'un article, avec bascule
 * immédiate en base à chaque clic (comme un simple interrupteur) et des
 * actions groupées par ligne/colonne pour ne pas avoir à cocher 28 cases
 * une par une. Embarqué dans l'onglet « Disponibilité » d'ArticleResource
 * via resources/views/filament/forms/components/matrice-disponibilite.blade.php.
 *
 * Quand le type d'article ne gère pas les tailles et/ou pas les couleurs
 * (gere_tailles/gere_couleurs sur TypeArticle), la dimension correspondante
 * se réduit à une unique entrée « sans objet » (taille_id ou couleur_id
 * NULL) — la grille devient une colonne, une ligne, ou un simple
 * interrupteur unique selon les cas.
 */
class MatriceDisponibiliteArticle extends Component
{
    public Article $article;

    /** @var array<string, bool> clé "{tailleId|x}_{couleurId|x}" => disponible */
    public array $etat = [];

    public bool $gereTailles = true;

    public bool $gereCouleurs = true;

    public bool $gereStock = true;

    public function mount(Article $article): void
    {
        $this->article = $article;
        $this->gereTailles = $article->gere_tailles;
        $this->gereCouleurs = $article->gere_couleurs;
        $this->gereStock = $article->gere_stock;
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

    /**
     * Pour une collection au stock géré, cette grille n'est plus qu'un
     * affichage : `disponible` est désormais dérivé du stock enregistré
     * dans « Mon stock » (ArticleVariante::booted()), la gérante ne peut
     * plus le cocher à la main ici. Renvoie true (et prévient) si l'action
     * appelante doit s'arrêter là.
     */
    private function bloqueSiStockGere(): bool
    {
        if (! $this->gereStock) {
            return false;
        }

        Notification::make()
            ->title('Disponibilité calculée depuis le stock')
            ->body('Cette collection gère son stock : la disponibilité suit automatiquement les quantités enregistrées dans « Mon stock », elle ne se coche plus ici.')
            ->warning()
            ->send();

        return true;
    }

    private function basculer(?int $tailleId, ?int $couleurId, bool $disponible): void
    {
        // Atteint uniquement pour une collection fabriquée à la demande
        // (bloqueSiStockGere() arrête tout le reste plus haut) : le stock
        // ne dérive rien ici, la valeur demandée est donc bien celle
        // persistée — pas besoin de recharger depuis la base.
        ArticleVariante::query()->updateOrCreate(
            ['article_id' => $this->article->id, 'taille_id' => $tailleId, 'couleur_id' => $couleurId],
            ['disponible' => $disponible]
        );

        $this->etat[$this->cle($tailleId, $couleurId)] = $disponible;
    }

    public function toggleCase(?int $tailleId, ?int $couleurId): void
    {
        if ($this->bloqueSiStockGere()) {
            return;
        }

        $this->basculer($tailleId, $couleurId, ! $this->estCoche($tailleId, $couleurId));
    }

    public function cocherLigne(?int $couleurId): void
    {
        if ($this->bloqueSiStockGere()) {
            return;
        }

        DB::transaction(function () use ($couleurId) {
            foreach ($this->tailles as $taille) {
                $this->basculer($taille?->id, $couleurId, true);
            }
        });
    }

    public function decocherLigne(?int $couleurId): void
    {
        if ($this->bloqueSiStockGere()) {
            return;
        }

        DB::transaction(function () use ($couleurId) {
            foreach ($this->tailles as $taille) {
                $this->basculer($taille?->id, $couleurId, false);
            }
        });
    }

    public function cocherColonne(?int $tailleId): void
    {
        if ($this->bloqueSiStockGere()) {
            return;
        }

        DB::transaction(function () use ($tailleId) {
            foreach ($this->couleurs as $couleur) {
                $this->basculer($tailleId, $couleur?->id, true);
            }
        });
    }

    public function decocherColonne(?int $tailleId): void
    {
        if ($this->bloqueSiStockGere()) {
            return;
        }

        DB::transaction(function () use ($tailleId) {
            foreach ($this->couleurs as $couleur) {
                $this->basculer($tailleId, $couleur?->id, false);
            }
        });
    }

    public function toutCocher(): void
    {
        if ($this->bloqueSiStockGere()) {
            return;
        }

        DB::transaction(function () {
            foreach ($this->tailles as $taille) {
                foreach ($this->couleurs as $couleur) {
                    $this->basculer($taille?->id, $couleur?->id, true);
                }
            }
        });
    }

    public function toutDecocher(): void
    {
        if ($this->bloqueSiStockGere()) {
            return;
        }

        DB::transaction(function () {
            foreach ($this->tailles as $taille) {
                foreach ($this->couleurs as $couleur) {
                    $this->basculer($taille?->id, $couleur?->id, false);
                }
            }
        });
    }

    public function render()
    {
        return view('livewire.matrice-disponibilite-article');
    }
}
