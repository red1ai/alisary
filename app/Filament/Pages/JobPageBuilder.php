<?php

namespace App\Filament\Pages;

use App\Http\Controllers\JobPageToolController;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class JobPageBuilder extends Page
{
    protected string $view = 'filament.pages.job-page-builder';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'منشئ صفحات الوظائف';

    protected static ?string $title = 'منشئ صفحات الوظائف';

    protected static string|UnitEnum|null $navigationGroup = 'التوظيف';

    protected static ?int $navigationSort = 30;

    public static function canAccess(): bool
    {
        return Gate::allows('useJobPageBuilder');
    }

    /**
     * @return array<string, string> page file name => preview URL
     */
    public function previews(): array
    {
        return collect(JobPageToolController::previewablePages())
            ->mapWithKeys(fn (string $page): array => [$page => route('job-pages.preview', $page)])
            ->all();
    }
}
