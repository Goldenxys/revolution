<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le fait comptable (CA, fidélité, décrément de stock) ne se déclenche
     * plus à la validation mais à la livraison confirmée — voir
     * Commande::confirmerLivraison(). `validee_at` reste le moment où la
     * gérante a composé/verrouillé la commande (reçu généré) ; `livree_at`
     * est le moment où elle a réellement été comptabilisée.
     */
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->timestamp('livree_at')->nullable()->after('validee_at');
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn('livree_at');
        });
    }
};
