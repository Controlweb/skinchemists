<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;

class ListContactMessages extends ListRecords
{
    protected static string $resource = ContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'nouveau' => Tab::make('À traiter')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'nouveau'))
                ->badge(ContactMessage::where('status', 'nouveau')->count()),
            'traite' => Tab::make('Traités')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'traite')),
            'all' => Tab::make('Tous'),
        ];
    }
}
