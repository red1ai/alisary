<?php

use App\Models\CareerApplication;
use App\Models\CareerOpening;
use App\Models\JobListing;
use Database\Seeders\BranchOpeningsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

it('serves a career opening on /jobs/{slug} and redirects the old /careers/{slug}', function () {
    $opening = CareerOpening::factory()->create();

    expect(route('jobs.show', $opening, false))->toBe('/jobs/'.$opening->slug);

    $this->get('/jobs/'.$opening->slug)->assertSuccessful()->assertSee($opening->title);
    $this->get('/careers/'.$opening->slug)->assertRedirect('/jobs/'.$opening->slug)->assertStatus(301);
    $this->get('/careers/no-such-opening')->assertNotFound();
    $this->get('/jobs/no-such-opening')->assertNotFound();
});

it('keeps legacy job listings working on the same /jobs/{slug} route', function () {
    $listing = JobListing::factory()->create();

    $this->get('/jobs/'.$listing->slug)->assertSuccessful()->assertSee($listing->title);
    $this->get('/jobs')->assertSuccessful();
});

it('shows a clear file picker with size note on the form', function () {
    $this->seed(BranchOpeningsSeeder::class);

    $this->get('/jobs/cycle-one-manager-ibra')
        ->assertSuccessful()
        ->assertSee('class="upload-button"', false)
        ->assertSee('لم يتم اختيار ملف بعد')
        ->assertSee('سيُرفع الملف عند إرسال الطلب');
});

it('stores the CV privately and confirms the attachment without sending mail', function () {
    Mail::fake();
    Storage::fake('local');
    $this->seed(BranchOpeningsSeeder::class);

    $this->post(route('careers.apply', 'english-teacher-bawshar'), [
        'full_name' => 'مقدّمة طلب',
        'phone' => '99999999',
        'email' => 'cv@example.com',
        'answers' => ['qualification' => 'بكالوريوس', 'years_experience' => 3, 'acknowledge_truth' => 1],
        'files' => ['cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')],
    ])->assertSessionHasNoErrors()->assertSessionHas('application_files_count', 1);

    Storage::disk('local')->assertExists(CareerApplication::firstOrFail()->files['cv']);

    $this->withSession(['application_success' => true, 'application_files_count' => 1])
        ->get('/jobs/english-teacher-bawshar')
        ->assertSee('تم إرفاق ملفاتك بنجاح');
});
