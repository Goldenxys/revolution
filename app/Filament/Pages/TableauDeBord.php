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
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
     * V2 §9 (révisé) — le chiffre d'affaires se compte sur les commandes
     * réellement COMPTÉES : livrées pour le flux V2 (`livree_at`), finales
     * dès la création pour le formulaire libre legacy (`created_at`) — voir
     * Commande::scopeComptees(). Une commande validée mais pas encore
     * livrée n'apparaît nulle part ici, cohérent avec Client.ca_cumule. Les
     * frais de livraison ne sont pas suivis ici : ils n'entrent jamais dans
     * le chiffre d'affaires et cette agrégation n'a aucune utilité de
     * pilotage.
     *
     * @return array<string, int>
     */
    private function indicateursPour(string $date): array
    {
        $compteesDuJour = $this->commandesComptees()->whereRaw('DATE(COALESCE(livree_at, created_at)) = ?', [$date]);

        return [
            'ca' => (int) (clone $compteesDuJour)->sum('total_articles'),
            'ventes' => (clone $compteesDuJour)->count(),
            'nouveaux_clients' => Client::query()->whereDate('premiere_commande_at', $date)->count(),
        ];
    }

    /**
     * Point d'entrée unique pour toutes les requêtes du tableau de bord
     * portant sur les commandes comptées (jour, mois, top articles, table,
     * récap e-mail) — évite que chaque site d'appel réécrive le même scope.
     */
    private function commandesComptees(): Builder
    {
        return Commande::query()->comptees();
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

        $compteesDuMois = $this->commandesComptees()
            ->whereRaw('COALESCE(livree_at, created_at) BETWEEN ? AND ?', [$debut, $fin]);

        return [
            'ca' => (int) (clone $compteesDuMois)->sum('total_articles'),
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
            ->whereHas('commande', fn ($q) => $q->comptees()
                ->whereRaw('COALESCE(livree_at, created_at) BETWEEN ? AND ?', [$debut, $fin]))
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
                $this->commandesComptees()
                    ->with(['client', 'lignes'])
                    ->whereRaw('DATE(COALESCE(livree_at, created_at)) = ?', [$this->date])
            )
            ->heading('Ventes du '.$this->libelleJour())
            ->defaultSort('livree_at', 'desc')
            ->columns([
                TextColumn::make('comptee_a')
                    ->label('Comptée')
                    ->state(fn (Commande $commande) => ($commande->livree_at ?? $commande->created_at)?->format('H:i'))
                    ->badge(fn (Commande $commande) => $commande->livree_at === null)
                    ->color('gray'),

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

            // Les deux exports fusionnent dans un seul menu : quatre boutons
            // séparés dans l'en-tête retombaient à la ligne et cassaient
            // l'alignement du titre sur les écrans moyens.
            ActionGroup::make([
                Action::make('exporter_jour')
                    ->label('Commandes du jour (CSV)')
                    ->icon('heroicon-o-shopping-bag')
                    ->url(fn () => route('admin.export.commandes', ['date' => $this->date]))
                    ->openUrlInNewTab(),

                Action::make('exporter_clients')
                    ->label('Clientes (CSV)')
                    ->icon('heroicon-o-users')
                    ->url(route('admin.export.clients'))
                    ->openUrlInNewTab(),
            ])
                ->label('Exporter')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray'),

            Action::make('recap_mail')
                ->label('Récap du jour par e-mail')
                ->icon('heroicon-o-envelope')
                ->color('primary')
                ->action(function () {
                    $commandes = $this->commandesComptees()
                        ->with(['client', 'lignes'])
                        ->whereRaw('DATE(COALESCE(livree_at, created_at)) = ?', [$this->date])
                        ->orderByRaw('COALESCE(livree_at, created_at)')
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
