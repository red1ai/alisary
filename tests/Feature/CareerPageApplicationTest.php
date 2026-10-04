<?php

use App\Mail\CareerApplicationReceived;
use App\Models\CareerApplication;
use App\Models\CareerOpening;
use App\Settings\GeneralSettings;
use Database\Seeders\CareerOpeningSeeder;
use Database\Seeders\DemoExecutiveHrOpeningSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * The payload the standalone pages (resources/job-pages) post: flat multipart fields
 * named as in the deployment guide, plus job_id, job_title and tier.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function pagePayload(array $overrides = []): array
{
    return array_replace([
        'job_id' => 'exec-hr-director',
        'job_title' => 'مدير الموارد البشرية',
        'tier' => 'executive',
        'name' => 'مرشح تجريبي',
        'nid' => '12345678',
        'dob' => '1985-05-05',
        'nationality' => 'عُماني',
        'whatsapp' => '91234567',
        'email' => 'page-applicant@example.com',
        'gov' => 'مسقط',
        'edu' => 'ماجستير',
        'major' => 'إدارة موارد بشرية',
        'exp' => '٧–١٠ سنوات',
        'employer' => 'جهة تجريبية',
        'cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        'certs' => [
            UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('b.png', 100, 'image/png'),
        ],
        'essay' => 'ثلاثة قرارات تجريبية.',
        'cover' => 'نبذة',
        'ref1' => 'مزكٍّ أول',
        'ref2' => 'مزكٍّ ثانٍ',
        'consent' => 'on',
    ], $overrides);
}

beforeEach(function () {
    $this->seed(DemoExecutiveHrOpeningSeeder::class);
    $this->withoutMiddleware(ThrottleRequests::class);
    Storage::fake('local');
    Mail::fake();
});

it('accepts a standalone page submission, stores private files and answers a reference', function () {
    $response = $this->post(route('job-pages.apply'), pagePayload())
        ->assertCreated()
        ->assertJsonStructure(['ref']);

    $application = CareerApplication::firstOrFail();

    expect($response->json('ref'))->toBe($application->reference_number)
        ->and($application->full_name)->toBe('مرشح تجريبي')
        ->and($application->phone)->toBe('91234567')
        ->and($application->email)->toBe('page-applicant@example.com')
        ->and($application->answers['nid'])->toBe('12345678')
        ->and($application->answers['consent'])->toBe('on')
        ->and($application->files['certs'])->toHaveCount(2);

    Storage::disk('local')->assertExists($application->files['cv']);
    foreach ($application->files['certs'] as $path) {
        Storage::disk('local')->assertExists($path);
    }
});

it('accepts several certificate files sent as certs[] and a single file sent as certs', function () {
    $this->post(route('job-pages.apply'), pagePayload(['certs' => [
        UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
        UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'),
        UploadedFile::fake()->create('c.png', 10, 'image/png'),
    ]]))->assertCreated();

    $this->post(route('job-pages.apply'), pagePayload([
        'email' => 'second@example.com',
        'whatsapp' => '92223344',
        'certs' => UploadedFile::fake()->create('only.pdf', 10, 'application/pdf'),
    ]))->assertCreated();

    expect(CareerApplication::orderBy('id')->get()->map(fn ($a) => count($a->files['certs']))->all())->toBe([3, 1]);
});

it('queues the internal notification when recipients are configured', function () {
    $settings = app(GeneralSettings::class);
    $settings->job_submission_recipients = [['email' => 'career@example.test']];
    $settings->save();

    $this->post(route('job-pages.apply'), pagePayload())->assertCreated();

    Mail::assertQueued(CareerApplicationReceived::class, fn ($mail) => $mail->hasTo('career@example.test'));
});

it('returns 422 json with page field names, never a redirect, when required data is missing', function () {
    $payload = pagePayload();
    unset($payload['name'], $payload['nid'], $payload['whatsapp'], $payload['consent']);

    $this->post(route('job-pages.apply'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'nid', 'whatsapp', 'consent'], 'errors')
        ->assertJsonStructure(['message', 'errors']);

    expect(CareerApplication::count())->toBe(0);
});

it('validates formats on the server: national id, whatsapp, choices, lengths and file types', function () {
    $cases = [
        'national id' => [['nid' => 'abc'], 'nid'],
        'whatsapp' => [['whatsapp' => '12345'], 'whatsapp'],
        'nationality' => [['nationality' => 'غير صالح'], 'nationality'],
        'governorate' => [['gov' => 'غير موجودة'], 'gov'],
        'essay length' => [['essay' => str_repeat('أ', 1201)], 'essay'],
        'email' => [['email' => 'not-an-email'], 'email'],
        'cv type' => [['cv' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')], 'cv'],
        'cv size' => [['cv' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf')], 'cv'],
    ];

    foreach ($cases as $label => [$override, $field]) {
        $this->post(route('job-pages.apply'), pagePayload($override))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field, 'errors');
    }

    $payload = pagePayload();
    $payload['certs'] = [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')];
    $this->post(route('job-pages.apply'), $payload)->assertUnprocessable()->assertJsonValidationErrors('certs', 'errors');

    expect(CareerApplication::count())->toBe(0);
});

it('accepts Arabic-Indic digits in the whatsapp number', function () {
    $this->post(route('job-pages.apply'), pagePayload(['whatsapp' => '٩١٢٣٤٥٦٧']))->assertCreated();
});

it('rejects a second application with the same email or the same whatsapp number', function () {
    $this->post(route('job-pages.apply'), pagePayload())->assertCreated();

    $this->post(route('job-pages.apply'), pagePayload(['whatsapp' => '92223344']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email', 'errors');

    foreach (['91234567', '+968 91234567', '96891234567', '٩١٢٣٤٥٦٧'] as $same) {
        $this->post(route('job-pages.apply'), pagePayload(['email' => fake()->unique()->safeEmail(), 'whatsapp' => $same]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('whatsapp', 'errors');
    }

    expect(CareerApplication::count())->toBe(1);
});

it('ignores fields the opening does not define', function () {
    $this->post(route('job-pages.apply'), pagePayload(['is_admin' => '1', 'prior_other' => 'x', 'website' => '']))->assertCreated();

    expect(array_keys(CareerApplication::firstOrFail()->answers))->not->toContain('is_admin', 'prior_other', 'website');
});

it('returns 404 for unknown, unpublished or content-less openings', function () {
    $this->post(route('job-pages.apply'), pagePayload(['job_id' => 'does-not-exist']))->assertNotFound();
    $this->post(route('job-pages.apply'), pagePayload(['job_id' => '']))->assertNotFound();

    $this->seed(CareerOpeningSeeder::class);
    $this->post(route('job-pages.apply'), pagePayload(['job_id' => 'school-principal-ibra']))->assertNotFound();

    $draft = CareerOpening::factory()->draft()->create();
    $this->post(route('job-pages.apply'), pagePayload(['job_id' => $draft->slug]))->assertNotFound();

    expect(CareerApplication::count())->toBe(0);
});

it('leaves the unapproved Ibra record untouched', function () {
    $this->seed(CareerOpeningSeeder::class);
    $ibra = CareerOpening::where('slug', 'school-principal-ibra')->firstOrFail();

    $this->post(route('job-pages.apply'), pagePayload())->assertCreated();

    expect($ibra->fresh()->status->value)->toBe('draft')
        ->and($ibra->applications()->count())->toBe(0);
});

it('rate limits submissions per address to five per hour', function () {
    $this->withMiddleware(ThrottleRequests::class);

    foreach (range(1, 5) as $i) {
        $this->post(route('job-pages.apply'), pagePayload([
            'email' => "limit{$i}@example.com",
            'whatsapp' => '9000000'.$i,
            'cv' => UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf'),
            'certs' => [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')],
        ]))->assertCreated();
    }

    $this->post(route('job-pages.apply'), pagePayload(['email' => 'limit6@example.com', 'whatsapp' => '90000006']))
        ->assertStatus(429);
});
