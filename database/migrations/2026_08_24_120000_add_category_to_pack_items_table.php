<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La colonne category_id et le product_id nullable sont désormais créés
        // directement par la migration de création de pack_items (compatible SQLite/MySQL).
        // Ce fichier est conservé pour ne pas casser l'historique des migrations déjà jouées.
    }

    public function down(): void
    {
        //
    }
};