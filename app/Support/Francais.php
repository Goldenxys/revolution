<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;

/**
 * Petits utilitaires de mise en forme francophone : ordinaux (« 4ᵉ »),
 * dates en toutes lettres (« samedi 26 août 2026 »).
 */
class Francais
{
    public static function ordinal(int $nombre): string
    {
        return $nombre === 1 ? '1ʳᵉ' : "{$nombre}ᵉ";
    }

    /**
     * Date en toutes lettres, format français long : « samedi 26 août 2026 ».
     */
    public static function dateLongue(DateTimeInterface|string|null $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        return Carbon::parse($date)->locale('fr')->translatedFormat('l j F Y');
    }

    /**
     * Date + heure en toutes lettres : « samedi 26 août 2026 à 14:00 ».
     */
    public static function dateHeureLongue(DateTimeInterface|string|null $date, ?string $heure): ?string
    {
        $dateLongue = static::dateLongue($date);

        if (! $dateLongue) {
            return null;
        }

        if (blank($heure)) {
            return $dateLongue;
        }

        $heureFormatee = Carbon::parse($heure)->format('H:i');

        return "{$dateLongue} à {$heureFormatee}";
    }

    public static function frais(int $montant): string
    {
        return number_format($montant, 0, ',', ' ').' francs CFA';
    }
}
