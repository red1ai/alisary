<?php

use App\Filament\Resources\CareerOrganizations\Pages\CreateCareerOrganization;
use App\Filament\Resources\CareerOrganizations\Pages\EditCareerOrganization;
use App\Models\CareerOpening;
use App\Models\CareerOrganization;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['app.url' => 'https://alisary.example.org']);
});

it('links an opening to an organization', function () {
    $organization = CareerOrganization::factory()->create();
    $opening = CareerOpening::factory()->create(['career_organization_id' => $organization->id]);

    expect($opening->organization->is($organization))->toBeTrue()
        ->and($organization->openings)->toHaveCount(1);
});

it('shows the organization page with only its published openings', function () {
    $organization = CareerOrganization::factory()->create(['name' => 'مؤسسة الاختبار', 'description' => 'وصف المؤسسة']);
    CareerOpening::factory()->create(['career_organization_id' => $organization->id, 'title' => 'وظيفة منشورة']);
    CareerOpening::factory()->draft()->create(['career_organization_id' => $organization->id, 'title' => 'وظيفة مسودة']);
    CareerOpening::factory()->create(['career_organization_id' => CareerOrganization::factory()->create()->id, 'title' => 'وظيفة مؤسسة أخرى']);

    $this->get(route('careers.organizations.show', $organization))
        ->assertSuccessful()
        ->assertSee('مؤسسة الاختبار')
        ->assertSee('وصف المؤسسة')
        ->assertSee('وظيفة منشورة')
        ->assertDontSee('وظيفة مسودة')
        ->assertDontSee('وظيفة مؤسسة أخرى')
        ->assertSee('data-share-url="https://alisary.example.org/careers/organizations/'.$organization->slug.'"', false)
        ->assertSee('نسخ الرابط');

    expect(route('careers.organizations.show', $organization, false))->toBe('/careers/organizations/'.$organization->slug);
});

it('keeps the organization route separate from job slugs', function () {
    $opening = CareerOpening::factory()->create(['slug' => 'organizations']);

    $this->get('/careers/organizations')->assertRedirect('/jobs/organizations');
    $this->get('/jobs/organizations')->assertSuccessful()->assertSee($opening->title);
    $this->get('/careers/organizations/missing')->assertNotFound();
});

it('renders open graph and twitter tags for a job using the organization logo', function () {
    $organization = CareerOrganization::factory()->create(['name' => 'مدرسة الاختبار', 'logo_path' => 'career-organizations/logos/logo.png']);
    $opening = CareerOpening::factory()->create([
        'career_organization_id' => $organization->id,
        'title' => 'معلمة رياضيات',
        'summary' => 'ملخص الوظيفة للمشاركة',
    ]);
    $url = 'https://alisary.example.org/jobs/'.$opening->slug;
    $image = 'https://alisary.example.org/storage/career-organizations/logos/logo.png';

    $this->get('http://127.0.0.1:8000/jobs/'.$opening->slug)
        ->assertSuccessful()
        ->assertSee('<meta property="og:title" content="معلمة رياضيات - مدرسة الاختبار">', false)
        ->assertSee('<meta property="og:description" content="ملخص الوظيفة للمشاركة">', false)
        ->assertSee('<meta property="og:url" content="'.$url.'">', false)
        ->assertSee('<meta property="og:image" content="'.$image.'">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
        ->assertSee('<meta name="twitter:image" content="'.$image.'">', false)
        ->assertSee('<link rel="canonical" href="'.$url.'">', false)
        ->assertSee(route('careers.organizations.show', $organization), false)
        ->assertDontSee('127.0.0.1:8000/storage', false);
});

it('falls back to the default image without a logo and never emits local urls', function () {
    $opening = CareerOpening::factory()->create(['career_organization_id' => CareerOrganization::factory()->create()->id]);

    $this->get(route('jobs.show', $opening))
        ->assertSee('<meta property="og:image" content="https://alisary.example.org/android-chrome-512x512.png">', false);

    config(['app.url' => 'http://localhost']);

    $this->get(route('jobs.show', $opening))
        ->assertSuccessful()
        ->assertSee('og:title', false)
        ->assertDontSee('og:url', false)
        ->assertDontSee('og:image', false)
        ->assertDontSee('localhost/storage', false);
});

it('renders open graph tags on the organization page', function () {
    $organization = CareerOrganization::factory()->create(['name' => 'مؤسسة', 'logo_path' => 'career-organizations/logos/a.png', 'description' => 'وصف']);

    $this->get(route('careers.organizations.show', $organization))
        ->assertSee('<meta property="og:title" content="مؤسسة - الوظائف">', false)
        ->assertSee('<meta property="og:url" content="https://alisary.example.org/careers/organizations/'.$organization->slug.'">', false)
        ->assertSee('<meta property="og:image" content="https://alisary.example.org/storage/career-organizations/logos/a.png">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
});

it('displays the uploaded logo on the job and organization pages', function () {
    $organization = CareerOrganization::factory()->create(['logo_path' => 'career-organizations/logos/shown.png']);
    $opening = CareerOpening::factory()->create(['career_organization_id' => $organization->id]);

    $this->get(route('jobs.show', $opening))->assertSee('src="/storage/career-organizations/logos/shown.png"', false);
    $this->get(route('careers.organizations.show', $organization))->assertSee('src="/storage/career-organizations/logos/shown.png"', false);
});

it('lets an admin upload and replace a logo and validates type and size', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    Livewire\Livewire::test(CreateCareerOrganization::class)
        ->fillForm(['name' => 'مؤسسة جديدة', 'slug' => 'new-org', 'logo_path' => UploadedFile::fake()->image('logo.png', 200, 200)])
        ->call('create')
        ->assertHasNoFormErrors();

    $organization = CareerOrganization::where('slug', 'new-org')->firstOrFail();
    Storage::disk('public')->assertExists($organization->logo_path);
    $first = $organization->logo_path;

    Livewire\Livewire::test(EditCareerOrganization::class, ['record' => $organization->getRouteKey()])
        ->fillForm(['logo_path' => [UploadedFile::fake()->image('second.jpg', 100, 100)]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($organization->fresh()->logo_path)->not->toBe($first);

    Livewire\Livewire::test(CreateCareerOrganization::class)
        ->fillForm(['name' => 'س', 'slug' => 'bad-type', 'logo_path' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
        ->call('create')
        ->assertHasFormErrors(['logo_path']);

    Livewire\Livewire::test(CreateCareerOrganization::class)
        ->fillForm(['name' => 'س', 'slug' => 'too-big', 'logo_path' => UploadedFile::fake()->image('big.png')->size(3000)])
        ->call('create')
        ->assertHasFormErrors(['logo_path']);
});
