<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'cle',
        'nom',
        'telephone',
        'email',
        'commune',
        'nb_commandes',
        'premiere_commande_at',
        'derniere_commande_at',
        'statut',
        'numero_client',
        'ca_cumule',
    ];

    protected $casts = [
        'nb_commandes' => 'integer',
        'premiere_commande_at' => 'datetime',
        'derniere_commande_at' => 'datetime',
        'ca_cumule' => 'integer',
    ];

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class);
    }

    public function reductionsFidelite(): HasMany
    {
        return $this->hasMany(ReductionFidelite::class)->latest('numero_commande');
    }

    /**
     * Numéro client stable, affiché sur la carte de fidélité, attribué dès
     * la première demande déposée (avant même toute validation). Basé sur
     * l'id auto-incrémenté : pas de table de compteur séparée, jamais de
     * collision.
     */
    public function genererNumeroClient(): string
    {
        return sprintf('REV-C-%04d', $this->id);
    }

    /**
     * Vrai dès la première commande VALIDÉE (nb_commandes ne compte que
     * celles-ci depuis la V2) — sert au badge « nouveau / fidèle » de
     * l'écran « Demandes à valider ».
     */
    public function estFidele(): bool
    {
        return $this->nb_commandes >= 1;
    }

    /**
     * Garde uniquement les chiffres du numéro et retourne les 8 derniers :
     * clé d'identification stable du client, insensible aux préfixes
     * internationaux (+225, 00225…) et à la mise en forme (espaces, tirets).
     */
    public static function cleDepuisTelephone(string $telephone): string
    {
        $chiffres = preg_replace('/\D+/', '', $telephone) ?? '';

        return substr($chiffres, -8);
    }

    /**
     * Normalise un nom pour comparaison : minuscules, sans accents, espaces
     * multiples réduits. Sert de repli quand aucun numéro ne correspond à
     * un client déjà connu.
     */
    public static function nomNormalise(string $nom): string
    {
        $nom = mb_strtolower(trim($nom));
        $translitere = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nom);
        $nom = $translitere !== false ? $translitere : $nom;
        $nom = preg_replace('/[^a-z0-9]+/', ' ', $nom) ?? $nom;

        return trim($nom);
    }

    /**
     * Palier atteint dans le cycle de fidélité de 8 commandes (1 à 8).
     */
    public function getPalierAttribute(): int
    {
        return (($this->nb_commandes - 1) % 8) + 1;
    }

    /**
     * Pourcentage débloqué si le palier courant est pair (2, 4, 6, 8),
     * sinon null.
     */
    public function getAvantageAttribute(): ?int
    {
        return static::avantagePourNumero($this->nb_commandes);
    }

    /**
     * Pourcentage débloqué par une commande de rang $numero donné (utile pour
     * annoncer, avant enregistrement, ce que la prochaine commande va
     * débloquer), sinon null si ce rang ne tombe pas sur un palier pair.
     */
    public static function avantagePourNumero(int $numero): ?int
    {
        $palier = (($numero - 1) % 8) + 1;

        return config('revolution.paliers')[$palier] ?? null;
    }

    /**
     * Pourcentage du prochain palier pair à venir dans le cycle.
     */
    public function getProchainAvantageAttribute(): int
    {
        $paliers = config('revolution.paliers');

        foreach ($paliers as $seuil => $pourcentage) {
            if ($seuil > $this->palier) {
                return $pourcentage;
            }
        }

        // Dernier seuil du cycle déjà dépassé : on annonce le premier
        // palier du cycle suivant.
        return $paliers[array_key_first($paliers)];
    }

    /**
     * Nombre de commandes restantes avant de débloquer le prochain avantage.
     */
    public function getCommandesRestantesAttribute(): int
    {
        $paliers = config('revolution.paliers');

        foreach (array_keys($paliers) as $seuil) {
            if ($seuil > $this->palier) {
                return $seuil - $this->palier;
            }
        }

        return (array_key_first($paliers) + 8) - $this->palier;
    }
}
