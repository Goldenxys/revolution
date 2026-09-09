<?php

namespace Tests\Feature\V2;

use App\Mail\DemandeRecue;
use App\Mail\RecuCommande;
use App\Mail\VenteRealisee;
use App\Models\Article;
use App\Models\Client;
use App\Models\CollectionCatalogue;
use App\Models\Commande;
use App\Models\TypeArticle;
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

    public function test_la_validation_genere_le_pdf_et_envoie_les_deux_emails(): void
    {
        Storage::fake('local');
        Mail::fake();
        $gerante = User::factory()->create();
        [, $commande] = $this->demandeValidable();

        $commande->valider($gerante);

        Storage::disk('local')->assertExists(RecuPdf::cheminRelatif($commande));
        Mail::assertQueued(RecuCommande::class, fn ($m) => $m->hasTo('cliente@example.com'));
        Mail::assertQueued(VenteRealisee::class);

        $this->assertDatabaseHas('commande_journal', ['commande_id' => $commande->id, 'evenement' => 'pdf_genere']);
    }

    public function test_sans_email_cliente_seul_le_mail_gerante_part(): void
    {
        Storage::fake('local');
        Mail::fake();
        $gerante = User::factory()->create();
        [, $commande] = $this->demandeValidable(email: null);

        $commande->valider($gerante);

        Mail::assertNotQueued(RecuCommande::class);
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

        $collection = CollectionCatalogue::create(['nom' => 'C', 'slug' => 'c']);
        $type = TypeArticle::create(['nom' => 'T', 'slug' => 't', 'gere_tailles' => true, 'gere_couleurs' => true]);
        $article = Article::create(['collection_id' => $collection->id, 'type_article_id' => $type->id, 'nom' => 'Art', 'slug' => 'art', 'prix' => 7000]);

        $this->post(route('commande.demande.store'), [
            'nom' => 'Aya', 'telephone' => '0102030405', 'email' => 'aya@example.com',
            'commune' => 'Cocody', 'mode_livraison' => 'livreur',
            'souhaits' => [['article_id' => $article->id, 'quantite' => 1]],
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
