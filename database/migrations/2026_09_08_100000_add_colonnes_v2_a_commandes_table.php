<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * V2 — « la commande validée par la gérante ». Extension strictement
     * additive : le formulaire libre (/commande/my-verse, /commande/autre)
     * et le catalogue self-service déjà en place ne perdent aucune colonne.
     */
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            // Ce que la cliente a demandé, tel quel — jamais modifié après
            // soumission, c'est la trace de sa demande (pas des lignes
            // fermes : celles-ci n'existent qu'à partir de la validation).
            $table->json('souhaits_client')->nullable()->after('utilise_catalogue');
            $table->text('message_client')->nullable()->after('souhaits_client');

            // total_articles = chiffre d'affaires de la commande (sous_total
            // - remise), jamais les frais de livraison. total_a_payer = ce
            // que la cliente règle (total_articles + frais_livraison).
            $table->unsignedInteger('total_articles')->default(0)->after('message_client');
            $table->unsignedInteger('total_a_payer')->default(0)->after('total_articles');

            // Horodatage de la validation par la gérante : clé de tout le
            // reporting (CA, fidélité, stock) — jamais created_at.
            $table->timestamp('validee_at')->nullable()->after('total_a_payer');
            $table->foreignId('validee_par')->nullable()->after('validee_at')
                ->constrained('users')->nullOnDelete();

            $table->boolean('remise_forcee')->default(false)->after('validee_par');

            // Jeton du lien public signé vers le reçu PDF (§7.2 du document
            // v2) — le jeton lui-même est le secret, pas besoin de signature
            // Laravel en plus.
            $table->string('recu_token', 40)->nullable()->unique()->after('remise_forcee');
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validee_par');
            $table->dropColumn([
                'souhaits_client',
                'message_client',
                'total_articles',
                'total_a_payer',
                'validee_at',
                'remise_forcee',
                'recu_token',
            ]);
        });
    }
};
