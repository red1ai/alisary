<?php

namespace App\Filament\Resources\CareerApplications\Pages;

use App\Filament\Resources\CareerApplications\CareerApplicationResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewCareerApplication extends ViewRecord
{
    protected static string $resource = CareerApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return collect($this->getRecord()->files ?? [])
            ->flatMap(fn (string|array $paths, string $key): array => collect((array) $paths)
                ->values()
                ->mapWithKeys(fn (string $path, int $index): array => ["{$key}_{$index}" => [$key, $path]])
                ->all())
            ->map(fn (array $entry, string $name): Action => Action::make("download_{$name}")
                ->label("تنزيل: {$entry[0]}")
                ->action(fn () => Storage::disk('local')->download($entry[1])))
            ->values()
            ->all();
    }
}
