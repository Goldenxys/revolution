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
 * Depuis la restructuration stock/disponibilité, cette grille est un pur
 * affichage — `disponible` est entièrement dérivée par
 * ArticleVariante::booted() (stock pour une collection qui le gère,
 * toujours vraie pour une collection fabriquée à la demande). ArticleResource
 * ne montre d'ailleurs cet onglet que pour les collections au stock géré.
 */
class MatriceDisponibiliteArticleTest extends TestCase
{
    use RefreshDatabase;

    private function creerArticle(bool $gereTailles = true, bool $gereCouleurs = true): Article
    {
        $collection = CollectionCatalogue::create(['nom' => 'Test', 'slug' => 'test-'.uniqid(), 'gere_stock' => true]);
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

    public function test_une_variante_en_stock_apparait_cochee(): void
    {
        $gerante = User::factory()->create();
        $taille = Taille::create(['libelle' => 'M']);
        $couleur = Couleur::create(['nom' => 'Blanc']);
        $article = $this->creerArticle(); // génère la variante M/Blanc à la création

        ArticleVariante::where('article_id', $article->id)
            ->where('taille_id', $taille->id)->where('couleur_id', $couleur->id)
            ->first()
            ->update(['stock' => 5]);

        Livewire::actingAs($gerante)
            ->test(MatriceDisponibiliteArticle::class, ['article' => $article])
            ->assertSeeHtml('En stock');

        $this->assertTrue(
            ArticleVariante::where('article_id', $article->id)
                ->where('taille_id', $taille->id)->where('couleur_id', $couleur->id)
                ->value('disponible')
        );
    }

    public function test_une_variante_sans_stock_apparait_decochee(): void
    {
        $gerante = User::factory()->create();
        $article = $this->creerArticle();
        Taille::create(['libelle' => 'M']);
        Couleur::create(['nom' => 'Blanc']);

        $composant = Livewire::actingAs($gerante)
            ->test(MatriceDisponibiliteArticle::class, ['article' => $article]);

        $composant->assertSeeHtml('Pas de stock enregistré');
        $this->assertTrue($composant->instance()->epuise);
    }

    public function test_type_sans_taille_reduit_la_grille_a_une_colonne_de_couleurs(): void
    {
        $gerante = User::factory()->create();
        $article = $this->creerArticle(gereTailles: false, gereCouleurs: true);
        Couleur::create(['nom' => 'Kaki']);

        Livewire::actingAs($gerante)
            ->test(MatriceDisponibiliteArticle::class, ['article' => $article])
            ->assertSet('gereTailles', false);
    }

    public function test_toutes_les_combinaisons_existent_deja_a_la_creation_de_larticle(): void
    {
        Taille::create(['libelle' => 'M']);
        Taille::create(['libelle' => 'L']);
        Couleur::create(['nom' => 'Blanc']);
        Couleur::create(['nom' => 'Noir']);

        $article = $this->creerArticle();

        $this->assertSame(4, $article->variantes()->count());
        $this->assertSame(0, $article->variantes()->where('disponible', true)->count());
    }
}
