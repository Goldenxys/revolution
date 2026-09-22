<?php

namespace Tests\Feature\Catalogue;

use App\Livewire\MatriceDisponibiliteArticle;
use App\Models\Article;
use App\Models\ArticleVariante;
use App\Models\CollectionCatalogue;
use App\Models\Couleur;
use App\Models\Taille;
use App\Models\TypeArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Depuis la restructuration stock/disponibilité, cette grille ne pilote
 * plus `disponible` à la main que pour les collections fabriquées à la
 * demande (`gere_stock = false`, ex. My verse) — voir tests
 * *_sur_demande_*. Pour une collection au stock géré (le défaut), la
 * grille est un simple affichage : toute tentative de bascule est bloquée
 * (voir tests *_stock_gere_*), `disponible` étant dérivé du stock dans
 * « Mon stock » (ArticleVariante::booted()).
 */
class MatriceDisponibiliteArticleTest extends TestCase
{
    use RefreshDatabase;

    private function creerArticle(bool $gereTailles = true, bool $gereCouleurs = true, bool $gereStock = false): Article
    {
        $collection = CollectionCatalogue::create(['nom' => 'Test', 'slug' => 'test-'.uniqid(), 'gere_stock' => $gereStock]);
        $type = TypeArticle::create([
            'nom' => 'Type test',
            'slug' => 'type-test-'.uniqid(),
            'gere_tailles' => $gereTailles,
            'gere_couleurs' => $gereCouleurs,
        ]);

        return Article::create([
            'collection_id' => $collection->id,
            'type_article_id' => $type->id,
            'nom' => 'Article test',
            'slug' => 'article-test-'.uniqid(),
            'prix' => 5000,
        ]);
    }

    public function test_toggle_case_bascule_une_variante_en_base_immediatement(): void
    {
        $gerante = User::factory()->create();
        $article = $this->creerArticle();
        $taille = Taille::create(['libelle' => 'M']);
        $couleur = Couleur::create(['nom' => 'Blanc']);

        Livewire::actingAs($gerante)
            ->test(MatriceDisponibiliteArticle::class, ['article' => $article])
            ->call('toggleCase', $taille->id, $couleur->id);

        $this->assertDatabaseHas('article_variantes', [
            'article_id' => $article->id,
            'taille_id' => $taille->id,
            'couleur_id' => $couleur->id,
            'disponible' => true,
        ]);
    }

    public function test_cocher_ligne_coche_toutes_les_tailles_dune_couleur(): void
    {
        $gerante = User::factory()->create();
        $article = $this->creerArticle();
        $tailles = collect(['M', 'L', 'XL'])->map(fn ($libelle) => Taille::create(['libelle' => $libelle]));
        $couleur = Couleur::create(['nom' => 'Noir']);

        Livewire::actingAs($gerante)
            ->test(MatriceDisponibiliteArticle::class, ['article' => $article])
            ->call('cocherLigne', $couleur->id);

        foreach ($tailles as $taille) {
            $this->assertDatabaseHas('article_variantes', [
                'article_id' => $article->id,
                'taille_id' => $taille->id,
                'couleur_id' => $couleur->id,
                'disponible' => true,
            ]);
        }
    }

    public function test_decocher_colonne_decoche_toutes_les_couleurs_dune_taille(): void
    {
        $gerante = User::factory()->create();
        $article = $this->creerArticle();
        $taille = Taille::create(['libelle' => 'XL']);
        $couleurs = collect(['Blanc', 'Noir'])->map(fn ($nom) => Couleur::create(['nom' => $nom]));

        foreach ($couleurs as $couleur) {
            ArticleVariante::create([
                'article_id' => $article->id,
                'taille_id' => $taille->id,
                'couleur_id' => $couleur->id,
                'disponible' => true,
            ]);
        }

        Livewire::actingAs($gerante)
            ->test(MatriceDisponibiliteArticle::class, ['article' => $article])
            ->call('decocherColonne', $taille->id);

        foreach ($couleurs as $couleur) {
            $this->assertDatabaseHas('article_variantes', [
                'article_id' => $article->id,
                'taille_id' => $taille->id,
                'couleur_id' => $couleur->id,
                'disponible' => false,
            ]);
        }
    }

    public function test_tout_cocher_puis_tout_decocher_couvrent_toute_la_grille(): void
    {
        $gerante = User::factory()->create();
        $article = $this->creerArticle();
        Taille::create(['libelle' => 'M']);
        Taille::create(['libelle' => 'L']);
        Couleur::create(['nom' => 'Blanc']);
        Couleur::create(['nom' => 'Noir']);

        Livewire::actingAs($gerante)
            ->test(MatriceDisponibiliteArticle::class, ['article' => $article])
            ->call('toutCocher');

        $this->assertSame(4, ArticleVariante::where('article_id', $article->id)->where('disponible', true)->count());

        Livewire::actingAs($gerante)
            ->test(MatriceDisponibiliteArticle::class, ['article' => $article])
            ->call('toutDecocher');

        $this->assertSame(0, ArticleVariante::where('article_id', $article->id)->where('disponible', true)->count());
    }

    public function test_type_sans_taille_reduit_la_grille_a_une_colonne_de_couleurs(): void
    {
        $gerante = User::factory()->create();
        $article = $this->creerArticle(gereTailles: false, gereCouleurs: true);
        $couleur = Couleur::create(['nom' => 'Kaki']);

        Livewire::actingAs($gerante)
            ->test(MatriceDisponibiliteArticle::class, ['article' => $article])
            ->assertSet('gereTailles', false)
            ->call('toggleCase', null, $couleur->id);

        $this->assertDatabaseHas('article_variantes', [
            'article_id' => $article->id,
            'taille_id' => null,
            'couleur_id' => $couleur->id,
            'disponible' => true,
        ]);
    }

    /**
     * Une collection au stock géré (le défaut) : la grille n'est plus
     * qu'un affichage, `disponible` suit le stock — la gérante ne peut
     * plus la cocher à la main ici.
     */
    public function test_toggle_case_est_sans_effet_pour_une_collection_au_stock_gere(): void
    {
        $gerante = User::factory()->create();
        $article = $this->creerArticle(gereStock: true);
        $taille = Taille::create(['libelle' => 'M']);
        $couleur = Couleur::create(['nom' => 'Blanc']);

        Livewire::actingAs($gerante)
            ->test(MatriceDisponibiliteArticle::class, ['article' => $article])
            ->assertSet('gereStock', true)
            ->call('toggleCase', $taille->id, $couleur->id);

        $this->assertDatabaseMissing('article_variantes', [
            'article_id' => $article->id,
            'taille_id' => $taille->id,
            'couleur_id' => $couleur->id,
            'disponible' => true,
        ]);
    }

    public function test_tout_cocher_est_sans_effet_pour_une_collection_au_stock_gere(): void
    {
        $gerante = User::factory()->create();
        $article = $this->creerArticle(gereStock: true);
        Taille::create(['libelle' => 'M']);
        Couleur::create(['nom' => 'Blanc']);

        Livewire::actingAs($gerante)
            ->test(MatriceDisponibiliteArticle::class, ['article' => $article])
            ->call('toutCocher');

        $this->assertSame(0, ArticleVariante::where('article_id', $article->id)->where('disponible', true)->count());
    }

    /**
     * Une variante déjà en stock (donc disponible=true, dérivé) reste
     * affichée cochée par la grille — c'est un affichage, pas une remise à
     * zéro de l'état réel.
     */
    public function test_une_variante_en_stock_apparait_cochee_dans_la_grille_au_stock_gere(): void
    {
        $gerante = User::factory()->create();
        $article = $this->creerArticle(gereStock: true);
        $taille = Taille::create(['libelle' => 'M']);
        $couleur = Couleur::create(['nom' => 'Blanc']);
        ArticleVariante::create(['article_id' => $article->id, 'taille_id' => $taille->id, 'couleur_id' => $couleur->id, 'stock' => 5]);

        Livewire::actingAs($gerante)
            ->test(MatriceDisponibiliteArticle::class, ['article' => $article])
            ->assertSet('gereStock', true)
            ->assertSeeHtml('En stock');
    }
}
