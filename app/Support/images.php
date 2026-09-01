<?php

if (! function_exists('image_url')) {
    /**
     * Public URL for a path stored relative to public/.
     *
     * Paths are stored decoded (that is what the filesystem holds and what an
     * upload produces), so each segment is encoded here. asset() does not do
     * it, and the product folders contain spaces and accents.
     */
    function image_url(?string $path): string
    {
        if (! $path) {
            return '';
        }

        return asset(implode('/', array_map('rawurlencode', explode('/', $path))));
    }
}
