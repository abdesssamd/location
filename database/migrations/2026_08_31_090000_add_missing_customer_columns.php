<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le modèle Customer accepte phone_secondary, wilaya et commune (formulaire
 * « Nouveau client » de la réservation), mais aucune migration ne les créait :
 * l'enregistrement échouait avec « Unknown column 'phone_secondary' ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! $this->hasColumn('customers', 'phone_secondary')) {
                $table->string('phone_secondary', 30)->nullable();
            }
            if (! $this->hasColumn('customers', 'wilaya')) {
                $table->string('wilaya', 100)->nullable();
            }
            if (! $this->hasColumn('customers', 'commune')) {
                $table->string('commune', 100)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['phone_secondary', 'wilaya', 'commune']);
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
