<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support technique : un magasin ouvre un ticket, l'equipe LouerPro repond
 * depuis l'espace Super Admin. Le fil de discussion accepte des pieces
 * jointes (captures d'ecran, photos d'un probleme).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('subject');
            $table->string('category')->default('other');
            $table->string('priority')->default('normal');
            $table->string('status')->default('open');

            // Dernier message : sert au tri « recemment actif » sans agreger.
            $table->timestamp('last_reply_at')->nullable();
            $table->string('last_reply_by')->nullable();

            // Compteurs de non-lus, tenus a jour a chaque message.
            $table->unsignedInteger('unread_for_store')->default(0);
            $table->unsignedInteger('unread_for_admin')->default(0);

            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['status', 'last_reply_at']);
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Qui parle : le magasin ou le support. Stocke plutot que deduit du
            // role, car un compte peut changer de role au fil du temps.
            $table->string('author_type');
            $table->string('author_name');
            $table->text('body');

            // Chemins sur le disque prive : jamais servis directement.
            $table->json('attachment_paths')->nullable();
            $table->timestamps();

            $table->index('support_ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_tickets');
    }
};
