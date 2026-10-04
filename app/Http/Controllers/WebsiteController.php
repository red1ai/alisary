<?php

namespace App\Http\Controllers;

use App\Enums\CareerCategory;
use App\Models\CareerOpening;
use App\Models\CareerOrganization;
use App\Models\Company;
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
        $openings = CareerOpening::query()
            ->with(['company', 'organization'])
            ->latest('published_at')
            ->get()
            ->filter(fn (CareerOpening $opening): bool => $opening->isPublished())
            ->values();

        $organizations = $openings
            ->map(fn (CareerOpening $opening): string => $opening->organizationName() ?? 'مجموعة العيسري')
            ->unique()
            ->values()
            ->mapWithKeys(fn (string $name, int $index): array => [$name => 'org-'.($index + 1)]);

        $schoolOrganization = CareerOrganization::query()->where('slug', 'g-reader-school')->first();
        $schoolOrganizationKey = $schoolOrganization === null ? null : $organizations->get($schoolOrganization->name);
        $schoolBranchOptions = $schoolOrganization === null
            ? collect()
            : $openings
                ->filter(fn (CareerOpening $opening): bool => $opening->career_organization_id === $schoolOrganization->id && $opening->location !== null)
                ->map(fn (CareerOpening $opening): array => [
                    'value' => $opening->location->value,
                    'label' => $opening->location->label(),
                ])
                ->unique('value')
                ->values();

        return view('website.listings.index', [
            'settings' => $settings,
            'type' => 'jobs',
            'label' => 'الوظائف',
            'description' => 'فرص مهنية لخدمة الطفل ومن يخدم الطفل، مع صفحة مستقلة ونموذج تقديم مخصص لكل وظيفة.',
            'listings' => $openings,
            'organizations' => $organizations,
            'categories' => CareerCategory::cases(),
            'schoolOrganizationKey' => $schoolOrganizationKey,
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
