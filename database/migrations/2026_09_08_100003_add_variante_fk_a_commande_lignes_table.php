<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `taille_libelle`/`couleur_nom` (copies figées) restent la seule
     * vérité historique de la ligne — jamais modifiées après coup. Ces deux
     * FK nullables sont un pur aide-mémoire technique, nécessaire pour
     * retrouver la bonne ArticleVariante à décrémenter/restituer en stock
     * (Commande::valider()/annuler()) sans avoir à reparser un libellé
     * texte.
     */
    public function up(): void
    {
        Schema::table('commande_lignes', function (Blueprint $table) {
            $table->foreignId('taille_id')->nullable()->after('article_id')
                ->constrained('tailles')->nullOnDelete();
            $table->foreignId('couleur_id')->nullable()->after('taille_id')
                ->constrained('couleurs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('commande_lignes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('taille_id');
            $table->dropConstrainedForeignId('couleur_id');
        });
    }
};
