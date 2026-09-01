<?php

namespace App\Filament\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;

/**
 * The image gallery editor, shared by products and coffrets.
 *
 * Both own images through the same polymorphic relation, so the uploader is
 * defined once here rather than duplicated per resource.
 */
class GallerySection
{
    public static function make(string $description): Section
    {
        return Section::make('Images')
            ->description($description)
            ->schema([
                Repeater::make('images')
                    ->hiddenLabel()
                    ->relationship()
                    // Drag to reorder; the first image is the one used on cards,
                    // in the cart and on order confirmations.
                    ->orderColumn('position')
                    ->reorderable()
                    ->addActionLabel('Ajouter une image')
                    ->itemLabel(fn (array $state) => isset($state['path'])
                        ? basename((string) $state['path'])
                        : 'Nouvelle image')
                    // Deliberately not collapsible: collapsing reduces an image
                    // to its filename, and a filename is not something anyone
                    // can recognise a product photo by.
                    ->schema([
                        FileUpload::make('path')
                            ->hiddenLabel()
                            ->image()
                            ->imageEditor()
                            // Big enough to identify the product at a glance,
                            // small enough that four of them still fit on one
                            // screen. panelAspectRatio would override this.
                            ->imagePreviewHeight('160')
                            ->disk('public_files')
                            ->directory('uploads/products')
                            // Keeps the stored value relative to public/, which
                            // is what image_url() expects.
                            ->visibility('public')
                            ->maxSize(4096)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('JPEG, PNG ou WebP · 4 Mo maximum')
                            ->required()
                            // The disk builds preview URLs by concatenation, so
                            // the original folder names — which contain spaces
                            // and accents — came out unencoded and 404'd in the
                            // editor. Build the preview the same way the
                            // storefront does.
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
                            }),
                    ]),
            ]);
    }
}
