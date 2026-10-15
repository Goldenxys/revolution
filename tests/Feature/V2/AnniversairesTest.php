<?php

namespace Tests\Feature\V2;

use App\Console\Commands\AlerterAnniversairesProches;
use App\Mail\AnniversairesProches;
use App\Models\Client;
use App\Models\Commande;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AnniversairesTest extends TestCase
{
    use RefreshDatabase;

    public function test_libelle_naissance_formate_jour_et_mois(): void
    {
        $client = Client::create([
            'cle' => '00000001', 'nom' => 'Aya', 'telephone' => '00000001', 'nb_commandes' => 0,
            'naissance_jour' => 15, 'naissance_mois' => 3,
        ]);

        $this->assertSame('15 mars', $client->libelleNaissance());
    }

    public function test_libelle_naissance_est_null_si_non_renseigne(): void
    {
        $client = Client::create(['cle' => '00000001', 'nom' => 'Aya', 'telephone' => '00000001', 'nb_commandes' => 0]);

        $this->assertNull($client->libelleNaissance());
    }

    /**
     * Le formulaire V2 enregistre la date de naissance sur le client (une
     * seule fois), jamais dupliquée par commande (souhaits_client ne la
     * contient pas).
     */
    public function test_la_demande_enregistre_la_date_de_naissance_sur_le_client(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();

        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya Kouassi', 'telephone' => '0102030405',
            'collection' => 'autre', 'commune' => 'Cocody', 'mode_livraison' => 'livreur',
            'naissance_jour' => 15, 'naissance_mois' => 3,
        ])->assertRedirect();

        $client = Client::first();
        $this->assertSame(15, $client->naissance_jour);
        $this->assertSame(3, $client->naissance_mois);
        $this->assertArrayNotHasKey('naissance_jour', Commande::first()->souhaits_client ?? []);
    }

    /**
     * Une demande suivante laissée vide ne doit jamais écraser la date de
     * naissance déjà connue — une seule valeur par cliente.
     */
    public function test_une_demande_suivante_sans_naissance_necrase_pas_la_valeur_connue(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();

        $payload = [
            'nom' => 'Aya Kouassi', 'telephone' => '0102030405',
            'collection' => 'autre', 'commune' => 'Cocody', 'mode_livraison' => 'livreur',
        ];

        $this->post(route('commande.demande.store'), $payload + ['naissance_jour' => 15, 'naissance_mois' => 3])->assertRedirect();
        $this->post(route('commande.demande.store'), $payload)->assertRedirect();

        $client = Client::first();
        $this->assertSame(15, $client->naissance_jour);
        $this->assertSame(3, $client->naissance_mois);
    }

    public function test_une_date_de_naissance_impossible_est_refusee(): void
    {
        User::factory()->create();

        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya', 'telephone' => '0102030405',
            'collection' => 'autre', 'commune' => 'Cocody', 'mode_livraison' => 'livreur',
            'naissance_jour' => 31, 'naissance_mois' => 2,
        ])->assertSessionHasErrors('naissance_jour');

        $this->assertSame(0, Commande::count());
    }

    /**
     * La date de livraison souhaitée devient disponible (facultative) pour
     * un livreur normal, pas seulement pour Yango.
     */
    public function test_la_date_souhaitee_est_facultative_pour_un_livreur_normal(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();

        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya', 'telephone' => '0102030405',
            'collection' => 'autre', 'commune' => 'Cocody', 'mode_livraison' => 'livreur',
            'date_souhaitee' => now()->addDays(3)->toDateString(),
        ])->assertRedirect();

        $this->assertSame(now()->addDays(3)->toDateString(), Commande::first()->date_souhaitee->toDateString());
    }

    public function test_la_date_souhaitee_reste_obligatoire_pour_yango(): void
    {
        User::factory()->create();

        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya', 'telephone' => '0102030405',
            'collection' => 'autre', 'commune' => 'Cocody', 'mode_livraison' => 'yango',
        ])->assertSessionHasErrors('date_souhaitee');
    }

    public function test_la_commande_alerte_anniversaires_proches_envoie_un_mail_si_match(): void
    {
        Mail::fake();

        Client::create([
            'cle' => '00000001', 'nom' => 'Aya', 'telephone' => '0700000001', 'nb_commandes' => 0,
            'naissance_jour' => now()->addDays(2)->day, 'naissance_mois' => now()->addDays(2)->month,
        ]);
        Client::create([
            'cle' => '00000002', 'nom' => 'Koffi', 'telephone' => '0700000002', 'nb_commandes' => 0,
            'naissance_jour' => now()->addDays(10)->day, 'naissance_mois' => now()->addDays(10)->month,
        ]);

        $this->artisan(AlerterAnniversairesProches::class)->assertExitCode(0);

        Mail::assertQueued(AnniversairesProches::class, fn (AnniversairesProches $m) => $m->clients->count() === 1 && $m->clients->first()->nom === 'Aya');
    }

    public function test_la_commande_alerte_anniversaires_proches_nenvoie_rien_sans_match(): void
    {
        Mail::fake();

        Client::create([
            'cle' => '00000001', 'nom' => 'Aya', 'telephone' => '0700000001', 'nb_commandes' => 0,
            'naissance_jour' => now()->addDays(10)->day, 'naissance_mois' => now()->addDays(10)->month,
        ]);

        $this->artisan(AlerterAnniversairesProches::class)->assertExitCode(0);

        Mail::assertNotQueued(AnniversairesProches::class);
    }
}
