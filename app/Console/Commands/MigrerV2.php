<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Commande;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migration de données V2 (§10 du document) — « la commande validée par la
 * gérante ». Les commandes déjà passées sont réelles et payées : elles
 * doivent rester dans l'historique et dans le chiffre d'affaires, avec
 * leurs montants d'origine.
 *
 * En une passe, réversible, jamais destructive :
 *
 *  | ancien statut | nouveau statut            | validee_at |
 *  |---------------|--------------------------|------------|
 *  | nouvelle      | en_attente               | NULL       |
 *  | confirmee     | validee                  | created_at |
 *  | preparation   | en_livraison (via validee)| created_at |
 *  | livree        | livree                   | created_at |
 *  | annulee       | annulee                  | NULL       |
 *
 * Puis, pour toute commande ayant un validee_at : total_articles /
 * total_a_payer / recu_token renseignés, souhaits_client laissé NULL (ces
 * commandes n'ont pas connu la phase de demande).
 *
 * Sur les clientes : statut, numero_client (par ordre d'ancienneté),
 * premiere_commande_at, ca_cumule et nb_commandes recalculés sur les
 * seules commandes validées.
 *
 * Sans --execute, la commande ne fait qu'AFFICHER le rapport avant/après
 * (tout est calculé dans une transaction annulée). Faites-la d'abord
 * tourner ainsi sur une copie de la base.
 */
class MigrerV2 extends Command
{
    protected $signature = 'revolution:migrer-v2 {--execute : Écrit réellement les changements (sinon simple simulation)}';

    protected $description = 'Aligne les commandes et clientes existantes sur le modèle V2 (demande → validation)';

    /** Ancien statut → nouveau statut cible. */
    private const MAP_STATUT = [
        'nouvelle' => 'en_attente',
        'confirmee' => 'validee',
        'preparation' => 'en_livraison',
        'livree' => 'livree',
        'annulee' => 'annulee',
    ];

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');

        $this->info($execute
            ? 'Migration V2 — écriture réelle.'
            : 'Migration V2 — SIMULATION (aucune écriture). Relancez avec --execute pour appliquer.');
        $this->newLine();

        $avant = $this->photographier();

        DB::beginTransaction();

        try {
            $this->migrerCommandes();
            $this->migrerClients();

            $apres = $this->photographier();

            $this->afficherRapport($avant, $apres);

            if ($execute) {
                DB::commit();
                $this->newLine();
                $this->info('✅ Migration appliquée.');
            } else {
                DB::rollBack();
                $this->newLine();
                $this->warn('↩️  Simulation : tout a été annulé. Vérifiez le rapport ci-dessus, puis relancez avec --execute.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Échec, transaction annulée : '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function photographier(): array
    {
        return [
            'commandes_par_statut' => Commande::query()
                ->selectRaw('statut, COUNT(*) as n')
                ->groupBy('statut')
                ->pluck('n', 'statut')
                ->all(),
            'ca_total' => (int) Commande::query()->validees()->sum('total_articles'),
            'clients' => Client::query()->count(),
            'clients_reels' => Client::query()->where('statut', 'client')->count(),
            'clients_sans_numero' => Client::query()->whereNull('numero_client')->count(),
        ];
    }

    private function migrerCommandes(): void
    {
        Commande::query()
            ->whereIn('statut', array_keys(self::MAP_STATUT))
            ->orderBy('id')
            ->chunkById(200, function ($commandes) {
                foreach ($commandes as $commande) {
                    $nouveauStatut = self::MAP_STATUT[$commande->statut];
                    $estValidee = in_array($nouveauStatut, ['validee', 'en_livraison', 'livree'], true);

                    $attributs = ['statut' => $nouveauStatut];

                    if ($estValidee && $commande->validee_at === null) {
                        $attributs['validee_at'] = $commande->created_at;
                    }

                    if ($estValidee) {
                        $sousTotal = (int) ($commande->sous_total ?? 0);
                        $remise = (int) ($commande->remise_montant ?? 0);
                        $totalArticles = max(0, $sousTotal - $remise);

                        // Ne réécrase pas un montant déjà figé (idempotence).
                        if ((int) $commande->total_articles === 0) {
                            $attributs['total_articles'] = $totalArticles;
                            $attributs['total_a_payer'] = $totalArticles + (int) $commande->frais_livraison;
                        }

                        if (blank($commande->recu_token)) {
                            $attributs['recu_token'] = Commande::genererRecuToken();
                        }
                    }

                    $commande->forceFill($attributs)->saveQuietly();
                }
            });
    }

    private function migrerClients(): void
    {
        // numero_client attribué par ordre d'ancienneté (id croissant), sans
        // écraser un numéro déjà présent.
        $prochainRang = (int) Client::query()->max('id');

        Client::query()->orderBy('id')->chunkById(200, function ($clients) {
            foreach ($clients as $client) {
                $validees = $client->commandes()->validees();

                $nbValidees = (clone $validees)->count();
                $caCumule = (int) (clone $validees)->sum('total_articles');

                $bornes = (clone $validees)
                    ->selectRaw('MIN(validee_at) as premiere, MAX(validee_at) as derniere')
                    ->first();

                $attributs = [
                    'nb_commandes' => $nbValidees,
                    'ca_cumule' => $caCumule,
                    'statut' => $nbValidees > 0 ? 'client' : 'prospect',
                    'premiere_commande_at' => $bornes?->premiere,
                    'derniere_commande_at' => $bornes?->derniere,
                ];

                if (blank($client->numero_client)) {
                    $attributs['numero_client'] = $client->genererNumeroClient();
                }

                $client->forceFill($attributs)->saveQuietly();
            }
        });
    }

    /**
     * @param  array<string, mixed>  $avant
     * @param  array<string, mixed>  $apres
     */
    private function afficherRapport(array $avant, array $apres): void
    {
        $this->newLine();
        $this->line('<comment>Commandes par statut</comment>');

        $statuts = collect($avant['commandes_par_statut'])
            ->keys()
            ->merge(collect($apres['commandes_par_statut'])->keys())
            ->unique()
            ->sort()
            ->values();

        $this->table(
            ['Statut', 'Avant', 'Après'],
            $statuts->map(fn ($s) => [
                $s,
                $avant['commandes_par_statut'][$s] ?? 0,
                $apres['commandes_par_statut'][$s] ?? 0,
            ])->all()
        );

        $this->line('<comment>Totaux</comment>');
        $this->table(
            ['Indicateur', 'Avant', 'Après'],
            [
                ['Chiffre d\'affaires (Σ total_articles validés)', number_format($avant['ca_total'], 0, ',', ' ').' F', number_format($apres['ca_total'], 0, ',', ' ').' F'],
                ['Clientes', $avant['clients'], $apres['clients']],
                ['Clientes « réelles »', $avant['clients_reels'], $apres['clients_reels']],
                ['Clientes sans numéro', $avant['clients_sans_numero'], $apres['clients_sans_numero']],
            ]
        );

        $this->newLine();
        $this->warn('Si le chiffre d\'affaires calculé ne correspond pas à ce que la gérante a en tête, ARRÊTEZ-VOUS et posez la question avant d\'exécuter.');
    }
}
