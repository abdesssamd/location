<?php

use App\Models\StoreToken;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_tokens', function (Blueprint $table) {
            if (! $this->hasColumn('store_tokens', 'token_hash')) {
                $table->string('token_hash', 64)->nullable()->index();
            }
        });

        // Les tokens déjà distribués continuent de fonctionner : on stocke leur
        // empreinte, puis la colonne « token » ne garde plus qu'un aperçu masqué.
        foreach (DB::table('store_tokens')->whereNull('token_hash')->get() as $row) {
            $plainText = (string) $row->token;

            if ($plainText === '') {
                continue;
            }

            DB::table('store_tokens')->where('id', $row->id)->update([
                'token_hash' => StoreToken::hashFor($plainText),
                'token' => StoreToken::mask($plainText),
            ]);

            DB::table('stores')
                ->where('id', $row->store_id)
                ->where('token', $plainText)
                ->update(['token' => StoreToken::mask($plainText)]);
        }
    }

    public function down(): void
    {
        Schema::table('store_tokens', function (Blueprint $table) {
            $table->dropColumn('token_hash');
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
