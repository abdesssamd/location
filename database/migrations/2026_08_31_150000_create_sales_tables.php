<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vente d'articles : un magasin qui loue peut aussi vendre certaines pièces
 * de son stock (ex. costume en fin de vie, accessoire). La vente retire
 * l'unité définitivement, contrairement à une location qui la rend
 * disponible après retour.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('status')->default('completed')->index(); // completed, cancelled
            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('paid_amount')->default(0);
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->date('date')->index();
            $table->timestamps();

            $table->index(['store_id', 'date']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name'); // conserve le nom même si l'article est ensuite supprimé
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('unit_price')->default(0);
            $table->unsignedInteger('line_total')->default(0);
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('sale_id')->nullable()->after('rental_id')->constrained()->cascadeOnDelete();
        });

        // Un paiement lié à une vente n'a pas de location : rental_id doit devenir
        // facultatif. change() nécessite doctrine/dbal (installé pour cette
        // migration) et fonctionne aussi bien sur MySQL que sur SQLite (tests).
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('rental_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_id');
        });

        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
