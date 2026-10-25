<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings as GeneralSettingsData;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class GeneralSettings extends SettingsPage
{
    protected static string $settings = GeneralSettingsData::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'الإعدادات العامة';

    protected static ?string $title = 'الإعدادات العامة';

    protected static string|UnitEnum|null $navigationGroup = 'إعدادات الموقع';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('إعدادات الموقع')
                    ->tabs([
                        Tab::make('الهوية')
                            ->schema([
                                Section::make('هوية الموقع')
                                    ->schema([
                                        TextInput::make('site_name')->label('اسم الموقع')->required()->maxLength(255),
                                        TextInput::make('slogan')->label('الشعار النصي')->maxLength(255),
                                        FileUpload::make('logo_path')
                                            ->label('الشعار')
                                            ->disk('public')
                                            ->visibility('public')
                                            ->image()
                                            ->directory('site')
                                            ->imageEditor(),
                                        TextInput::make('assistant_url')->label('رابط المساعد الذكي')->url()->maxLength(255),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('التواصل')
                            ->schema([
                                Section::make('بيانات التواصل')
                                    ->schema([
                                        TextInput::make('email')->label('البريد')->email()->maxLength(255),
                                        TextInput::make('phone')->label('الهاتف')->tel()->maxLength(50),
                                        Textarea::make('address')->label('العنوان')->rows(3)->columnSpanFull(),
                                        Repeater::make('social_links')
                                            ->label('الروابط الاجتماعية')
                                            ->schema([
                                                TextInput::make('label')->label('التسمية')->required()->maxLength(120),
                                                TextInput::make('url')->label('الرابط')->url()->required()->maxLength(255),
                                                TextInput::make('icon')->label('الأيقونة')->maxLength(80),
                                            ])
                                            ->columns(3)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('SEO')
                            ->schema([
                                Section::make('محركات البحث')
                                    ->schema([
                                        TextInput::make('seo_title')->label('عنوان SEO')->maxLength(255),
                                        Textarea::make('seo_description')->label('وصف SEO')->rows(3)->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('إشعارات التقديم')
                            ->schema([
                                Section::make('مستلمو تنبيهات الطلبات المكررة')
                                    ->description('يُستخدم هذا الإعداد فقط لتنبيه «طلب مكرر محتمل» عند التقديم عبر النموذج الموحّد. لا يؤثر في إشعارات الطلبات الجديدة، فهي تُرسل دائمًا إلى العنوان المضبوط في إعدادات الخادم (CAREER_APPLICATIONS_NOTIFY_TO)، ولا في إشعار نشر الوظائف.')
                                    ->schema([
                                        Repeater::make('job_submission_recipients')
                                            ->label('البريد المستلم')
                                            ->schema([
                                                TextInput::make('email')
                                                    ->label('البريد الإلكتروني')
                                                    ->email()
                                                    ->required()
                                                    ->maxLength(255),
                                            ])
                                            ->addActionLabel('إضافة بريد')
                                            ->columns(1)
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                                Section::make('مستلمو طلبات المناقصات')
                                    ->schema([
                                        Repeater::make('tender_submission_recipients')
                                            ->label('البريد المستلم')
                                            ->schema([
                                                TextInput::make('email')
                                                    ->label('البريد الإلكتروني')
                                                    ->email()
                                                    ->required()
                                                    ->maxLength(255),
                                            ])
                                            ->addActionLabel('إضافة بريد')
                                            ->columns(1)
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                                Section::make('مستلمو طلبات حقوق البيانات')
                                    ->schema([
                                        Repeater::make('privacy_rights_recipients')
                                            ->label('البريد المستلم')
                                            ->schema([
                                                TextInput::make('email')
                                                    ->label('البريد الإلكتروني')
                                                    ->email()
                                                    ->required()
                                                    ->maxLength(255),
                                            ])
                                            ->addActionLabel('إضافة بريد')
                                            ->columns(1)
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }
}
