<?php

namespace Tests\Feature\Catalogue;

use App\Filament\Resources\ArticleResource\Pages\CreateArticle;
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

class ArticleResourceTest extends TestCase
{
    use RefreshDatabase;

    private function chemin(string $sousChemin = ''): string
    {
        return '/'.config('revolution.admin_path').$sousChemin;
    }

    public function test_lecran_articles_est_accessible_a_la_gerante(): void
    {
        $gerante = User::factory()->create();

        $this->actingAs($gerante)->get($this->chemin('/articles'))->assertOk();
    }

    /**
     * Régression : la table ne doit pas planter une fois qu'elle contient
     * un article avec des variantes — le badge de disponibilité affiche
     * un état sous forme de tableau ['disponibles'=>x,'total'=>y], que
     * ->badge() peut interpréter à tort comme plusieurs valeurs à
     * afficher séparément si on ne le convertit pas d'abord en chaîne.
     */
    public function test_lecran_articles_saffiche_sans_erreur_avec_un_article_et_ses_variantes(): void
    {
        $gerante = User::factory()->create();
        $article = $this->creerArticleAvecType(gereTailles: true, gereCouleurs: true);
        $taille = Taille::create(['libelle' => 'M']);
        $couleur = Couleur::create(['nom' => 'Blanc']);

        ArticleVariante::create([
            'article_id' => $article->id,
            'taille_id' => $taille->id,
            'couleur_id' => $couleur->id,
            'disponible' => true,
        ]);

        $this->actingAs($gerante)->get($this->chemin('/articles'))
            ->assertOk()
            ->assertSee('1/1');
    }

    public function test_les_pages_creer_et_modifier_un_article_se_chargent_sans_erreur(): void
    {
        $gerante = User::factory()->create();
        $article = $this->creerArticleAvecType(gereTailles: true, gereCouleurs: true);

        // Rendu HTTP complet (pas seulement via Livewire::test) : c'est le
        // seul moyen de détecter une erreur Blade dans l'imbrication
        // Tabs > ViewField > @livewire(MatriceDisponibiliteArticle).
        $this->actingAs($gerante)->get($this->chemin('/articles/creer'))->assertOk();
        $this->actingAs($gerante)->get($this->chemin("/articles/{$article->id}/modifier"))->assertOk();
    }

    public function test_creer_un_article_genere_le_slug_et_reste_masque_tant_quaucune_variante_nest_disponible(): void
    {
        $gerante = User::factory()->create();
        $collection = CollectionCatalogue::create(['nom' => 'My verse', 'slug' => 'my_verse']);
        $type = TypeArticle::create(['nom' => 'Tee-shirt', 'slug' => 'tee-shirt', 'gere_tailles' => true, 'gere_couleurs' => true]);

        Livewire::actingAs($gerante)
            ->test(CreateArticle::class)
            ->fillForm([
                'collection_id' => $collection->id,
                'type_article_id' => $type->id,
                'nom' => 'Tee-shirt Couronne d\'épines',
                'slug' => 'tee-shirt-couronne-depines',
                'prix' => 7000,
                'active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = Article::where('slug', 'tee-shirt-couronne-depines')->first();

        $this->assertNotNull($article);
        $this->assertTrue($article->estEpuise(), 'Un article fraîchement créé sans variante doit être considéré épuisé.');
        $this->assertFalse(Article::query()->visiblesPublic()->whereKey($article->id)->exists());
    }

    public function test_ratio_disponibilite_reflete_les_variantes_actives(): void
    {
        $article = $this->creerArticleAvecType(gereTailles: true, gereCouleurs: true);
        $tailles = collect(['M', 'L', 'XL', 'XXL'])->map(fn ($libelle) => Taille::create(['libelle' => $libelle]));
        $couleurs = collect(['Blanc', 'Noir', 'Kaki'])->map(fn ($nom) => Couleur::create(['nom' => $nom]));

        foreach ($tailles as $i => $taille) {
            foreach ($couleurs as $j => $couleur) {
                ArticleVariante::create([
                    'article_id' => $article->id,
                    'taille_id' => $taille->id,
                    'couleur_id' => $couleur->id,
                    'disponible' => ($i + $j) % 3 !== 0, // 8 disponibles sur 12
                ]);
            }
        }

        $ratio = $article->ratioDisponibilite();

        $this->assertSame(12, $ratio['total']);
        $this->assertSame(8, $ratio['disponibles']);
        $this->assertFalse($article->estEpuise());
        $this->assertTrue(Article::query()->visiblesPublic()->whereKey($article->id)->exists());
    }

    public function test_un_article_entierement_epuise_disparait_du_site_public(): void
    {
        $article = $this->creerArticleAvecType(gereTailles: false, gereCouleurs: true);
        $couleur = Couleur::create(['nom' => 'Terracotta']);

        ArticleVariante::create([
            'article_id' => $article->id,
            'taille_id' => null,
            'couleur_id' => $couleur->id,
            'disponible' => false,
        ]);

        $this->assertTrue($article->estEpuise());
        $this->assertFalse(Article::query()->visiblesPublic()->whereKey($article->id)->exists());
    }

    public function test_existe_deja_detecte_le_doublon_meme_avec_taille_id_null(): void
    {
        $article = $this->creerArticleAvecType(gereTailles: false, gereCouleurs: true);
        $couleur = Couleur::create(['nom' => 'Terracotta']);

        ArticleVariante::create([
            'article_id' => $article->id,
            'taille_id' => null,
            'couleur_id' => $couleur->id,
            'disponible' => true,
        ]);

        $this->assertTrue(ArticleVariante::existeDeja($article->id, null, $couleur->id));
        $this->assertFalse(ArticleVariante::existeDeja($article->id, null, null));
    }

    private function creerArticleAvecType(bool $gereTailles, bool $gereCouleurs): Article
    {
        $collection = CollectionCatalogue::create(['nom' => 'Test', 'slug' => 'test-'.uniqid()]);
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
}
