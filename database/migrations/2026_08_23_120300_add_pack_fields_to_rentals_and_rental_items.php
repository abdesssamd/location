<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentals', function (Blueprint $table) {
            $table->integer('pack_savings')->default(0)->after('discount');
        });

        Schema::table('rental_items', function (Blueprint $table) {
            $table->foreignId('pack_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->string('pack_name')->nullable()->after('pack_id');
            $table->boolean('is_pack_component')->default(false)->after('line_total');
            $table->string('return_condition')->nullable()->after('is_pack_component');
            $table->integer('return_damage_fee')->default(0)->after('return_condition');
            $table->text('return_notes')->nullable()->after('return_damage_fee');

            $table->index(['rental_id', 'pack_id']);
        });
    }

    public function down(): void
    {
        Schema::table('rental_items', function (Blueprint $table) {
            $table->dropIndex(['rental_id', 'pack_id']);
            $table->dropConstrainedForeignId('pack_id');
            $table->dropColumn([
                'pack_name',
                'is_pack_component',
                'return_condition',
                'return_damage_fee',
                'return_notes',
            ]);
        });

        Schema::table('rentals', function (Blueprint $table) {
            $table->dropColumn('pack_savings');
        });
    }
};
