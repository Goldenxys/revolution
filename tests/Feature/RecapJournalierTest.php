<?php

namespace Tests\Feature;

use App\Filament\Pages\TableauDeBord;
use App\Mail\RecapJournalier;
use App\Models\Client;
use App\Models\Commande;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class RecapJournalierTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_bouton_recap_du_jour_envoie_bien_le_mail(): void
    {
        Mail::fake();

        $gerante = User::factory()->create();

        $client = Client::create([
            'cle' => '00000009',
            'nom' => 'Fatou Diarra',
            'telephone' => '0900000009',
            'nb_commandes' => 1,
            'premiere_commande_at' => now(),
            'derniere_commande_at' => now(),
        ]);

        Commande::create([
            'client_id' => $client->id,
            'collection' => 'my_verse',
            'taille' => 'M',
            'commune' => 'Cocody',
            'frais_livraison' => 1500,
            'mode_livraison' => 'livreur',
            'numero_commande_client' => 1,
        ]);

        Livewire::actingAs($gerante)
            ->test(TableauDeBord::class)
            ->callAction('recap_mail')
            ->assertNotified('Récap envoyé');

        Mail::assertQueued(RecapJournalier::class, function (RecapJournalier $mail) {
            return $mail->indicateurs['commandes'] === 1
                && $mail->commandes->first()->client->nom === 'Fatou Diarra';
        });
    }
}
