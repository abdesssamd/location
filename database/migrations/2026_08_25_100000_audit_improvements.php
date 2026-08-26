<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_items', function (Blueprint $table) {
            $table->text('return_image_paths')->nullable()->after('return_notes');
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->integer('late_fee')->default(0)->after('paid_amount');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->integer('late_fee_per_day')->default(0)->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['wilaya', 'commune', 'phone_secondary']);
        });

        Schema::table('rental_items', function (Blueprint $table) {
            $table->dropColumn('return_image_paths');
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn('late_fee');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('late_fee_per_day');
        });
    }
};
