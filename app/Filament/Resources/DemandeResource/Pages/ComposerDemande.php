<?php

namespace App\Filament\Resources\DemandeResource\Pages;

use App\Filament\Resources\CommandeResource;
use App\Filament\Resources\DemandeResource;
use App\Models\Article;
use App\Models\ArticleVariante;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Couleur;
use App\Models\Taille;
use App\Support\Francais;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * Le compositeur de commande (V2 §5.2) — l'écran neuf qui porte tout le
 * travail de la gérante. À gauche (dans la vue) : ce que la cliente a dit,
 * en lecture seule. Ici, le formulaire : les lignes réelles qu'elle
 * compose, la remise qu'elle ajuste, et le bouton « Valider la commande »
 * qui déclenche Commande::valider() — le fait comptable.
 */
class ComposerDemande extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithRecord;

    protected static string $resource = DemandeResource::class;

    protected static string $view = 'filament.resources.demande-resource.pages.composer-demande';

    protected static ?string $title = 'Composer et valider';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(int|string $record): void
    {
        // Résolution non filtrée (pas via resolveRecord() qui applique le
        // scope `en_attente` de la ressource) : une demande déjà validée
        // doit pouvoir être résolue ici pour rediriger proprement.
        $this->record = Commande::query()->findOrFail($record);

        // Ne compose (et ne valide) que de vraies demandes en attente : une
        // commande déjà validée renvoie vers sa fiche.
        if ($this->record->statut !== 'en_attente') {
            $this->redirect(CommandeResource::getUrl('view', ['record' => $this->record]));

            return;
        }

        $this->form->fill([
            'lignes' => $this->lignesInitiales(),
            'remise_manuelle' => false,
            'remise_pourcentage' => $this->remiseProposee(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Ce que la gérante décide')
                    ->description('Les prix sont pré-remplis depuis le catalogue et restent modifiables. Le stock est indiqué, jamais bloquant.')
                    ->schema([
                        Repeater::make('lignes')
                            ->hiddenLabel()
                            ->addActionLabel('Ajouter une ligne')
                            ->reorderable(false)
                            ->defaultItems(1)
                            ->columns(12)
                            ->schema([
                                Select::make('article_id')
                                    ->label('Article')
                                    ->options(static::optionsArticles())
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->columnSpan(5)
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        $article = $state ? Article::find($state) : null;
                                        $set('prix_unitaire', $article?->prix);

                                        if (! $article?->gere_tailles) {
                                            $set('taille_id', null);
                                        }
                                        if (! $article?->gere_couleurs) {
                                            $set('couleur_id', null);
                                        }
                                    }),

                                Select::make('taille_id')
                                    ->label('Taille')
                                    ->options(fn () => Taille::query()->actives()->orderBy('ordre')->pluck('libelle', 'id'))
                                    ->native(false)
                                    ->live()
                                    ->columnSpan(2)
                                    ->visible(fn (Get $get) => (bool) Article::find($get('article_id'))?->gere_tailles)
                                    ->required(fn (Get $get) => (bool) Article::find($get('article_id'))?->gere_tailles),

                                Select::make('couleur_id')
                                    ->label('Couleur')
                                    ->options(fn () => Couleur::query()->actives()->orderBy('ordre')->pluck('nom', 'id'))
                                    ->native(false)
                                    ->live()
                                    ->columnSpan(2)
                                    ->visible(fn (Get $get) => (bool) Article::find($get('article_id'))?->gere_couleurs)
                                    ->required(fn (Get $get) => (bool) Article::find($get('article_id'))?->gere_couleurs),

                                TextInput::make('quantite')
                                    ->label('Qté')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->maxValue(50)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->columnSpan(1),

                                TextInput::make('prix_unitaire')
                                    ->label('Prix unit.')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->suffix('F')
                                    ->columnSpan(2),

                                Placeholder::make('stock_info')
                                    ->hiddenLabel()
                                    ->columnSpan(12)
                                    ->content(fn (Get $get): Htmlable => static::indicationStock(
                                        $get('article_id'),
                                        $get('taille_id'),
                                        $get('couleur_id'),
                                    )),

                                TextInput::make('verset')
                                    ->label('Verset (référence + texte)')
                                    ->columnSpan(6)
                                    ->visible(fn (Get $get) => (bool) Article::find($get('article_id'))?->collection?->verset_requis),

                                TextInput::make('modele')
                                    ->label('Modèle')
                                    ->columnSpan(6)
                                    ->visible(fn (Get $get) => filled(Article::find($get('article_id'))?->collection?->modeles_disponibles)),
                            ]),
                    ]),

                Section::make('Remise')
                    ->schema([
                        Placeholder::make('remise_proposee')
                            ->label('Proposition automatique (palier de fidélité)')
                            ->content(fn (): string => $this->remiseProposee() > 0
                                ? '− '.$this->remiseProposee().' %'
                                : 'Aucune remise (cette commande ne tombe pas sur un palier)'),

                        Toggle::make('remise_manuelle')
                            ->label('Ajuster la remise à la main')
                            ->live(),

                        TextInput::make('remise_pourcentage')
                            ->label('Remise appliquée (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->visible(fn (Get $get) => (bool) $get('remise_manuelle'))
                            ->live(onBlur: true),
                    ])
                    ->columns(1),

                Section::make('Montants')
                    ->schema([
                        Placeholder::make('recap')
                            ->hiddenLabel()
                            ->content(fn (Get $get): Htmlable => $this->recapMontants($get)),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reprendre_souhaits')
                ->label('Reprendre la demande de la cliente')
                ->icon('heroicon-o-arrow-down-on-square-stack')
                ->color('gray')
                ->action(function () {
                    $this->data['lignes'] = $this->lignesInitiales();
                    $this->form->fill($this->data);

                    Notification::make()
                        ->title('Demande reprise')
                        ->body('Taille, couleur et verset repris de la demande. Choisissez l\'article et vérifiez le prix.')
                        ->success()
                        ->send();
                }),

            Action::make('valider')
                ->label('Valider la commande')
                ->icon('heroicon-o-check-badge')
                ->requiresConfirmation()
                ->modalHeading('Valider la commande ?')
                ->modalDescription('Cette commande sera comptabilisée dans votre chiffre d\'affaires. Le stock des variantes vendues sera décrémenté et la cliente passera en commande validée.')
                ->modalSubmitActionLabel('Oui, valider')
                ->action('valider'),
        ];
    }

    public function valider(): void
    {
        $etat = $this->form->getState();
        $lignes = collect($etat['lignes'] ?? [])->filter(fn ($l) => filled($l['article_id'] ?? null));

        if ($lignes->isEmpty()) {
            Notification::make()->title('Ajoutez au moins une ligne avant de valider.')->danger()->send();

            return;
        }

        $remiseForcee = ($etat['remise_manuelle'] ?? false)
            ? (int) ($etat['remise_pourcentage'] ?? 0)
            : null;

        DB::transaction(function () use ($lignes, $remiseForcee) {
            // On repart de zéro : la composition remplace toute ébauche
            // précédente. Les copies figées deviennent la vérité historique.
            $this->record->lignes()->delete();

            foreach ($lignes as $l) {
                $article = Article::find($l['article_id']);
                $taille = filled($l['taille_id'] ?? null) ? Taille::find($l['taille_id']) : null;
                $couleur = filled($l['couleur_id'] ?? null) ? Couleur::find($l['couleur_id']) : null;

                $this->record->lignes()->create([
                    'article_id' => $article->id,
                    'taille_id' => $taille?->id,
                    'couleur_id' => $couleur?->id,
                    'article_nom' => $article->nom,
                    'taille_libelle' => $taille?->libelle,
                    'couleur_nom' => $couleur?->nom,
                    'quantite' => (int) $l['quantite'],
                    'prix_unitaire' => (int) $l['prix_unitaire'],
                    'verset' => $l['verset'] ?? null,
                    'modele' => $l['modele'] ?? null,
                ]);
            }

            $this->record->valider(auth()->user(), $remiseForcee);
        });

        Notification::make()
            ->title('Commande validée')
            ->body('Elle est comptabilisée dans votre chiffre d\'affaires et rejoint « Commandes ».')
            ->success()
            ->send();

        $this->redirect(CommandeResource::getUrl('view', ['record' => $this->record]));
    }

    // --- Helpers d'affichage / de résolution -------------------------------

    /**
     * @return array<string, array<int, string>>
     */
    public static function optionsArticles(): array
    {
        return Article::query()
            ->where('active', true)
            ->with('collection:id,nom')
            ->orderBy('ordre')
            ->get(['id', 'collection_id', 'nom', 'prix'])
            ->groupBy(fn (Article $a) => $a->collection?->nom ?? 'Sans collection')
            ->map(fn ($articles) => $articles
                ->mapWithKeys(fn (Article $a) => [$a->id => $a->nom.' — '.number_format($a->prix, 0, ',', ' ').' F'])
                ->all())
            ->all();
    }

    public static function indicationStock(mixed $articleId, mixed $tailleId, mixed $couleurId): Htmlable
    {
        if (blank($articleId)) {
            return new HtmlString('<span class="text-sm text-gray-400">Choisissez un article.</span>');
        }

        $article = Article::find($articleId);

        if (! $article) {
            return new HtmlString('');
        }

        $variante = ArticleVariante::query()
            ->where('article_id', $articleId)
            ->where('taille_id', $article->gere_tailles ? $tailleId : null)
            ->where('couleur_id', $article->gere_couleurs ? $couleurId : null)
            ->first();

        $libelle = collect([
            $tailleId ? Taille::find($tailleId)?->libelle : null,
            $couleurId ? Couleur::find($couleurId)?->nom : null,
        ])->filter()->implode(' / ') ?: $article->nom;

        if (! $variante) {
            return new HtmlString('<span class="text-sm text-warning-600 dark:text-warning-400">'
                .e($libelle).' — variante inconnue du catalogue. Vous pouvez quand même la vendre.</span>');
        }

        if (! $variante->disponible) {
            return new HtmlString('<span class="text-sm text-danger-600 dark:text-danger-400">'
                .e($libelle).' — marquée indisponible. Le système avertit, vous décidez.</span>');
        }

        if ($variante->stock === null) {
            return new HtmlString('<span class="text-sm text-gray-500">'.e($libelle).' — stock non suivi.</span>');
        }

        $couleurClasse = $variante->stock <= 0
            ? 'text-danger-600 dark:text-danger-400'
            : ($variante->stock <= $variante->seuil_alerte ? 'text-warning-600 dark:text-warning-400' : 'text-success-600 dark:text-success-400');

        return new HtmlString('<span class="text-sm '.$couleurClasse.'">'
            .e($libelle).' — '.(int) $variante->stock.' en stock</span>');
    }

    public function recapMontants(Get $get): Htmlable
    {
        $lignes = collect($get('lignes') ?? []);
        $sousTotal = $lignes->sum(fn ($l) => (int) ($l['prix_unitaire'] ?? 0) * (int) ($l['quantite'] ?? 0));

        $pct = ($get('remise_manuelle') ?? false)
            ? (int) ($get('remise_pourcentage') ?? 0)
            : $this->remiseProposee();

        $remise = (int) round($sousTotal * $pct / 100);
        $ca = $sousTotal - $remise;
        $frais = (int) $this->record->frais_livraison;

        $l = fn (string $libelle, string $valeur, bool $fort = false) => '<div class="flex justify-between py-1 '
            .($fort ? 'font-semibold text-base' : 'text-sm').'"><span>'.e($libelle).'</span><span>'.e($valeur).'</span></div>';

        return new HtmlString(
            '<div class="max-w-md">'
            .$l('Sous-total articles', Francais::frais($sousTotal))
            .($pct > 0 ? $l("Remise fidélité − {$pct} %", '− '.Francais::frais($remise)) : '')
            .'<div class="border-t border-gray-200 dark:border-white/10 my-1"></div>'
            .$l('Chiffre d\'affaires', Francais::frais($ca), true)
            .$l('Frais de livraison ('.e($this->record->commune).') — hors CA', Francais::frais($frais))
            .'<div class="border-t border-gray-200 dark:border-white/10 my-1"></div>'
            .$l('À encaisser', Francais::frais($ca + $frais), true)
            .'</div>'
        );
    }

    public function remiseProposee(): int
    {
        $client = $this->record->client;
        $rang = ($client?->nb_commandes ?? 0) + 1;

        return Client::avantagePourNumero($rang) ?? 0;
    }

    /**
     * Lignes de départ. La cliente ne choisit plus d'article — la gérante
     * le fait ici, ainsi que la taille et la couleur. Pour My Verse, on
     * crée une ligne par verset demandé, avec le verset pré-rempli.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function lignesInitiales(): array
    {
        if ($this->record->estMyVerse()) {
            $versets = collect($this->record->souhaits_client['versets'] ?? []);

            if ($versets->isNotEmpty()) {
                return $versets->map(function (array $v) {
                    $ligne = $this->ligneVide();
                    $ligne['verset'] = trim(collect([$v['reference'] ?? null, $v['texte'] ?? null])
                        ->filter()->implode(' — ')) ?: null;

                    return $ligne;
                })->all();
            }
        }

        return [$this->ligneVide()];
    }

    /** @return array<string, mixed> */
    protected function ligneVide(): array
    {
        return [
            'article_id' => null, 'taille_id' => null, 'couleur_id' => null,
            'quantite' => 1, 'prix_unitaire' => null, 'verset' => null, 'modele' => null,
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Composer — '.$this->record->reference;
    }

    public function getBreadcrumb(): string
    {
        return 'Composer';
    }
}
