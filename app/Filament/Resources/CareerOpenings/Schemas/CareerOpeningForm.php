<?php

namespace App\Filament\Resources\CareerOpenings\Schemas;

use App\Enums\CareerCategory;
use App\Enums\ListingLocation;
use App\Enums\ListingStatus;
use App\Filament\Forms\CareerFieldBuilder;
use App\Filament\Resources\CareerOrganizations\Schemas\CareerOrganizationForm;
use App\Support\CareerBlocks;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CareerOpeningForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('الوظيفة')
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('البيانات الأساسية')->schema(self::basics()),
                        Tab::make('أقسام الصفحة')->schema(self::blocks()),
                        Tab::make('الاستمارة')->schema(self::form()),
                        Tab::make('شروط الأهلية')->schema(self::eligibility()),
                    ]),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function basics(): array
    {
        return [
            Section::make()->schema([
                TextInput::make('title')->label('المسمى الوظيفي')->required()->maxLength(255),
                TextInput::make('slug')
                    ->label('الرابط (slug)')
                    ->helperText('يظهر في /careers/{slug}')
                    ->required()
                    ->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->default(fn (): string => Str::lower(Str::random(8))),
                TextInput::make('subtitle')->label('سطر تعريفي تحت العنوان')->maxLength(255)->columnSpanFull(),
                TextInput::make('unit')->label('سطر الجهة فوق العنوان (اختياري)')->helperText('مثال: اسم المدرسة · الإدارة')->maxLength(255)->columnSpanFull(),
                TextInput::make('notice')->label('شريط تنبيه أعلى الصفحة (يظهر للزوار)')->maxLength(255)->columnSpanFull(),
                Select::make('career_organization_id')->label('المؤسسة')->relationship('organization', 'name')->searchable()->preload()
                    ->createOptionForm(fn (Schema $schema): Schema => CareerOrganizationForm::configure($schema)),
                Select::make('location')
                    ->label('الموقع')
                    ->options(collect(ListingLocation::cases())->mapWithKeys(fn (ListingLocation $case): array => [$case->value => $case->label()]))
                    ->searchable(),
                Select::make('category')
                    ->label('الفئة')
                    ->options(collect(CareerCategory::cases())->mapWithKeys(fn (CareerCategory $case): array => [$case->value => $case->label()])),
                Select::make('status')
                    ->label('الحالة')
                    ->options(collect(ListingStatus::cases())->mapWithKeys(fn (ListingStatus $case): array => [$case->value => $case->label()]))
                    ->required()
                    ->default(ListingStatus::Draft->value)
                    ->helperText('المسودة لا تظهر للزوار. لا يُفتح التقديم إلا بعد النشر وإضافة حقول استمارة ومحتوى للصفحة.'),
                DateTimePicker::make('published_at')->label('تاريخ النشر'),
                DateTimePicker::make('expires_at')->label('تاريخ الانتهاء'),
                Textarea::make('summary')->label('ملخص قصير (يظهر أعلى المحتوى)')->maxLength(500)->rows(3)->columnSpanFull(),
                Repeater::make('chips')
                    ->label('شارات البيانات السريعة (تظهر في الترويسة)')
                    ->schema([
                        TextInput::make('k')->label('العنوان')->required(),
                        TextInput::make('v')->label('القيمة')->required(),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->addActionLabel('إضافة شارة')
                    ->columnSpanFull(),
            ])->columns(2),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function blocks(): array
    {
        $title = fn (string $key): TextInput => TextInput::make("blocks.{$key}.title")
            ->label('عنوان القسم')
            ->placeholder(CareerBlocks::definitions()[$key]['title']);
        $on = fn (string $key): Toggle => Toggle::make("blocks.{$key}.on")->label('إظهار هذا القسم')->default(false);
        $lines = fn (string $key, string $label): Repeater => Repeater::make("blocks.{$key}.{$label}")
            ->label($label === 'items' ? 'البنود' : ($label === 'must' ? 'شروط أساسية' : 'يُفضَّل'))
            ->simple(TextInput::make('text')->required())
            ->defaultItems(0)
            ->addActionLabel('إضافة بند')
            ->reorderable();
        $pairs = fn (string $key): Repeater => Repeater::make("blocks.{$key}.items")
            ->label('البنود')
            ->schema([
                TextInput::make('k')->label('العنوان')->required(),
                TextInput::make('v')->label('التفصيل'),
            ])
            ->columns(2)
            ->defaultItems(0)
            ->addActionLabel('إضافة بند');

        return [
            RichEditor::make('description')
                ->label('وصف حر إضافي (اختياري، يظهر بعد الأقسام)')
                ->columnSpanFull(),
            Section::make('عن الوظيفة')->collapsed()->schema([$on('about'), $title('about'), Textarea::make('blocks.about.text')->label('النص')->rows(4)]),
            Section::make('ماذا ستفعل؟')->collapsed()->schema([$on('tasks'), $title('tasks'), $lines('tasks', 'items')]),
            Section::make('من نبحث عنه؟')->collapsed()->schema([$on('who'), $title('who'), $lines('who', 'must'), $lines('who', 'prefer')]),
            Section::make('الوقت والالتزام')->collapsed()->schema([$on('schedule'), $title('schedule'), $pairs('schedule')]),
            Section::make('الفروع والمقاعد')->collapsed()->schema([
                $on('branches'),
                $title('branches'),
                Repeater::make('blocks.branches.rows')->label('الفروع')->schema([
                    TextInput::make('name')->label('الفرع')->required(),
                    TextInput::make('seats')->label('المقاعد')->numeric(),
                ])->columns(2)->defaultItems(0)
                    ->addActionLabel('إضافة فرع'),
            ]),
            Section::make('الأجر')->collapsed()->schema([$on('pay'), $title('pay'), Textarea::make('blocks.pay.text')->label('النص')->rows(3)]),
            Section::make('مسار الترقّي')->collapsed()->schema([$on('growth'), $title('growth'), $lines('growth', 'items')]),
            Section::make('كيف يُقاس نجاحك؟')->collapsed()->schema([$on('kpis'), $title('kpis'), $lines('kpis', 'items')]),
            Section::make('خماسية السكينة')->collapsed()->schema([
                $on('values'),
                $title('values'),
                CheckboxList::make('blocks.values.emph')
                    ->label('الأعمدة التي تتأكد عليها الوظيفة')
                    ->options(collect(CareerBlocks::pillars())->mapWithKeys(fn (array $pillar): array => [$pillar['id'] => $pillar['name']])),
                Textarea::make('blocks.values.note')->label('ملاحظة تحت الأعمدة')->rows(2),
            ]),
            Section::make('ماذا بعد التقديم؟')->collapsed()->schema([$on('process'), $title('process'), $pairs('process')]),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function form(): array
    {
        return [
            TextInput::make('form_title')->label('عنوان الاستمارة')->placeholder('نموذج التقديم'),
            Textarea::make('form_lead')->label('تعريف قصير أعلى الاستمارة')->rows(2),
            Textarea::make('success_message')->label('رسالة النجاح بعد الإرسال')->rows(2),
            Toggle::make('identity_in_first_step')
                ->label('وضع الاسم والهاتف والبريد داخل الخطوة الأولى')
                ->helperText('عند التفعيل لا تظهر خطوة «البيانات الأساسية» منفصلة، وتُعرض هذه الحقول في أول خطوة من الخطوات أدناه.')
                ->default(false),
            TextInput::make('core_field_labels.full_name')->label('تسمية حقل الاسم')->placeholder('الاسم الكامل'),
            TextInput::make('core_field_labels.phone')->label('تسمية حقل الهاتف')->placeholder('رقم الهاتف'),
            TextInput::make('core_field_labels.email')->label('تسمية حقل البريد')->placeholder('البريد الإلكتروني'),
            Repeater::make('form_sections')
                ->label('خطوات الاستمارة')
                ->helperText('الخطوة الأولى (الاسم والهاتف والبريد) ثابتة. كل قسم هنا يصبح خطوة، ويمكن ترتيب الأقسام والحقول بالسحب.')
                ->schema([
                    TextInput::make('title')->label('عنوان الخطوة')->required()->maxLength(255),
                    Textarea::make('description')->label('وصف قصير')->rows(2)->columnSpanFull(),
                    Repeater::make('fields')
                        ->label('حقول الخطوة')
                        ->schema(CareerFieldBuilder::schema())
                        ->columns(2)
                        ->reorderable()
                        ->defaultItems(0)
                        ->addActionLabel('إضافة حقل')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->reorderable()
                ->defaultItems(0)
                ->addActionLabel('إضافة خطوة')
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function eligibility(): array
    {
        return [
            Repeater::make('eligibility_rules')
                ->label('الشروط الحاسمة')
                ->helperText('يتحقق منها الخادم عند الإرسال، ويُرفض الطلب برسالتك إن لم تتحقق. مفتاح الحقل هو «المفتاح» المكتوب في الاستمارة.')
                ->schema([
                    TextInput::make('field')->label('مفتاح الحقل')->required(),
                    Select::make('operator')
                        ->label('الشرط')
                        ->options([
                            'equals' => 'يساوي',
                            'not_equals' => 'لا يساوي',
                            'in' => 'ضمن القيم (مفصولة بفاصلة)',
                            'not_in' => 'ليس ضمن القيم (مفصولة بفاصلة)',
                            'min' => 'رقم لا يقل عن',
                            'max' => 'رقم لا يزيد عن',
                            'min_age' => 'عمر لا يقل عن (حقل تاريخ ميلاد)',
                        ])
                        ->required(),
                    TextInput::make('value')->label('القيمة')->required(),
                    TextInput::make('message')->label('رسالة الرفض')->maxLength(255)->columnSpanFull(),
                ])
                ->columns(3)
                ->defaultItems(0)
                ->addActionLabel('إضافة شرط'),
        ];
    }
}
