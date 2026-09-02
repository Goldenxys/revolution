<?php

namespace Tests\Feature\Catalogue;

use App\Filament\Resources\TypeArticleResource\Pages\ManageTypesArticles;
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

class EcransCatalogueTest extends TestCase
{
    use RefreshDatabase;

    private function chemin(string $sousChemin = ''): string
    {
        return '/'.config('revolution.admin_path').$sousChemin;
    }

    public function test_les_quatre_ecrans_du_catalogue_sont_accessibles_a_la_gerante(): void
    {
        $gerante = User::factory()->create();

        $this->actingAs($gerante)->get($this->chemin('/collections'))->assertOk();
        $this->actingAs($gerante)->get($this->chemin('/type-articles'))->assertOk();
        $this->actingAs($gerante)->get($this->chemin('/tailles'))->assertOk();
        $this->actingAs($gerante)->get($this->chemin('/couleurs'))->assertOk();
    }

    public function test_les_ecrans_du_catalogue_exigent_une_connexion(): void
    {
        $this->get($this->chemin('/collections'))->assertRedirect(route('login'));
        $this->get($this->chemin('/couleurs'))->assertRedirect(route('login'));
    }

    public function test_creer_un_type_darticle_genere_le_slug(): void
    {
        $gerante = User::factory()->create();

        Livewire::actingAs($gerante)
            ->test(ManageTypesArticles::class)
            ->callAction('create', data: [
                'nom' => 'Sweat à capuche',
                'slug' => 'sweat-a-capuche',
                'gere_tailles' => true,
                'gere_couleurs' => true,
                'ordre' => 0,
                'active' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('types_articles', [
            'nom' => 'Sweat à capuche',
            'slug' => 'sweat-a-capuche',
        ]);
    }

    public function test_une_taille_utilisee_par_une_variante_ne_peut_pas_etre_supprimee(): void
    {
        $collection = CollectionCatalogue::create(['nom' => 'Test', 'slug' => 'test']);
        $type = TypeArticle::create(['nom' => 'Tee', 'slug' => 'tee']);
        $article = Article::create([
            'collection_id' => $collection->id,
            'type_article_id' => $type->id,
            'nom' => 'Article test',
            'slug' => 'article-test',
            'prix' => 10000,
        ]);
        $taille = Taille::create(['libelle' => 'M']);
        ArticleVariante::create([
            'article_id' => $article->id,
            'taille_id' => $taille->id,
            'disponible' => true,
        ]);

        $this->assertFalse($taille->fresh()->estSupprimable());

        $couleurLibre = Couleur::create(['nom' => 'Corail']);
        $this->assertTrue($couleurLibre->estSupprimable());
    }
}
