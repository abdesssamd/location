<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! $this->hasColumn('categories', 'sizes')) {
                $table->text('sizes')->nullable()->after('color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('sizes');
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
