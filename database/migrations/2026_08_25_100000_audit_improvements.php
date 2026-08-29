<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'wilaya')) {
                $table->string('wilaya')->nullable()->after('address');
            }
            if (! Schema::hasColumn('customers', 'commune')) {
                $table->string('commune')->nullable()->after('wilaya');
            }
            if (! Schema::hasColumn('customers', 'phone_secondary')) {
                $table->string('phone_secondary')->nullable()->after('phone');
            }
        });

        Schema::table('rental_items', function (Blueprint $table) {
            if (! Schema::hasColumn('rental_items', 'return_image_paths')) {
                $table->text('return_image_paths')->nullable()->after('return_notes');
            }
        });

        Schema::table('rentals', function (Blueprint $table) {
            if (! Schema::hasColumn('rentals', 'late_fee')) {
                $table->integer('late_fee')->default(0)->after('paid_amount');
            }
        });

        Schema::table('stores', function (Blueprint $table) {
            if (! Schema::hasColumn('stores', 'late_fee_per_day')) {
                $table->integer('late_fee_per_day')->default(0)->after('tax_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $columns = collect(['wilaya', 'commune', 'phone_secondary'])->filter(fn ($c) => Schema::hasColumn('customers', $c))->all();
            if ($columns) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('rental_items', function (Blueprint $table) {
            if (Schema::hasColumn('rental_items', 'return_image_paths')) {
                $table->dropColumn('return_image_paths');
            }
        });

        Schema::table('rentals', function (Blueprint $table) {
            if (Schema::hasColumn('rentals', 'late_fee')) {
                $table->dropColumn('late_fee');
            }
        });

        Schema::table('stores', function (Blueprint $table) {
            if (Schema::hasColumn('stores', 'late_fee_per_day')) {
                $table->dropColumn('late_fee_per_day');
            }
        });
    }
};
