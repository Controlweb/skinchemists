<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    protected $guarded = [];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Public URL for the stored path.
     *
     * Paths are stored decoded, because that is what the filesystem holds and
     * what an upload produces. The product folders came from the source data
     * with spaces and accents in their names, so each segment has to be encoded
     * here — asset() does not do it, and an un-encoded space breaks the URL.
     */
    public function url(): string
    {
        return image_url($this->path);
    }

    /** True when the file behind this record still exists on disk. */
    public function exists(): bool
    {
        return is_file(public_path($this->path));
    }
}
