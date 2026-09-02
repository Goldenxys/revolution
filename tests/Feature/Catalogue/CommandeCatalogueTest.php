<?php

namespace Tests\Feature\Catalogue;

use App\Mail\CommandeRecue;
use App\Models\Article;
use App\Models\ArticleVariante;
use App\Models\Client;
use App\Models\Commande;
use App\Models\CollectionCatalogue;
use App\Models\Couleur;
use App\Models\Taille;
use App\Models\TypeArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CommandeCatalogueTest extends TestCase
{
    use RefreshDatabase;

    private function creerArticleDisponible(int $prix = 7000, bool $gereTailles = true, bool $gereCouleurs = true): array
    {
        $collection = CollectionCatalogue::create(['nom' => 'Test', 'slug' => 'test-'.uniqid()]);
        $type = TypeArticle::create([
            'nom' => 'Type test', 'slug' => 'type-'.uniqid(),
            'gere_tailles' => $gereTailles, 'gere_couleurs' => $gereCouleurs,
        ]);
        $article = Article::create([
            'collection_id' => $collection->id, 'type_article_id' => $type->id,
            'nom' => 'Article test', 'slug' => 'article-'.uniqid(), 'prix' => $prix,
        ]);

        $taille = $gereTailles ? Taille::create(['libelle' => 'M']) : null;
        $couleur = $gereCouleurs ? Couleur::create(['nom' => 'Blanc']) : null;

        $variante = ArticleVariante::create([
            'article_id' => $article->id,
            'taille_id' => $taille?->id,
            'couleur_id' => $couleur?->id,
            'disponible' => true,
        ]);

        return compact('article', 'taille', 'couleur', 'variante');
    }

    private function donneesBase(array $override = []): array
    {
        return array_merge([
            'quantite' => 1,
            'nom' => 'Aya Kouassi',
            'telephone' => '0102030405',
            'commune' => 'Yopougon',
            'mode_livraison' => 'livreur',
        ], $override);
    }

    public function test_la_page_du_formulaire_catalogue_se_charge(): void
    {
        $this->get(route('commande.catalogue.creer'))->assertOk();
    }

    /**
     * Avec le vrai catalogue chargé (39 articles + tote bag, dont des noms
     * avec apostrophes comme « God's Daughter ») : le JSON injecté dans
     * x-data via @js() doit s'échapper correctement en HTML, et la
     * présélection par slug (?collection=) doit fonctionner.
     */
    public function test_la_page_se_charge_avec_le_catalogue_reel_et_gere_les_apostrophes(): void
    {
        $this->seed(\Database\Seeders\TypeArticleSeeder::class);
        $this->seed(\Database\Seeders\TailleSeeder::class);
        $this->seed(\Database\Seeders\CouleurSeeder::class);
        $this->seed(\Database\Seeders\CollectionSeeder::class);
        $this->seed(\Database\Seeders\CatalogueInitialSeeder::class);

        $this->get(route('commande.catalogue.creer'))->assertOk();
        $this->get(route('commande.catalogue.creer', ['collection' => 'my_verse']))->assertOk();
        $this->get(route('commande.catalogue.json'))->assertOk()->assertJsonCount(40, 'articles');
    }

    public function test_creation_reussie_avec_article_taille_et_couleur_disponibles(): void
    {
        Mail::fake();
        Notification::fake();

        ['article' => $article, 'taille' => $taille, 'couleur' => $couleur] = $this->creerArticleDisponible(7000);

        $reponse = $this->post(route('commande.catalogue.store'), $this->donneesBase([
            'article_id' => $article->id,
            'taille_id' => $taille->id,
            'couleur_id' => $couleur->id,
        ]));

        $commande = Commande::first();

        $this->assertNotNull($commande);
        $reponse->assertRedirect(route('commande.confirmation', $commande->reference));
        $this->assertTrue($commande->utilise_catalogue);
        $this->assertSame('nouvelle', $commande->statut);
        $this->assertSame(7000, $commande->sous_total);
        $this->assertSame(1000, $commande->frais_livraison); // Yopougon
        $this->assertSame(8000, $commande->total);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $commande->reference);

        $ligne = $commande->lignes->first();
        $this->assertNotNull($ligne);
        $this->assertSame($article->id, $ligne->article_id);
        $this->assertSame('Article test', $ligne->article_nom);
        $this->assertSame('M', $ligne->taille_libelle);
        $this->assertSame('Blanc', $ligne->couleur_nom);
        $this->assertSame(7000, $ligne->prix_unitaire);

        Mail::assertQueued(CommandeRecue::class);
    }

    public function test_le_prix_est_toujours_recalcule_depuis_larticle_jamais_depuis_la_requete(): void
    {
        Mail::fake();

        ['article' => $article, 'taille' => $taille, 'couleur' => $couleur] = $this->creerArticleDisponible(7000);

        $this->post(route('commande.catalogue.store'), $this->donneesBase([
            'article_id' => $article->id,
            'taille_id' => $taille->id,
            'couleur_id' => $couleur->id,
            // Tentative de falsification : ces champs n'existent même pas
            // dans les règles de validation, donc ignorés, mais le test
            // vérifie explicitement que le total en base ne peut PAS être
            // influencé par une valeur soumise.
            'prix' => 1,
            'sous_total' => 1,
            'total' => 1,
        ]));

        $commande = Commande::first();
        $this->assertSame(7000, $commande->sous_total);
        $this->assertSame(7000 + $commande->frais_livraison, $commande->total);
    }

    public function test_revalidation_de_disponibilite_a_la_soumission_rejette_une_variante_epuisee_entre_temps(): void
    {
        Mail::fake();

        ['article' => $article, 'taille' => $taille, 'couleur' => $couleur, 'variante' => $variante] = $this->creerArticleDisponible();

        // La cliente avait chargé la page quand c'était disponible, mais la
        // variante s'épuise juste avant qu'elle ne valide.
        $variante->update(['disponible' => false]);

        $reponse = $this->from(route('commande.catalogue.creer'))->post(route('commande.catalogue.store'), $this->donneesBase([
            'article_id' => $article->id,
            'taille_id' => $taille->id,
            'couleur_id' => $couleur->id,
        ]));

        $reponse->assertRedirect(route('commande.catalogue.creer'));
        $reponse->assertSessionHasErrors('taille_id');
        $this->assertSame(0, Commande::count());
        Mail::assertNothingQueued();
    }

    public function test_article_sans_taille_ni_couleur_nexige_ni_lune_ni_lautre(): void
    {
        Mail::fake();

        ['article' => $article, 'variante' => $variante] = $this->creerArticleDisponible(2000, gereTailles: false, gereCouleurs: false);

        $reponse = $this->post(route('commande.catalogue.store'), $this->donneesBase([
            'article_id' => $article->id,
        ]));

        $commande = Commande::first();
        $this->assertNotNull($commande);
        $reponse->assertRedirect(route('commande.confirmation', $commande->reference));

        $ligne = $commande->lignes->first();
        $this->assertNull($ligne->taille_libelle);
        $this->assertNull($ligne->couleur_nom);
    }

    public function test_la_fidelite_sapplique_sans_plafond_meme_sur_un_article_cher(): void
    {
        Mail::fake();

        // Client à sa 8e commande : palier 65 %.
        $client = Client::create([
            'cle' => Client::cleDepuisTelephone('0102030405'),
            'nom' => 'Aya Kouassi',
            'telephone' => '0102030405',
            'nb_commandes' => 7,
            'premiere_commande_at' => now()->subMonths(2),
            'derniere_commande_at' => now()->subWeek(),
        ]);

        ['article' => $article, 'taille' => $taille, 'couleur' => $couleur] = $this->creerArticleDisponible(35000);

        $this->post(route('commande.catalogue.store'), $this->donneesBase([
            'article_id' => $article->id,
            'taille_id' => $taille->id,
            'couleur_id' => $couleur->id,
            'nom' => 'Aya Kouassi',
            'telephone' => '0102030405',
        ]));

        $commande = Commande::where('client_id', $client->id)->first();

        $this->assertNotNull($commande);
        $this->assertSame(8, $commande->numero_commande_client);
        $this->assertSame(65, $commande->remise_pourcentage);

        // 65 % plein sur (35000 + frais), sans aucun plafond appliqué.
        $attendu = (int) round(($commande->sous_total + $commande->frais_livraison) * 0.65);
        $this->assertSame($attendu, $commande->remise_montant);
    }

    public function test_notification_gerante_envoyee_immediatement_pour_une_commande_catalogue(): void
    {
        Mail::fake();

        $gerante = \App\Models\User::factory()->create();
        ['article' => $article, 'taille' => $taille, 'couleur' => $couleur] = $this->creerArticleDisponible();

        $this->post(route('commande.catalogue.store'), $this->donneesBase([
            'article_id' => $article->id,
            'taille_id' => $taille->id,
            'couleur_id' => $couleur->id,
        ]));

        $gerante->refresh();
        $this->assertSame(1, $gerante->unreadNotifications()->count());

        $notification = $gerante->unreadNotifications()->first();
        $this->assertSame('Nouvelle commande RÉVOLUTION', $notification->data['title']);
        $this->assertStringContainsString('Aya Kouassi', $notification->data['body']);
        // Le libellé de collection vient de la vraie collection de
        // l'article (chemin catalogue), pas de la colonne legacy.
        $this->assertStringContainsString('Test', $notification->data['body']);
    }
}
