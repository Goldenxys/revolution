<?php

namespace App\Events;

use App\Models\Commande;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Déclenché après commit par Commande::valider() (jamais avant — une vente
 * ne doit jamais dépendre d'un envoi SMTP lent). Le reçu PDF et les deux
 * e-mails de la Phase 4 (« votre commande est validée », « nouvelle vente
 * réalisée ») s'enregistrent comme auditeurs sur cet événement — aucun
 * auditeur n'est encore enregistré tant que la Phase 4 n'est pas livrée,
 * ce qui est sans effet : un événement sans auditeur ne fait rien.
 */
class CommandeValidee
{
    use Dispatchable, SerializesModels;

    public function __construct(public Commande $commande) {}
}
