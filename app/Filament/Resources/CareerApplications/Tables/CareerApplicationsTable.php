<?php

namespace App\Filament\Resources\CareerApplications\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CareerApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')->label('المرجع')->searchable(),
                TextColumn::make('careerOpening.title')->label('الوظيفة')->searchable(),
                TextColumn::make('full_name')->label('الاسم')->searchable(),
                TextColumn::make('email')->label('البريد')->searchable(),
                TextColumn::make('phone')->label('الهاتف'),
                TextColumn::make('created_at')->label('تاريخ التقديم')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
