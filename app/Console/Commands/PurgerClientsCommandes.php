<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Commande;
use App\Models\CommandeJournal;
use App\Models\CommandeLigne;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Bascule finale de la restructuration stock/livraison : repartir sur des
 * bases propres en supprimant DÉFINITIVEMENT toutes les clientes et
 * commandes existantes (et leurs lignes/journal). Le catalogue — articles,
 * variantes/stock, tailles, couleurs, types d'articles, collections —
 * n'est jamais touché.
 *
 * Sans --execute, la commande ne fait qu'AFFICHER les compteurs (aucune
 * écriture). Irréversible une fois lancée avec --execute : à n'exécuter
 * qu'après confirmation explicite de la gérante.
 */
class PurgerClientsCommandes extends Command
{
    protected $signature = 'revolution:purger-clients-commandes {--execute : Supprime réellement (sinon simple simulation)}';

    protected $description = 'Supprime définitivement toutes les clientes et commandes — catalogue conservé';

    public function handle(): int
    {
        $this->table(['Table', 'Lignes'], [
            ['clients', Client::count()],
            ['commandes', Commande::count()],
            ['commande_lignes', CommandeLigne::count()],
            ['commande_journal', CommandeJournal::count()],
        ]);

        if (! $this->option('execute')) {
            $this->warn('SIMULATION — aucune donnée supprimée. Relancez avec --execute pour supprimer réellement.');

            return self::SUCCESS;
        }

        if (! $this->confirm('IRRÉVERSIBLE : ceci supprime TOUTES les clientes et commandes (catalogue conservé). Continuer ?')) {
            $this->comment('Annulé.');

            return self::SUCCESS;
        }

        DB::transaction(function () {
            CommandeJournal::query()->delete();
            CommandeLigne::query()->delete();
            Commande::query()->delete();
            Client::query()->delete();
        });

        $this->info('Purge terminée. Catalogue (articles, variantes, tailles, couleurs, types, collections) inchangé.');

        return self::SUCCESS;
    }
}
