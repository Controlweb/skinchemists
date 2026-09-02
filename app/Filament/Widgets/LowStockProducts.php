<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LowStockProducts extends TableWidget
{
    protected static ?string $heading = 'Stock à réapprovisionner';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    /** Hidden entirely when nothing needs attention. */
    public static function canView(): bool
    {
        return Product::whereColumn('stock', '<=', 'low_stock_threshold')->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->whereColumn('stock', '<=', 'low_stock_threshold')
                    ->orderBy('stock')
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label('Produit')->wrap()->limit(60),
                TextColumn::make('sku')->label('SKU')->visibleFrom('md'),
                TextColumn::make('stock')->label('Stock')->badge()
                    ->color(fn (Product $record) => $record->stock < 1 ? 'danger' : 'warning'),
                TextColumn::make('low_stock_threshold')->label('Seuil')->visibleFrom('md'),
            ])
            ->recordUrl(fn (Product $record) => ProductResource::getUrl('edit', ['record' => $record]));
    }
}
