<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->morphs('imageable');          // products and bundles today
            // Path relative to public/, stored decoded. The URL is built by
            // Image::url(), which encodes each segment — the folder names
            // contain spaces and accents.
            $table->string('path');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['imageable_type', 'imageable_id', 'position'], 'images_owner_position_index');
        });

        if (Schema::hasTable('product_images')) {
            foreach (DB::table('product_images')->orderBy('id')->cursor() as $row) {
                DB::table('images')->insert([
                    'imageable_type' => 'App\Models\Product',
                    'imageable_id' => $row->product_id,
                    'path' => rawurldecode($row->path),
                    'position' => $row->position,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }

            Schema::drop('product_images');
        }
    }

    public function down(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        foreach (DB::table('images')->where('imageable_type', 'App\Models\Product')->cursor() as $row) {
            DB::table('product_images')->insert([
                'product_id' => $row->imageable_id,
                'path' => $row->path,
                'position' => $row->position,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::dropIfExists('images');
    }
};
