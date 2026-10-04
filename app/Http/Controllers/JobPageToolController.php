<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Serves the job-page builder and previews of the standalone pages to authorised
 * admin users only. Guests are sent to the admin login; users without the
 * `useJobPageBuilder` ability get 403.
 */
class JobPageToolController extends Controller
{
    public function builder(): Response|RedirectResponse
    {
        return $this->guarded(fn (): Response => $this->html(resource_path('job-pages/out/builder.html')));
    }

    public function preview(string $page): Response|RedirectResponse
    {
        return $this->guarded(function () use ($page): Response {
            abort_unless(in_array($page, self::previewablePages(), true), 404);

            return $this->html(public_path("job-pages/{$page}.html"));
        });
    }

    /**
     * Standalone pages that exist in public/job-pages (file names without extension).
     *
     * @return array<int, string>
     */
    public static function previewablePages(): array
    {
        return collect(glob(public_path('job-pages/*.html')) ?: [])
            ->map(fn (string $path): string => Str::beforeLast(basename($path), '.html'))
            ->filter(fn (string $name): bool => preg_match('/^[A-Za-z0-9_-]+$/', $name) === 1)
            ->values()
            ->all();
    }

    private function guarded(callable $serve): Response|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->guest(route('filament.admin.auth.login'));
        }

        abort_unless(Gate::allows('useJobPageBuilder'), 403);

        return $serve();
    }

    private function html(string $path): Response
    {
        abort_unless(is_file($path), 404, 'الملف غير موجود. شغّل node build.cjs داخل resources/job-pages.');

        return response((string) file_get_contents($path), 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store, private',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
