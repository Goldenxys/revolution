<?php

namespace Tests\Feature\V2;

use App\Models\Article;
use App\Models\ArticleVariante;
use App\Models\CollectionCatalogue;
use App\Models\Couleur;
use App\Models\Taille;
use App\Models\TypeArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le blocage réel de la vente d'une variante en rupture (restructuration
 * stock, ComposerDemande) repose sur ArticleVariante::estAchetable()/
 * scopeAchetable() et Article::taillesAchetables()/couleursAchetables() —
 * c'est le vrai point de contrôle, les Select de ComposerDemande n'en sont
 * qu'un affichage.
 */
class DisponibiliteAchatTest extends TestCase
{
    use RefreshDatabase;

    private function article(): Article
    {
        return Article::create([
            'collection_id' => CollectionCatalogue::create(['nom' => 'C', 'slug' => 'c'])->id,
            'type_article_id' => TypeArticle::create(['nom' => 'T', 'slug' => 't', 'gere_tailles' => true, 'gere_couleurs' => true])->id,
            'nom' => 'Art', 'slug' => 'art', 'prix' => 7000,
        ]);
    }

    /**
     * Une collection fabriquée à la demande (ex. My verse) : la gérante
     * compose librement, sans jamais être bloquée par une rupture qui n'a
     * pas de sens pour un article sans pièces en réserve.
     */
    private function articleSurDemande(): Article
    {
        return Article::create([
            'collection_id' => CollectionCatalogue::create(['nom' => 'My verse', 'slug' => 'my_verse', 'gere_stock' => false])->id,
            'type_article_id' => TypeArticle::create(['nom' => 'T', 'slug' => 't2', 'gere_tailles' => true, 'gere_couleurs' => true])->id,
            'nom' => 'Tee-shirt My verse', 'slug' => 'tee-my-verse', 'prix' => 7000,
        ]);
    }

    public function test_une_variante_en_rupture_nest_pas_achetable(): void
    {
        $variante = ArticleVariante::create([
            'article_id' => $this->article()->id,
            'taille_id' => Taille::create(['libelle' => 'XL'])->id,
            'couleur_id' => Couleur::create(['nom' => 'Noir'])->id,
            'disponible' => true,
            'stock' => 0,
        ]);

        $this->assertFalse($variante->estAchetable());
    }

    public function test_une_variante_indisponible_nest_pas_achetable_meme_avec_du_stock(): void
    {
        $variante = ArticleVariante::create([
            'article_id' => $this->article()->id,
            'taille_id' => Taille::create(['libelle' => 'XL'])->id,
            'couleur_id' => Couleur::create(['nom' => 'Noir'])->id,
            'disponible' => false,
            'stock' => 10,
        ]);

        $this->assertFalse($variante->estAchetable());
    }

    public function test_une_variante_au_stock_non_suivi_reste_achetable(): void
    {
        $variante = ArticleVariante::create([
            'article_id' => $this->article()->id,
            'taille_id' => Taille::create(['libelle' => 'XL'])->id,
            'couleur_id' => Couleur::create(['nom' => 'Noir'])->id,
            'disponible' => true,
            'stock' => null,
        ]);

        $this->assertTrue($variante->estAchetable());
    }

    public function test_une_variante_avec_du_stock_est_achetable(): void
    {
        $variante = ArticleVariante::create([
            'article_id' => $this->article()->id,
            'taille_id' => Taille::create(['libelle' => 'XL'])->id,
            'couleur_id' => Couleur::create(['nom' => 'Noir'])->id,
            'disponible' => true,
            'stock' => 3,
        ]);

        $this->assertTrue($variante->estAchetable());
    }

    public function test_taillesAchetables_exclut_la_taille_en_rupture_pour_une_couleur_donnee(): void
    {
        $article = $this->article();
        $noir = Couleur::create(['nom' => 'Noir']);
        $xl = Taille::create(['libelle' => 'XL', 'ordre' => 1]);
        $m = Taille::create(['libelle' => 'M', 'ordre' => 2]);

        // XL noir : rupture — ne doit pas apparaître.
        ArticleVariante::create(['article_id' => $article->id, 'taille_id' => $xl->id, 'couleur_id' => $noir->id, 'disponible' => true, 'stock' => 0]);
        // M noir : en stock — doit apparaître.
        ArticleVariante::create(['article_id' => $article->id, 'taille_id' => $m->id, 'couleur_id' => $noir->id, 'disponible' => true, 'stock' => 4]);

        $tailles = $article->taillesAchetables($noir->id)->pluck('libelle')->all();

        $this->assertSame(['M'], $tailles);
    }

    public function test_couleursAchetables_exclut_la_couleur_indisponible_pour_une_taille_donnee(): void
    {
        $article = $this->article();
        $xl = Taille::create(['libelle' => 'XL']);
        $noir = Couleur::create(['nom' => 'Noir', 'ordre' => 1]);
        $blanc = Couleur::create(['nom' => 'Blanc', 'ordre' => 2]);

        // XL noir : marquée indisponible — ne doit pas apparaître.
        ArticleVariante::create(['article_id' => $article->id, 'taille_id' => $xl->id, 'couleur_id' => $noir->id, 'disponible' => false, 'stock' => 10]);
        // XL blanc : en vente et en stock — doit apparaître.
        ArticleVariante::create(['article_id' => $article->id, 'taille_id' => $xl->id, 'couleur_id' => $blanc->id, 'disponible' => true, 'stock' => 2]);

        $couleurs = $article->couleursAchetables($xl->id)->pluck('nom')->all();

        $this->assertSame(['Blanc'], $couleurs);
    }

    public function test_taillesAchetables_inclut_une_taille_au_stock_non_suivi(): void
    {
        $article = $this->article();
        $couleur = Couleur::create(['nom' => 'Noir']);
        $xl = Taille::create(['libelle' => 'XL']);

        ArticleVariante::create(['article_id' => $article->id, 'taille_id' => $xl->id, 'couleur_id' => $couleur->id, 'disponible' => true, 'stock' => null]);

        $this->assertSame(['XL'], $article->taillesAchetables($couleur->id)->pluck('libelle')->all());
    }

    public function test_une_variante_en_rupture_dune_collection_sur_demande_reste_achetable(): void
    {
        $variante = ArticleVariante::create([
            'article_id' => $this->articleSurDemande()->id,
            'taille_id' => Taille::create(['libelle' => 'M'])->id,
            'couleur_id' => Couleur::create(['nom' => 'Blanc'])->id,
            'disponible' => true,
            'stock' => 0, // saisi par erreur, ne devrait jamais bloquer My verse
        ]);

        $this->assertTrue($variante->estAchetable());
    }

    public function test_une_variante_indisponible_dune_collection_sur_demande_reste_bloquee(): void
    {
        // gere_stock=false lève le blocage lié au STOCK, pas celui lié à
        // `disponible` — la gérante garde la main pour retirer un modèle
        // de la vente.
        $variante = ArticleVariante::create([
            'article_id' => $this->articleSurDemande()->id,
            'taille_id' => Taille::create(['libelle' => 'M'])->id,
            'couleur_id' => Couleur::create(['nom' => 'Blanc'])->id,
            'disponible' => false,
            'stock' => 0,
        ]);

        $this->assertFalse($variante->estAchetable());
    }

    public function test_taillesAchetables_dune_collection_sur_demande_ignore_le_stock(): void
    {
        $article = $this->articleSurDemande();
        $couleur = Couleur::create(['nom' => 'Blanc']);
        $xl = Taille::create(['libelle' => 'XL', 'ordre' => 1]);
        $m = Taille::create(['libelle' => 'M', 'ordre' => 2]);

        // Les deux ont un stock à zéro (saisi par erreur) : les deux
        // doivent quand même apparaître, My verse est fabriqué à la demande.
        ArticleVariante::create(['article_id' => $article->id, 'taille_id' => $xl->id, 'couleur_id' => $couleur->id, 'disponible' => true, 'stock' => 0]);
        ArticleVariante::create(['article_id' => $article->id, 'taille_id' => $m->id, 'couleur_id' => $couleur->id, 'disponible' => true, 'stock' => 0]);

        $tailles = $article->taillesAchetables($couleur->id)->pluck('libelle')->all();

        $this->assertSame(['XL', 'M'], $tailles);
    }
}
