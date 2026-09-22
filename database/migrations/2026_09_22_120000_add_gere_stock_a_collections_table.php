<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * « My verse » est fabriqué à la demande : la gérante compose taille et
     * couleur librement au compositeur, il n'y a jamais de pièces en
     * réserve. `gere_stock` (par défaut true, pour ne rien changer aux
     * autres collections) permet de désactiver le suivi de stock au niveau
     * de la collection plutôt qu'en dur sur un slug — n'importe quelle
     * future collection sur-demande pourra être basculée pareil depuis
     * l'Espace RÉVOLUTION, sans nouvelle migration.
     *
     * On profite de cette migration pour nettoyer les quelques variantes
     * « My verse » qui avaient un stock numérique saisi par erreur (ex.
     * Pull-Over Modèle 1, M/Blanc et M/Noir) : un stock suivi qui tombe à
     * zéro aurait bloqué leur sélection au compositeur (restructuration
     * stock/disponibilité), alors qu'aucune rupture n'existe réellement
     * pour un article fabriqué à la demande.
     */
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->boolean('gere_stock')->default(true)->after('modeles_disponibles');
        });

        DB::table('collections')->where('slug', 'my_verse')->update(['gere_stock' => false]);

        $articleIds = DB::table('articles')
            ->whereIn('collection_id', DB::table('collections')->where('slug', 'my_verse')->pluck('id'))
            ->pluck('id');

        DB::table('article_variantes')->whereIn('article_id', $articleIds)->update(['stock' => null]);
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn('gere_stock');
        });
    }
};
