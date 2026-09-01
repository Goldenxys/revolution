<?php

namespace App\Filament\Pages;

use App\Mail\RecapJournalier;
use App\Models\Client;
use App\Models\Commande;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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
     * @return array<string, int>
     */
    private function indicateursPour(string $date): array
    {
        $commandesDuJour = Commande::query()->whereDate('created_at', $date);

        return [
            'commandes' => (clone $commandesDuJour)->count(),
            'nouveaux_clients' => Client::query()->whereDate('premiere_commande_at', $date)->count(),
            'my_verse' => (clone $commandesDuJour)->where('collection', 'my_verse')->count(),
            'total_frais' => (int) (clone $commandesDuJour)->sum('frais_livraison'),
        ];
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
            'commandes' => $construire($actuels['commandes'], $veille['commandes']),
            'nouveaux_clients' => $construire($actuels['nouveaux_clients'], $veille['nouveaux_clients']),
            'my_verse' => $construire($actuels['my_verse'], $veille['my_verse']),
            'total_frais' => $construire($actuels['total_frais'], $veille['total_frais'], enFrancs: true),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Commande::query()
                    ->with('client')
                    ->whereDate('created_at', $this->date)
            )
            ->heading('Commandes du '.$this->libelleJour())
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Heure')->time('H:i'),

                TextColumn::make('client.nom')
                    ->label('Client')
                    ->description(fn (Commande $commande) => $commande->client->telephone),

                TextColumn::make('collection')
                    ->label('Collection')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'my_verse' ? 'MY VERSE' : 'Autre collection')
                    ->color(fn (string $state) => $state === 'my_verse' ? 'gold' : 'gray'),

                TextColumn::make('article')
                    ->label('Article')
                    ->state(function (Commande $commande) {
                        if ($commande->estMyVerse()) {
                            return trim(($commande->verset_reference ?: 'Verset').' · '.Str::limit($commande->verset_texte ?: '—', 40));
                        }

                        return trim(($commande->type_article ?? '').' « '.($commande->nom_article ?? '').' »');
                    })
                    ->wrap(),

                TextColumn::make('taille_couleur')
                    ->label('Taille / couleur')
                    ->state(fn (Commande $commande) => trim($commande->taille.($commande->couleur ? ' · '.$commande->couleur : ''))),

                TextColumn::make('commune')
                    ->label('Commune')
                    ->description(fn (Commande $commande) => Francais::frais($commande->frais_livraison).($commande->quartier ? ' · '.$commande->quartier : '')),

                TextColumn::make('mode_livraison')
                    ->label('Livraison')
                    ->badge()
                    ->color(fn (Commande $commande) => $commande->estYango() ? 'primary' : 'gray')
                    ->formatStateUsing(fn (Commande $commande) => $commande->estYango()
                        ? 'Yango — '.Francais::dateHeureLongue($commande->date_souhaitee, $commande->heure_souhaitee)
                        : 'Selon les zones'),

                TextColumn::make('numero_commande_client')
                    ->label('Fidélité')
                    ->formatStateUsing(fn (Commande $commande) => $commande->numero_commande_client <= 1
                        ? 'Nouveau'
                        : Francais::ordinal($commande->numero_commande_client).' cde')
                    ->badge()
                    ->color(fn (Commande $commande) => $commande->numero_commande_client <= 1 ? 'success' : 'gold'),
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
                    $commandes = Commande::with('client')->whereDate('created_at', $this->date)->orderBy('created_at')->get();

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
