<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    // Filament admits any user only in the local environment (User does not implement FilamentUser).
    app()->detectEnvironment(fn (): string => 'local');
    config(['app.env' => 'local']);
});

it('sends guests to the admin login for the builder, the previews and the panel page', function () {
    $login = route('filament.admin.auth.login');

    $this->get(route('job-pages.builder'))->assertRedirect($login);
    $this->get(route('job-pages.preview', 'exec-hr-director'))->assertRedirect($login);
    $this->get('/admin/job-page-builder')->assertRedirect($login);
});

it('never reveals the builder markup to a guest', function () {
    $this->followingRedirects()->get(route('job-pages.builder'))
        ->assertDontSee('أداة بناء صفحة الوظيفة')
        ->assertDontSee('نسخ الإعداد');
});

it('serves the builder to a signed-in admin without caching', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('job-pages.builder'))
        ->assertSuccessful()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeader('Cache-Control')
        ->assertSee('أداة بناء صفحة الوظيفة', false);
});

it('serves page previews to a signed-in admin and rejects unknown or traversing names', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('job-pages.preview', 'exec-hr-director'))
        ->assertSuccessful()
        ->assertSee('exec-hr-director', false)
        ->assertSee('window.CONFIG', false);

    $this->get('/admin/job-pages/preview/missing-page')->assertNotFound();
    $this->get('/admin/job-pages/preview/..%2F..%2F.env')->assertNotFound();
    $this->get('/admin/job-pages/preview/builder')->assertNotFound();
});

it('shows the link and the previews on the panel page for an admin', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin/job-page-builder')
        ->assertSuccessful()
        ->assertSee('منشئ صفحات الوظائف')
        ->assertSee(route('job-pages.builder'), false)
        ->assertSee(route('job-pages.preview', 'exec-hr-director'), false);
});

it('denies the tool and hides the navigation when the ability is withheld', function () {
    Gate::define('useJobPageBuilder', fn (?User $user): bool => false);
    $this->actingAs(User::factory()->create());

    $this->get(route('job-pages.builder'))->assertForbidden();
    $this->get(route('job-pages.preview', 'exec-hr-director'))->assertForbidden();
    $this->get('/admin/job-page-builder')->assertForbidden();
    $this->get('/admin')->assertDontSee('منشئ صفحات الوظائف');
});

it('lists the navigation entry on the admin dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertSuccessful()
        ->assertSee('منشئ صفحات الوظائف');
});

it('keeps closing html tags out of the inline scripts so injected scripts cannot cut them short', function () {
    // Middleware such as Laravel Boost's browser logger inserts markup before the first </body>;
    // a literal "</body>" inside a JavaScript string would split the builder's script in two.
    foreach ([resource_path('job-pages/out/builder.html') => 1, public_path('job-pages/exec-hr-director.html') => 2] as $file => $scripts) {
        $html = file_get_contents($file);

        expect(substr_count($html, '</body>'))->toBe(1)
            ->and(substr_count($html, '</html>'))->toBe(1)
            ->and(substr_count($html, '</head>'))->toBe(1)
            ->and(substr_count($html, '</script>'))->toBe($scripts);
    }
});

it('serves a builder whose html is still intact after an injected script is added before </body>', function () {
    $this->actingAs(User::factory()->create());

    $html = $this->get(route('job-pages.builder'))->assertSuccessful()->getContent();
    $injected = str_replace('</body>', '<script>window.injected = true;</script></body>', $html);

    expect($injected)->toContain('window.injected = true;')
        ->and(substr_count($injected, '</script>'))->toBe(substr_count($html, '</script>') + 1)
        ->and($html)->not->toContain("\\n\\n' +'");
});
