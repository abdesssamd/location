<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (! $this->hasColumn('plans', 'max_rentals_per_month')) {
                $table->integer('max_rentals_per_month')->nullable()->after('max_customers');
            }
            if (! $this->hasColumn('plans', 'promo_price')) {
                $table->integer('promo_price')->nullable()->after('price');
            }
            if (! $this->hasColumn('plans', 'promo_ends_at')) {
                $table->timestamp('promo_ends_at')->nullable()->after('promo_price');
            }
            if (! $this->hasColumn('plans', 'promo_label')) {
                $table->string('promo_label', 80)->nullable()->after('promo_ends_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['max_rentals_per_month', 'promo_price', 'promo_ends_at', 'promo_label']);
        });
    }

    /**
     * Schema::hasColumn interroge information_schema avec des colonnes absentes
     * des MariaDB/MySQL anciens : on teste la colonne de façon portable.
     */
    protected function hasColumn(string $table, string $column): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return Schema::hasColumn($table, $column);
        }

        return (int) DB::selectOne(
            'select count(*) as total from information_schema.columns
             where table_schema = database() and table_name = ? and column_name = ?',
            [$table, $column]
        )->total > 0;
    }
};
