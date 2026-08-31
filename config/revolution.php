<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Adresse de réception des commandes
    |--------------------------------------------------------------------------
    |
    | Valeur de repli utilisée tant qu'aucun réglage n'a été enregistré en
    | base par la gérante (voir App\Models\Parametre).
    |
    */
    'email_reception' => env('REVO_EMAIL', 'djiehic@gmail.com'),

    /*
    |--------------------------------------------------------------------------
    | Code d'accès de repli pour l'espace RÉVOLUTION
    |--------------------------------------------------------------------------
    */
    'code_acces' => env('REVO_ADMIN_CODE', 'REVO2026'),

    /*
    |--------------------------------------------------------------------------
    | Chemin de l'Espace RÉVOLUTION (panneau gérante)
    |--------------------------------------------------------------------------
    |
    | Volontairement non-devinable plutôt que /admin (sécurité par
    | l'obscurité, en plus de l'authentification Breeze qui reste la vraie
    | protection). Aucun lien public n'y mène : on le partage directement
    | à la gérante.
    |
    */
    'admin_path' => env('REVO_ADMIN_PATH', 'protection-revolution-xyd-source'),

    /*
    |--------------------------------------------------------------------------
    | Catalogue
    |--------------------------------------------------------------------------
    */
    'tailles' => ['M', 'L', 'XL', 'XXL'],

    'types' => ['Tee-shirt', 'Pull', 'Tote bag', 'Chaussette', 'Casquette'],

    'couleurs' => ['Blanc', 'Noir', 'Beige', 'Terracotta', 'Kaki', 'Bleu nuit', 'Autre'],

    /*
    |--------------------------------------------------------------------------
    | Communes et tarifs de livraison (F CFA)
    |--------------------------------------------------------------------------
    |
    | Les frais de livraison sont toujours recalculés côté serveur depuis
    | cette configuration au moment de l'enregistrement : on ne fait jamais
    | confiance à la valeur envoyée par le formulaire.
    |
    */
    'communes' => [
        'Yopougon' => 1000,
        'Cocody' => 1500,
        'Koumassi' => 1500,
        'Treichville' => 1500,
        'Adjamé' => 1500,
        'Marcory' => 2000,
        'Bingerville' => 2000,
        'Faya' => 2000,
        'Jules Vernes' => 2000,
        'Bassam' => 3000,
        'Autres' => 1500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Paliers de fidélité
    |--------------------------------------------------------------------------
    |
    | Cycle de 8 commandes. Les paliers pairs débloquent un avantage,
    | toujours appliqué sur la commande suivante.
    |
    */
    'paliers' => [2 => 15, 4 => 30, 6 => 45, 8 => 65],

];
