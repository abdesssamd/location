<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pack_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pack_id')->constrained()->cascadeOnDelete();
            // product_id nullable : une ligne de pack peut viser une catégorie (au choix)
            $table->foreignId('product_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity')->default(1);
            $table->string('selection_mode')->default('auto'); // auto | manual
            $table->string('variant_hint')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['pack_id', 'product_id']);
            $table->index(['pack_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pack_items');
    }
};
