<?php

namespace App\Console\Commands;

use App\Mail\AnniversairesProches;
use App\Models\Client;
use App\Models\Parametre;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Alerte la gérante par e-mail 2 jours avant l'anniversaire d'une cliente
 * (jour + mois renseignés au dépôt d'une demande, clients.naissance_jour/
 * naissance_mois) — pour proposer la carte anniversaire (+500 F, voir
 * ComposerDemande) au bon moment. Programmée quotidiennement
 * (routes/console.php) ; silencieuse si aucun anniversaire ne tombe dans
 * 2 jours.
 */
class AlerterAnniversairesProches extends Command
{
    protected $signature = 'revolution:alerter-anniversaires-proches';

    protected $description = 'Alerte la gérante par e-mail des anniversaires clientes dans 2 jours';

    public function handle(): int
    {
        $clients = Client::query()->anniversaireDansJours(2)->orderBy('nom')->get();

        if ($clients->isEmpty()) {
            $this->info('Aucun anniversaire dans 2 jours.');

            return self::SUCCESS;
        }

        Mail::to(Parametre::emailReception())->queue(new AnniversairesProches($clients));

        $this->info("{$clients->count()} anniversaire(s) dans 2 jours : e-mail envoyé.");

        return self::SUCCESS;
    }
}
