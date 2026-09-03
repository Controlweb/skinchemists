<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-page SEO overrides for everything with a public URL.
 *
 * Both columns are nullable on purpose: left empty, the page keeps deriving its
 * title and description from the content, which is right far more often than a
 * hand-written override. These exist for the pages where it is not.
 */
return new class extends Migration
{
    /** Tables whose models have a public route. */
    private const TABLES = ['products', 'articles', 'ingredients', 'bundles'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('meta_title')->nullable();
                $table->string('meta_description', 500)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['meta_title', 'meta_description']);
            });
        }
    }
};
