<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trace immuable des événements d'une commande (créée, validée, annulée,
 * email envoyé, PDF généré, WhatsApp envoyé…) — voir Commande::valider()/
 * annuler() et CommandeCatalogueController::store(). Le jour où une
 * cliente affirme n'avoir jamais reçu son reçu, cette table répond en
 * trois secondes.
 */
class CommandeJournal extends Model
{
    protected $table = 'commande_journal';

    const UPDATED_AT = null;

    protected $fillable = [
        'commande_id',
        'evenement',
        'utilisateur_id',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    /**
     * Point d'écriture unique pour tous les appelants : évite que chaque
     * site d'appel reconstruise le tableau de colonnes à la main.
     */
    public static function consigner(Commande $commande, string $evenement, array $details = [], ?int $utilisateurId = null): self
    {
        return static::create([
            'commande_id' => $commande->id,
            'evenement' => $evenement,
            'utilisateur_id' => $utilisateurId,
            'details' => $details,
        ]);
    }
}
