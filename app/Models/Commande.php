<?php

namespace App\Models;

use App\Events\CommandeValidee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class Commande extends Model
{
    use HasFactory;

    /**
     * Caractères utilisés pour générer une référence de commande, sans les
     * caractères ambigus à l'oral/à l'écrit (0, O, 1, I, L...).
     */
    private const ALPHABET_REFERENCE = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    protected $fillable = [
        'reference',
        'client_id',
        'collection',
        'type_article',
        'nom_article',
        'taille',
        'couleur',
        'verset_reference',
        'verset_texte',
        'commune',
        'frais_livraison',
        'quartier',
        'mode_livraison',
        'date_souhaitee',
        'heure_souhaitee',
        'numero_commande_client',
        'sous_total',
        'remise_pourcentage',
        'remise_montant',
        'total',
        'statut',
        'notes',
        'utilise_catalogue',
        'souhaits_client',
        'message_client',
        'total_articles',
        'total_a_payer',
        'validee_at',
        'validee_par',
        'remise_forcee',
        'recu_token',
    ];

    protected $casts = [
        'frais_livraison' => 'integer',
        'numero_commande_client' => 'integer',
        'date_souhaitee' => 'date',
        'heure_souhaitee' => 'datetime:H:i',
        'sous_total' => 'integer',
        'remise_pourcentage' => 'integer',
        'remise_montant' => 'integer',
        'total' => 'integer',
        'utilise_catalogue' => 'boolean',
        'souhaits_client' => 'array',
        'total_articles' => 'integer',
        'total_a_payer' => 'integer',
        'validee_at' => 'datetime',
        'remise_forcee' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Commande $commande) {
            if (blank($commande->reference)) {
                $commande->reference = static::genererReference();
            }
        });

        // Suppression d'une commande (correction d'erreur de saisie, doublon,
        // test…) depuis l'Espace RÉVOLUTION : le compteur de fidélité et les
        // dates de la cliente ne doivent pas rester en décalage avec la
        // réalité. On ne renumérote pas les autres commandes (l'historique —
        // mails déjà envoyés, paliers déjà annoncés — reste inchangé), mais
        // on recalcule bien nb_commandes/ca_cumule et les bornes de dates du
        // client depuis ses commandes qui comptent : celles du formulaire
        // libre (finales dès la soumission) et les demandes V2 validées —
        // jamais une demande V2 encore `en_attente`, jamais une commande
        // annulée.
        static::deleted(function (Commande $commande) {
            $client = $commande->client()->first();

            if (! $client) {
                return;
            }

            $comptees = $client->commandes()->comptees();

            $client->nb_commandes = (clone $comptees)->count();
            $client->ca_cumule = (clone $comptees)->sum('total_articles');
            $client->statut = $client->nb_commandes > 0 ? 'client' : 'prospect';

            $bornes = (clone $comptees)
                ->selectRaw('MIN(COALESCE(validee_at, created_at)) as premiere, MAX(COALESCE(validee_at, created_at)) as derniere')
                ->first();

            $client->premiere_commande_at = $bornes?->premiere;
            $client->derniere_commande_at = $bornes?->derniere;
            $client->save();
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(CommandeLigne::class);
    }

    public function journal(): HasMany
    {
        return $this->hasMany(CommandeJournal::class);
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validee_par');
    }

    /**
     * Une demande déposée par une cliente, pas encore composée/validée par
     * la gérante (V2 §2). Exclut délibérément les commandes du formulaire
     * libre, qui n'utilisent jamais ce statut.
     */
    public function scopeEnAttente(Builder $query): Builder
    {
        return $query->where('statut', 'en_attente');
    }

    /**
     * Le fait comptable (V2 §9) : une commande qui a été validée et n'a pas
     * été annulée depuis — couvre `validee`, `en_livraison` et `livree`
     * (la livraison ne déclenche plus rien de comptable, elle ne retire
     * rien non plus). `validee_at` reste renseigné après une annulation
     * (trace historique de la validation d'origine) : c'est pourquoi le
     * statut, pas seulement la date, doit être vérifié ici.
     */
    public function scopeValidees(Builder $query): Builder
    {
        return $query->whereNotNull('validee_at')->where('statut', '!=', 'annulee');
    }

    /**
     * Les commandes d'une cliente qui comptent pour sa fidélité et son
     * chiffre d'affaires cumulé : le formulaire libre est final dès la
     * soumission (`nouvelle`, `confirmee`, `preparation`, `livree`), une
     * demande V2 ne compte qu'une fois `validee` (donc `validee`,
     * `en_livraison`, `livree`). Restent exclues les demandes encore
     * `en_attente` et toutes les commandes `annulee`.
     */
    public function scopeComptees(Builder $query): Builder
    {
        return $query->whereNotIn('statut', ['en_attente', 'annulee']);
    }

    /**
     * Génère une référence courte (6 caractères, majuscules + chiffres,
     * sans caractères ambigus) et garantit son unicité.
     */
    public static function genererReference(): string
    {
        $alphabet = self::ALPHABET_REFERENCE;
        $longueur = strlen($alphabet);

        do {
            $reference = '';
            for ($i = 0; $i < 6; $i++) {
                $reference .= $alphabet[random_int(0, $longueur - 1)];
            }
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    public function estMyVerse(): bool
    {
        return $this->collection === 'my_verse';
    }

    /**
     * Nom de collection à afficher (mail, notification) — la vraie
     * collection du catalogue pour une commande catalogue (déduite de sa
     * première ligne), le libellé legacy sinon. Centralisé ici pour que
     * CommandeRecue, ResoutClientEtNotifie et le futur export ne
     * réimplémentent pas chacun leur propre logique.
     */
    public function libelleCollection(): string
    {
        if ($this->utilise_catalogue) {
            return $this->lignes->first()?->article?->collection?->nom ?? 'RÉVOLUTION';
        }

        return $this->estMyVerse() ? 'MY VERSE' : 'Autre collection';
    }

    /**
     * Vrai si la commande relève de la collection « My verse » — legacy ou
     * catalogue — utile pour les touches visuelles (icône dorée, verset).
     */
    public function estCollectionMyVerse(): bool
    {
        if ($this->utilise_catalogue) {
            return $this->lignes->first()?->article?->collection?->slug === 'my_verse';
        }

        return $this->estMyVerse();
    }

    public function estYango(): bool
    {
        return $this->mode_livraison === 'yango';
    }

    /**
     * Libellé de l'article pour affichage (tableau de bord, mail, export).
     * Une commande née du parcours catalogue (utilise_catalogue) a ses
     * articles dans `lignes` plutôt que dans les colonnes legacy ci-dessous
     * — dans ce cas on résume le panier. Sinon, comportement legacy inchangé.
     */
    public function getLibelleArticleAttribute(): string
    {
        if ($this->relationLoaded('lignes') ? $this->lignes->isNotEmpty() : $this->lignes()->exists()) {
            $lignes = $this->relationLoaded('lignes') ? $this->lignes : $this->lignes()->get();

            if ($lignes->count() === 1) {
                $ligne = $lignes->first();

                return trim($ligne->article_nom
                    .($ligne->taille_libelle ? " · {$ligne->taille_libelle}" : '')
                    .($ligne->couleur_nom ? " · {$ligne->couleur_nom}" : '')
                    .($ligne->quantite > 1 ? " ×{$ligne->quantite}" : ''));
            }

            return $lignes->count().' articles';
        }

        if ($this->estMyVerse()) {
            return trim('Tee-shirt MY VERSE'.($this->couleur ? " · {$this->couleur}" : ''));
        }

        return trim(($this->type_article ?? 'Article').' « '.($this->nom_article ?? '').' »');
    }

    /**
     * Resomme sous_total/total de la commande depuis ses lignes actuelles —
     * appelé après une correction de ligne depuis l'Espace RÉVOLUTION.
     * remise_pourcentage n'est jamais recalculé ici : il reflète le palier
     * de fidélité de la cliente au moment de l'achat, pas le contenu du
     * panier, et ne doit donc jamais bouger après coup.
     */
    public function recalculerMontants(): void
    {
        $sousTotal = $this->lignes()->get()->sum(fn (CommandeLigne $ligne) => $ligne->prix_unitaire * $ligne->quantite);
        $remisePourcentage = $this->remise_pourcentage ?? 0;
        $remiseMontant = (int) round(($sousTotal + $this->frais_livraison) * $remisePourcentage / 100);

        $this->update([
            'sous_total' => $sousTotal,
            'remise_montant' => $remiseMontant,
            'total' => $sousTotal + $this->frais_livraison - $remiseMontant,
        ]);
    }

    /**
     * Génère un jeton de reçu (40 caractères) et garantit son unicité —
     * même logique que genererReference().
     */
    public static function genererRecuToken(): string
    {
        do {
            $token = Str::random(40);
        } while (static::where('recu_token', $token)->exists());

        return $token;
    }

    /**
     * Le moment central de la V2 (§5.3) : compose les lignes réelles en
     * chiffre d'affaires. Idempotente — un second appel (double-clic,
     * requête rejouée) ne fait rien, silencieusement, plutôt que de
     * recompter la fidélité une deuxième fois (critère de réussite #12).
     * Tout réussit ensemble ou échoue ensemble.
     *
     * @param  int|null  $remiseForceePourcentage  Remise saisie à la main par
     *                                             la gérante (positionne
     *                                             remise_forcee = true) ;
     *                                             sinon la proposition
     *                                             automatique du palier de
     *                                             fidélité est utilisée.
     *
     * @throws RuntimeException si la commande n'a aucune ligne composée.
     */
    public function valider(User $utilisateur, ?int $remiseForceePourcentage = null): void
    {
        // Idempotent : déjà validée (ou annulée, ou jamais en_attente pour
        // commencer) — aucun effet, pas d'exception. Seul un appel sur une
        // vraie demande en_attente doit produire un effet.
        if ($this->statut !== 'en_attente') {
            return;
        }

        $lignes = $this->lignes()->get();

        if ($lignes->isEmpty()) {
            throw new RuntimeException('Impossible de valider une commande sans lignes.');
        }

        DB::transaction(function () use ($utilisateur, $remiseForceePourcentage, $lignes) {
            $client = $this->client()->lockForUpdate()->first();

            $sousTotal = $lignes->sum(fn (CommandeLigne $ligne) => $ligne->prix_unitaire * $ligne->quantite);
            $numeroCommandeClient = $client->nb_commandes + 1;

            $remisePourcentage = $remiseForceePourcentage
                ?? (Client::avantagePourNumero($numeroCommandeClient) ?? 0);

            // Remise calculée sur le chiffre d'affaires seul : les frais de
            // livraison n'entrent jamais dans son calcul (V2 §5.2 — cohérent
            // avec « le CA exclut toujours la livraison »).
            $remiseMontant = (int) round($sousTotal * $remisePourcentage / 100);
            $totalArticles = $sousTotal - $remiseMontant;
            $totalAPayer = $totalArticles + $this->frais_livraison;

            $this->update([
                'sous_total' => $sousTotal,
                'remise_pourcentage' => $remisePourcentage,
                'remise_montant' => $remiseMontant,
                'total_articles' => $totalArticles,
                'total_a_payer' => $totalAPayer,
                'total' => $totalAPayer,
                'statut' => 'validee',
                'validee_at' => now(),
                'validee_par' => $utilisateur->id,
                'numero_commande_client' => $numeroCommandeClient,
                'remise_forcee' => $remiseForceePourcentage !== null,
                'recu_token' => static::genererRecuToken(),
            ]);

            foreach ($lignes as $ligne) {
                $variante = ArticleVariante::query()
                    ->where('article_id', $ligne->article_id)
                    ->where('taille_id', $ligne->taille_id)
                    ->where('couleur_id', $ligne->couleur_id)
                    ->first();

                $variante?->decrementerStock($ligne->quantite, $this);
            }

            $client->nb_commandes = $numeroCommandeClient;
            $client->ca_cumule += $totalArticles;
            $client->derniere_commande_at = now();

            if ($client->statut === 'prospect') {
                $client->statut = 'client';
                $client->premiere_commande_at = now();
            }

            if (blank($client->numero_client)) {
                $client->numero_client = $client->genererNumeroClient();
            }

            $client->save();

            CommandeJournal::consigner($this, 'validee', [
                'total_articles' => $totalArticles,
                'total_a_payer' => $totalAPayer,
                'remise_pourcentage' => $remisePourcentage,
                'remise_forcee' => $remiseForceePourcentage !== null,
            ], $utilisateur->id);

            // Point d'extension pour la Phase 4 (reçu PDF + e-mails) : un
            // auditeur s'enregistre sur cet événement pour ne jamais faire
            // dépendre la vente elle-même d'un envoi SMTP lent (V2 §5.3,
            // dernière ligne).
            DB::afterCommit(fn () => event(new CommandeValidee($this)));
        });
    }

    /**
     * Défait proprement une commande validée (V2 §5.5) : restitue le stock,
     * retire son chiffre d'affaires du cumul client, décrémente son
     * compteur de fidélité et recalcule ses bornes de dates — depuis les
     * seules commandes encore validées. Idempotente. Ne supprime jamais la
     * commande : son statut devient `annulee`, `validee_at` reste comme
     * trace historique de la validation d'origine.
     */
    public function annuler(string $motif, User $utilisateur): void
    {
        if ($this->statut === 'annulee') {
            return;
        }

        DB::transaction(function () use ($motif, $utilisateur) {
            $etaitValidee = $this->validee_at !== null;

            if ($etaitValidee) {
                foreach ($this->lignes as $ligne) {
                    $variante = ArticleVariante::query()
                        ->where('article_id', $ligne->article_id)
                        ->where('taille_id', $ligne->taille_id)
                        ->where('couleur_id', $ligne->couleur_id)
                        ->first();

                    $variante?->restituerStock($ligne->quantite);
                }

                $client = $this->client()->lockForUpdate()->first();
                $restantes = $client->commandes()->comptees()->where('id', '!=', $this->id);

                $client->nb_commandes = (clone $restantes)->count();
                $client->ca_cumule = max(0, (clone $restantes)->sum('total_articles'));
                $client->statut = $client->nb_commandes > 0 ? 'client' : 'prospect';

                $bornes = (clone $restantes)
                    ->selectRaw('MIN(COALESCE(validee_at, created_at)) as premiere, MAX(COALESCE(validee_at, created_at)) as derniere')
                    ->first();
                $client->premiere_commande_at = $bornes?->premiere;
                $client->derniere_commande_at = $bornes?->derniere;

                $client->save();
            }

            $this->update(['statut' => 'annulee']);

            CommandeJournal::consigner($this, 'annulee', [
                'motif' => $motif,
                'etait_validee' => $etaitValidee,
            ], $utilisateur->id);
        });
    }
}
