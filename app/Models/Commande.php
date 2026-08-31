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
