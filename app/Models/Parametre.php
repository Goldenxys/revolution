<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ligne unique de réglages modifiables par la gérante depuis l'Espace
 * RÉVOLUTION : adresse de réception des commandes, clé du service d'envoi
 * de mail, code d'accès. Les valeurs de config/revolution.php (elles-mêmes
 * pilotées par le .env) servent de repli tant qu'aucun réglage n'a été
 * enregistré en base.
 */
class Parametre extends Model
{
    protected $fillable = [
        'email_reception',
        'mail_cle',
        'code_acces',
    ];

    /**
     * Retourne l'unique ligne de réglages, en la créant avec les valeurs de
     * repli si elle n'existe pas encore.
     */
    public static function actuel(): self
    {
        return static::query()->firstOrCreate([], [
            'email_reception' => config('revolution.email_reception'),
            'mail_cle' => null,
            'code_acces' => config('revolution.code_acces'),
        ]);
    }

    public static function emailReception(): string
    {
        return static::actuel()->email_reception;
    }

    public static function codeAcces(): string
    {
        return static::actuel()->code_acces;
    }
}
