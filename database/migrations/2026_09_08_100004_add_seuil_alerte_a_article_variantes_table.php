<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Déclenche le widget « stock faible » de l'écran Mon stock (V2 §8.2) :
     * une variante dont le stock suivi tombe à ce seuil ou en dessous doit
     * apparaître dans la liste à réapprovisionner. Le champ `stock`
     * lui-même existe déjà depuis la V1 (prévu « pour plus tard »).
     */
    public function up(): void
    {
        Schema::table('article_variantes', function (Blueprint $table) {
            $table->integer('seuil_alerte')->default(3)->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('article_variantes', function (Blueprint $table) {
            $table->dropColumn('seuil_alerte');
        });
    }
};
