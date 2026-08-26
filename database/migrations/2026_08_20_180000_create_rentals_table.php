<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('actual_return_date')->nullable();
            $table->string('status'); // reserved | active | completed | cancelled
            $table->integer('subtotal')->default(0);
            $table->integer('discount')->default(0);
            $table->integer('caution')->default(0);
            $table->integer('total')->default(0);
            $table->integer('paid_amount')->default(0);
            $table->string('payment_method')->nullable(); // cash | card | transfer | check
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};