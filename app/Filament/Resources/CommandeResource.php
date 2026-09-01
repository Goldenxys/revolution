<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommandeResource\Pages;
use App\Models\Commande;
use App\Support\Francais;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Forms\Get;
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
     * Pas de création manuelle depuis Filament : une commande naît toujours
     * du formulaire public. En revanche, la corriger ou la supprimer en cas
     * d'erreur de saisie ou de doublon reste nécessaire — voir table().
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            FormSection::make('Cliente')
                ->columns(2)
                ->schema([
                    TextInput::make('client_nom')
                        ->label('Nom et prénom')
                        ->required()
                        ->maxLength(120)
                        ->afterStateHydrated(function (TextInput $component, ?Commande $record) {
                            $component->state($record?->client?->nom);
                        }),

                    TextInput::make('client_telephone')
                        ->label('Téléphone')
                        ->required()
                        ->maxLength(30)
                        ->afterStateHydrated(function (TextInput $component, ?Commande $record) {
                            $component->state($record?->client?->telephone);
                        }),
                ]),

            FormSection::make('Article')
                ->columns(2)
                ->schema([
                    Select::make('taille')
                        ->label('Taille')
                        ->options(array_combine(config('revolution.tailles'), config('revolution.tailles')))
                        ->native(false)
                        ->visible(fn (Get $get) => ! in_array($get('type_article'), config('revolution.types_sans_taille'), true))
                        ->required(fn (Get $get) => ! in_array($get('type_article'), config('revolution.types_sans_taille'), true)),

                    Select::make('couleur')
                        ->label('Couleur')
                        ->options(array_combine(config('revolution.couleurs'), config('revolution.couleurs')))
                        ->native(false)
                        ->placeholder('Sans préférence'),

                    TextInput::make('verset_reference')
                        ->label('Verset')
                        ->maxLength(120)
                        ->placeholder('Ex. Philippiens 4:13')
                        ->visible(fn (?Commande $record) => $record?->estMyVerse() ?? true),

                    Textarea::make('verset_texte')
                        ->label('Texte du verset')
                        ->rows(3)
                        ->columnSpanFull()
                        ->helperText('Le verset est imprimé tel qu\'écrit ici : vérifiez l\'orthographe.')
                        ->visible(fn (?Commande $record) => $record?->estMyVerse() ?? true),

                    Select::make('type_article')
                        ->label('Type d\'article')
                        ->options(array_combine(config('revolution.types'), config('revolution.types')))
                        ->native(false)
                        ->live()
                        ->visible(fn (?Commande $record) => ! ($record?->estMyVerse() ?? false)),

                    TextInput::make('nom_article')
                        ->label('Nom de l\'article')
                        ->maxLength(190)
                        ->placeholder('Ex. Couronne d\'épines')
                        ->visible(fn (?Commande $record) => ! ($record?->estMyVerse() ?? false)),
                ]),

            FormSection::make('Livraison')
                ->columns(2)
                ->schema([
                    Select::make('commune')
                        ->label('Commune')
                        ->options(array_combine(array_keys(config('revolution.communes')), array_keys(config('revolution.communes'))))
                        ->native(false)
                        ->required()
                        ->helperText('Les frais de livraison sont recalculés automatiquement si la commune change.'),

                    TextInput::make('quartier')
                        ->label('Quartier, point de repère')
                        ->maxLength(190),

                    Select::make('mode_livraison')
                        ->label('Mode de livraison')
                        ->options([
                            'yango' => 'Yango livraison',
                            'livreur' => 'Livreur normal',
                        ])
                        ->native(false)
                        ->required()
                        ->live(),

                    // Champ de remplissage pour garder la grille à 2 colonnes
                    // propre quand les champs Yango sont masqués.
                    DatePicker::make('date_souhaitee')
                        ->label('Date souhaitée')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->visible(fn (Get $get) => $get('mode_livraison') === 'yango'),

                    TimePicker::make('heure_souhaitee')
                        ->label('Heure souhaitée')
                        ->native(false)
                        ->seconds(false)
                        ->visible(fn (Get $get) => $get('mode_livraison') === 'yango'),
                ]),
        ]);
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
                    ->state(fn (Commande $commande) => collect([$commande->taille, $commande->couleur])->filter()->implode(' · ') ?: '—'),

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

                // Pas de page 'edit' déclarée dans getPages() : Filament
                // ouvre donc ce formulaire dans une modale plutôt que de
                // naviguer vers un écran séparé.
                Tables\Actions\EditAction::make()
                    ->modalHeading('Modifier la commande')
                    ->modalDescription('Corrigez une faute de frappe, une taille ou un verset mal saisi, etc.')
                    ->modalWidth('2xl')
                    ->modalSubmitActionLabel('Enregistrer les modifications')
                    ->using(fn (Commande $record, array $data): Commande => static::sauvegarderModification($record, $data)),

                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Supprimer cette commande ?')
                    ->modalDescription('Cette action est définitive. Le compteur de fidélité et les dates de la cliente seront recalculés automatiquement à partir de ses commandes restantes.')
                    ->modalSubmitActionLabel('Supprimer définitivement'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalHeading('Supprimer les commandes sélectionnées ?')
                        ->modalDescription('Cette action est définitive pour chacune d\'elles. Les compteurs de fidélité des clientes concernées seront recalculés automatiquement.')
                        ->modalSubmitActionLabel('Supprimer définitivement'),
                ]),
            ]);
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
                    TextEntry::make('taille')->label('Taille')->placeholder('Taille unique'),
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

    /**
     * Logique d'enregistrement partagée par la modale d'édition (table) et
     * le bouton « Modifier » de l'écran de détail : nom/téléphone reportés
     * sur la cliente (champs virtuels, absents du modèle Commande), frais
     * de livraison recalculés si la commune change, date/heure Yango
     * effacées si le mode de livraison n'est plus Yango.
     */
    public static function sauvegarderModification(Commande $record, array $data): Commande
    {
        $nomClient = $data['client_nom'] ?? null;
        $telephoneClient = $data['client_telephone'] ?? null;
        unset($data['client_nom'], $data['client_telephone']);

        if ($record->client && (filled($nomClient) || filled($telephoneClient))) {
            $record->client->update(array_filter([
                'nom' => $nomClient,
                'telephone' => $telephoneClient,
            ], fn ($valeur) => filled($valeur)));
        }

        if (isset($data['commune'])) {
            $data['frais_livraison'] = config('revolution.communes')[$data['commune']]
                ?? $record->frais_livraison;
        }

        if (($data['mode_livraison'] ?? null) !== 'yango') {
            $data['date_souhaitee'] = null;
            $data['heure_souhaitee'] = null;
        }

        $record->update($data);

        return $record;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommandes::route('/'),
            'view' => Pages\ViewCommande::route('/{record}'),
        ];
    }
}
