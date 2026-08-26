<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('token', 64)->unique();
            $table->string('logo_path')->nullable();
            $table->string('address')->nullable();
            $table->string('wilaya')->nullable();
            $table->string('commune')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_secondary')->nullable();
            $table->string('email')->nullable();
            $table->string('manager_name')->nullable();
            $table->string('currency', 8)->default('DA');
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('tax_enabled')->default(false);
            $table->longText('rental_conditions')->nullable();
            $table->longText('settings')->nullable();
            $table->string('contract_prefix')->default('CTR');
            $table->string('status')->default('active'); // active | suspended | pending
            $table->timestamp('suspended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};