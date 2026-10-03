<?php

namespace App\Filament\Resources\CareerOpenings;

use App\Filament\Resources\CareerOpenings\Pages\CreateCareerOpening;
use App\Filament\Resources\CareerOpenings\Pages\EditCareerOpening;
use App\Filament\Resources\CareerOpenings\Pages\ListCareerOpenings;
use App\Filament\Resources\CareerOpenings\Schemas\CareerOpeningForm;
use App\Filament\Resources\CareerOpenings\Tables\CareerOpeningsTable;
use App\Models\CareerOpening;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CareerOpeningResource extends Resource
{
    protected static ?string $model = CareerOpening::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $navigationLabel = 'الوظائف الجديدة (صفحات مستقلة)';

    protected static ?string $modelLabel = 'وظيفة';

    protected static ?string $pluralModelLabel = 'الوظائف الجديدة';

    protected static string|\UnitEnum|null $navigationGroup = 'التوظيف';

    protected static ?int $navigationSort = 14;

    public static function form(Schema $schema): Schema
    {
        return CareerOpeningForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CareerOpeningsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCareerOpenings::route('/'),
            'create' => CreateCareerOpening::route('/create'),
            'edit' => EditCareerOpening::route('/{record}/edit'),
        ];
    }
}
