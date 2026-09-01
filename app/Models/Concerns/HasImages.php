<?php

namespace App\Models\Concerns;

use App\Models\Image;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Shared by products and coffrets.
 *
 * A polymorphic relation cannot carry a database foreign key, so there is no
 * ON DELETE CASCADE to lean on. Without the hook below, deleting an owner
 * leaves its image rows behind — and they would later attach themselves to
 * whatever record next takes that id.
 */
trait HasImages
{
    public static function bootHasImages(): void
    {
        static::deleting(function ($model) {
            $model->images()->delete();
        });
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->orderBy('position');
    }

    public function primaryImage(): ?Image
    {
        return $this->images->first();
    }

    /** Empty string rather than null: it goes straight into a CSS url(). */
    public function primaryImageUrl(): string
    {
        return $this->primaryImage()?->url() ?? '';
    }
}
