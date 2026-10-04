<?php

use App\Enums\ListingStatus;
use App\Filament\Resources\CareerApplications\Pages\ViewCareerApplication;
use App\Models\CareerApplication;
use App\Models\CareerOpening;
use App\Models\CareerOrganization;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\BranchOpeningsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->company = Company::factory()->create(['slug' => 'g-reader-school']);
    $this->seed(BranchOpeningsSeeder::class);
});

function publishBranchOpenings(): void
{
    CareerOpening::query()->update(['status' => ListingStatus::Published, 'published_at' => now()->subMinute()]);
}

it('creates the four openings as hidden drafts linked to the school organization', function () {
    $organization = CareerOrganization::where('slug', 'g-reader-school')->firstOrFail();

    expect(CareerOpening::whereIn('slug', BranchOpeningsSeeder::SLUGS)->get())->toHaveCount(4)
        ->each(fn ($opening) => $opening->status->toBe(ListingStatus::Draft)
            ->career_organization_id->toBe($organization->id)
            ->company_id->toBe($this->company->id));

    foreach (BranchOpeningsSeeder::SLUGS as $slug) {
        $this->get(route('jobs.show', $slug))->assertNotFound();
        $this->post(route('careers.apply', $slug), [])->assertNotFound();
    }

    $this->actingAs(User::factory()->create())
        ->get(route('jobs.show', 'english-teacher-bawshar'))
        ->assertSuccessful()
        ->assertSee('معاينة للإدارة فقط');
});

it('is insert-only so a rerun never overwrites edits or publication', function () {
    CareerOpening::where('slug', 'cycle-one-manager-ibra')->update(['title' => 'عنوان معدَّل', 'status' => ListingStatus::Published]);

    $this->seed(BranchOpeningsSeeder::class);

    $opening = CareerOpening::where('slug', 'cycle-one-manager-ibra')->firstOrFail();
    expect($opening->title)->toBe('عنوان معدَّل')
        ->and($opening->status)->toBe(ListingStatus::Published)
        ->and(CareerOpening::whereIn('slug', BranchOpeningsSeeder::SLUGS)->count())->toBe(4);
});

it('leaves existing openings and applications untouched', function () {
    $existing = CareerOpening::factory()->create();
    $application = CareerApplication::factory()->create(['career_opening_id' => $existing->id]);

    $this->seed(BranchOpeningsSeeder::class);

    expect($existing->fresh()->title)->toBe($existing->title)
        ->and(CareerApplication::count())->toBe(1)
        ->and($application->fresh()->email)->toBe($application->email);
});

it('aborts without creating anything when the school company is missing', function () {
    CareerOpening::query()->delete();
    CareerOrganization::query()->delete();
    Company::query()->delete();

    expect(fn () => $this->seed(BranchOpeningsSeeder::class))->toThrow(RuntimeException::class)
        ->and(CareerOpening::count())->toBe(0);
});

it('shows each published opening on its own page with its own content', function (string $slug, string $title, array $expected) {
    publishBranchOpenings();

    $this->get(route('jobs.show', $slug))
        ->assertSuccessful()
        ->assertSee($title)
        ->assertSee($expected)
        ->assertSee('name="answers[years_experience]"', false)
        ->assertSee('name="files[cv]"', false);
})->with([
    ['early-ed-manager-bawshar', 'مديرة فرع — تعليم مبكر — فرع بوشر', ['امتلاء الفرع ≥٩٠٪']],
    ['cycle-one-manager-ibra', 'مديرة فرع — الحلقة الأولى — فرع إبراء', ['تفعيل الأدوار الداعمة ≥٩٥٪']],
    ['english-teacher-bawshar', 'معلمة لغة إنجليزية — تعليم مبكر — فرع بوشر', ['يقول الحسن بلسانين']],
    ['domain-one-teacher-udhaibah', 'معلمة مجال أول — الحلقة الأولى — فرع العذيبة', ['القلب المطمئن']],
]);

it('stores the application in the database and its files on the private disk, linked to the opening', function () {
    publishBranchOpenings();
    Mail::fake();
    Storage::fake('local');
    Storage::fake('public');

    $this->post(route('careers.apply', 'english-teacher-bawshar'), [
        'full_name' => 'مقدّمة طلب',
        'phone' => '99999999',
        'email' => 'applicant@example.com',
        'answers' => ['qualification' => 'بكالوريوس لغة إنجليزية', 'years_experience' => 3, 'expected_salary' => '٤٠٠ ريال', 'acknowledge_truth' => 1],
        'files' => [
            'cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
            'certificates' => UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf'),
        ],
    ])->assertSessionHasNoErrors()->assertSessionHas('application_success');

    $application = CareerApplication::firstOrFail();
    expect($application->careerOpening->slug)->toBe('english-teacher-bawshar')
        ->and($application->email)->toBe('applicant@example.com');

    foreach (['cv', 'certificates'] as $key) {
        Storage::disk('local')->assertExists($application->files[$key]);
        expect(Storage::disk('public')->allFiles())->toBe([]);
    }
});

it('rejects an application without the required cv or acknowledgement', function () {
    publishBranchOpenings();

    $this->post(route('careers.apply', 'cycle-one-manager-ibra'), [
        'full_name' => 'مقدّمة طلب',
        'phone' => '99999999',
        'email' => 'applicant@example.com',
        'answers' => ['qualification' => 'بكالوريوس تربية', 'years_experience' => 5],
    ])->assertSessionHasErrors(['files.cv', 'answers.acknowledge_truth']);

    expect(CareerApplication::count())->toBe(0);
});

it('hides pay and asks for the expected salary on each of the four openings', function (string $slug) {
    publishBranchOpenings();

    expect(CareerOpening::where('slug', $slug)->firstOrFail()->blocks)->not->toHaveKey('pay');

    $this->get(route('jobs.show', $slug))
        ->assertSuccessful()
        ->assertDontSee(['نطاق الراتب', 'ريالًا عُمانيًا', 'class="block pay"'], false)
        ->assertSee('كم الراتب المتوقع؟')
        ->assertSee('name="answers[expected_salary]"', false);
})->with(BranchOpeningsSeeder::SLUGS);

it('stores the expected salary answer and shows it to the admin reviewing the application', function () {
    publishBranchOpenings();
    Mail::fake();
    Storage::fake('local');

    $payload = fn (array $answers) => [
        'full_name' => 'مقدّمة طلب',
        'phone' => '99999999',
        'email' => 'applicant@example.com',
        'answers' => ['qualification' => 'بكالوريوس', 'years_experience' => 3, 'acknowledge_truth' => 1, ...$answers],
        'files' => ['cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')],
    ];

    $this->post(route('careers.apply', 'english-teacher-bawshar'), $payload([]))
        ->assertSessionHasErrors('answers.expected_salary');

    $this->post(route('careers.apply', 'english-teacher-bawshar'), $payload(['expected_salary' => '٤٠٠ ريال']))
        ->assertSessionHasNoErrors();

    $application = CareerApplication::firstOrFail();
    expect($application->answers['expected_salary'])->toBe('٤٠٠ ريال');

    $this->actingAs(User::factory()->create());
    Livewire\Livewire::test(ViewCareerApplication::class, ['record' => $application->getKey()])
        ->assertSee('كم الراتب المتوقع؟')
        ->assertSee('٤٠٠ ريال');
})->skip(fn () => ! class_exists(Livewire\Livewire::class));

it('migrates only the four published openings and leaves every other opening untouched', function () {
    publishBranchOpenings();
    $other = CareerOpening::factory()->create(['blocks' => ['pay' => ['on' => true, 'title' => 'الأجر', 'text' => 'مئة ريال']]]);
    $otherSections = $other->form_sections;

    foreach (BranchOpeningsSeeder::SLUGS as $slug) {
        $opening = CareerOpening::where('slug', $slug)->firstOrFail();
        $opening->update([
            'blocks' => [...$opening->blocks, 'pay' => ['on' => true, 'title' => 'نطاق الراتب', 'text' => 'قديم']],
            'form_sections' => collect($opening->form_sections)->map(fn ($s) => [...$s, 'fields' => collect($s['fields'])->reject(fn ($f) => $f['key'] === 'expected_salary')->values()->all()])->all(),
        ]);
    }

    $migration = require database_path('migrations/2026_10_04_145725_remove_pay_and_add_expected_salary_to_branch_openings.php');
    $migration->up();
    $migration->up();

    foreach (BranchOpeningsSeeder::SLUGS as $slug) {
        $opening = CareerOpening::where('slug', $slug)->firstOrFail();
        expect($opening->blocks)->not->toHaveKey('pay')->toHaveKey('about')
            ->and(collect($opening->fields())->where('key', 'expected_salary'))->toHaveCount(1);
    }

    $other->refresh();
    expect($other->blocks)->toHaveKey('pay')
        ->and($other->form_sections)->toBe($otherSections)
        ->and(collect($other->fields())->where('key', 'expected_salary'))->toHaveCount(0);
});
