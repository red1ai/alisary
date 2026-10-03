<?php

namespace App\Filament\Resources\CareerOrganizations\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CareerOrganizationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('logo_path')->label('الشعار')->disk('public')->visibility('public'),
                        TextEntry::make('name')->label('الاسم'),
                        TextEntry::make('slug')->label('الرابط')->copyable(),
                        TextEntry::make('openings_count')->label('الوظائف المرتبطة')->state(fn ($record): int => $record->openings()->count()),
                        TextEntry::make('description')->label('الوصف')->placeholder('—')->columnSpanFull(),
                    ]),
            ]);
    }
}
