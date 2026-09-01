<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Commercial range within a brand (Caviar, Édition Limitée, …).
            // A plain string like ingredient and concern: a short fixed list
            // picked from a dropdown, never joined on.
            $table->string('gamme')->nullable()->after('brand')->index();
        });

        Schema::table('products', function (Blueprint $table) {
            // Several brands are sold here, so the column is worth an index.
            $table->index('brand');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['brand']);
            $table->dropColumn('gamme');
        });
    }
};
