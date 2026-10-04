<?php

use App\Models\CareerApplication;
use App\Models\CareerOpening;
use Database\Seeders\BranchOpeningsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => $this->seed(BranchOpeningsSeeder::class));

it('publishes the four branch openings with their own pages and content', function (string $slug, string $title, array $expected) {
    $this->get(route('jobs.show', $slug))
        ->assertSuccessful()
        ->assertSee($title)
        ->assertSee($expected)
        ->assertSee('name="answers[years_experience]"', false)
        ->assertSee('name="files[cv]"', false);

    expect(CareerOpening::where('slug', $slug)->firstOrFail()->isAcceptingSubmissions())->toBeTrue();
})->with([
    ['early-ed-manager-bawshar', 'مديرة فرع — تعليم مبكر — فرع بوشر', ['٤٨٨ إلى ٥٤٧', 'امتلاء الفرع ≥٩٠٪']],
    ['cycle-one-manager-ibra', 'مديرة فرع — الحلقة الأولى — فرع إبراء', ['٥٤٧ إلى ٦٢٢', 'تفعيل الأدوار الداعمة ≥٩٥٪']],
    ['english-teacher-bawshar', 'معلمة لغة إنجليزية — تعليم مبكر — فرع بوشر', ['٣٥٨ إلى ٣٨٦', 'يقول الحسن بلسانين']],
    ['domain-one-teacher-udhaibah', 'معلمة مجال أول — الحلقة الأولى — فرع العذيبة', ['٤٣٠ إلى ٤٤٦', 'القلب المطمئن']],
]);

it('is idempotent and leaves other openings untouched', function () {
    $other = CareerOpening::factory()->create();

    $this->seed(BranchOpeningsSeeder::class);

    expect(CareerOpening::whereIn('slug', BranchOpeningsSeeder::SLUGS)->count())->toBe(4)
        ->and(CareerOpening::find($other->id)->title)->toBe($other->title);
});

it('stores an application against the right opening only', function () {
    Mail::fake();
    Storage::fake('local');

    $this->post(route('careers.apply', 'english-teacher-bawshar'), [
        'full_name' => 'مقدّمة طلب',
        'phone' => '99999999',
        'email' => 'applicant@example.com',
        'answers' => ['qualification' => 'بكالوريوس لغة إنجليزية', 'years_experience' => 3, 'acknowledge_truth' => 1],
        'files' => ['cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')],
    ])->assertSessionHasNoErrors()->assertSessionHas('application_success');

    $application = CareerApplication::firstOrFail();
    expect($application->careerOpening->slug)->toBe('english-teacher-bawshar');
    Storage::disk('local')->assertExists($application->files['cv']);
    expect(CareerApplication::where('career_opening_id', '!=', $application->career_opening_id)->count())->toBe(0);
});

it('rejects an application without the required cv or acknowledgement', function () {
    $this->post(route('careers.apply', 'cycle-one-manager-ibra'), [
        'full_name' => 'مقدّمة طلب',
        'phone' => '99999999',
        'email' => 'applicant@example.com',
        'answers' => ['qualification' => 'بكالوريوس تربية', 'years_experience' => 5],
    ])->assertSessionHasErrors(['files.cv', 'answers.acknowledge_truth']);

    expect(CareerApplication::count())->toBe(0);
});
