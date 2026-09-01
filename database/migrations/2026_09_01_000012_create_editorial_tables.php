<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            // Matches products.ingredient by name. Kept as a string join rather
            // than a foreign key: the facet is a fixed shortlist, and this table
            // exists for the editorial page, not for the catalogue's integrity.
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('intro')->nullable();
            $table->text('what')->nullable();
            $table->json('benefits')->nullable();
            $table->text('how')->nullable();
            $table->text('who')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category');
            $table->string('author');
            $table->unsignedSmallInteger('read_minutes')->default(5);
            $table->text('excerpt')->nullable();
            $table->text('lead')->nullable();
            $table->json('body')->nullable();          // [{h, p}, …]
            $table->string('image_path')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('article_product', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->primary(['article_id', 'product_id']);
        });

        Schema::create('bundles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tag')->nullable();
            $table->text('blurb')->nullable();
            // The saving actually applied at checkout, not just advertised.
            $table->unsignedTinyInteger('discount_percent')->default(15);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('bundle_product', function (Blueprint $table) {
            $table->foreignId('bundle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->primary(['bundle_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bundle_product');
        Schema::dropIfExists('bundles');
        Schema::dropIfExists('article_product');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('ingredients');
    }
};
