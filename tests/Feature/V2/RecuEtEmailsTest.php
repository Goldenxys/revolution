<?php

namespace Tests\Feature\V2;

use App\Events\CommandeValidee;
use App\Listeners\EnvoyerRecuEtNotifierVente;
use App\Mail\DemandeRecue;
use App\Mail\RecuCommande;
use App\Mail\VenteRealisee;
use App\Models\Client;
use App\Models\Commande;
use App\Models\User;
use App\Support\RecuPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecuEtEmailsTest extends TestCase
{
    use RefreshDatabase;

    private function demandeValidable(?string $email = 'cliente@example.com'): array
    {
        $client = Client::create([
            'cle' => Client::cleDepuisTelephone('0700000123'),
            'nom' => 'Marie Koffi', 'telephone' => '0700000123', 'email' => $email,
            'statut' => 'prospect', 'numero_client' => 'REV-C-0123', 'nb_commandes' => 0,
        ]);
        $commande = Commande::create([
            'client_id' => $client->id, 'commune' => 'Cocody', 'frais_livraison' => 1500,
            'mode_livraison' => 'livreur', 'statut' => 'en_attente',
        ]);
        $commande->lignes()->create([
            'article_nom' => 'Tee-shirt Couronne', 'taille_libelle' => 'XL', 'couleur_nom' => 'Blanc',
            'quantite' => 2, 'prix_unitaire' => 7000,
        ]);

        return [$client, $commande];
    }

    /**
     * Livre une commande validée — passe par « en_livraison » comme le
     * fait AvancerStatutAction, avant confirmerLivraison() qui déclenche
     * CommandeLivree (et donc le mail « vente réalisée » à la gérante).
     */
    private function livrer(Commande $commande, User $gerante): void
    {
        $commande->update(['statut' => 'en_livraison']);
        $commande->confirmerLivraison($gerante);
    }

    public function test_la_validation_genere_le_pdf_et_le_recu_puis_la_livraison_notifie_la_gerante(): void
    {
        Storage::fake('local');
        Mail::fake();
        $gerante = User::factory()->create();
        [, $commande] = $this->demandeValidable();

        $commande->valider($gerante);

        Storage::disk('local')->assertExists(RecuPdf::cheminRelatif($commande));
        Mail::assertQueued(RecuCommande::class, fn ($m) => $m->hasTo('cliente@example.com'));
        // La notification « vente réalisée » à la gérante attend la
        // livraison confirmée — pas encore envoyée à la validation.
        Mail::assertNotQueued(VenteRealisee::class);

        $this->assertDatabaseHas('commande_journal', ['commande_id' => $commande->id, 'evenement' => 'pdf_genere']);

        $this->livrer($commande, $gerante);

        Mail::assertQueued(VenteRealisee::class);
    }

    /**
     * L'objet ne porte plus la référence (mise à jour) : juste « Reçu
     * RÉVOLUTION », reconnaissable au premier coup d'œil dans la boîte mail.
     */
    public function test_le_recu_a_pour_objet_recu_revolution_sans_reference(): void
    {
        Storage::fake('local');
        Mail::fake();
        $gerante = User::factory()->create();
        [, $commande] = $this->demandeValidable();

        $commande->valider($gerante);

        Mail::assertQueued(RecuCommande::class, function (RecuCommande $mail) use ($commande) {
            $sujet = $mail->envelope()->subject;

            return $sujet === 'Reçu RÉVOLUTION' && ! str_contains($sujet, $commande->reference);
        });
    }

    /**
     * Garde-fou anti-doublon : si CommandeValidee est livré deux fois pour
     * la même commande (retry de file, événement rejoué), le reçu
     * automatique ne repart pas une seconde fois côté cliente.
     */
    public function test_le_recu_automatique_ne_part_jamais_deux_fois(): void
    {
        Storage::fake('local');
        Mail::fake();
        $gerante = User::factory()->create();
        [, $commande] = $this->demandeValidable();
        $commande->valider($gerante);
        $commande->refresh();

        // Deuxième livraison de l'événement, comme le ferait un retry.
        (new EnvoyerRecuEtNotifierVente)->handle(new CommandeValidee($commande));

        // 1 reçu cliente — jamais 2. Le mail « vente réalisée » gérante ne
        // dépend pas de cet événement (CommandeValidee) : il attend
        // CommandeLivree, jamais déclenché ici.
        Mail::assertQueuedCount(1);
        Mail::assertQueued(RecuCommande::class, 1);
        Mail::assertNotQueued(VenteRealisee::class);
    }

    public function test_sans_email_cliente_rien_ne_part_a_la_validation_puis_la_gerante_est_notifiee_a_la_livraison(): void
    {
        Storage::fake('local');
        Mail::fake();
        $gerante = User::factory()->create();
        [, $commande] = $this->demandeValidable(email: null);

        $commande->valider($gerante);

        Mail::assertNotQueued(RecuCommande::class);
        Mail::assertNotQueued(VenteRealisee::class);

        $this->livrer($commande, $gerante);

        // Le mail « vente réalisée » part chez la gérante (Parametre::emailReception()),
        // indépendamment de l'e-mail de la cliente.
        Mail::assertQueued(VenteRealisee::class);
    }

    public function test_le_lien_public_du_recu_sert_le_pdf(): void
    {
        Storage::fake('local');
        Mail::fake();
        $gerante = User::factory()->create();
        [, $commande] = $this->demandeValidable();
        $commande->valider($gerante);

        $this->get(route('recu.afficher', $commande->fresh()->recu_token))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('x-robots-tag', 'noindex, nofollow');
    }

    public function test_un_jeton_inconnu_donne_404(): void
    {
        $this->get(route('recu.afficher', 'jeton-bidon'))->assertNotFound();
    }

    public function test_le_formulaire_demande_accuse_reception_si_email(): void
    {
        Mail::fake();
        Notification::fake();
        User::factory()->create();

        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya', 'telephone' => '0102030405', 'email' => 'aya@example.com',
            'collection' => 'autre', 'commune' => 'Cocody', 'mode_livraison' => 'livreur',
        ]);

        Mail::assertQueued(DemandeRecue::class, fn ($m) => $m->hasTo('aya@example.com'));
    }

    public function test_le_message_whatsapp_contient_le_lien_de_recu(): void
    {
        Storage::fake('local');
        Mail::fake();
        $gerante = User::factory()->create();
        [, $commande] = $this->demandeValidable();
        $commande->valider($gerante);
        $commande->refresh();

        $message = $commande->messageWhatsappRecu();
        $this->assertStringContainsString($commande->recu_token, $message);
        $this->assertStringContainsString('À payer', $message);
        $this->assertStringStartsWith('https://wa.me/225', $commande->lienWhatsappRecu());
    }
}
