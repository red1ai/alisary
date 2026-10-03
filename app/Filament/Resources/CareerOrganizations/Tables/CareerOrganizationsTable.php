<?php

namespace App\Filament\Resources\CareerOrganizations\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CareerOrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_path')->label('الشعار')->disk('public')->visibility('public'),
                TextColumn::make('name')->label('الاسم')->searchable(),
                TextColumn::make('slug')->label('الرابط')->copyable(),
                TextColumn::make('openings_count')->label('الوظائف')->counts('openings'),
            ])
            ->recordActions([
                Action::make('page')->label('صفحة المؤسسة')->icon('heroicon-o-eye')->url(fn ($record): string => route('careers.organizations.show', $record))->openUrlInNewTab(),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
