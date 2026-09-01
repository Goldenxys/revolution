<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected $casts = [
        'frais_livraison' => 'integer',
        'numero_commande_client' => 'integer',
        'date_souhaitee' => 'date',
        'heure_souhaitee' => 'datetime:H:i',
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
        // on recalcule bien nb_commandes et les bornes de dates du client.
        static::deleted(function (Commande $commande) {
            $client = $commande->client()->first();

            if (! $client) {
                return;
            }

            $client->nb_commandes = $client->commandes()->count();

            $bornes = $client->commandes()
                ->selectRaw('MIN(created_at) as premiere, MAX(created_at) as derniere')
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

    public function estYango(): bool
    {
        return $this->mode_livraison === 'yango';
    }

    /**
     * Libellé de l'article pour affichage (tableau de bord, mail).
     */
    public function getLibelleArticleAttribute(): string
    {
        if ($this->estMyVerse()) {
            return trim('Tee-shirt MY VERSE'.($this->couleur ? " · {$this->couleur}" : ''));
        }

        return trim(($this->type_article ?? 'Article').' « '.($this->nom_article ?? '').' »');
    }
}
