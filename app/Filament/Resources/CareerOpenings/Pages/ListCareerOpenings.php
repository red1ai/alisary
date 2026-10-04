<?php

namespace App\Filament\Resources\CareerOpenings\Pages;

use App\Filament\Resources\CareerOpenings\CareerOpeningResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCareerOpenings extends ListRecords
{
    protected static string $resource = CareerOpeningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
