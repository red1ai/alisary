<?php

namespace App\Filament\Resources\CareerOrganizations\Pages;

use App\Filament\Resources\CareerOrganizations\CareerOrganizationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCareerOrganization extends ViewRecord
{
    protected static string $resource = CareerOrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
