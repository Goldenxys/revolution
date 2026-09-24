<?php

namespace Tests\Feature\V2;

use App\Mail\DemandeDeposee;
use App\Models\Article;
use App\Models\ArticleVariante;
use App\Models\Client;
use App\Models\CollectionCatalogue;
use App\Models\Commande;
use App\Models\Couleur;
use App\Models\Taille;
use App\Models\TypeArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FormulaireDemandeTest extends TestCase
{
    use RefreshDatabase;

    private function creerArticleCatalogue(): array
    {
        $collection = CollectionCatalogue::create(['nom' => 'Identité', 'slug' => 'identite-'.uniqid()]);
        $type = TypeArticle::create([
            'nom' => 'Tee-shirt', 'slug' => 'tee-'.uniqid(),
            'gere_tailles' => true, 'gere_couleurs' => true,
        ]);
        $article = Article::create([
            'collection_id' => $collection->id, 'type_article_id' => $type->id,
            'nom' => 'Tee-shirt God\'s Daughter', 'slug' => 'gods-daughter-'.uniqid(), 'prix' => 8000,
        ]);
        $taille = Taille::create(['libelle' => 'L']);
        $couleur = Couleur::create(['nom' => 'Beige']);
        ArticleVariante::create([
            'article_id' => $article->id, 'taille_id' => $taille->id, 'couleur_id' => $couleur->id,
            'disponible' => true, 'stock' => 5,
        ]);

        return compact('article', 'taille', 'couleur');
    }

    private function creerArticleMyVerse(): Article
    {
        $collection = CollectionCatalogue::create(['nom' => 'My verse', 'slug' => 'my_verse']);
        $type = TypeArticle::create([
            'nom' => 'Tee-shirt My Verse', 'slug' => 'tee-my-verse-'.uniqid(),
            'gere_tailles' => true, 'gere_couleurs' => true,
        ]);

        return Article::create([
            'collection_id' => $collection->id, 'type_article_id' => $type->id,
            'nom' => 'Tee-shirt My Verse Modèle 1', 'slug' => 'my-verse-modele-1', 'prix' => 8000,
        ]);
    }

    public function test_les_deux_pages_se_chargent(): void
    {
        $this->get(route('commande.demande.creer'))->assertOk()->assertSee('Je passe ma commande My Verse');
        $this->get(route('commande.demande.autre'))->assertOk()->assertSee('Je passe ma commande');
    }

    public function test_l_accueil_propose_les_deux_boutons(): void
    {
        $this->get(route('accueil'))
            ->assertSee(route('commande.demande.creer'), false)
            ->assertSee(route('commande.demande.autre'), false);
    }

    public function test_une_demande_my_verse_enregistre_plusieurs_versets(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();

        $response = $this->post(route('commande.demande.store'), [
            'nom' => 'Aya Kouassi',
            'telephone' => '0102030405',
            'email' => 'aya@example.com',
            'collection' => 'my_verse',
            'versets' => [
                ['reference' => 'Philippiens 4:13', 'texte' => 'Je puis tout par celui qui me fortifie.'],
                ['reference' => 'Jean 3:16', 'texte' => null],
            ],
            'precisions' => 'Écriture dorée',
            'commune' => 'Cocody',
            'mode_livraison' => 'livreur',
        ]);

        $commande = Commande::first();
        $response->assertRedirect(route('commande.demande.merci', $commande->reference));

        $this->assertSame('en_attente', $commande->statut);
        $this->assertSame('my_verse', $commande->collection);
        $this->assertNull($commande->taille);          // réglée par la gérante
        $this->assertNull($commande->couleur);
        $this->assertCount(2, $commande->souhaits_client['versets']);
        $this->assertSame('Philippiens 4:13', $commande->souhaits_client['versets'][0]['reference']);
        $this->assertSame('Philippiens 4:13', $commande->verset_reference); // 1er verset sur la colonne historique
        $this->assertSame('Écriture dorée', $commande->message_client);
        $this->assertSame(0, $commande->total_articles);

        $this->assertSame('prospect', $commande->client->statut);
        $this->assertNotNull($commande->client->numero_client);

        Mail::assertQueued(DemandeDeposee::class);
    }

    public function test_une_demande_my_verse_enregistre_taille_et_couleur_par_verset(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();
        $taille = Taille::create(['libelle' => 'M']);
        $couleur = Couleur::create(['nom' => 'Noir']);

        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya Kouassi', 'telephone' => '0102030405',
            'collection' => 'my_verse',
            'versets' => [
                ['reference' => 'Philippiens 4:13', 'texte' => null, 'taille_id' => $taille->id, 'couleur_id' => $couleur->id],
            ],
            'commune' => 'Cocody', 'mode_livraison' => 'livreur',
        ])->assertRedirect();

        $verset = Commande::first()->souhaits_client['versets'][0];
        $this->assertSame($taille->id, $verset['taille_id']);
        $this->assertSame('M', $verset['taille_libelle']);
        $this->assertSame($couleur->id, $verset['couleur_id']);
        $this->assertSame('Noir', $verset['couleur_nom']);
    }

    public function test_une_demande_autre_collection_enregistre_plusieurs_articles(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();
        $catalogue = $this->creerArticleCatalogue();

        $this->post(route('commande.demande.store'), [
            'nom' => 'Koffi', 'telephone' => '0102030406',
            'collection' => 'autre',
            'articles' => [
                // Article reconnu dans le catalogue (choisi via la recherche).
                [
                    'nom' => $catalogue['article']->nom,
                    'article_id' => $catalogue['article']->id,
                    'taille_id' => $catalogue['taille']->id,
                    'couleur_id' => $catalogue['couleur']->id,
                    'quantite' => 2,
                ],
                // Article hors catalogue (nom tapé librement, pas de match).
                [
                    'nom' => 'Le pull vu sur Instagram',
                    'article_id' => null,
                    'taille_id' => null,
                    'couleur_id' => null,
                    'quantite' => 1,
                ],
            ],
            'commune' => 'Yopougon', 'mode_livraison' => 'livreur',
        ])->assertRedirect();

        $articles = Commande::first()->souhaits_client['articles'];
        $this->assertCount(2, $articles);

        $this->assertSame($catalogue['article']->id, $articles[0]['article_id']);
        $this->assertSame($catalogue['article']->nom, $articles[0]['nom']);
        $this->assertSame('L', $articles[0]['taille_libelle']);
        $this->assertSame('Beige', $articles[0]['couleur_nom']);
        $this->assertSame(2, $articles[0]['quantite']);

        $this->assertNull($articles[1]['article_id']);
        $this->assertSame('Le pull vu sur Instagram', $articles[1]['nom']);
        $this->assertSame(1, $articles[1]['quantite']);
    }

    /**
     * My Verse a son propre formulaire dédié : impossible de le contourner
     * en soumettant son article_id via le formulaire « Autre collection »
     * (même si l'affichage JS l'exclut déjà de la recherche, le serveur
     * doit refuser aussi — champ caché, requête forgée...).
     */
    public function test_un_article_my_verse_est_refuse_dans_une_demande_autre_collection(): void
    {
        User::factory()->create();
        $articleMyVerse = $this->creerArticleMyVerse();

        $this->post(route('commande.demande.store'), [
            'nom' => 'Koffi', 'telephone' => '0102030407',
            'collection' => 'autre',
            'articles' => [
                ['nom' => $articleMyVerse->nom, 'article_id' => $articleMyVerse->id, 'quantite' => 1],
            ],
            'commune' => 'Yopougon', 'mode_livraison' => 'livreur',
        ])->assertSessionHasErrors('articles.0.article_id');

        $this->assertSame(0, Commande::count());
    }

    public function test_une_demande_autre_collection_ne_demande_que_les_infos(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();

        $this->post(route('commande.demande.store'), [
            'nom' => 'Koffi', 'telephone' => '0102030406',
            'collection' => 'autre',
            'precisions' => 'Le pull beige vu sur WhatsApp, taille L',
            'commune' => 'Yopougon', 'mode_livraison' => 'livreur',
        ])->assertRedirect();

        $commande = Commande::first();
        $this->assertSame('autre', $commande->collection);
        // Aucun article détaillé ici (répéteur laissé vide) : souhaits_client
        // garde sa structure ('collection' + 'articles'), mais articles est vide.
        $this->assertSame(['collection' => 'autre', 'articles' => []], $commande->souhaits_client);
        $this->assertNull($commande->verset_reference);
        $this->assertSame('Le pull beige vu sur WhatsApp, taille L', $commande->message_client);
        $this->assertNotNull($commande->client->numero_client); // carte de fidélité aussi
    }

    public function test_my_verse_exige_au_moins_un_verset_renseigne(): void
    {
        User::factory()->create();

        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya', 'telephone' => '0102030405',
            'collection' => 'my_verse',
            'versets' => [['reference' => '', 'texte' => '']],
            'commune' => 'Cocody', 'mode_livraison' => 'livreur',
        ])->assertSessionHasErrors('versets.0.texte');

        $this->assertSame(0, Commande::count());
    }

    public function test_anti_doublon_90_secondes(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();

        $payload = [
            'nom' => 'Aya Kouassi', 'telephone' => '0102030405',
            'collection' => 'autre', 'commune' => 'Cocody', 'mode_livraison' => 'livreur',
        ];

        $this->post(route('commande.demande.store'), $payload)->assertRedirect();
        $this->post(route('commande.demande.store'), $payload)->assertSessionHasErrors('telephone');

        $this->assertSame(1, Commande::count());
    }

    public function test_la_confirmation_affiche_la_carte_de_fidelite(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();

        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya Kouassi', 'telephone' => '0102030405',
            'collection' => 'autre', 'commune' => 'Cocody', 'mode_livraison' => 'livreur',
        ]);

        $commande = Commande::first();
        $this->get(route('commande.demande.merci', $commande->reference))
            ->assertOk()
            ->assertSee('Carte de fidélité')
            ->assertSee('comptée une fois livrée');
    }

    /**
     * Geste de clôture (§ mise à jour) : la carte téléchargeable compte la
     * demande tout juste déposée comme si elle était déjà validée, même si
     * l'écran affiché reste honnête sur ce qui est réellement validé.
     */
    public function test_le_bouton_de_telechargement_projette_la_commande_en_cours_comme_validee(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();

        // Cliente déjà à 1 commande validée : la demande qu'elle vient de
        // déposer serait sa 2ᵉ, palier qui débloque -15 %.
        $client = Client::create([
            'cle' => Client::cleDepuisTelephone('0102030405'),
            'nom' => 'Aya Kouassi', 'telephone' => '0102030405',
            'nb_commandes' => 1, 'statut' => 'client', 'numero_client' => 'REV-C-0001',
        ]);

        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya Kouassi', 'telephone' => '0102030405',
            'collection' => 'autre', 'commune' => 'Cocody', 'mode_livraison' => 'livreur',
        ]);

        $commande = Commande::first();

        $response = $this->get(route('commande.demande.merci', $commande->reference));
        $response->assertOk()
            ->assertSee('Télécharger ma carte de fidélité')
            ->assertSee(route('commande.demande.carte', $commande->reference), false);

        // L'écran, lui, reste honnête : encore 1 commande validée affichée.
        $response->assertSee('1 commande validée');
    }

    /**
     * Le nouveau gabarit remplace le canvas partout : le lien vers la carte
     * doit apparaître même quand cette demande ne fait franchir aucun
     * palier (route commande.demande.carte sert alors la variante
     * « progression », jamais un 404 — voir CarteFideliteTest).
     */
    public function test_le_lien_de_telechargement_apparait_meme_sans_palier_franchi(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();

        // Cliente sans commande antérieure : cette demande serait sa 1ʳᵉ,
        // aucun palier ne se débloque.
        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya Kouassi', 'telephone' => '0102030405',
            'collection' => 'autre', 'commune' => 'Cocody', 'mode_livraison' => 'livreur',
        ]);

        $commande = Commande::first();

        $this->get(route('commande.demande.merci', $commande->reference))
            ->assertOk()
            ->assertSee('Télécharger ma carte de fidélité')
            ->assertSee(route('commande.demande.carte', $commande->reference), false);
    }

    public function test_une_demande_deja_validee_ne_montre_plus_la_page_merci(): void
    {
        $client = Client::create(['cle' => '00000009', 'nom' => 'X', 'telephone' => '0700000009', 'nb_commandes' => 0]);
        $commande = Commande::create([
            'client_id' => $client->id, 'commune' => 'Cocody', 'frais_livraison' => 1500,
            'mode_livraison' => 'livreur', 'statut' => 'validee', 'validee_at' => now(),
        ]);

        $this->get(route('commande.demande.merci', $commande->reference))->assertNotFound();
    }
}
