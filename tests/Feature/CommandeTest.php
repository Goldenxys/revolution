<?php

namespace Tests\Feature;

use App\Mail\CommandeRecue;
use App\Models\Client;
use App\Models\Commande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CommandeTest extends TestCase
{
    use RefreshDatabase;

    private function donneesMyVerse(array $override = []): array
    {
        return array_merge([
            'collection' => 'my_verse',
            'nom' => 'Djiehi Carine',
            'telephone' => '0700000000',
            'email' => 'carine@exemple.com',
            'commune' => 'Cocody',
            'quartier' => 'Angré, carrefour de la pharmacie',
            'mode_livraison' => 'yango',
            'date_souhaitee' => now()->addDays(3)->toDateString(),
            'heure_souhaitee' => '14:00',
            'taille' => 'L',
            'couleur' => 'Blanc',
            'verset_reference' => 'Philippiens 4:13',
            'verset_texte' => 'Je puis tout par celui qui me fortifie.',
        ], $override);
    }

    private function donneesAutre(array $override = []): array
    {
        return array_merge([
            'collection' => 'autre',
            'nom' => 'Aya Kouassi',
            'telephone' => '0102030405',
            'commune' => 'Yopougon',
            'mode_livraison' => 'livreur',
            'taille' => 'M',
            'type_article' => 'Pull',
            'nom_article' => 'Couronne d\'épines',
        ], $override);
    }

    public function test_creation_commande_my_verse(): void
    {
        Mail::fake();

        $reponse = $this->post(route('commande.store'), $this->donneesMyVerse());

        $commande = Commande::first();

        $this->assertNotNull($commande);
        $reponse->assertRedirect(route('commande.confirmation', $commande->reference));

        $this->assertSame('my_verse', $commande->collection);
        $this->assertSame('L', $commande->taille);
        $this->assertSame('Philippiens 4:13', $commande->verset_reference);
        $this->assertSame(1500, $commande->frais_livraison);
        $this->assertSame(1, $commande->numero_commande_client);

        $this->assertDatabaseHas('clients', [
            'nom' => 'Djiehi Carine',
            'nb_commandes' => 1,
        ]);

        Mail::assertQueued(
            CommandeRecue::class,
            fn (CommandeRecue $mail) => $mail->envelope()->subject === 'Super Nouvelle commande — Djiehi Carine · MY VERSE'
        );
    }

    public function test_creation_commande_autre_collection(): void
    {
        Mail::fake();

        $reponse = $this->post(route('commande.store'), $this->donneesAutre());

        $commande = Commande::first();

        $this->assertNotNull($commande);
        $reponse->assertRedirect(route('commande.confirmation', $commande->reference));

        $this->assertSame('autre', $commande->collection);
        $this->assertSame('Pull', $commande->type_article);
        $this->assertSame('Couronne d\'épines', $commande->nom_article);
        $this->assertSame(1000, $commande->frais_livraison); // Yopougon
        $this->assertNull($commande->verset_reference);

        Mail::assertQueued(CommandeRecue::class);
    }

    public function test_client_existant_reconnu_par_telephone_incremente_compteur(): void
    {
        Mail::fake();

        $this->post(route('commande.store'), $this->donneesAutre(['telephone' => '0102030405']));

        $this->assertDatabaseHas('clients', ['nb_commandes' => 1]);
        $this->assertSame(1, Client::count());

        // Même numéro, mise en forme différente (espaces + indicatif) :
        // doit être reconnu comme le même client via les 8 derniers chiffres.
        $this->post(route('commande.store'), $this->donneesAutre([
            'telephone' => '+225 01 02 03 04 05',
            'nom' => 'Aya Kouassi',
        ]));

        $this->assertSame(1, Client::count(), 'Aucun doublon de client ne doit être créé.');

        $client = Client::first();
        $this->assertSame(2, $client->nb_commandes);
        $this->assertSame(2, $client->palier);
        $this->assertSame(15, $client->avantage);

        $commandes = Commande::orderBy('numero_commande_client')->pluck('numero_commande_client')->all();
        $this->assertSame([1, 2], $commandes);
    }

    public function test_refus_date_de_livraison_avec_mode_livreur(): void
    {
        Mail::fake();

        $reponse = $this->from(route('commande.autre'))->post(route('commande.store'), $this->donneesAutre([
            'mode_livraison' => 'livreur',
            'date_souhaitee' => now()->addDays(2)->toDateString(),
        ]));

        $reponse->assertRedirect(route('commande.autre'));
        $reponse->assertSessionHasErrors('date_souhaitee');

        $this->assertSame(0, Commande::count());
        Mail::assertNothingQueued();
    }

    public function test_frais_livraison_recalcules_cote_serveur(): void
    {
        Mail::fake();

        // La cliente (ou un script malveillant) envoie un frais de livraison
        // falsifié : il doit être ignoré et recalculé depuis la config.
        $this->post(route('commande.store'), $this->donneesAutre([
            'commune' => 'Bassam',
            'frais_livraison' => 1,
        ]));

        $commande = Commande::first();

        $this->assertNotNull($commande);
        $this->assertSame(3000, $commande->frais_livraison);
    }
}
