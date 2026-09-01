<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('gtin')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('brand')->default('skinChemists');
            $table->foreignId('category_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();

            // Filter facets. Deliberately plain strings, not lookup tables:
            // ~8 fixed values each, edited from a dropdown, never joined on.
            $table->string('ingredient')->nullable()->index();
            $table->string('concern')->nullable()->index();

            // Money in integer centimes. See PlaceOrder for why.
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('sale_price_cents')->nullable();

            $table->text('short')->nullable();
            $table->json('bullets')->nullable();
            $table->json('actifs')->nullable();

            $table->integer('stock')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);

            $table->decimal('rating_avg', 2, 1)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);

            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
