<?php

namespace App\Filament\Resources\CareerOpenings\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CareerOpeningsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('المسمى')->searchable(),
                TextColumn::make('category')->label('الفئة')->badge()->formatStateUsing(fn ($state) => $state?->label()),
                TextColumn::make('status')->label('الحالة')->badge()->formatStateUsing(fn ($state) => $state?->label()),
                TextColumn::make('applications_count')->label('الطلبات')->counts('applications'),
                TextColumn::make('slug')->label('الرابط')->copyable(),
            ])
            ->recordActions([
                Action::make('preview')->label('معاينة')->icon('heroicon-o-eye')->url(fn ($record): string => route('jobs.show', $record))->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
