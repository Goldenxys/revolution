<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Un palier de fidélité réellement débloqué (2, 4, 6 ou 8ᵉ commande de la
 * cliente sur l'ancien formulaire libre, seul parcours où une commande est
 * finale dès le clic du client — voir CarteFidelite::genererSiPalierAtteint()).
 * La carte PNG associée vit sur le disque local, jamais exposée directement :
 * seul `token` en donne l'accès (CarteFideliteController).
 */
class ReductionFidelite extends Model
{
    protected $table = 'reductions_fidelite';

    protected $fillable = [
        'client_id',
        'commande_id',
        'numero_commande',
        'palier',
        'pourcentage',
        'chemin_fichier',
        'token',
        'utilisee_at',
    ];

    protected $casts = [
        'numero_commande' => 'integer',
        'palier' => 'integer',
        'pourcentage' => 'integer',
        'utilisee_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function estUtilisee(): bool
    {
        return $this->utilisee_at !== null;
    }

    /**
     * Jeton opaque (40 car.) garantissant l'accès au téléchargement — même
     * principe que Commande::genererRecuToken().
     */
    public static function genererToken(): string
    {
        do {
            $token = Str::random(40);
        } while (static::where('token', $token)->exists());

        return $token;
    }
}
