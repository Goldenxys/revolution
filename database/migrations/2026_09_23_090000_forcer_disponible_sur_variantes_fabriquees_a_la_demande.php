<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Suite directe de la migration `gere_stock` : les variantes d'une
     * collection fabriquée à la demande (My verse) n'ont plus de case
     * « disponible » à cocher à la main, elles sont toujours achetables
     * (ArticleVariante::booted()). Corrige les variantes déjà en base dont
     * `disponible` était resté à false faute d'avoir été cochées dans
     * l'ancienne grille — jusqu'ici, cela les rendait sélectionnables
     * nulle part, y compris au compositeur.
     */
    public function up(): void
    {
        $articleIds = DB::table('articles')
            ->whereIn('collection_id', DB::table('collections')->where('gere_stock', false)->pluck('id'))
            ->pluck('id');

        DB::table('article_variantes')->whereIn('article_id', $articleIds)->update(['disponible' => true]);
    }

    public function down(): void
    {
        // Correction de données, pas de retour en arrière significatif :
        // l'ancienne grille manuelle n'existe plus pour ces collections.
    }
};
