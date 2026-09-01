<?php

namespace Tests\Feature;

use App\Filament\Resources\CommandeResource\Pages\ListCommandes;
use App\Models\Client;
use App\Models\Commande;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommandeEditionTest extends TestCase
{
    use RefreshDatabase;

    private function creerCommande(array $override = []): Commande
    {
        $client = Client::create(array_merge([
            'cle' => '00000099',
            'nom' => 'Aya Kuassi', // faute de frappe volontaire à corriger
            'telephone' => '0900000099',
            'nb_commandes' => 1,
            'premiere_commande_at' => now(),
            'derniere_commande_at' => now(),
        ], $override['client'] ?? []));

        return Commande::create(array_merge([
            'client_id' => $client->id,
            'collection' => 'my_verse',
            'taille' => 'M',
            'couleur' => 'Blanc',
            'verset_reference' => 'Philippiens 4:13',
            'verset_texte' => 'Texte avec une coquille.',
            'commune' => 'Cocody',
            'frais_livraison' => 1500,
            'mode_livraison' => 'yango',
            'date_souhaitee' => now()->addDays(2)->toDateString(),
            'heure_souhaitee' => '14:00',
            'numero_commande_client' => 1,
        ], $override['commande'] ?? []));
    }

    public function test_modifier_une_commande_corrige_le_nom_de_la_cliente_et_larticle(): void
    {
        $gerante = User::factory()->create();
        $commande = $this->creerCommande();

        Livewire::actingAs($gerante)
            ->test(ListCommandes::class)
            ->callTableAction('edit', $commande, data: [
                'client_nom' => 'Aya Kouassi',
                'client_telephone' => $commande->client->telephone,
                'taille' => 'L',
                'couleur' => 'Noir',
                'verset_reference' => 'Philippiens 4:13',
                'verset_texte' => 'Je puis tout par celui qui me fortifie.',
                'commune' => $commande->commune,
                'quartier' => null,
                'mode_livraison' => 'yango',
                'date_souhaitee' => $commande->date_souhaitee,
                'heure_souhaitee' => '15:30',
            ]);

        $commande->refresh();
        $this->assertSame('Aya Kouassi', $commande->client->nom);
        $this->assertSame('L', $commande->taille);
        $this->assertSame('Noir', $commande->couleur);
        $this->assertSame('Je puis tout par celui qui me fortifie.', $commande->verset_texte);
    }

    public function test_modifier_la_commune_recalcule_les_frais_de_livraison(): void
    {
        $gerante = User::factory()->create();
        $commande = $this->creerCommande(); // Cocody, 1500

        Livewire::actingAs($gerante)
            ->test(ListCommandes::class)
            ->callTableAction('edit', $commande, data: [
                'client_nom' => $commande->client->nom,
                'client_telephone' => $commande->client->telephone,
                'taille' => $commande->taille,
                'couleur' => $commande->couleur,
                'verset_reference' => $commande->verset_reference,
                'verset_texte' => $commande->verset_texte,
                'commune' => 'Bassam', // 3000
                'quartier' => null,
                'mode_livraison' => 'yango',
                'date_souhaitee' => $commande->date_souhaitee,
                'heure_souhaitee' => '15:30',
            ]);

        $commande->refresh();
        $this->assertSame('Bassam', $commande->commune);
        $this->assertSame(3000, $commande->frais_livraison);
    }

    public function test_passer_en_livreur_normal_efface_la_date_et_lheure(): void
    {
        $gerante = User::factory()->create();
        $commande = $this->creerCommande(); // yango, avec date/heure

        Livewire::actingAs($gerante)
            ->test(ListCommandes::class)
            ->callTableAction('edit', $commande, data: [
                'client_nom' => $commande->client->nom,
                'client_telephone' => $commande->client->telephone,
                'taille' => $commande->taille,
                'couleur' => $commande->couleur,
                'verset_reference' => $commande->verset_reference,
                'verset_texte' => $commande->verset_texte,
                'commune' => $commande->commune,
                'quartier' => null,
                'mode_livraison' => 'livreur',
            ]);

        $commande->refresh();
        $this->assertSame('livreur', $commande->mode_livraison);
        $this->assertNull($commande->date_souhaitee);
        $this->assertNull($commande->heure_souhaitee);
    }

    public function test_supprimer_une_commande_recalcule_le_compteur_de_la_cliente(): void
    {
        $gerante = User::factory()->create();

        $client = Client::create([
            'cle' => '00000098',
            'nom' => 'Fatou Diarra',
            'telephone' => '0900000098',
            'nb_commandes' => 2,
            'premiere_commande_at' => now()->subDays(5),
            'derniere_commande_at' => now(),
        ]);

        $premiereCommande = Commande::create([
            'client_id' => $client->id,
            'collection' => 'autre',
            'taille' => 'M',
            'type_article' => 'Pull',
            'nom_article' => 'Test',
            'commune' => 'Cocody',
            'frais_livraison' => 1500,
            'mode_livraison' => 'livreur',
            'numero_commande_client' => 1,
            'created_at' => now()->subDays(5),
        ]);

        $commandeErronee = Commande::create([
            'client_id' => $client->id,
            'collection' => 'autre',
            'taille' => 'M',
            'type_article' => 'Pull',
            'nom_article' => 'Doublon par erreur',
            'commune' => 'Cocody',
            'frais_livraison' => 1500,
            'mode_livraison' => 'livreur',
            'numero_commande_client' => 2,
            'created_at' => now(),
        ]);

        Livewire::actingAs($gerante)
            ->test(ListCommandes::class)
            ->callTableAction('delete', $commandeErronee);

        $this->assertModelMissing($commandeErronee);
        $this->assertModelExists($premiereCommande);

        $client->refresh();
        $this->assertSame(1, $client->nb_commandes);
        $this->assertNotNull($client->derniere_commande_at);
        $this->assertTrue($client->derniere_commande_at->equalTo($premiereCommande->created_at));
    }
}
