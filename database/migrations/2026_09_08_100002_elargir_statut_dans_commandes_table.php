<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * V2 introduit trois nouveaux statuts (en_attente, validee,
     * en_livraison) sans retirer les anciens : les commandes du formulaire
     * libre restent sur nouvelle/confirmee/preparation/livree/annulee, la
     * migration de données (revolution:migrer-v2) les fait cohabiter avec
     * le nouveau modèle sans jamais réécrire l'historique par surprise.
     * Défaut inchangé ('nouvelle') : le formulaire libre continue de créer
     * des commandes exactement comme avant.
     */
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->enum('statut', [
                'nouvelle', 'confirmee', 'preparation', 'livree', 'annulee',
                'en_attente', 'validee', 'en_livraison',
            ])->default('nouvelle')->change();
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->enum('statut', ['nouvelle', 'confirmee', 'preparation', 'livree', 'annulee'])
                ->default('nouvelle')->change();
        });
    }
};
