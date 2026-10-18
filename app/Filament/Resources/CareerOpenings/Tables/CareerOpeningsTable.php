<?php

namespace App\Filament\Resources\CareerOpenings\Tables;

use App\Enums\ListingStatus;
use App\Models\CareerOpening;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class CareerOpeningsTable
{
    /**
     * Timezone used to show and enter dates in the table and bulk actions; storage stays in the app timezone (UTC).
     */
    public const DISPLAY_TIMEZONE = 'Asia/Muscat';

    public const KEEP = 'keep';

    public const SET = 'set';

    public const CLEAR = 'clear';

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('المسمى')->searchable(),
                TextColumn::make('category')->label('الفئة')->badge()->formatStateUsing(fn ($state) => $state?->label()),
                TextColumn::make('status')->label('الحالة')->badge()->formatStateUsing(fn ($state) => $state?->label()),
                TextColumn::make('published_at')
                    ->label('تاريخ النشر (توقيت مسقط)')
                    ->dateTime('Y-m-d H:i', self::DISPLAY_TIMEZONE)
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('applications_count')->label('الطلبات')->counts('applications'),
                TextColumn::make('slug')->label('الرابط')->copyable(),
            ])
            ->recordActions([
                Action::make('preview')->label('معاينة')->icon('heroicon-o-eye')->url(fn ($record): string => route('jobs.show', $record))->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::publishSelectedAction(),
                    self::draftSelectedAction(),
                    self::editDatesAction(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function publishSelectedAction(): BulkAction
    {
        return BulkAction::make('publishSelected')
            ->label('نشر المحدد')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(fn (Collection $records): string => 'نشر '.$records->count().' وظيفة؟')
            ->modalDescription('ستُنشر الوظائف المحددة فقط. الوظيفة التي ليس لها تاريخ نشر يُعيَّن لها الوقت الحالي، ولا يتغير أي تاريخ نشر أو انتهاء موجود.')
            ->action(function (Collection $records): void {
                $records->each(function (CareerOpening $opening): void {
                    $opening->status = ListingStatus::Published;
                    $opening->published_at ??= now();
                    $opening->save();
                });

                Notification::make()->success()->title('تم نشر '.$records->count().' وظيفة')->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    public static function draftSelectedAction(): BulkAction
    {
        return BulkAction::make('draftSelected')
            ->label('تحويل المحدد إلى مسودة')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading(fn (Collection $records): string => 'تحويل '.$records->count().' وظيفة إلى مسودة؟')
            ->modalDescription('ستختفي الوظائف المحددة عن الزوار، مع الاحتفاظ بتاريخَي النشر والانتهاء.')
            ->action(function (Collection $records): void {
                $records->each(fn (CareerOpening $opening): bool => $opening->update(['status' => ListingStatus::Draft]));

                Notification::make()->success()->title('تم تحويل '.$records->count().' وظيفة إلى مسودة')->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    public static function editDatesAction(): BulkAction
    {
        return BulkAction::make('editDates')
            ->label('تعديل التواريخ')
            ->icon('heroicon-o-calendar-days')
            ->modalHeading(fn (Collection $records): string => 'تعديل تواريخ '.$records->count().' وظيفة')
            ->modalDescription('التواريخ بتوقيت مسقط (GMT+4). الحقل المضبوط على «بدون تغيير» لا يُمس.')
            ->schema([
                ...self::dateFieldSchema('published', 'تاريخ النشر'),
                ...self::dateFieldSchema('expires', 'تاريخ الانتهاء'),
            ])
            ->action(function (Collection $records, array $data): void {
                $changes = self::resolveDateChanges($data);

                if ($changes === []) {
                    Notification::make()->warning()->title('لم يُحدَّد أي تعديل')->send();

                    return;
                }

                $invalid = $records->filter(function (CareerOpening $opening) use ($changes): bool {
                    $published = array_key_exists('published_at', $changes) ? $changes['published_at'] : $opening->published_at;
                    $expires = array_key_exists('expires_at', $changes) ? $changes['expires_at'] : $opening->expires_at;

                    return $published !== null && $expires !== null && $expires->lte($published);
                });

                if ($invalid->isNotEmpty()) {
                    Notification::make()->danger()
                        ->title('تاريخ الانتهاء يجب أن يكون بعد تاريخ النشر')
                        ->body('لم يُحفظ أي تعديل. الوظائف المتعارضة: '.$invalid->pluck('title')->implode('، '))
                        ->persistent()
                        ->send();

                    return;
                }

                $records->each(fn (CareerOpening $opening): bool => $opening->update($changes));

                Notification::make()->success()->title('تم تعديل تواريخ '.$records->count().' وظيفة')->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * @return array<int, Radio|DateTimePicker>
     */
    private static function dateFieldSchema(string $name, string $label): array
    {
        return [
            Radio::make($name.'_mode')
                ->label($label)
                ->options([
                    self::KEEP => 'بدون تغيير',
                    self::SET => 'تعيين تاريخ',
                    self::CLEAR => 'مسح التاريخ',
                ])
                ->default(self::KEEP)
                ->required()
                ->live(),
            DateTimePicker::make($name.'_at')
                ->label($label.' الجديد (توقيت مسقط)')
                ->timezone(self::DISPLAY_TIMEZONE)
                ->seconds(false)
                ->visible(fn (callable $get): bool => $get($name.'_mode') === self::SET)
                ->required(fn (callable $get): bool => $get($name.'_mode') === self::SET),
        ];
    }

    /**
     * Maps the modal state to attributes to update; untouched fields are absent.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, ?Carbon>
     */
    public static function resolveDateChanges(array $data): array
    {
        $changes = [];

        foreach (['published' => 'published_at', 'expires' => 'expires_at'] as $name => $column) {
            $mode = $data[$name.'_mode'] ?? self::KEEP;

            if ($mode === self::CLEAR) {
                $changes[$column] = null;
            } elseif ($mode === self::SET && filled($data[$name.'_at'] ?? null)) {
                $changes[$column] = Carbon::parse($data[$name.'_at'], config('app.timezone'));
            }
        }

        return $changes;
    }
}
