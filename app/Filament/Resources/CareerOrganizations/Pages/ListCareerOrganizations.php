<?php

namespace App\Filament\Resources\CareerOrganizations\Pages;

use App\Filament\Resources\CareerOrganizations\CareerOrganizationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCareerOrganizations extends ListRecords
{
    protected static string $resource = CareerOrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
