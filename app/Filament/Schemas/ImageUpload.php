<?php

namespace App\Filament\Schemas;

use Filament\Forms\Components\FileUpload;

/**
 * A single image stored relative to public/, the way the storefront expects.
 *
 * Shared because the preview workaround is not obvious and is easy to leave
 * out: the public_files disk builds URLs by concatenation, so a folder name
 * containing a space or an accent comes back unencoded and 404s in the editor
 * even though the file is there. Previews are built the way image_url() does.
 */
class ImageUpload
{
    public static function make(string $name, string $directory): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->imageEditor()
            ->imagePreviewHeight('160')
            ->disk('public_files')
            ->directory($directory)
            // Keeps the stored value relative to public/, which is what
            // image_url() expects.
            ->visibility('public')
            ->maxSize(4096)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->getUploadedFileUsing(function (string $file): ?array {
                $absolute = public_path($file);

                if (! is_file($absolute)) {
                    return null;
                }

                return [
                    'name' => basename($file),
                    'size' => filesize($absolute) ?: 0,
                    'type' => match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
                        'png' => 'image/png',
                        'jpg', 'jpeg' => 'image/jpeg',
                        default => 'image/webp',
                    },
                    'url' => image_url($file),
                ];
            });
    }
}
