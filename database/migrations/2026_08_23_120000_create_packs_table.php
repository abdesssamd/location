<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('duplicated_from_id')->nullable()->constrained('packs')->nullOnDelete();
            $table->string('reference')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('main_image_path')->nullable();
            $table->string('pricing_mode')->default('fixed'); // fixed | calculated
            $table->integer('pack_price')->default(0);
            $table->string('discount_type')->nullable(); // percent | amount
            $table->decimal('discount_value', 8, 2)->nullable();
            $table->integer('caution')->default(0);
            $table->string('status')->default('active')->index(); // active | archived | draft
            $table->longText('rental_conditions')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packs');
    }
};
