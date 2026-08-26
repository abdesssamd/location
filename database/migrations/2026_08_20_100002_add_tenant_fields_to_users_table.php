<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->boolean('is_super_admin')->default(false)->after('store_id');
            $table->string('phone')->nullable()->after('email');
            $table->string('locale', 5)->default('fr')->after('phone');
            $table->string('avatar_path')->nullable()->after('locale');
            $table->boolean('is_active')->default(true)->after('avatar_path');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn([
                'store_id',
                'is_super_admin',
                'phone',
                'locale',
                'avatar_path',
                'is_active',
                'deleted_at',
            ]);
        });
    }
};