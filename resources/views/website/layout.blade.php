<!DOCTYPE html>
<html lang="ar" dir="rtl" class="scroll-smooth">
<head>
    @php
        $configuredLogoPath = $settings->logo_path;
        $configuredLogoPath = is_array($configuredLogoPath) ? collect($configuredLogoPath)->first() : $configuredLogoPath;
        $logoUrl = $configuredLogoPath
            ? (\Illuminate\Support\Str::startsWith($configuredLogoPath, ['http://', 'https://', '/'])
                ? $configuredLogoPath
                : asset($configuredLogoPath === 'logo.svg' ? 'logo.svg' : 'storage/'.$configuredLogoPath))
            : asset('logo.svg');
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? $settings->seo_title ?? $settings->site_name }}</title>
    <meta name="description" content="{{ $description ?? $settings->seo_description ?? '' }}">
    @if (! empty($meta))
        <meta property="og:site_name" content="{{ $settings->site_name }}">
        <meta property="og:locale" content="ar_AR">
        <meta property="og:type" content="{{ $meta['type'] }}">
        <meta property="og:title" content="{{ $meta['title'] }}">
        <meta property="og:description" content="{{ $meta['description'] }}">
        <meta name="twitter:card" content="{{ $meta['image'] ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $meta['title'] }}">
        <meta name="twitter:description" content="{{ $meta['description'] }}">
        @if ($meta['url'])
            <link rel="canonical" href="{{ $meta['url'] }}">
            <meta property="og:url" content="{{ $meta['url'] }}">
        @endif
        @if ($meta['image'])
            <meta property="og:image" content="{{ $meta['image'] }}">
            <meta name="twitter:image" content="{{ $meta['image'] }}">
        @endif
    @endif
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#1C463C">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-alisary-ivory text-alisary-ink antialiased selection:bg-alisary-gold selection:text-alisary-deep">
    <x-local-environment-alert data-website-layout-alert class="fixed inset-x-0 top-0 z-[60]" />

    <header data-site-header
        class="fixed inset-x-0 z-50 px-3 pt-3 text-white sm:px-5 {{ app()->environment('local') ? 'top-24 sm:top-14' : 'top-0' }}">
        <nav class="site-nav-shell mx-auto flex items-center justify-between gap-6 px-4 py-3 lg:px-6">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3 sm:gap-4">
                <img src="{{ $logoUrl }}" alt="{{ $settings->site_name }}" class="h-10 w-auto max-w-24 object-contain sm:h-14 sm:max-w-44">
                <span class="hidden font-display text-xl text-white/90 sm:inline">{{ $settings->site_name }}</span>
            </a>
            <div class="hidden items-center gap-8 text-sm text-white/80 md:flex">
                <a href="{{ route('home') }}#legacy" class="nav-link">الإرث</a>
                <a href="{{ route('home') }}#companies" class="nav-link">مؤسساتنا</a>
                <a href="{{ route('jobs.index') }}" class="nav-link">الوظائف</a>
                <a href="{{ route('tenders.index') }}" class="nav-link">المناقصات</a>
                <a href="{{ route('story') }}" class="nav-link">الحكاية</a>
                @if ($settings->assistant_url)
                    <a href="{{ $settings->assistant_url }}" class="nav-action">
                        المساعد الذكي
                        <x-icons.remix.arrow-left class="size-4" />
                    </a>
                @endif
            </div>
            <button type="button" class="mobile-menu-button md:hidden" data-mobile-nav-toggle aria-controls="mobile-navigation" aria-expanded="false" aria-label="فتح القائمة">
                <span class="hamburger-icon" aria-hidden="true"><span></span></span>
            </button>
        </nav>
    </header>

    <button type="button" class="mobile-nav-backdrop md:hidden" data-mobile-nav-close aria-label="إغلاق القائمة"></button>
    <aside id="mobile-navigation" class="mobile-nav-drawer md:hidden" data-mobile-nav-drawer aria-hidden="true">
        <div class="flex h-full flex-col px-6 py-5">
            <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-5">
                <a href="{{ route('home') }}" class="flex items-center gap-3" data-mobile-nav-close>
                    <img src="{{ $logoUrl }}" alt="{{ $settings->site_name }}" class="h-12 w-auto max-w-36 object-contain">
                    <span class="font-display text-lg text-white/90">{{ $settings->site_name }}</span>
                </a>
                <button type="button" class="mobile-menu-button" data-mobile-nav-close aria-label="إغلاق القائمة">
                    <span class="close-icon" aria-hidden="true"></span>
                </button>
            </div>
            <nav class="grid gap-2 py-8 text-lg font-bold text-white/82">
                <a href="{{ route('home') }}#legacy" class="rounded-md border border-white/10 px-4 py-3 transition hover:border-alisary-gold/50 hover:text-white" data-mobile-nav-close>الإرث</a>
                <a href="{{ route('home') }}#companies" class="rounded-md border border-white/10 px-4 py-3 transition hover:border-alisary-gold/50 hover:text-white" data-mobile-nav-close>مؤسساتنا</a>
                <a href="{{ route('jobs.index') }}" class="rounded-md border border-white/10 px-4 py-3 transition hover:border-alisary-gold/50 hover:text-white" data-mobile-nav-close>الوظائف</a>
                <a href="{{ route('tenders.index') }}" class="rounded-md border border-white/10 px-4 py-3 transition hover:border-alisary-gold/50 hover:text-white" data-mobile-nav-close>المناقصات</a>
                <a href="{{ route('story') }}" class="rounded-md border border-white/10 px-4 py-3 transition hover:border-alisary-gold/50 hover:text-white" data-mobile-nav-close>الحكاية</a>
            </nav>
            @if ($settings->assistant_url)
                <a href="{{ $settings->assistant_url }}" class="nav-action mt-auto justify-center" data-mobile-nav-close>
                    المساعد الذكي
                    <x-icons.remix.arrow-left class="size-4" />
                </a>
            @endif
        </div>
    </aside>

    <main>
        {{ $slot }}
    </main>

    <footer class="section-deep relative overflow-hidden text-white">
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-alisary-gold/70 to-transparent"></div>
        <div class="mx-auto grid max-w-[90rem] gap-10 px-5 py-16 lg:grid-cols-[1.2fr_0.8fr] lg:px-10">
            <div>
                <img src="{{ $logoUrl }}" alt="{{ $settings->site_name }}" class="h-20 w-auto max-w-64 object-contain">
                <div class="mt-8 max-w-2xl font-display text-4xl leading-relaxed text-alisary-gold md:text-5xl">{{ $settings->slogan }}</div>
                <p class="mt-6 max-w-2xl text-lg leading-loose text-white/78">ما توفيقُنا إلّا بالله؛ عليه نتوكّل، وإليه نُنيب.</p>
            </div>
            <div class="feature-panel p-6">
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <div class="text-sm font-bold text-alisary-gold">روابط</div>
                        <div class="mt-4 grid gap-3 text-sm text-white/68">
                            <a href="{{ route('home') }}">الرئيسية</a>
                            <a href="{{ route('jobs.index') }}">الوظائف</a>
                            <a href="{{ route('tenders.index') }}">المناقصات</a>
                            <a href="{{ route('story') }}">الحكاية</a>
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-alisary-gold">تواصل</div>
                        <div class="mt-4 grid gap-3 text-sm text-white/68">
                            @if ($settings->email)
                                <a href="mailto:{{ $settings->email }}" class="inline-flex items-center gap-2">
                                    <x-icons.remix.mail class="size-4 text-alisary-gold" />
                                    {{ $settings->email }}
                                </a>
                            @endif
                            @if ($settings->phone)
                                <a href="tel:{{ $settings->phone }}" class="inline-flex items-center gap-2">
                                    <x-icons.remix.phone class="size-4 text-alisary-gold" />
                                    {{ $settings->phone }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10 pt-6 text-xs text-white/40 lg:col-span-2">© {{ $settings->site_name }} — جميع الحقوق محفوظة</div>
        </div>
    </footer>
</body>
</html>
