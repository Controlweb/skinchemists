<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // Nulled rather than cascaded: deleting a product must never
            // erase the line it was sold on.
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot. A later price or name edit must not rewrite history.
            $table->string('name');
            $table->string('sku');
            $table->string('image_path')->nullable();
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('line_total_cents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
