<?php

namespace App\Filament\Resources\CareerApplications\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CareerApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('reference_number')->label('المرجع'),
                TextEntry::make('careerOpening.title')->label('الوظيفة'),
                TextEntry::make('full_name')->label('الاسم'),
                TextEntry::make('email')->label('البريد'),
                TextEntry::make('phone')->label('الهاتف'),
                TextEntry::make('created_at')->label('تاريخ التقديم')->dateTime(),
                KeyValueEntry::make('answers')
                    ->label('الإجابات')
                    ->state(function ($record): array {
                        $labels = collect($record->careerOpening?->fields() ?? [])->pluck('label', 'key');

                        return collect($record->answers ?? [])
                            ->mapWithKeys(fn ($value, $key): array => [
                                (string) ($labels[$key] ?? $key) => is_array($value) ? implode('، ', $value) : (string) $value,
                            ])
                            ->all();
                    })
                    ->columnSpanFull(),
            ]);
    }
}
