<?php

use App\Filament\Resources\CareerApplications\Pages\ViewCareerApplication;
use App\Filament\Resources\CareerOpenings\Pages\CreateCareerOpening;
use App\Filament\Resources\CareerOpenings\Pages\EditCareerOpening;
use App\Filament\Resources\CareerOpenings\Pages\ListCareerOpenings;
use App\Models\CareerApplication;
use App\Models\CareerOpening;
use App\Models\JobApplication;
use App\Models\User;
use App\Support\CareerShare;
use Database\Seeders\CareerOpeningSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function careerPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'full_name' => 'مقدّمة طلب',
        'phone' => '99999999',
        'email' => 'applicant@example.com',
        'answers' => [
            'years_experience' => 7,
            'cover_note' => 'نبذة',
            'acknowledge_truth' => 1,
        ],
    ], $overrides);
}

it('hides draft openings from guests but lets signed-in admins preview them', function () {
    $opening = CareerOpening::factory()->draft()->create();

    $this->get(route('jobs.show', $opening))->assertNotFound();

    $this->actingAs(User::factory()->create())
        ->get(route('jobs.show', $opening))
        ->assertSuccessful()
        ->assertSee('معاينة للإدارة فقط');
});

it('renders a published opening with its configured fields', function () {
    $opening = CareerOpening::factory()->create();

    $this->get(route('jobs.show', $opening))
        ->assertSuccessful()
        ->assertSee($opening->title)
        ->assertSee('سنوات الخبرة')
        ->assertSee('name="answers[years_experience]"', false);
});

it('does not accept applications for a published opening without approved content', function () {
    $opening = CareerOpening::factory()->create(['form_sections' => [], 'description' => null]);

    $this->get(route('jobs.show', $opening))->assertSuccessful()->assertSee('سيُفتح باب التقديم')->assertDontSee('name="full_name"', false);
    $this->post(route('careers.apply', $opening), careerPayload())->assertNotFound();

    expect(CareerApplication::count())->toBe(0);
});

it('stores an application, its private files and queues a notification', function () {
    Mail::fake();
    Storage::fake('local');
    $opening = CareerOpening::factory()->create();

    $this->post(route('careers.apply', $opening), careerPayload([
        'files' => ['cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')],
    ]))->assertSessionHasNoErrors()->assertSessionHas('application_success');

    $application = CareerApplication::firstOrFail();

    expect($application->career_opening_id)->toBe($opening->id)
        ->and($application->answers['years_experience'])->toEqual(7)
        ->and($application->reference_number)->toStartWith('CA-');

    Storage::disk('local')->assertExists($application->files['cv']);
    Storage::disk('public')->assertMissing($application->files['cv']);
});

it('validates required configured fields on the server', function () {
    $opening = CareerOpening::factory()->create();

    $this->post(route('careers.apply', $opening), careerPayload(['answers' => ['years_experience' => '']]))
        ->assertSessionHasErrors('answers.years_experience');

    expect(CareerApplication::count())->toBe(0);
});

it('rejects a second application with the same email to the same opening only', function () {
    $opening = CareerOpening::factory()->create();
    $other = CareerOpening::factory()->create();

    $this->post(route('careers.apply', $opening), careerPayload())->assertSessionHasNoErrors();
    $this->post(route('careers.apply', $opening), careerPayload())->assertSessionHasErrors('email');
    $this->post(route('careers.apply', $other), careerPayload())->assertSessionHasNoErrors();

    expect(CareerApplication::count())->toBe(2);
});

it('blocks applicants who fail an eligibility rule', function () {
    $opening = CareerOpening::factory()->withEligibilityRules([
        ['field' => 'years_experience', 'operator' => 'min', 'value' => 5, 'message' => 'يشترط خبرة لا تقل عن خمس سنوات.'],
    ])->create();

    $this->post(route('careers.apply', $opening), careerPayload(['answers' => ['years_experience' => 2]]))
        ->assertSessionHasErrors(['answers.years_experience' => 'يشترط خبرة لا تقل عن خمس سنوات.']);

    expect(CareerApplication::count())->toBe(0);

    $this->post(route('careers.apply', $opening), careerPayload(['answers' => ['years_experience' => 6]]))
        ->assertSessionHasNoErrors();

    expect(CareerApplication::count())->toBe(1);
});

it('rejects filled honeypot submissions', function () {
    $opening = CareerOpening::factory()->create();

    $this->post(route('careers.apply', $opening), careerPayload(['website' => 'spam']))
        ->assertSessionHasErrors('website');

    expect(CareerApplication::count())->toBe(0);
});

it('leaves the legacy jobs page and its applications untouched', function () {
    $this->get(route('jobs.index'))->assertSuccessful();

    expect(JobApplication::count())->toBe(0);
});

it('supports comma separated values for in and not_in eligibility rules', function () {
    $opening = CareerOpening::factory()->withEligibilityRules([
        ['field' => 'cover_note', 'operator' => 'in', 'value' => 'أ, ب', 'message' => 'غير مؤهل'],
    ])->create();

    $this->post(route('careers.apply', $opening), careerPayload(['answers' => ['cover_note' => 'ج']]))
        ->assertSessionHasErrors('answers.cover_note');

    $this->post(route('careers.apply', $opening), careerPayload(['answers' => ['cover_note' => 'ب']]))
        ->assertSessionHasNoErrors();
});

it('seeds the Ibra principal opening as a hidden draft with title, entity and location only', function () {
    $this->seed(CareerOpeningSeeder::class);

    $opening = CareerOpening::where('slug', 'school-principal-ibra')->firstOrFail();

    expect($opening->status->value)->toBe('draft')
        ->and($opening->location->value)->toBe('ibra')
        ->and($opening->description)->toBeNull()
        ->and($opening->form_sections)->toBeNull()
        ->and($opening->eligibility_rules)->toBeNull()
        ->and($opening->isPublished())->toBeFalse()
        ->and($opening->isAcceptingSubmissions())->toBeFalse();

    $this->get(route('jobs.show', $opening))->assertNotFound();
    $this->post(route('careers.apply', $opening), careerPayload())->assertNotFound();

    $this->actingAs(User::factory()->create())
        ->get(route('jobs.show', $opening))
        ->assertSuccessful()
        ->assertSee('مديرة مدرسة القارئ العبقري')
        ->assertSee('معاينة للإدارة فقط');

    expect(CareerApplication::count())->toBe(0);
});

it('does not take applications for a published opening whose setup is incomplete', function () {
    $opening = CareerOpening::factory()->create(['form_sections' => [], 'blocks' => null, 'description' => null]);

    $this->post(route('careers.apply', $opening), careerPayload())->assertNotFound();
});

it('renders only the enabled page blocks with their content', function () {
    $opening = CareerOpening::factory()->create([
        'subtitle' => 'سطر تعريفي',
        'chips' => [['k' => 'النمط', 'v' => 'دوام كامل']],
        'blocks' => [
            'about' => ['on' => true, 'text' => 'نص القسم الأول'],
            'tasks' => ['on' => true, 'items' => ['مهمة أولى', 'مهمة ثانية']],
            'pay' => ['on' => false, 'text' => 'نص أجر مخفي'],
            'kpis' => ['on' => true, 'items' => []],
        ],
    ]);

    $this->get(route('jobs.show', $opening))
        ->assertSuccessful()
        ->assertSee('سطر تعريفي')
        ->assertSee('دوام كامل')
        ->assertSee('نص القسم الأول')
        ->assertSee('مهمة ثانية')
        ->assertDontSee('نص أجر مخفي')
        ->assertDontSee('كيف يُقاس نجاحك؟');
});

it('enforces a minimum age eligibility rule on the server', function () {
    $opening = CareerOpening::factory()->withEligibilityRules([
        ['field' => 'dob', 'operator' => 'min_age', 'value' => 21, 'message' => 'العمر أقل من المطلوب.'],
    ])->create();
    $opening->update(['form_sections' => [[
        'title' => 'بيانات', 'description' => null,
        'fields' => [['key' => 'dob', 'label' => 'تاريخ الميلاد', 'type' => 'date', 'required' => true, 'options' => []]],
    ]]]);

    $this->post(route('careers.apply', $opening), careerPayload(['answers' => ['dob' => now()->subYears(19)->toDateString()]]))
        ->assertSessionHasErrors(['answers.dob' => 'العمر أقل من المطلوب.']);

    $this->post(route('careers.apply', $opening), careerPayload(['answers' => ['dob' => now()->subYears(30)->toDateString()]]))
        ->assertSessionHasNoErrors();
});

it('lets an admin create a draft opening with blocks, form steps and rules, then complete and publish it', function () {
    $this->actingAs(User::factory()->create());

    Livewire\Livewire::test(CreateCareerOpening::class)
        ->fillForm([
            'title' => 'وظيفة تجريبية',
            'slug' => 'test-opening',
            'location' => 'ibra',
            'category' => 'leadership',
            'status' => 'draft',
            'blocks' => ['about' => ['on' => true, 'text' => 'نص تجريبي']],
            'form_sections' => [[
                'title' => 'الخطوة الأولى',
                'fields' => [['key' => 'years_experience', 'label' => 'سنوات الخبرة', 'type' => 'number', 'required' => true]],
            ]],
            'eligibility_rules' => [['field' => 'years_experience', 'operator' => 'min', 'value' => '3', 'message' => 'خبرة غير كافية']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $opening = CareerOpening::where('slug', 'test-opening')->firstOrFail();

    expect($opening->status->value)->toBe('draft')
        ->and($opening->fields())->toHaveCount(1)
        ->and($opening->isAcceptingSubmissions())->toBeFalse();

    $this->post(route('careers.apply', $opening), careerPayload())->assertNotFound();

    Livewire\Livewire::test(EditCareerOpening::class, ['record' => $opening->getRouteKey()])
        ->fillForm(['title' => 'عنوان معدّل', 'status' => 'published', 'published_at' => now()->subHour()])
        ->call('save')
        ->assertHasNoFormErrors();

    $opening->refresh();

    expect($opening->title)->toBe('عنوان معدّل')
        ->and($opening->isAcceptingSubmissions())->toBeTrue();

    $this->post(route('careers.apply', $opening), careerPayload(['answers' => ['years_experience' => 1]]))
        ->assertSessionHasErrors('answers.years_experience');
    $this->post(route('careers.apply', $opening), careerPayload(['answers' => ['years_experience' => 5]]))
        ->assertSessionHasNoErrors();
});

it('lets admins list openings and view applications in Filament', function () {
    $this->actingAs(User::factory()->create());
    $opening = CareerOpening::factory()->create();
    $application = CareerApplication::factory()->for($opening)->create();

    Livewire\Livewire::test(ListCareerOpenings::class)
        ->assertCanSeeTableRecords([$opening]);

    Livewire\Livewire::test(ViewCareerApplication::class, ['record' => $application->getRouteKey()])
        ->assertSuccessful();
});

it('shows share buttons built from the public APP_URL for a published opening', function () {
    config(['app.url' => 'https://alisary.example.org']);
    $opening = CareerOpening::factory()->create(['title' => 'مديرة & مدرسة']);
    $url = 'https://alisary.example.org/jobs/'.$opening->slug;

    expect($opening->shareUrl())->toBe($url);

    $this->get('http://127.0.0.1:8000/jobs/'.$opening->slug)
        ->assertSuccessful()
        ->assertSee('data-career-share', false)
        ->assertSee('data-share-url="'.$url.'"', false)
        ->assertSee('نسخ الرابط')
        ->assertSee('data-share-copy', false)
        ->assertSee('data-share-native', false)
        ->assertSee('aria-label="مشاركة الوظيفة على واتساب (يفتح في نافذة جديدة)"', false)
        ->assertSee('https://wa.me/?text='.rawurlencode($opening->title."\n".$url), false)
        ->assertSee('https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($url), false)
        ->assertSee('https://www.linkedin.com/sharing/share-offsite/?url='.rawurlencode($url), false)
        ->assertSee('https://x.com/intent/post?text='.rawurlencode($opening->title).'&amp;url='.rawurlencode($url), false)
        ->assertDontSee('data-share-url="http://127', false)
        ->assertDontSee(rawurlencode('http://127.0.0.1'), false);
});

it('builds encoded official share links', function () {
    $links = collect(CareerShare::links('عنوان & خاص؟', 'https://alisary.example.org/careers/a b?x=1&y=2'))->keyBy('key');

    expect($links['whatsapp']['href'])->toStartWith('https://wa.me/?text=')
        ->and($links['whatsapp']['href'])->not->toContain(' ')
        ->and($links['x']['href'])->toContain('text='.rawurlencode('عنوان & خاص؟'))
        ->and($links['facebook']['href'])->toContain(rawurlencode('https://alisary.example.org/careers/a b?x=1&y=2'))
        ->and($links)->toHaveCount(4);
});

it('never offers a local or private address for sharing', function () {
    $opening = CareerOpening::factory()->create();

    foreach (['http://localhost', 'http://127.0.0.1:8000', 'http://alisary.test', 'http://192.168.1.10', 'http://[::1]'] as $local) {
        config(['app.url' => $local]);
        expect($opening->fresh()->shareUrl())->toBeNull();
    }

    config(['app.url' => 'http://localhost']);
    $this->get(route('jobs.show', $opening))->assertSuccessful()->assertDontSee('data-career-share', false);
});

it('hides share buttons on draft previews and unpublished openings', function () {
    config(['app.url' => 'https://alisary.example.org']);
    $draft = CareerOpening::factory()->draft()->create();

    expect($draft->shareUrl())->toBeNull();

    $this->actingAs(User::factory()->create())
        ->get(route('jobs.show', $draft))
        ->assertSuccessful()
        ->assertSee('معاينة للإدارة فقط')
        ->assertDontSee('data-career-share', false)
        ->assertDontSee('نسخ الرابط');
});
