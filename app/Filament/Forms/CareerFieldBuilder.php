<?php

namespace App\Filament\Forms;

use App\Enums\CustomFieldType;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Field builder for the new career openings. The legacy CustomFieldBuilder is left untouched.
 */
class CareerFieldBuilder
{
    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            ...collect(CustomFieldType::cases())->mapWithKeys(fn (CustomFieldType $type): array => [$type->value => $type->label()])->all(),
            'radio' => 'اختيار واحد (أزرار)',
            'core' => 'حقل أساسي: الاسم أو الهاتف أو البريد',
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function schema(): array
    {
        $choices = fn (Get $get): bool => in_array($get('type'), ['select', 'radio', 'checkbox_list'], true);
        $isFile = fn (Get $get): bool => $get('type') === 'file';
        $isText = fn (Get $get): bool => in_array($get('type'), ['text', 'textarea', 'phone', 'email', 'core'], true);
        $isPlainText = fn (Get $get): bool => in_array($get('type'), ['text', 'textarea'], true);

        return [
            TextInput::make('key')
                ->label('المفتاح')
                ->helperText('حروف إنجليزية وأرقام وشرطات فقط، مثال: experience_years. للحقل الأساسي استخدم full_name أو phone أو email.')
                ->required()
                ->alphaDash()
                ->maxLength(80)
                ->rules(fn (Get $get): array => $get('type') === 'core' ? ['in:full_name,phone,email'] : []),
            TextInput::make('label')->label('التسمية')->required()->maxLength(500),
            Select::make('type')
                ->label('نوع الحقل')
                ->options(self::typeOptions())
                ->required()
                ->live()
                ->default(CustomFieldType::Text->value),
            Toggle::make('required')->label('إلزامي')->default(false),
            TextInput::make('hint')->label('نص مساعد تحت الحقل')->maxLength(255)->columnSpanFull(),
            TextInput::make('max_length')->label('أقصى عدد أحرف')->numeric()->minValue(1)->visible($isText),
            TextInput::make('pattern')
                ->label('نمط تحقق (Regex)')
                ->helperText('مثال: ^\d{6,10}$ ')
                ->maxLength(255)
                ->visible($isText),
            TextInput::make('pattern_message')->label('رسالة خطأ النمط')->maxLength(255)->visible($isText),
            Select::make('inputmode')
                ->label('لوحة المفاتيح على الجوال')
                ->options(['numeric' => 'أرقام', 'tel' => 'هاتف', 'decimal' => 'أرقام عشرية', 'email' => 'بريد'])
                ->placeholder('الافتراضي')
                ->visible($isPlainText),
            Repeater::make('options')
                ->label('خيارات القائمة')
                ->schema([
                    TextInput::make('label')->label('التسمية')->required(),
                    TextInput::make('value')->label('القيمة')->required(),
                ])
                ->columns(2)
                ->defaultItems(0)
                ->addActionLabel('إضافة خيار')
                ->visible($choices)
                ->columnSpanFull(),
            Select::make('accepted_file_types')
                ->label('أنواع الملفات')
                ->multiple()
                ->options(['pdf' => 'PDF', 'doc' => 'DOC', 'docx' => 'DOCX', 'jpg' => 'JPG', 'jpeg' => 'JPEG', 'png' => 'PNG'])
                ->visible($isFile),
            TextInput::make('max_file_size_kb')->label('أقصى حجم بالكيلوبايت')->numeric()->default(5120)->visible($isFile),
            Toggle::make('multiple')->label('السماح بعدة ملفات')->default(false)->visible($isFile),
        ];
    }
}
