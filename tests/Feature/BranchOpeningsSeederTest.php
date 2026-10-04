<?php

use App\Enums\ListingStatus;
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
    ['early-ed-manager-bawshar', 'مديرة فرع — تعليم مبكر — فرع بوشر', ['٤٨٨ إلى ٥٤٧', 'امتلاء الفرع ≥٩٠٪']],
    ['cycle-one-manager-ibra', 'مديرة فرع — الحلقة الأولى — فرع إبراء', ['٥٤٧ إلى ٦٢٢', 'تفعيل الأدوار الداعمة ≥٩٥٪']],
    ['english-teacher-bawshar', 'معلمة لغة إنجليزية — تعليم مبكر — فرع بوشر', ['٣٥٨ إلى ٣٨٦', 'يقول الحسن بلسانين']],
    ['domain-one-teacher-udhaibah', 'معلمة مجال أول — الحلقة الأولى — فرع العذيبة', ['٤٣٠ إلى ٤٤٦', 'القلب المطمئن']],
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
        'answers' => ['qualification' => 'بكالوريوس لغة إنجليزية', 'years_experience' => 3, 'acknowledge_truth' => 1],
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
