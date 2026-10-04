<?php

use App\Models\CareerApplication;
use App\Models\CareerOpening;
use Database\Seeders\CareerOpeningSeeder;
use Database\Seeders\DemoExecutiveHrOpeningSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function executivePayload(array $overrides = []): array
{
    return array_replace_recursive([
        'full_name' => 'مرشح تجريبي',
        'phone' => '91234567',
        'email' => 'executive@example.com',
        'answers' => [
            'nid' => '12345678',
            'dob' => '1985-05-05',
            'nationality' => 'عُماني',
            'gov' => 'مسقط',
            'edu' => 'ماجستير',
            'major' => 'إدارة موارد بشرية',
            'exp' => '٧–١٠ سنوات',
            'employer' => 'جهة تجريبية',
            'essay' => 'ثلاثة قرارات تجريبية.',
            'cover' => 'نبذة',
            'ref1' => 'مزكٍّ أول',
            'ref2' => 'مزكٍّ ثانٍ',
            'consent' => 1,
        ],
        'files' => [
            'cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
            'certs' => [
                UploadedFile::fake()->create('a.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('b.png', 100, 'image/png'),
            ],
        ],
    ], $overrides);
}

beforeEach(function () {
    $this->seed(DemoExecutiveHrOpeningSeeder::class);
    $this->opening = CareerOpening::where('slug', 'exec-hr-director')->firstOrFail();
});

it('renders the executive template sections and keeps disabled sections hidden', function () {
    $this->get(route('jobs.show', $this->opening))
        ->assertSuccessful()
        ->assertSee('نسخة تجريبية — راجِع النصوص والشروط قبل النشر')
        ->assertSee('مدرسة القارئ العبقري · الإدارة التنفيذية')
        ->assertSee('الهدف العام للوظيفة')
        ->assertSee('ما تقوده')
        ->assertSee('لا بدّ منه')
        ->assertSee('يُقدَّم من عنده')
        ->assertSee('كيف يُقاس نجاحك؟')
        ->assertSee('كيف نختار؟')
        ->assertSee('محور أساسي لهذه الوظيفة')
        ->assertSee('الوظيفة تحمل قيم العبادة وقيم العمل')
        ->assertDontSee('الفروع والمقاعد')
        ->assertDontSee('مسار الترقّي')
        ->assertDontSee('الوقت والالتزام')
        ->assertSee('ملخّص سريع');
});

it('renders the four template steps in a stepper with identity fields in template order', function () {
    $html = $this->get(route('jobs.show', $this->opening))->assertSuccessful()->getContent();

    foreach (['هويتك', 'مسيرتك', 'رؤيتك', 'المزكّون'] as $title) {
        expect($html)->toContain('<span>'.$title.'</span>');
    }

    expect($html)->toContain('<ol class="stepper"')
        ->and(substr_count($html, '<section data-career-step'))->toBe(4)
        ->and($html)->not->toContain('لنتمكّن من التواصل معك')
        ->and($html)->toContain('novalidate')
        ->and($html)->not->toContain('data-form-wizard');

    $order = collect(['name="full_name"', 'name="answers[nid]"', 'name="answers[dob]"', 'name="answers[nationality]"', 'name="phone"', 'name="email"', 'name="answers[gov]"'])
        ->map(fn (string $needle): int|false => strpos($html, $needle));

    expect($order->contains(false))->toBeFalse()
        ->and($order->all())->toBe($order->sort()->values()->all());

    expect($html)->toContain('الاسم الثلاثي')
        ->and($html)->toContain('رقم الواتساب')
        ->and($html)->toContain('inputmode="numeric"')
        ->and($html)->toContain('inputmode="tel"')
        ->and($html)->toContain('مثال: 91234567')
        ->and($html)->toContain('data-pattern="^(\+?968)?\s?\d{8}$"')
        ->and($html)->toContain('data-pattern="^\d{6,10}$"')
        ->and($html)->toContain('type="radio"')
        ->and($html)->toContain('name="files[certs][]"')
        ->and($html)->toContain('multiple')
        ->and($html)->toContain('maxlength="1200"')
        ->and($html)->toContain('اكتب بما تراه عمليًّا، لا بما تراه مثاليًّا')
        ->and($html)->toContain('class="consent"')
        ->and($html)->toContain('data-career-form');
});

it('validates the Omani WhatsApp number and national id on the server', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    Storage::fake('local');
    $url = route('careers.apply', $this->opening);

    foreach (['91234567', '+96891234567', '96891234567', '968 91234567', '٩١٢٣٤٥٦٧'] as $valid) {
        $this->post($url, executivePayload(['phone' => $valid, 'email' => fake()->unique()->safeEmail()]))
            ->assertSessionHasNoErrors();
    }

    foreach (['9123456', '912345678', 'abc12345', '+9689123'] as $invalid) {
        $this->post($url, executivePayload(['phone' => $invalid, 'email' => fake()->unique()->safeEmail()]))
            ->assertSessionHasErrors(['phone' => 'أدخل رقمًا عُمانيًّا من ٨ أرقام.']);
    }

    foreach (['12345', '12345678901', '12ab56'] as $invalidId) {
        $this->post($url, executivePayload(['answers' => ['nid' => $invalidId], 'email' => fake()->unique()->safeEmail()]))
            ->assertSessionHasErrors(['answers.nid' => 'أدخل رقم البطاقة أرقامًا فقط.']);
    }

    $this->post($url, executivePayload(['answers' => ['nid' => '123456'], 'email' => fake()->unique()->safeEmail()]))
        ->assertSessionHasNoErrors();
});

it('shows the template success screen with the application reference', function () {
    Mail::fake();
    Storage::fake('local');

    $this->followingRedirects()
        ->from(route('jobs.show', $this->opening))
        ->post(route('careers.apply', $this->opening), executivePayload())
        ->assertSuccessful()
        ->assertSee('وصلَنا طلبُك')
        ->assertSee('نقرأ كل طلبٍ كاملًا. سيصلك الردّ بعد الفرز الأولي')
        ->assertSee('رقم طلبك:')
        ->assertSee(CareerApplication::firstOrFail()->reference_number)
        ->assertDontSee('data-career-form', false)
        ->assertDontSee('<ol class="stepper"', false);
});

it('exposes eligibility rules to the form so gates can show before submission', function () {
    $opening = CareerOpening::factory()->withEligibilityRules([
        ['field' => 'years_experience', 'operator' => 'min', 'value' => 5, 'message' => 'شرط تجريبي: خبرة لا تقل عن 5 سنوات.'],
    ])->create();

    $html = $this->get(route('jobs.show', $opening))->assertSuccessful()->getContent();

    expect($html)->toContain('data-gates=')
        ->and($html)->toContain('years_experience')
        ->and($html)->toContain('شرط تجريبي: خبرة لا تقل عن 5 سنوات.');
});
it('accepts a complete executive application with multiple certificate files', function () {
    Mail::fake();
    Storage::fake('local');

    $this->post(route('careers.apply', $this->opening), executivePayload())
        ->assertSessionHasNoErrors()
        ->assertSessionHas('application_success');

    $application = CareerApplication::firstOrFail();

    expect($application->career_opening_id)->toBe($this->opening->id)
        ->and($application->answers['nid'])->toBe('12345678')
        ->and($application->files['certs'])->toHaveCount(2);

    Storage::disk('local')->assertExists($application->files['cv']);

    foreach ($application->files['certs'] as $path) {
        Storage::disk('local')->assertExists($path);
    }
});

it('validates template rules on the server: pattern, choices, lengths, files and consent', function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    Storage::fake('local');
    $url = route('careers.apply', $this->opening);

    $this->post($url, executivePayload(['answers' => ['nid' => 'abc']]))
        ->assertSessionHasErrors(['answers.nid' => 'أدخل رقم البطاقة أرقامًا فقط.']);

    $this->post($url, executivePayload(['answers' => ['nationality' => 'غير صالح']]))
        ->assertSessionHasErrors('answers.nationality');

    $this->post($url, executivePayload(['answers' => ['essay' => str_repeat('أ', 1201)]]))
        ->assertSessionHasErrors('answers.essay');

    $this->post($url, executivePayload(['answers' => ['cover' => str_repeat('أ', 501)]]))
        ->assertSessionHasErrors('answers.cover');

    $payload = executivePayload();
    unset($payload['files']['certs']);
    $this->post($url, $payload)->assertSessionHasErrors('files.certs');

    $payload = executivePayload();
    $payload['files']['certs'] = [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')];
    $this->post($url, $payload)->assertSessionHasErrors('files.certs.0');

    $payload = executivePayload();
    unset($payload['answers']['consent']);
    $this->post($url, $payload)->assertSessionHasErrors('answers.consent');

    expect(CareerApplication::count())->toBe(0);
});

it('uses field labels in validation messages', function () {
    $payload = executivePayload();
    unset($payload['answers']['nid'], $payload['full_name']);

    $errors = $this->post(route('careers.apply', $this->opening), $payload)
        ->assertSessionHasErrors(['answers.nid', 'full_name'])
        ->baseResponse->getSession()->get('errors');

    expect($errors->first('answers.nid'))->toContain('رقم البطاقة الشخصية')
        ->and($errors->first('full_name'))->toContain('الاسم الثلاثي');
});

it('keeps the Ibra record independent from the executive demo', function () {
    $this->seed(CareerOpeningSeeder::class);

    $ibra = CareerOpening::where('slug', 'school-principal-ibra')->firstOrFail();

    expect($ibra->status->value)->toBe('draft')
        ->and($ibra->form_sections)->toBeNull()
        ->and($ibra->unit)->toBeNull()
        ->and($ibra->identity_in_first_step)->toBeFalse()
        ->and($this->opening->id)->not->toBe($ibra->id);
});

it('does not seed the executive demo outside local and testing environments', function () {
    CareerOpening::query()->delete();
    app()->detectEnvironment(fn () => 'production');

    (new DemoExecutiveHrOpeningSeeder)->run();

    expect(CareerOpening::count())->toBe(0);
});
