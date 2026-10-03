<?php

namespace App\Filament\Resources\CareerOpenings\Pages;

use App\Filament\Resources\CareerOpenings\CareerOpeningResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCareerOpening extends EditRecord
{
    protected static string $resource = CareerOpeningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('معاينة الصفحة')
                ->icon('heroicon-o-eye')
                ->url(fn (): string => route('careers.show', $this->getRecord()))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
