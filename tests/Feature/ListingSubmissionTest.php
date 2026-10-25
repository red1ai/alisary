<?php

use App\Enums\CustomFieldType;
use App\Enums\ListingStatus;
use App\Mail\JobSubmissionReceived;
use App\Models\JobListing;
use App\Support\DefaultJobApplicationForm;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

test('visitor can submit a valid job application with default fields, cv, and custom fields', function () {
    Storage::fake('public');

    $jobListing = JobListing::factory()->create([
        'form_fields' => [
            ['key' => 'experience_years', 'label' => 'سنوات الخبرة', 'type' => CustomFieldType::Number->value, 'required' => true],
            ['key' => 'portfolio', 'label' => 'ملف إضافي', 'type' => CustomFieldType::File->value, 'required' => true, 'accepted_file_types' => ['pdf'], 'max_file_size_kb' => 2048],
        ],
    ]);

    $this->post(route('jobs.apply', $jobListing), [
        'full_name' => 'أحمد العيسري',
        'phone' => '90000000',
        'email' => 'ahmed@example.com',
        'birthday' => '1995-01-01',
        'cv' => UploadedFile::fake()->create('cv.pdf', 250, 'application/pdf'),
        'answers' => [
            'experience_years' => 5,
        ],
        'files' => [
            'portfolio' => UploadedFile::fake()->create('portfolio.pdf', 250, 'application/pdf'),
        ],
    ])->assertRedirect();

    $submission = $jobListing->submissions()->first();

    expect($submission)->not->toBeNull()
        ->and($submission->full_name)->toBe('أحمد العيسري')
        ->and($submission->email)->toBe('ahmed@example.com')
        ->and($submission->phone)->toBe('90000000')
        ->and($submission->birthday->toDateString())->toBe('1995-01-01')
        ->and((int) $submission->answers['experience_years'])->toBe(5)
        ->and($submission->files)->toHaveKey('portfolio')
        ->and($submission->submittable->is($jobListing))->toBeTrue();

    Storage::disk('public')->assertExists($submission->cv_path);
    Storage::disk('public')->assertExists($submission->files['portfolio']);
});

test('job submissions queue a notification to the career address', function () {
    Storage::fake('public');
    Mail::fake();

    $jobListing = JobListing::factory()->create([
        'title' => 'Frontend Engineer',
        'form_fields' => [
            ['key' => 'portfolio', 'label' => 'Portfolio', 'type' => CustomFieldType::File->value, 'required' => true, 'accepted_file_types' => ['pdf']],
        ],
    ]);

    $this->post(route('jobs.apply', $jobListing), [
        'full_name' => 'Applicant Name',
        'phone' => '90000000',
        'email' => 'applicant@example.com',
        'birthday' => '1995-01-01',
        'cv' => UploadedFile::fake()->create('cv.pdf', 250, 'application/pdf'),
        'files' => [
            'portfolio' => UploadedFile::fake()->create('portfolio.pdf', 250, 'application/pdf'),
        ],
    ])->assertRedirect();

    $submission = $jobListing->submissions()->first();

    Mail::assertQueued(JobSubmissionReceived::class, function (JobSubmissionReceived $mail) use ($jobListing, $submission): bool {
        return $mail->hasTo('career@alisary.com')
            && $mail->jobListing->is($jobListing)
            && $mail->submission->is($submission);
    });
});

test('job submissions do not queue notifications when the career address is unset', function () {
    Storage::fake('public');
    Mail::fake();
    config(['career_notifications.applications_to' => null]);

    $jobListing = JobListing::factory()->create();

    $this->post(route('jobs.apply', $jobListing), [
        'full_name' => 'Applicant Name',
        'phone' => '90000000',
        'email' => 'applicant@example.com',
        'birthday' => '1995-01-01',
        'cv' => UploadedFile::fake()->create('cv.pdf', 250, 'application/pdf'),
    ])->assertRedirect();

    Mail::assertNothingOutgoing();
});

test('job submission email renders basic details without file references', function () {
    Storage::fake('public');

    $jobListing = JobListing::factory()->create(['title' => 'Frontend Engineer'])->load('company');
    $submission = $jobListing->submissions()->create([
        'full_name' => 'Applicant Name',
        'phone' => '90000000',
        'email' => 'applicant@example.com',
        'birthday' => '1995-01-01',
        'cv_path' => 'submissions/jobs/1/cv/cv.pdf',
        'answers' => [],
        'files' => ['portfolio' => 'submissions/jobs/1/files/portfolio.pdf'],
    ]);

    $mailable = new JobSubmissionReceived($jobListing, $submission);

    $mailable
        ->assertSeeInHtml('Frontend Engineer')
        ->assertSeeInHtml('طلب تقديم على وظيفة')
        ->assertSeeInHtml('Applicant Name')
        ->assertSeeInHtml('applicant@example.com')
        ->assertSeeInHtml('90000000')
        ->assertSeeInHtml('logo.svg')
        ->assertDontSeeInHtml('laravel.com/img/notification-logo')
        ->assertDontSeeInHtml('submissions/jobs/1/cv/cv.pdf')
        ->assertDontSeeInHtml('submissions/jobs/1/files/portfolio.pdf');
});

test('required job custom fields are validated', function () {
    $jobListing = JobListing::factory()->create([
        'form_fields' => [
            ['key' => 'experience_years', 'label' => 'سنوات الخبرة', 'type' => CustomFieldType::Number->value, 'required' => true],
        ],
    ]);

    $this->from(route('jobs.show', $jobListing))
        ->post(route('jobs.apply', $jobListing), [
            'full_name' => 'أحمد',
            'phone' => '90000000',
            'email' => 'ahmed@example.com',
            'birthday' => '1995-01-01',
            'cv' => UploadedFile::fake()->create('cv.pdf', 250, 'application/pdf'),
        ])
        ->assertRedirect(route('jobs.show', $jobListing))
        ->assertSessionHasErrors('answers.experience_years');
});

test('visitor can submit grouped job application fields with checkbox lists', function () {
    Storage::fake('public');

    $jobListing = JobListing::factory()->create([
        'form_fields' => [
            [
                'title' => 'الوظيفة والمؤسسة',
                'description' => 'تفاصيل التقديم',
                'fields' => [
                    [
                        'key' => 'contract_types',
                        'label' => 'نمط التعاقد الذي تقبله',
                        'type' => CustomFieldType::CheckboxList->value,
                        'required' => true,
                        'options' => [
                            ['label' => 'دوام كامل', 'value' => 'full_time'],
                            ['label' => 'عن بعد', 'value' => 'remote'],
                        ],
                    ],
                    [
                        'key' => 'expected_salary',
                        'label' => 'الراتب الشهري المتوقع',
                        'type' => CustomFieldType::Text->value,
                        'required' => true,
                    ],
                ],
            ],
        ],
    ]);

    $this->post(route('jobs.apply', $jobListing), [
        'full_name' => 'أحمد العيسري',
        'phone' => '90000000',
        'email' => 'ahmed@example.com',
        'birthday' => '1995-01-01',
        'cv' => UploadedFile::fake()->create('cv.pdf', 250, 'application/pdf'),
        'answers' => [
            'contract_types' => ['full_time', 'remote'],
            'expected_salary' => '900',
        ],
    ])->assertRedirect();

    $submission = $jobListing->submissions()->first();

    expect($submission)->not->toBeNull()
        ->and($submission->answers['contract_types'])->toBe(['full_time', 'remote'])
        ->and($submission->answers['expected_salary'])->toBe('900');
});

test('default job application schema matches the grouped careers form', function () {
    $sections = DefaultJobApplicationForm::sections();

    expect($sections)->toHaveCount(7)
        ->and(collect($sections)->pluck('title')->all())->toBe([
            'البيانات الأساسية',
            'الوظيفة والمؤسسة',
            'الخبرة والأدوات',
            'الكفاءة والإنجاز',
            'مواقف',
            'آفاقٌ مستقبلية',
            'الإقرارات',
        ])
        ->and($sections[1]['fields'][3]['type'])->toBe(CustomFieldType::CheckboxList->value)
        ->and($sections[6]['fields'][0]['required'])->toBeTrue();
});

test('closed jobs reject submissions', function () {
    $jobListing = JobListing::factory()->create([
        'status' => ListingStatus::Closed,
    ]);

    $this->post(route('jobs.apply', $jobListing), [
        'full_name' => 'أحمد',
        'phone' => '90000000',
        'email' => 'ahmed@example.com',
        'birthday' => '1995-01-01',
        'cv' => UploadedFile::fake()->create('cv.pdf', 250, 'application/pdf'),
    ])->assertForbidden();
});
