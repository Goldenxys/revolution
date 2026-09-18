<?php

namespace App\Filament\Pages;

use App\Filament\Resources\DemandeResource;
use App\Filament\Support\GuideAction;
use App\Mail\RecapJournalier;
use App\Models\Client;
use App\Models\Commande;
use App\Models\CommandeLigne;
use App\Models\Parametre;
use App\Support\Francais;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class TableauDeBord extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Tableau de bord';

    protected static ?int $navigationSort = -10;

    protected static string $view = 'filament.pages.tableau-de-bord';

    /**
     * Fait de cette page le tableau de bord du panneau (route racine /admin).
     */
    protected static ?string $slug = '/';

    public string $date;

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function getTitle(): string
    {
        return 'Tableau de bord';
    }

    protected function carbonDate(): Carbon
    {
        return Carbon::parse($this->date);
    }

    public function jourPrecedent(): void
    {
        $this->date = $this->carbonDate()->subDay()->toDateString();
        $this->resetTable();
    }

    public function jourSuivant(): void
    {
        if (! $this->peutAvancer()) {
            return;
        }

        $this->date = $this->carbonDate()->addDay()->toDateString();
        $this->resetTable();
    }

    public function peutAvancer(): bool
    {
        return $this->carbonDate()->lt(Carbon::today());
    }

    public function estAujourdhui(): bool
    {
        return $this->carbonDate()->isToday();
    }

    public function libelleJour(): string
    {
        $libelle = Francais::dateLongue($this->date);

        return $this->estAujourdhui() ? "{$libelle} · aujourd'hui" : $libelle;
    }

    /**
     * @return array<string, int>
     */
    public function indicateurs(): array
    {
        return $this->indicateursPour($this->date);
    }

    /**
     * V2 §9 — le chiffre d'affaires se compte sur les commandes VALIDÉES,
     * à la date de `validee_at`, jamais `created_at`. Les frais de livraison
     * ne sont pas suivis ici : ils n'entrent jamais dans le chiffre
     * d'affaires et cette agrégation n'a aucune utilité de pilotage.
     *
     * @return array<string, int>
     */
    private function indicateursPour(string $date): array
    {
        $valideesDuJour = Commande::query()->validees()->whereDate('validee_at', $date);

        return [
            'ca' => (int) (clone $valideesDuJour)->sum('total_articles'),
            'ventes' => (clone $valideesDuJour)->count(),
            'nouveaux_clients' => Client::query()->whereDate('premiere_commande_at', $date)->count(),
        ];
    }

    /**
     * Nombre de demandes en attente de validation — le premier regard du
     * matin (V2 §9, widget 1). Non borné à une date : c'est un « à faire ».
     */
    public function demandesAValider(): int
    {
        return Commande::query()->where('statut', 'en_attente')->count();
    }

    public function lienDemandes(): string
    {
        return DemandeResource::getUrl('index');
    }

    /**
     * @return array<string, int>
     */
    public function indicateursDuMois(): array
    {
        $debut = $this->carbonDate()->copy()->startOfMonth();
        $fin = $this->carbonDate()->copy()->endOfMonth();

        $valideesDuMois = Commande::query()->validees()->whereBetween('validee_at', [$debut, $fin]);

        return [
            'ca' => (int) (clone $valideesDuMois)->sum('total_articles'),
            'nouveaux_clients' => Client::query()->whereBetween('premiere_commande_at', [$debut, $fin])->count(),
        ];
    }

    /**
     * Top articles du mois, par quantité validée (V2 §9, widget 7).
     *
     * @return Collection<int, object>
     */
    public function topArticlesDuMois(): Collection
    {
        $debut = $this->carbonDate()->copy()->startOfMonth();
        $fin = $this->carbonDate()->copy()->endOfMonth();

        return CommandeLigne::query()
            ->selectRaw('article_nom, SUM(quantite) as quantite')
            ->whereHas('commande', fn ($q) => $q->validees()->whereBetween('validee_at', [$debut, $fin]))
            ->groupBy('article_nom')
            ->orderByDesc('quantite')
            ->limit(5)
            ->get();
    }

    /**
     * Indicateurs du jour affiché, chacun accompagné d'un delta réel (jamais
     * inventé) par rapport à la veille de ce même jour — calculé depuis les
     * commandes effectivement enregistrées, pas un pourcentage fabriqué.
     *
     * @return array<string, array{valeur: int, delta: int, sens: string, libelle_delta: string}>
     */
    public function indicateursAvecTendance(): array
    {
        $actuels = $this->indicateurs();
        $veille = $this->indicateursPour($this->carbonDate()->copy()->subDay()->toDateString());

        $construire = function (int $valeur, int $valeurVeille, bool $enFrancs = false): array {
            $delta = $valeur - $valeurVeille;
            $sens = $delta > 0 ? 'hausse' : ($delta < 0 ? 'baisse' : 'stable');

            $texteDelta = match (true) {
                $delta === 0 => 'stable vs hier',
                $delta > 0 => '+'.($enFrancs ? Francais::frais($delta) : $delta).' vs hier',
                default => '−'.($enFrancs ? Francais::frais(abs($delta)) : abs($delta)).' vs hier',
            };

            return [
                'valeur' => $valeur,
                'delta' => $delta,
                'sens' => $sens,
                'libelle_delta' => $texteDelta,
            ];
        };

        return [
            'ca' => $construire($actuels['ca'], $veille['ca'], enFrancs: true),
            'ventes' => $construire($actuels['ventes'], $veille['ventes']),
            'nouveaux_clients' => $construire($actuels['nouveaux_clients'], $veille['nouveaux_clients']),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Commande::query()
                    ->with(['client', 'lignes'])
                    ->validees()
                    ->whereDate('validee_at', $this->date)
            )
            ->heading('Ventes validées du '.$this->libelleJour())
            ->defaultSort('validee_at', 'desc')
            ->columns([
                TextColumn::make('validee_at')->label('Validée')->time('H:i'),

                TextColumn::make('client.nom')
                    ->label('Cliente')
                    ->description(fn (Commande $commande) => $commande->client->telephone),

                TextColumn::make('article')
                    ->label('Articles')
                    ->state(fn (Commande $commande) => $commande->libelle_article)
                    ->wrap(),

                TextColumn::make('total_articles')
                    ->label('Chiffre d\'affaires')
                    ->formatStateUsing(fn ($state) => Francais::frais((int) $state))
                    ->weight('bold'),

                TextColumn::make('commune')
                    ->label('Livraison')
                    ->badge()
                    ->color(fn (Commande $commande) => $commande->estYango() ? 'primary' : 'gray')
                    ->formatStateUsing(fn (Commande $commande) => $commande->commune.' — '.($commande->estYango() ? 'Yango' : 'livreur')),

                TextColumn::make('numero_commande_client')
                    ->label('Fidélité')
                    ->formatStateUsing(fn (Commande $commande) => ($commande->numero_commande_client ?? 1) <= 1
                        ? 'Nouvelle'
                        : Francais::ordinal($commande->numero_commande_client).' cde')
                    ->badge()
                    ->color(fn (Commande $commande) => ($commande->numero_commande_client ?? 1) <= 1 ? 'success' : 'gold'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Commande $commande) => route('filament.admin.resources.commandes.view', $commande)),
            ])
            ->paginated([10, 25, 50]);
    }

    protected function getHeaderActions(): array
    {
        return [
            GuideAction::make('dashboard'),

            Action::make('exporter_jour')
                ->label('Exporter la journée (CSV)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn () => route('admin.export.commandes', ['date' => $this->date]))
                ->openUrlInNewTab(),

            Action::make('exporter_clients')
                ->label('Exporter les clients (CSV)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('admin.export.clients'))
                ->openUrlInNewTab(),

            Action::make('recap_mail')
                ->label('Recevoir le récap du jour par mail')
                ->icon('heroicon-o-envelope')
                ->color('primary')
                ->action(function () {
                    $commandes = Commande::with(['client', 'lignes'])
                        ->validees()
                        ->whereDate('validee_at', $this->date)
                        ->orderBy('validee_at')
                        ->get();

                    try {
                        Mail::to(Parametre::emailReception())
                            ->queue(new RecapJournalier($this->carbonDate(), $commandes, $this->indicateurs()));

                        Notification::make()
                            ->title('Récap envoyé')
                            ->body('Le récapitulatif du '.$this->libelleJour().' part vers '.Parametre::emailReception().'.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Échec de l\'envoi')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
