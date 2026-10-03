<?php

namespace App\Filament\Resources\CareerOrganizations;

use App\Filament\Resources\CareerOrganizations\Pages\CreateCareerOrganization;
use App\Filament\Resources\CareerOrganizations\Pages\EditCareerOrganization;
use App\Filament\Resources\CareerOrganizations\Pages\ListCareerOrganizations;
use App\Filament\Resources\CareerOrganizations\Pages\ViewCareerOrganization;
use App\Filament\Resources\CareerOrganizations\Schemas\CareerOrganizationForm;
use App\Filament\Resources\CareerOrganizations\Schemas\CareerOrganizationInfolist;
use App\Filament\Resources\CareerOrganizations\Tables\CareerOrganizationsTable;
use App\Models\CareerOrganization;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CareerOrganizationResource extends Resource
{
    protected static ?string $model = CareerOrganization::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'مؤسسات الوظائف';

    protected static ?string $modelLabel = 'مؤسسة';

    protected static ?string $pluralModelLabel = 'مؤسسات الوظائف';

    protected static string|\UnitEnum|null $navigationGroup = 'التوظيف';

    protected static ?int $navigationSort = 13;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CareerOrganizationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CareerOrganizationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CareerOrganizationsTable::configure($table);
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
            'index' => ListCareerOrganizations::route('/'),
            'create' => CreateCareerOrganization::route('/create'),
            'view' => ViewCareerOrganization::route('/{record}'),
            'edit' => EditCareerOrganization::route('/{record}/edit'),
        ];
    }
}
