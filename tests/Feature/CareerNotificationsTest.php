<?php

use App\Enums\JobTrack;
use App\Enums\ListingStatus;
use App\Filament\Pages\GeneralSettings;
use App\Filament\Resources\CareerApplications\CareerApplicationResource;
use App\Filament\Resources\CareerOpenings\Pages\ListCareerOpenings;
use App\Filament\Resources\JobApplications\JobApplicationResource;
use App\Filament\Resources\Submissions\SubmissionResource;
use App\Mail\CareerApplicationReceived;
use App\Mail\CareerOpeningPublished;
use App\Mail\JobSubmissionReceived;
use App\Models\CareerApplication;
use App\Models\CareerOpening;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobFamily;
use App\Models\JobListing;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

it('notifies once when an opening is created as published', function () {
    Mail::fake();

    $opening = CareerOpening::factory()->create(['title' => 'معلم رياضيات']);

    Mail::assertQueued(CareerOpeningPublished::class, 1);
    Mail::assertQueued(CareerOpeningPublished::class, fn ($mail) => $mail->hasTo('a.alisary@alisary.com')
        && $mail->opening->is($opening)
        && str_contains($mail->render(), route('jobs.show', $opening)));
});

it('does not notify for drafts, but notifies once when a draft becomes published', function () {
    Mail::fake();

    $opening = CareerOpening::factory()->draft()->create();
    Mail::assertNothingQueued();

    $opening->update(['status' => ListingStatus::Published]);
    Mail::assertQueued(CareerOpeningPublished::class, 1);
});

it('does not notify when a published opening is edited or re-saved', function () {
    $opening = CareerOpening::factory()->create();
    Mail::fake();

    $opening->update(['title' => 'عنوان جديد']);
    $opening->update(['status' => ListingStatus::Published]);
    $opening->save();

    Mail::assertNothingQueued();
});

it('notifies for each opening published through the bulk action', function () {
    $first = CareerOpening::factory()->draft()->create();
    $second = CareerOpening::factory()->draft()->create();
    $already = CareerOpening::factory()->create();
    Mail::fake();

    $this->actingAs(User::factory()->create());

    Livewire::test(ListCareerOpenings::class)
        ->callTableBulkAction('publishSelected', [$first, $second, $already]);

    Mail::assertQueued(CareerOpeningPublished::class, 2);
});

it('notifies the career address once for a career opening application', function () {
    Mail::fake();
    Storage::fake('local');
    $opening = CareerOpening::factory()->create();

    $this->post(route('careers.apply', $opening), [
        'full_name' => 'مقدّم',
        'phone' => '99999999',
        'email' => 'a@example.com',
        'answers' => ['years_experience' => 3, 'cover_note' => 'x', 'acknowledge_truth' => 1],
    ])->assertSessionHasNoErrors();

    $application = CareerApplication::firstOrFail();

    Mail::assertQueued(CareerApplicationReceived::class, 1);
    Mail::assertQueued(CareerApplicationReceived::class, function (CareerApplicationReceived $mail) use ($application, $opening): bool {
        $html = $mail->render();

        return $mail->hasTo('career@alisary.com')
            && str_contains($html, 'مقدّم')
            && str_contains($html, $opening->title)
            && str_contains($html, $application->reference_number)
            && str_contains($html, '/admin/')
            && $mail->attachments === [];
    });
});

it('notifies the career address once for a v2 job application and not again on resubmission', function () {
    Mail::fake();
    [, , $payload] = notificationJobApplication();

    $this->post(route('jobs.apply.unified'), $payload);
    $this->post(route('jobs.apply.unified'), $payload);

    expect(JobApplication::count())->toBe(1);
    Mail::assertQueued(JobSubmissionReceived::class, 1);
    Mail::assertQueued(JobSubmissionReceived::class, function (JobSubmissionReceived $mail): bool {
        $html = $mail->render();

        return $mail->hasTo('career@alisary.com')
            && str_contains($html, 'John Doe')
            && str_contains($html, 'Software Engineer')
            && str_contains($html, $mail->application->reference_number);
    });
});

it('notifies the career address for a legacy job listing application', function () {
    Mail::fake();
    Storage::fake('public');
    $listing = JobListing::factory()->create();

    $this->post(route('jobs.apply', $listing), [
        'full_name' => 'Legacy Applicant',
        'phone' => '90000000',
        'email' => 'legacy@example.com',
        'birthday' => '1995-01-01',
        'cv' => UploadedFile::fake()->create('cv.pdf', 250, 'application/pdf'),
    ])->assertRedirect();

    Mail::assertQueued(JobSubmissionReceived::class, 1);
    Mail::assertQueued(JobSubmissionReceived::class, fn ($mail) => $mail->hasTo('career@alisary.com')
        && str_contains($mail->render(), $listing->title));
});

it('keeps admin application links behind login', function () {
    $application = CareerApplication::factory()->create();

    $url = CareerApplicationResource::getUrl('view', ['record' => $application]);

    $this->get($url)->assertRedirect()->assertDontSee($application->full_name);
});

/**
 * @return array{0: Company, 1: JobListing, 2: array<string, mixed>}
 */
function notificationJobApplication(): array
{
    $company = Company::factory()->create();
    $listing = JobListing::factory()->for($company)->for(JobFamily::factory()->create(['track' => JobTrack::Teach]))
        ->create(['title' => 'Software Engineer']);

    return [$company, $listing, [
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
        'job_priority_1' => $listing->job_code,
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
    ]];
}

it('links each notification to the matching applicant record in the admin panel', function () {
    Mail::fake();
    Storage::fake('local');
    Storage::fake('public');

    $opening = CareerOpening::factory()->create();
    $this->post(route('careers.apply', $opening), [
        'full_name' => 'Career Path Applicant',
        'phone' => '99999999',
        'email' => 'career-path@example.com',
        'answers' => ['years_experience' => 3, 'cover_note' => 'x', 'acknowledge_truth' => 1],
    ])->assertSessionHasNoErrors();

    [, , $payload] = notificationJobApplication();
    $payload['full_name'] = 'Unified Path Applicant';
    $this->post(route('jobs.apply.unified'), $payload)->assertSessionHasNoErrors();

    $listing = JobListing::factory()->create();
    $this->post(route('jobs.apply', $listing), [
        'full_name' => 'Legacy Path Applicant',
        'phone' => '90000000',
        'email' => 'legacy-path@example.com',
        'birthday' => '1995-01-01',
        'cv' => UploadedFile::fake()->create('cv.pdf', 250, 'application/pdf'),
    ]);

    $expected = [
        'Career Path Applicant' => CareerApplicationResource::getUrl('view', ['record' => CareerApplication::where('full_name', 'Career Path Applicant')->firstOrFail()]),
        'Unified Path Applicant' => JobApplicationResource::getUrl('view', ['record' => JobApplication::where('full_name', 'Unified Path Applicant')->firstOrFail()]),
        'Legacy Path Applicant' => SubmissionResource::getUrl('edit', ['record' => Submission::where('full_name', 'Legacy Path Applicant')->firstOrFail()]),
    ];

    $this->actingAs(User::factory()->create());

    foreach ($expected as $name => $url) {
        $mails = array_merge(
            Mail::queued(CareerApplicationReceived::class)->all(),
            Mail::queued(JobSubmissionReceived::class)->all(),
        );
        $mail = collect($mails)->first(fn ($m) => str_contains($m->render(), $name));

        expect($mail)->not->toBeNull()
            ->and($mail->render())->toContain($url);

        $this->get($url)->assertSuccessful()->assertSee($name);
    }
});

it('explains in settings that the legacy recipients only receive duplicate alerts', function () {
    $this->actingAs(User::factory()->create())
        ->get(GeneralSettings::getUrl())
        ->assertSuccessful()
        ->assertSee('مستلمو تنبيهات الطلبات المكررة')
        ->assertSee('CAREER_APPLICATIONS_NOTIFY_TO');
});
