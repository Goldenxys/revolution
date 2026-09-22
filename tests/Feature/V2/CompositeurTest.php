<?php

namespace Tests\Feature\V2;

use App\Filament\Resources\CommandeResource;
use App\Filament\Resources\DemandeResource;
use App\Filament\Resources\DemandeResource\Pages\ComposerDemande;
use App\Filament\Resources\DemandeResource\Pages\ListDemandes;
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
use Livewire\Livewire;
use Tests\TestCase;

class CompositeurTest extends TestCase
{
    use RefreshDatabase;

    private function catalogue(): array
    {
        $collection = CollectionCatalogue::create(['nom' => 'Couronne', 'slug' => 'couronne']);
        $type = TypeArticle::create(['nom' => 'Tee-shirt', 'slug' => 'tee', 'gere_tailles' => true, 'gere_couleurs' => true]);
        $article = Article::create([
            'collection_id' => $collection->id, 'type_article_id' => $type->id,
            'nom' => 'Tee-shirt Couronne d\'épines', 'slug' => 'tee-couronne', 'prix' => 7000,
        ]);
        $taille = Taille::create(['libelle' => 'XL']);
        $couleur = Couleur::create(['nom' => 'Blanc']);
        $variante = ArticleVariante::create([
            'article_id' => $article->id, 'taille_id' => $taille->id, 'couleur_id' => $couleur->id,
            'disponible' => true, 'stock' => 5,
        ]);

        return compact('article', 'taille', 'couleur', 'variante');
    }

    private function demande(string $tel = '0700000009'): array
    {
        $client = Client::create([
            'cle' => Client::cleDepuisTelephone($tel),
            'nom' => 'Marie Koffi', 'telephone' => $tel,
            'statut' => 'prospect', 'numero_client' => 'REV-C-'.substr($tel, -4), 'nb_commandes' => 0,
        ]);
        $commande = Commande::create([
            'client_id' => $client->id,
            'commune' => 'Cocody', 'frais_livraison' => 1500, 'mode_livraison' => 'livreur',
            'statut' => 'en_attente',
            'collection' => 'my_verse',
            'verset_reference' => 'Philippiens 4:13',
            'message_client' => 'écriture dorée',
            'souhaits_client' => ['collection' => 'my_verse', 'versets' => [
                ['reference' => 'Philippiens 4:13', 'texte' => 'Je puis tout par celui qui me fortifie.'],
                ['reference' => 'Jean 3:16', 'texte' => null],
            ]],
        ]);

        return [$client, $commande];
    }

    public function test_la_liste_ne_montre_que_les_demandes_en_attente(): void
    {
        $gerante = User::factory()->create();
        [, $enAttente] = $this->demande('0700000001');
        $validee = Commande::create([
            'client_id' => $enAttente->client_id, 'commune' => 'Cocody', 'frais_livraison' => 1500,
            'mode_livraison' => 'livreur', 'statut' => 'validee', 'validee_at' => now(),
        ]);

        Livewire::actingAs($gerante)->test(ListDemandes::class)
            ->assertCanSeeTableRecords([$enAttente])
            ->assertCanNotSeeTableRecords([$validee]);

        $this->assertSame('1', DemandeResource::getNavigationBadge());
    }

    public function test_composer_et_valider_une_demande(): void
    {
        $gerante = User::factory()->create();
        $cat = $this->catalogue();
        [$client, $commande] = $this->demande();

        Livewire::actingAs($gerante)
            ->test(ComposerDemande::class, ['record' => $commande->getKey()])
            ->fillForm([
                'lignes' => [
                    [
                        'article_id' => $cat['article']->id,
                        'taille_id' => $cat['taille']->id,
                        'couleur_id' => $cat['couleur']->id,
                        'quantite' => 2,
                        'prix_unitaire' => 7000,
                    ],
                ],
                'remise_manuelle' => false,
            ])
            ->call('valider')
            ->assertHasNoFormErrors();

        $commande->refresh();
        $client->refresh();

        $this->assertSame('validee', $commande->statut);
        $this->assertSame(14000, $commande->total_articles);
        $this->assertSame(15500, $commande->total_a_payer);
        $this->assertSame(1, $commande->lignes()->count());
        $this->assertSame('Tee-shirt Couronne d\'épines', $commande->lignes()->first()->article_nom);

        // La validation ne comptabilise plus rien : ni le stock, ni la
        // fidélité/le CA de la cliente. Ce fait comptable attend la
        // livraison confirmée (Commande::confirmerLivraison()).
        $this->assertSame('prospect', $client->statut);
        $this->assertSame(0, $client->nb_commandes);
        $this->assertSame(5, $cat['variante']->refresh()->stock);

        $commande->update(['statut' => 'en_livraison']);
        $commande->confirmerLivraison($gerante);
        $client->refresh();

        $this->assertSame('livree', $commande->fresh()->statut);
        $this->assertSame('client', $client->statut);
        $this->assertSame(1, $client->nb_commandes);
        $this->assertSame(3, $cat['variante']->refresh()->stock); // 5 − 2
    }

    public function test_le_compositeur_cree_une_ligne_par_verset_avec_le_verset_pre_rempli(): void
    {
        $gerante = User::factory()->create();
        $this->catalogue();
        [, $commande] = $this->demande();

        Livewire::actingAs($gerante)
            ->test(ComposerDemande::class, ['record' => $commande->getKey()])
            ->assertFormSet(function (array $state) {
                $lignes = array_values($state['lignes']);

                return count($lignes) === 2
                    && str_contains((string) $lignes[0]['verset'], 'Philippiens 4:13')
                    && str_contains((string) $lignes[1]['verset'], 'Jean 3:16')
                    && $lignes[0]['article_id'] === null   // la gérante choisit l'article
                    && $lignes[0]['taille_id'] === null;   // et la taille
            });
    }

    public function test_une_demande_deja_validee_redirige_vers_sa_fiche(): void
    {
        $gerante = User::factory()->create();
        [, $commande] = $this->demande();
        $commande->update(['statut' => 'validee', 'validee_at' => now()]);

        Livewire::actingAs($gerante)
            ->test(ComposerDemande::class, ['record' => $commande->getKey()])
            ->assertRedirect(CommandeResource::getUrl('view', ['record' => $commande]));
    }
}
