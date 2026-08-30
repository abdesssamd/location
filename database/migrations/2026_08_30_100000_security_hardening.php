<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_tokens', function (Blueprint $table) {
            if (! $this->hasColumn('store_tokens', 'last_used_at')) {
                $table->timestamp('last_used_at')->nullable();
            }
            if (! $this->hasColumn('store_tokens', 'last_ip')) {
                $table->string('last_ip', 45)->nullable();
            }
        });

        // Cloisonnement des lignes et images de packs : sans store_id, une suppression
        // par identifiant n'est bornée par aucun scope tenant.
        foreach (['pack_images', 'pack_items'] as $name) {
            if (! $this->hasColumn($name, 'store_id')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->unsignedBigInteger('store_id')->nullable()->index();
                });
            }

            // Reprise des données existantes depuis le pack parent.
            DB::statement(
                "update {$name} set store_id = (select store_id from packs where packs.id = {$name}.pack_id) where store_id is null"
            );
        }
    }

    public function down(): void
    {
        Schema::table('store_tokens', function (Blueprint $table) {
            $table->dropColumn(['last_used_at', 'last_ip']);
        });

        foreach (['pack_images', 'pack_items'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn('store_id');
            });
        }
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
