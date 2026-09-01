<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dépenses du magasin : loyer, électricité, salaires, réparations… Séparées
 * des achats fournisseurs (lot suivant) et du chiffre d'affaires vente/location.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['store_id', 'name']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('label');
            $table->unsignedInteger('amount');
            $table->string('payment_method')->nullable();
            $table->date('date')->index();
            $table->text('notes')->nullable();
            $table->string('proof_path')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();

            $table->index(['store_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
