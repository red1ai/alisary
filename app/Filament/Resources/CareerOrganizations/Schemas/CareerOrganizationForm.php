<?php

namespace App\Filament\Resources\CareerOrganizations\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CareerOrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('اسم المؤسسة')->required()->maxLength(255),
                        TextInput::make('slug')
                            ->label('الرابط (slug)')
                            ->helperText('يظهر في /careers/organizations/{slug}')
                            ->required()
                            ->alphaDash()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->default(fn (): string => Str::lower(Str::random(8))),
                        FileUpload::make('logo_path')
                            ->label('الشعار')
                            ->helperText('PNG أو JPG أو WebP، حتى 2 ميغابايت. يُستخدم أيضًا كصورة مشاركة الروابط.')
                            ->disk('public')
                            ->visibility('public')
                            ->directory('career-organizations/logos')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->maxSize(2048)
                            ->imageEditor()
                            ->columnSpanFull(),
                        Textarea::make('description')->label('وصف المؤسسة (اختياري)')->rows(4)->maxLength(1000)->columnSpanFull(),
                    ]),
            ]);
    }
}
