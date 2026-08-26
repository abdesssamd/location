<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La FK product_id s'appuie sur l'index unique : la supprimer d'abord
        Schema::table('pack_items', function (Blueprint $table) {
            $table->dropForeign('pack_items_product_id_foreign');
        });

        // La FK pack_id utilise l'index unique comme index de gauche : index de remplacement
        Schema::table('pack_items', function (Blueprint $table) {
            $table->index('pack_id', 'pack_items_pack_id_index');
        });

        Schema::table('pack_items', function (Blueprint $table) {
            $table->dropUnique('pack_items_pack_id_product_id_unique');
        });

        Schema::table('pack_items', function (Blueprint $table) {
            // product_id devient nullable : une ligne de pack peut viser une catégorie
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->unique(['pack_id', 'product_id']);
            $table->index(['pack_id', 'category_id']);
        });

        Schema::table('pack_items', function (Blueprint $table) {
            $table->dropIndex('pack_items_pack_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('pack_items', function (Blueprint $table) {
            $table->dropForeign('pack_items_category_id_foreign');
            $table->dropForeign('pack_items_product_id_foreign');
            $table->dropUnique('pack_items_pack_id_product_id_unique');
            $table->dropIndex(['pack_id', 'category_id']);
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            $table->unique(['pack_id', 'product_id']);
        });
    }
};