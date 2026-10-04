<x-website.layout :settings="$settings" :title="$organization->name . ' - الوظائف - ' . $settings->site_name" :meta="$meta">
    @php
        $shareUrl = $organization->shareUrl();
    @endphp

    @include('website.careers.partials.styles')

    <div class="career-page">
        <header class="hero">
            <div class="wrap">
                @if ($organization->logoDisplayUrl())
                    <img src="{{ $organization->logoDisplayUrl() }}" alt="شعار {{ $organization->name }}" class="org-logo lg">
                @endif
                <h1 style="margin-top:12px">{{ $organization->name }}</h1>
                @if (filled($organization->description))
                    <p class="sub">{{ $organization->description }}</p>
                @endif
                @if ($shareUrl)
                    @include('website.careers.partials.share', ['shareUrl' => $shareUrl, 'shareTitle' => $organization->name, 'shareNoun' => 'المؤسسة'])
                @endif
            </div>
        </header>

        <main>
            <div class="wrap org-list">
                @forelse ($openings as $opening)
                    <a class="org-card" href="{{ route('jobs.show', $opening) }}">
                        <h2>{{ $opening->title }}</h2>
                        @if (filled($opening->summary))
                            <p>{{ \Illuminate\Support\Str::limit($opening->summary, 160) }}</p>
                        @endif
                    </a>
                @empty
                    <div class="empty">لا توجد وظائف منشورة لدى هذه المؤسسة حاليًا.</div>
                @endforelse
            </div>
        </main>
    </div>
</x-website.layout>
