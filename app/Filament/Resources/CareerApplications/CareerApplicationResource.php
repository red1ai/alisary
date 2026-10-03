<?php

namespace App\Filament\Resources\CareerApplications;

use App\Filament\Resources\CareerApplications\Pages\ListCareerApplications;
use App\Filament\Resources\CareerApplications\Pages\ViewCareerApplication;
use App\Filament\Resources\CareerApplications\Schemas\CareerApplicationInfolist;
use App\Filament\Resources\CareerApplications\Tables\CareerApplicationsTable;
use App\Models\CareerApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CareerApplicationResource extends Resource
{
    protected static ?string $model = CareerApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?string $navigationLabel = 'طلبات الوظائف الجديدة';

    protected static ?string $modelLabel = 'طلب وظيفة';

    protected static ?string $pluralModelLabel = 'طلبات الوظائف الجديدة';

    protected static string|UnitEnum|null $navigationGroup = 'التوظيف';

    protected static ?int $navigationSort = 15;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return CareerApplicationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CareerApplicationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCareerApplications::route('/'),
            'view' => ViewCareerApplication::route('/{record}'),
        ];
    }
}
