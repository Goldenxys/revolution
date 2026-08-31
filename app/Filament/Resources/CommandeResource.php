<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommandeResource\Pages;
use App\Models\Commande;
use App\Support\Francais;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CommandeResource extends Resource
{
    protected static ?string $model = Commande::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Commandes';

    protected static ?string $modelLabel = 'commande';

    protected static ?string $pluralModelLabel = 'commandes';

    protected static ?int $navigationSort = 20;

    /**
     * Lecture seule pour l'essentiel : pas de création ni d'édition
     * manuelle depuis Filament au MVP.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('client'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Heure')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('client.nom')
                    ->label('Client')
                    ->description(fn (Commande $commande) => $commande->client->telephone)
                    ->searchable(['nom', 'telephone'], query: function (Builder $query, string $search) {
                        $query->whereHas('client', function (Builder $q) use ($search) {
                            $q->where('nom', 'like', "%{$search}%")
                                ->orWhere('telephone', 'like', "%{$search}%");
                        });
                    }),

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
            ->filters([
                Filter::make('jour')
                    ->form([
                        DatePicker::make('jour')
                            ->label('Jour')
                            ->native(false)
                            ->maxDate(now())
                            ->default(now()->toDateString()),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! ($data['jour'] ?? null)) {
                            return $query;
                        }

                        return $query->whereDate('created_at', Carbon::parse($data['jour']));
                    })
                    ->indicateUsing(function (array $data) {
                        if (! ($data['jour'] ?? null)) {
                            return null;
                        }

                        return 'Jour : '.Francais::dateLongue($data['jour']);
                    })
                    ->default(['jour' => now()->toDateString()]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Commande')
                ->columns(2)
                ->schema([
                    TextEntry::make('reference')->label('Référence'),
                    TextEntry::make('created_at')->label('Reçue le')->dateTime('d/m/Y à H:i'),
                    TextEntry::make('client.nom')->label('Cliente'),
                    TextEntry::make('client.telephone')->label('Téléphone'),
                    TextEntry::make('client.email')->label('Email')->placeholder('—'),
                    TextEntry::make('collection')->label('Collection')
                        ->formatStateUsing(fn (string $state) => $state === 'my_verse' ? 'MY VERSE BY RÉVOLUTION' : 'Autre collection'),
                ]),

            InfolistSection::make('Article')
                ->columns(2)
                ->schema([
                    TextEntry::make('taille')->label('Taille'),
                    TextEntry::make('couleur')->label('Couleur')->placeholder('—'),
                    TextEntry::make('type_article')->label('Type d\'article')->placeholder('—'),
                    TextEntry::make('nom_article')->label('Nom de l\'article')->placeholder('—'),
                    TextEntry::make('verset_reference')->label('Verset')->placeholder('—'),
                    TextEntry::make('verset_texte')->label('Texte du verset')->placeholder('—')->columnSpanFull(),
                ]),

            InfolistSection::make('Livraison')
                ->columns(2)
                ->schema([
                    TextEntry::make('commune')->label('Commune'),
                    TextEntry::make('frais_livraison')->label('Frais')->formatStateUsing(fn ($state) => Francais::frais($state)),
                    TextEntry::make('quartier')->label('Quartier / repère')->placeholder('—'),
                    TextEntry::make('mode_livraison')->label('Mode de livraison')
                        ->formatStateUsing(fn (Commande $commande) => $commande->estYango()
                            ? 'Yango — '.Francais::dateHeureLongue($commande->date_souhaitee, $commande->heure_souhaitee)
                            : 'Livreur normal — selon les zones'),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommandes::route('/'),
            'view' => Pages\ViewCommande::route('/{record}'),
        ];
    }
}
