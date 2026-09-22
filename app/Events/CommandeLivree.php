<?php

namespace App\Events;

use App\Models\Commande;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Déclenché après commit par Commande::confirmerLivraison() — le vrai fait
 * comptable (CA, fidélité, stock) désormais rattaché à la livraison
 * confirmée plutôt qu'à la validation. L'e-mail « nouvelle vente réalisée »
 * envoyé à la gérante s'enregistre comme auditeur sur cet événement.
 */
class CommandeLivree
{
    use Dispatchable, SerializesModels;

    public function __construct(public Commande $commande) {}
}
