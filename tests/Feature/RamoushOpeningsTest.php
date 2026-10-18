<?php

use App\Enums\ListingStatus;
use App\Models\CareerApplication;
use App\Models\CareerOpening;
use App\Models\CareerOrganization;
use App\Models\Company;
use App\Support\RamoushOpenings;
use Database\Seeders\BranchOpeningsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    $this->company = Company::factory()->create(['slug' => 'g-reader-school']);
    RamoushOpenings::install();
});

function publishRamoushOpenings(): void
{
    foreach (RamoushOpenings::SLUGS as $position => $slug) {
        CareerOpening::where('slug', $slug)->update([
            'status' => ListingStatus::Published,
            'published_at' => now()->subHour()->addSeconds(count(RamoushOpenings::SLUGS) - $position),
        ]);
    }
}

/**
 * @return array<string, mixed>
 */
function ramoushPayload(string $slug, array $override = []): array
{
    $opening = CareerOpening::where('slug', $slug)->firstOrFail();
    $answers = ['qualification' => 'بكالوريوس', 'years_experience' => 6, 'relevant_experience' => 'خبرة ذات صلة', 'acknowledge_truth' => 1];

    if (collect($opening->fields())->contains('key', 'portfolio_url')) {
        $answers['portfolio_url'] = 'https://example.com/work';
    }

    if (collect($opening->fields())->contains('key', 'preferred_branches')) {
        $answers['preferred_branches'] = ['الخوض', 'بوشر'];
    }

    return array_replace_recursive([
        'full_name' => 'مقدّمة طلب تجريبي',
        'phone' => '99999999',
        'email' => 'test-'.$slug.'@example.com',
        'answers' => $answers,
        'files' => ['cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')],
    ], $override);
}

it('creates the eight openings as hidden drafts linked to the school organization', function () {
    $organization = CareerOrganization::where('slug', 'g-reader-school')->firstOrFail();

    expect(CareerOpening::whereIn('slug', RamoushOpenings::SLUGS)->get())->toHaveCount(8)
        ->each(fn ($opening) => $opening->status->toBe(ListingStatus::Draft)
            ->career_organization_id->toBe($organization->id)
            ->eligibility_rules->toBe([]));

    foreach (RamoushOpenings::SLUGS as $slug) {
        $this->get(route('jobs.show', $slug))->assertNotFound();
        $this->post(route('careers.apply', $slug), [])->assertNotFound();
    }
});

it('is insert-only so a rerun never overwrites edits or recreates anything', function () {
    CareerOpening::where('slug', 'ramoush-accountant')->update(['title' => 'عنوان معدَّل', 'status' => ListingStatus::Published]);
    CareerOpening::where('slug', 'ramoush-call-center')->delete();

    expect(RamoushOpenings::install())->toBe(1);

    $opening = CareerOpening::where('slug', 'ramoush-accountant')->firstOrFail();
    expect($opening->title)->toBe('عنوان معدَّل')
        ->and($opening->status)->toBe(ListingStatus::Published)
        ->and(CareerOpening::whereIn('slug', RamoushOpenings::SLUGS)->count())->toBe(8);
});

it('aborts without creating anything when the school company is missing', function () {
    CareerOpening::query()->delete();
    CareerOrganization::query()->delete();
    Company::query()->delete();

    expect(fn () => RamoushOpenings::install())->toThrow(RuntimeException::class)
        ->and(CareerOpening::count())->toBe(0);
});

it('keeps the four earlier branch openings and their applications untouched', function () {
    $this->seed(BranchOpeningsSeeder::class);
    $before = CareerOpening::whereIn('slug', BranchOpeningsSeeder::SLUGS)->get()->map->only(['slug', 'title', 'blocks', 'form_sections', 'status'])->all();
    $application = CareerApplication::factory()->create(['career_opening_id' => CareerOpening::where('slug', BranchOpeningsSeeder::SLUGS[0])->value('id')]);

    RamoushOpenings::install();
    publishRamoushOpenings();

    expect(CareerOpening::whereIn('slug', BranchOpeningsSeeder::SLUGS)->get()->map->only(['slug', 'title', 'blocks', 'form_sections', 'status'])->all())->toEqual($before)
        ->and($application->fresh()->email)->toBe($application->email);
});

it('lists the eight openings on /jobs after the four pinned branch openings', function () {
    $this->seed(BranchOpeningsSeeder::class);
    CareerOpening::whereIn('slug', BranchOpeningsSeeder::SLUGS)->update(['status' => ListingStatus::Published, 'published_at' => now()->subDay()]);
    publishRamoushOpenings();

    $titles = collect(RamoushOpenings::openings())->pluck('title');
    $response = $this->get('/jobs')->assertSuccessful();

    foreach ($titles as $title) {
        $response->assertSee($title);
    }

    foreach (RamoushOpenings::SLUGS as $slug) {
        $response->assertSee('/jobs/'.$slug, false);
    }

    $html = $response->getContent();
    expect(strpos($html, 'cycle-one-manager-ibra'))->toBeLessThan(strpos($html, 'ramoush-program-manager'));
});

it('has short unique slugs under /jobs', function () {
    expect(RamoushOpenings::SLUGS)->toHaveCount(8)->and(array_unique(RamoushOpenings::SLUGS))->toHaveCount(8);

    foreach (RamoushOpenings::SLUGS as $slug) {
        expect(route('jobs.show', $slug, false))->toBe('/jobs/'.$slug)->and(strlen($slug))->toBeLessThan(40);
    }
});

it('shows each opening on its own page with its announcement content and form', function (string $slug, array $expected, array $fields) {
    publishRamoushOpenings();

    $this->get(route('jobs.show', $slug))
        ->assertSuccessful()
        ->assertSee($expected)
        ->assertSee('name="files[cv]"', false)
        ->assertSee('name="answers[acknowledge_truth]"', false)
        ->assertSee($fields, false)
        ->assertSee('بوابة العبور القيمية')
        ->assertDontSee('class="block pay"', false);
})->with([
    ['ramoush-program-manager', ['مدير البرنامج — برنامج رموش المساء — الإدارة المركزية بمسقط', 'إغلاق الشكاوى خلال ٤٨ ساعة'], []],
    ['ramoush-program-supervisor', ['مشرف البرنامج — الإشراف التربوي — جميع فروع البرنامج', 'تغطية الزيارات'], []],
    ['ramoush-accountant', ['محاسب مالي (ومدير مالي بالإنابة)', 'تحويلات غير مطابقة في نهاية الشهر'], []],
    ['ramoush-digital-marketer', ['مسوق رقمي — حملة رموش المساء — مسقط', '١٫٢ ر.ع أو أقل'], ['name="answers[portfolio_url]"']],
    ['ramoush-graphic-designer', ['مصمم جرافيك — برنامج رموش المساء — مسقط', 'جولتان أو أقل'], ['name="answers[portfolio_url]"']],
    ['ramoush-commission-marketer', ['مسوق بالعمولة — حملة رموش المساء — مسقط والمحافظات', 'الهدف يُتفق عليه عند التعاقد'], []],
    ['ramoush-call-center', ['موظفة مركز الاتصالات — برنامج رموش المساء — مسقط', 'دقة البيانات'], []],
    ['ramoush-teacher', ['معلمة رموش المساء — الأطفال ٤–١٢ سنة — فروع المدرسة', 'حتى ٦٤ معلمة + ٧ بديلات', '٢٠٢٦-١٠-٢٤'], ['name="answers[preferred_branches][]"', 'name="files[quran_certificate]"', 'data-max-choices="2"']],
]);

it('matches the vacancies in the announcements', function () {
    $vacancies = fn (string $slug) => collect(CareerOpening::where('slug', $slug)->firstOrFail()->chips)->firstWhere('k', 'الشواغر')['v'];

    expect($vacancies('ramoush-digital-marketer'))->toBe('٢')
        ->and($vacancies('ramoush-call-center'))->toBe('٢ (نوبتان)')
        ->and($vacancies('ramoush-teacher'))->toContain('٦٤')->toContain('٧ بديلات')
        ->and(array_sum(RamoushOpenings::BRANCH_SEATS))->toBe(64)
        ->and(RamoushOpenings::BRANCH_SEATS)->toHaveCount(12);

    foreach (['ramoush-program-manager', 'ramoush-program-supervisor', 'ramoush-accountant', 'ramoush-graphic-designer', 'ramoush-commission-marketer'] as $slug) {
        expect($vacancies($slug))->toBe('١');
    }
});

it('only the teacher opening has a closing date, 24 October 2026', function () {
    foreach (RamoushOpenings::SLUGS as $slug) {
        $opening = CareerOpening::where('slug', $slug)->firstOrFail();

        if ($slug === 'ramoush-teacher') {
            expect($opening->expires_at->setTimezone('Asia/Muscat')->toDateString())->toBe('2026-10-24');

            continue;
        }

        expect($opening->expires_at)->toBeNull();
    }

    publishRamoushOpenings();

    $this->travelTo(now()->setDate(2026, 10, 24)->setTime(12, 0));
    $this->get(route('jobs.show', 'ramoush-teacher'))->assertSuccessful();

    $this->travelTo(now()->setDate(2026, 10, 25)->setTime(12, 0));
    $this->get(route('jobs.show', 'ramoush-teacher'))->assertNotFound();
    $this->get(route('jobs.show', 'ramoush-call-center'))->assertSuccessful();
});

it('does not turn nationality, age, gender or Quran memorisation into a required field or a gate', function (string $slug) {
    $opening = CareerOpening::where('slug', $slug)->firstOrFail();
    $keys = collect($opening->fields())->pluck('key')->all();

    expect($opening->eligibility_rules)->toBe([])
        ->and($keys)->not->toContain('nationality', 'age', 'birth_date', 'gender', 'quran_memorization', 'quran_parts');
})->with(RamoushOpenings::SLUGS);

it('accepts a complete application and stores files privately for every form shape', function (string $slug) {
    publishRamoushOpenings();
    Mail::fake();
    Storage::fake('local');
    Storage::fake('public');

    $this->post(route('careers.apply', $slug), ramoushPayload($slug, [
        'files' => ['qualification_images' => [UploadedFile::fake()->image('degree.png'), UploadedFile::fake()->create('degree.pdf', 50, 'application/pdf')]],
    ]))->assertSessionHasNoErrors()->assertSessionHas('application_success');

    $application = CareerApplication::firstOrFail();
    expect($application->careerOpening->slug)->toBe($slug)
        ->and(Storage::disk('public')->allFiles())->toBe([]);

    Storage::disk('local')->assertExists($application->files['cv']);
    expect($application->files['qualification_images'])->toHaveCount(2);
})->with(RamoushOpenings::SLUGS);

it('rejects an incomplete application: missing required fields, cv or acknowledgement', function (string $slug) {
    publishRamoushOpenings();

    $this->post(route('careers.apply', $slug), [
        'full_name' => 'مقدّمة طلب',
        'phone' => '99999999',
        'email' => 'incomplete@example.com',
    ])->assertSessionHasErrors(['answers.qualification', 'answers.years_experience', 'answers.relevant_experience', 'answers.acknowledge_truth', 'files.cv']);

    expect(CareerApplication::count())->toBe(0);
})->with(RamoushOpenings::SLUGS);

it('requires name, phone and a valid email', function () {
    publishRamoushOpenings();

    $this->post(route('careers.apply', 'ramoush-call-center'), ramoushPayload('ramoush-call-center', ['full_name' => '', 'phone' => '', 'email' => 'not-an-email']))
        ->assertSessionHasErrors(['full_name', 'phone', 'email']);
});

it('treats the school-employee question and the qualification images as optional', function () {
    publishRamoushOpenings();
    Storage::fake('local');

    $this->post(route('careers.apply', 'ramoush-accountant'), ramoushPayload('ramoush-accountant'))
        ->assertSessionHasNoErrors();

    $this->post(route('careers.apply', 'ramoush-call-center'), ramoushPayload('ramoush-call-center', ['answers' => ['works_at_school' => 'ليس خيارًا']]))
        ->assertSessionHasErrors(['answers.works_at_school']);

    $this->post(route('careers.apply', 'ramoush-call-center'), ramoushPayload('ramoush-call-center', ['answers' => ['works_at_school' => 'نعم']]))
        ->assertSessionHasNoErrors();
});

it('requires a portfolio link only for the digital marketer and the designer', function (string $slug) {
    publishRamoushOpenings();

    $payload = ramoushPayload($slug);
    unset($payload['answers']['portfolio_url']);

    $this->post(route('careers.apply', $slug), $payload)->assertSessionHasErrors(['answers.portfolio_url']);

    $this->post(route('careers.apply', $slug), ramoushPayload($slug, ['answers' => ['portfolio_url' => 'not a link']]))
        ->assertSessionHasErrors(['answers.portfolio_url']);

    Storage::fake('local');
    $this->post(route('careers.apply', $slug), ramoushPayload($slug, ['answers' => ['portfolio_url' => 'https://www.behance.net/someone']]))
        ->assertSessionHasNoErrors();
})->with(['ramoush-digital-marketer', 'ramoush-graphic-designer']);

it('does not ask for a portfolio on the other openings', function (string $slug) {
    expect(collect(CareerOpening::where('slug', $slug)->firstOrFail()->fields())->pluck('key')->all())->not->toContain('portfolio_url');
})->with(['ramoush-program-manager', 'ramoush-program-supervisor', 'ramoush-accountant', 'ramoush-commission-marketer', 'ramoush-call-center', 'ramoush-teacher']);

it('requires one or two preferred branches from the twelve on the teacher opening only', function () {
    publishRamoushOpenings();
    Storage::fake('local');

    $post = function (array $branches, string $email = 'branches@example.com') {
        $payload = ramoushPayload('ramoush-teacher', ['email' => $email]);
        $payload['answers']['preferred_branches'] = $branches;

        return $this->post(route('careers.apply', 'ramoush-teacher'), $payload);
    };

    $withoutBranches = ramoushPayload('ramoush-teacher');
    unset($withoutBranches['answers']['preferred_branches']);

    $this->post(route('careers.apply', 'ramoush-teacher'), $withoutBranches)->assertSessionHasErrors(['answers.preferred_branches']);
    $post([])->assertSessionHasErrors(['answers.preferred_branches']);
    $post(['الخوض', 'بوشر', 'بدبد'])->assertSessionHasErrors(['answers.preferred_branches']);
    $post(['فرع غير موجود'])->assertSessionHasErrors(['answers.preferred_branches.0']);

    $post(['بدبد'])->assertSessionHasNoErrors();
    $post(['الخوض', 'صحار'], 'second@example.com')->assertSessionHasNoErrors();

    expect(CareerApplication::count())->toBe(2)
        ->and(CareerApplication::latest('id')->first()->answers['preferred_branches'])->toBe(['الخوض', 'صحار']);

    expect(collect(CareerOpening::where('slug', 'ramoush-call-center')->firstOrFail()->fields())->pluck('key')->all())->not->toContain('preferred_branches');
});

it('rejects wrong file types and oversized files', function () {
    publishRamoushOpenings();
    Storage::fake('local');

    $this->post(route('careers.apply', 'ramoush-teacher'), ramoushPayload('ramoush-teacher', ['files' => ['cv' => UploadedFile::fake()->create('cv.exe', 10, 'application/x-msdownload')]]))
        ->assertSessionHasErrors(['files.cv']);

    $this->post(route('careers.apply', 'ramoush-teacher'), ramoushPayload('ramoush-teacher', ['files' => ['cv' => UploadedFile::fake()->create('cv.pdf', 6000, 'application/pdf')]]))
        ->assertSessionHasErrors(['files.cv']);

    $this->post(route('careers.apply', 'ramoush-teacher'), ramoushPayload('ramoush-teacher', ['files' => ['quran_certificate' => UploadedFile::fake()->create('x.docx', 10)]]))
        ->assertSessionHasErrors(['files.quran_certificate']);

    expect(CareerApplication::count())->toBe(0);
});

it('blocks a second application with the same email for the same opening only', function () {
    publishRamoushOpenings();
    Storage::fake('local');

    $this->post(route('careers.apply', 'ramoush-accountant'), ramoushPayload('ramoush-accountant', ['email' => 'same@example.com']))->assertSessionHasNoErrors();
    $this->post(route('careers.apply', 'ramoush-accountant'), ramoushPayload('ramoush-accountant', ['email' => 'same@example.com']))->assertSessionHasErrors(['email']);
    $this->post(route('careers.apply', 'ramoush-call-center'), ramoushPayload('ramoush-call-center', ['email' => 'same@example.com']))->assertSessionHasNoErrors();
});

it('does not show the expired school-teacher window but keeps the stated priority', function () {
    publishRamoushOpenings();

    $this->get(route('jobs.show', 'ramoush-teacher'))
        ->assertSuccessful()
        ->assertDontSee('١٤–١٧')
        ->assertDontSee('مواعيد التقديم')
        ->assertSee('تُمنح الأولوية القصوى في التعيين لمعلمات مدرسة القارئ العبقري الحاليات ذوات الخبرة بمناهج المدرسة التأسيسية.');
});

it('keeps the closing date independent of published_at and uses Muscat time', function () {
    $closing = CareerOpening::where('slug', 'ramoush-teacher')->firstOrFail()->expires_at->copy();

    CareerOpening::where('slug', 'ramoush-teacher')->update(['status' => ListingStatus::Published, 'published_at' => now()->subDays(3)]);
    CareerOpening::where('slug', 'ramoush-teacher')->update(['published_at' => now()->subMinute()]);

    expect(CareerOpening::where('slug', 'ramoush-teacher')->firstOrFail()->expires_at->equalTo($closing))->toBeTrue()
        ->and($closing->setTimezone('Asia/Muscat')->format('Y-m-d H:i:s'))->toBe('2026-10-24 23:59:59');

    $this->travelTo($closing->copy()->utc()->subSecond());
    $this->get(route('jobs.show', 'ramoush-teacher'))->assertSuccessful();

    $this->travelTo($closing->copy()->utc()->addSecond());
    $this->get(route('jobs.show', 'ramoush-teacher'))->assertNotFound();
});

it('shows the teacher opening under each of its twelve branch filters without changing other openings', function () {
    $this->seed(BranchOpeningsSeeder::class);
    CareerOpening::whereIn('slug', BranchOpeningsSeeder::SLUGS)->update(['status' => ListingStatus::Published, 'published_at' => now()->subDay()]);
    publishRamoushOpenings();

    $teacher = CareerOpening::where('slug', 'ramoush-teacher')->firstOrFail();
    expect(collect($teacher->branchLocations())->pluck('value')->all())->toHaveCount(12)
        ->and(collect(CareerOpening::where('slug', 'cycle-one-manager-ibra')->firstOrFail()->branchLocations())->pluck('value')->all())->toBe(['ibra'])
        ->and(CareerOpening::where('slug', 'ramoush-accountant')->firstOrFail()->branchLocations())->toBe([]);

    $html = $this->get('/jobs')->assertSuccessful()->getContent();

    foreach (['khoudh', 'mawaleh', 'bawshar', 'udhaibah', 'mabaila_eighth', 'mabaila_south', 'amarat', 'sohar', 'barka', 'nizwa', 'ibra', 'bidbid'] as $branch) {
        expect($html)->toContain('value="branch-'.$branch.'"');
    }

    preg_match('/data-branch="([^"]*)"[^>]*data-family="[^"]*"[^>]*data-search="[^"]*معلمة رموش المساء/u', $html, $m);
    expect(explode(' ', $m[1] ?? ''))->toHaveCount(12)->toContain('khoudh', 'bidbid');

    preg_match('/data-branch="([^"]*)"[^>]*data-family="[^"]*"[^>]*data-search="[^"]*مديرة فرع — الحلقة الأولى/u', $html, $old);
    expect($old[1] ?? null)->toBe('ibra');
});
