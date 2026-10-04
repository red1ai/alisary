<?php

namespace App\Http\Controllers;

use App\Enums\ListingLocation;
use App\Models\CareerOpening;
use App\Models\Company;
use App\Models\JobFamily;
use App\Models\JobListing;
use App\Models\TenderListing;
use App\Settings\GeneralSettings;
use App\Settings\HomepageSettings;
use App\Settings\StorySettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;

class WebsiteController extends Controller
{
    public function home(GeneralSettings $settings, HomepageSettings $homepageSettings): View
    {
        return view('website.home', [
            'settings' => $settings,
            'sections' => $this->sections($homepageSettings),
            'companies' => Company::query()->active()->orderBy('sort_order')->get(),
            'jobs' => JobListing::query()->published()->with('company')->latest('published_at')->take(3)->get(),
            'tenders' => TenderListing::query()->published()->with('contractor')->latest('published_at')->take(3)->get(),
        ]);
    }

    public function story(GeneralSettings $settings, StorySettings $storySettings): View
    {
        return view('website.story', [
            'settings' => $settings,
            'storySettings' => $storySettings,
        ]);
    }

    public function jobs(GeneralSettings $settings): View
    {
        $companies = Company::query()->active()->orderBy('sort_order')->get();
        $jobFamilies = JobFamily::query()->active()->orderBy('sort_order')->orderBy('name')->get();
        $listings = JobListing::query()->published()->with(['company', 'jobFamily'])->latest('published_at')->get();
        $schoolCompany = $companies->firstWhere('slug', 'g-reader-school');
        $schoolBranchOptions = $schoolCompany === null
            ? collect()
            : $listings
                ->where('company_id', $schoolCompany->id)
                ->flatMap(fn (JobListing $listing): array => $listing->locations ?? [])
                ->filter()
                ->unique()
                ->map(fn (string $value): array => [
                    'value' => $value,
                    'label' => ListingLocation::tryFrom($value)?->label() ?? $value,
                ])
                ->values();

        $jobTitles = $listings
            ->sortBy('title')
            ->groupBy('company_id')
            ->map(fn ($jobs) => $jobs->map(fn (JobListing $job): array => [
                'title' => $job->title,
                'code' => $job->job_code,
                'value' => $job->job_code ?? $job->title,
                'label' => $job->title,
                'track' => $job->jobFamily?->track?->value,
                'locations' => collect($job->locations ?? [])
                    ->map(function (string $value): array {
                        $location = ListingLocation::tryFrom($value);
                        $governorate = $location?->governorate();

                        return [
                            'value' => $value,
                            'label' => $location?->label() ?? $value,
                            'governorate_value' => $governorate?->value,
                            'governorate_label' => $governorate?->label(),
                        ];
                    })
                    ->values()
                    ->all(),
            ])->values());

        return view('website.listings.index', [
            'settings' => $settings,
            'type' => 'jobs',
            'label' => 'الوظائف',
            'description' => 'فرص مهنية لخدمة الطفل ومن يخدم الطفل، مع نماذج تقديم مخصصة بحسب احتياج كل وظيفة.',
            'listings' => $listings,
            'companies' => $companies,
            'jobFamilies' => $jobFamilies,
            'jobTitles' => $jobTitles,
            'schoolCompany' => $schoolCompany,
            'schoolBranchOptions' => $schoolBranchOptions,
        ]);
    }

    public function tenders(GeneralSettings $settings): View
    {
        return view('website.listings.index', [
            'settings' => $settings,
            'type' => 'tenders',
            'label' => 'المناقصات',
            'description' => 'دعوات منظمة للموردين والشركاء، بخطوات تقديم واضحة لكل مناقصة.',
            'listings' => TenderListing::query()->published()->with('contractor')->latest('published_at')->paginate(9),
        ]);
    }

    public function showJob(GeneralSettings $settings, string $slug): View
    {
        $careerOpening = CareerOpening::where('slug', $slug)->first();

        if ($careerOpening !== null) {
            return app(CareerOpeningController::class)->show($settings, $careerOpening);
        }

        $jobListing = JobListing::where('slug', $slug)->firstOrFail();

        abort_unless($jobListing->isAcceptingSubmissions(), 404);

        return view('website.listings.show', [
            'settings' => $settings,
            'type' => 'jobs',
            'label' => 'وظيفة',
            'listing' => $jobListing->load(['company', 'jobFamily']),
        ]);
    }

    public function showTender(GeneralSettings $settings, TenderListing $tenderListing): View
    {
        abort_unless($tenderListing->isAcceptingSubmissions(), 404);

        return view('website.listings.show', [
            'settings' => $settings,
            'type' => 'tenders',
            'label' => 'مناقصة',
            'listing' => $tenderListing->load('contractor'),
        ]);
    }

    protected function sections(HomepageSettings $homepageSettings): Collection
    {
        return collect([
            'hero' => $homepageSettings->hero,
            'proof' => $homepageSettings->proof,
            'legacy' => $homepageSettings->legacy,
            'impact' => $homepageSettings->impact,
            'waqf' => $homepageSettings->waqf,
            'doors' => $homepageSettings->doors,
            'founder' => $homepageSettings->founder,
        ])->map(fn (array $section, string $key): Fluent => new Fluent([
            'key' => $key,
            'title' => $section['title'] ?? null,
            'eyebrow' => $section['eyebrow'] ?? null,
            'content' => collect($section)->except(['title', 'eyebrow'])->all(),
        ]));
    }
}
