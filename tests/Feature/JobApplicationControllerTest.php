<?php

use App\Enums\JobApplicationStatus;
use App\Enums\JobTrack;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobFamily;
use App\Models\JobListing;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function jobApplicationListing(Company $company): JobListing
{
    $jobFamily = JobFamily::factory()->create(['track' => JobTrack::Teach]);

    return JobListing::factory()
        ->for($company)
        ->for($jobFamily)
        ->create(['title' => 'Software Engineer']);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validJobApplicationPayload(Company $company, JobListing $jobListing, array $overrides = []): array
{
    return array_replace([
        'submission_token' => (string) Str::uuid(),
        'full_name' => 'John Doe',
        'phone_country_code' => '+968',
        'phone' => '91234567',
        'email' => 'john@example.com',
        'gender' => 'male',
        'nationality' => 'Omani',
        'country' => 'OM',
        'city' => 'Muscat',
        'company_id' => $company->id,
        'job_priority_1' => $jobListing->job_code,
        'contract_types' => ['دوام كامل'],
        'expected_salary' => 1000,
        'years_experience' => '4-7',
        'previously_worked' => 1,
        'previous_institution' => $company->name,
        'cv_link' => 'https://example.com/cv.pdf',
        'q_achievement' => 'I improved a measurable process from ten hours to two hours.',
        'q_sample_teaching' => 'I would assess the learner, adapt the activity, and measure reading fluency.',
        'q_compelling_reason' => 'I combine relevant experience with measurable ownership and fast learning.',
        'consent_accurate' => 1,
        'consent_ai' => 1,
        'consent_pool' => 1,
    ], $overrides);
}

it('stores a job application and no longer renders the form on the jobs page', function () {
    Mail::fake();

    $company = Company::factory()->create();
    $jobListing = jobApplicationListing($company);

    $this->get(route('jobs.index'))
        ->assertSuccessful()
        ->assertDontSee('data-job-application-form', false);

    $response = $this->from(route('jobs.index'))->post(
        route('jobs.apply.unified'),
        validJobApplicationPayload($company, $jobListing),
    );

    $application = JobApplication::query()->firstOrFail();

    $response->assertRedirect(route('jobs.index').'#apply-form')
        ->assertSessionHas('application_success', true)
        ->assertSessionHas('application_reference_number', $application->reference_number);

    expect($application)
        ->full_name->toBe('John Doe')
        ->phone_country_code->toBe('+968')
        ->phone->toBe('91234567')
        ->email->toBe('john@example.com')
        ->gender->toBe('male')
        ->country->toBe('OM')
        ->years_experience->toBe('4-7')
        ->cv_link->toBe('https://example.com/cv.pdf')
        ->status->toBe(JobApplicationStatus::New)
        ->previously_worked->toBeTrue()
        ->consent_pool->toBeTrue()
        ->reference_number->not->toBeNull()
        ->contract_types->toBe(['دوام كامل'])
        ->q_compelling_reason->not->toBeEmpty();

    expect($application->toArray())->not->toHaveKeys(['cv_link', 'cv_path']);
});

it('rejects a second application to the same job with the same email', function () {
    Mail::fake();

    $company = Company::factory()->create();
    $jobListing = jobApplicationListing($company);
    $payload = validJobApplicationPayload($company, $jobListing);

    $this->from(route('jobs.index'))->post(route('jobs.apply.unified'), $payload)
        ->assertRedirect(route('jobs.index').'#apply-form');

    expect(JobApplication::query()->count())->toBe(1);

    $this->from(route('jobs.index'))->post(route('jobs.apply.unified'), [
        ...$payload,
        'submission_token' => (string) Str::uuid(),
    ])
        ->assertSessionHasErrors('email');

    expect(JobApplication::query()->count())->toBe(1);
});

it('allows the same email to apply to a different job', function () {
    Mail::fake();

    $company = Company::factory()->create();
    $firstJobListing = jobApplicationListing($company);
    $secondJobListing = jobApplicationListing($company);

    $this->from(route('jobs.index'))
        ->post(route('jobs.apply.unified'), validJobApplicationPayload($company, $firstJobListing))
        ->assertRedirect(route('jobs.index').'#apply-form');

    $this->from(route('jobs.index'))
        ->post(route('jobs.apply.unified'), validJobApplicationPayload($company, $secondJobListing))
        ->assertRedirect(route('jobs.index').'#apply-form');

    expect(JobApplication::query()->count())->toBe(2);
});

it('stores a long tools_and_ai answer without truncating it', function () {
    Mail::fake();

    $company = Company::factory()->create();
    $jobListing = jobApplicationListing($company);
    $toolsAndAi = str_repeat('Worked with automation and artificial intelligence tools. ', 80);

    $this->from(route('jobs.index'))->post(route('jobs.apply.unified'), validJobApplicationPayload(
        $company,
        $jobListing,
        ['tools_and_ai' => $toolsAndAi],
    ))->assertRedirect(route('jobs.index').'#apply-form');

    expect(JobApplication::query()->firstOrFail()->tools_and_ai)
        ->toBe(rtrim($toolsAndAi));
});

it('stores an uploaded cv when no cv link is provided', function () {
    Mail::fake();
    Storage::fake('public');

    $company = Company::factory()->create();
    $jobListing = jobApplicationListing($company);
    $cv = UploadedFile::fake()->create('cv.pdf', 500, 'application/pdf');

    $this->from(route('jobs.index'))->post(
        route('jobs.apply.unified'),
        validJobApplicationPayload($company, $jobListing, [
            'cv_link' => null,
            'cv' => $cv,
        ]),
    )->assertRedirect(route('jobs.index').'#apply-form');

    $application = JobApplication::query()->sole();

    expect($application->cv_link)->toBeNull()
        ->and($application->cv_path)->not->toBeNull();

    Storage::disk('public')->assertExists($application->cv_path);
});

it('requires either a cv link or an uploaded cv file', function () {
    Mail::fake();

    $company = Company::factory()->create();
    $jobListing = jobApplicationListing($company);

    $this->from(route('jobs.index'))->post(
        route('jobs.apply.unified'),
        validJobApplicationPayload($company, $jobListing, ['cv_link' => null]),
    )
        ->assertRedirect(route('jobs.index').'#apply-form')
        ->assertSessionHasErrors(['cv_link', 'cv']);

    expect(JobApplication::query()->count())->toBe(0);
});

it('rejects unsupported cv file types', function () {
    Mail::fake();
    Storage::fake('public');

    $company = Company::factory()->create();
    $jobListing = jobApplicationListing($company);

    $this->from(route('jobs.index'))->post(
        route('jobs.apply.unified'),
        validJobApplicationPayload($company, $jobListing, [
            'cv_link' => null,
            'cv' => UploadedFile::fake()->create('cv.exe', 100, 'application/octet-stream'),
        ]),
    )->assertSessionHasErrors('cv');

    expect(JobApplication::query()->count())->toBe(0);
});

it('rejects cv files larger than five megabytes', function () {
    Mail::fake();
    Storage::fake('public');

    $company = Company::factory()->create();
    $jobListing = jobApplicationListing($company);

    $this->from(route('jobs.index'))->post(
        route('jobs.apply.unified'),
        validJobApplicationPayload($company, $jobListing, [
            'cv_link' => null,
            'cv' => UploadedFile::fake()->create('cv.pdf', 5121, 'application/pdf'),
        ]),
    )->assertSessionHasErrors('cv');

    expect(JobApplication::query()->count())->toBe(0);
});

it('validates contact, numeric, gender, and pivotal question fields', function (string $field, mixed $invalidValue) {
    Mail::fake();

    $company = Company::factory()->create();
    $jobListing = jobApplicationListing($company);

    $this->from(route('jobs.index'))
        ->post(route('jobs.apply.unified'), validJobApplicationPayload($company, $jobListing, [
            $field => $invalidValue,
        ]))
        ->assertRedirect(route('jobs.index').'#apply-form')
        ->assertSessionHasErrors($field);

    expect(JobApplication::query()->count())->toBe(0);
})->with([
    'invalid email' => ['email', 'not-an-email'],
    'non-numeric expected salary' => ['expected_salary', '1000 OMR'],
    'unsupported experience range' => ['years_experience', 'five'],
    'missing gender' => ['gender', null],
    'missing achievement answer' => ['q_achievement', null],
    'missing track-specific answer' => ['q_sample_teaching', null],
    'missing compelling reason' => ['q_compelling_reason', null],
]);

it('rejects every invalid field with validation errors', function () {
    Mail::fake();

    $company = Company::factory()->create();
    $jobListing = jobApplicationListing($company);

    $response = $this->from(route('jobs.index'))->post(
        route('jobs.apply.unified'),
        validJobApplicationPayload($company, $jobListing, [
            'country' => 'invalid-country',
            'ready_date' => 'not-a-date',
            'years_experience' => 'five',
            'previous_institution' => 'مؤسسة غير موجودة',
            'previous_role' => str_repeat('a', 256),
            'cv_link' => null,
            'q_compelling_reason' => null,
            'consent_accurate' => null,
            'consent_ai' => null,
        ]),
    );

    $response->assertRedirect(route('jobs.index').'#apply-form');

    $response->assertSessionHasErrors([
        'country',
        'ready_date',
        'years_experience',
        'previous_institution',
        'previous_role',
        'cv_link',
        'q_compelling_reason',
        'consent_accurate',
        'consent_ai',
    ]);
});

it('does not render the application form on the jobs page', function () {
    $company = Company::factory()->create(['name' => 'مؤسسة الاختبار']);
    jobApplicationListing($company);

    $this->get(route('jobs.index'))
        ->assertSuccessful()
        ->assertDontSee('name="country"', false)
        ->assertDontSee('name="cv_link"', false)
        ->assertDontSee('id="apply-form"', false);
});

it('uses Oman as the default phone country code', function () {
    Mail::fake();

    $company = Company::factory()->create();
    $jobListing = jobApplicationListing($company);
    $payload = validJobApplicationPayload($company, $jobListing);
    unset($payload['phone_country_code']);

    $this->post(route('jobs.apply.unified'), $payload)->assertRedirect();

    expect(JobApplication::query()->firstOrFail()->phone_country_code)->toBe('+968');
});

it('does not create a duplicate application for the same submission token', function () {
    Mail::fake();
    Storage::fake('public');

    $company = Company::factory()->create();
    $jobListing = jobApplicationListing($company);
    $payload = validJobApplicationPayload($company, $jobListing, [
        'cv_link' => null,
        'cv' => UploadedFile::fake()->create('first-cv.pdf', 100, 'application/pdf'),
    ]);

    $firstResponse = $this->post(route('jobs.apply.unified'), $payload)->assertRedirect();
    $secondResponse = $this->post(route('jobs.apply.unified'), [
        ...$payload,
        'cv' => UploadedFile::fake()->create('second-cv.pdf', 100, 'application/pdf'),
    ])->assertRedirect();
    $application = JobApplication::query()->sole();

    $firstResponse->assertSessionHas('application_reference_number', $application->reference_number);
    $secondResponse->assertSessionHas('application_reference_number', $application->reference_number);
    expect(JobApplication::query()->count())->toBe(1)
        ->and(Storage::disk('public')->allFiles('job-applications/cvs'))->toHaveCount(1);
});
