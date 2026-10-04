<?php

namespace App\Filament\Resources\CareerOrganizations\Pages;

use App\Filament\Resources\CareerOrganizations\CareerOrganizationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCareerOrganization extends EditRecord
{
    protected static string $resource = CareerOrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
