<x-website.layout :settings="$settings" :title="$opening->title . ' - ' . $settings->site_name" :meta="$meta">
    @php
        $sections = collect($opening->sections())->values();
        $coreDefaults = ['full_name' => 'الاسم الكامل', 'phone' => 'رقم الهاتف', 'email' => 'البريد الإلكتروني'];
        $placeCore = count($opening->coreFieldEntries()) > 0 && $sections->isNotEmpty();
        $inline = ($placeCore || $opening->identity_in_first_step) && $sections->isNotEmpty();
        $missingCore = $placeCore ? array_values(array_diff(array_keys($coreDefaults), array_keys($opening->coreFieldEntries()))) : array_keys($coreDefaults);
        $stepTitles = $inline ? $sections->pluck('title')->all() : array_merge(['البيانات الأساسية'], $sections->pluck('title')->all());
        $stepsCount = count($stepTitles);
        $gates = collect($opening->eligibility_rules ?? [])
            ->filter(fn ($rule) => filled($rule['field'] ?? null) && filled($rule['operator'] ?? null))
            ->map(fn ($rule) => ['field' => $rule['field'], 'operator' => $rule['operator'], 'value' => $rule['value'] ?? null, 'message' => $rule['message'] ?? null])
            ->values();
        $accepting = $opening->isAcceptingSubmissions();
        $published = $opening->isPublished();
        $blocks = \App\Support\CareerBlocks::active($opening);
        $pillars = \App\Support\CareerBlocks::pillars();
        $chips = collect($opening->chips ?? [])->filter(fn ($chip) => filled($chip['k'] ?? null) && filled($chip['v'] ?? null));
        $shareUrl = $opening->shareUrl();
        $hasBody =count($blocks) > 0 || filled($opening->description);
    @endphp

    @include('website.careers.partials.styles')

    <div class="career-page">
        <header class="hero">
            <div class="wrap">
                @if (filled($opening->notice))
                    <div class="draft" role="note">{{ $opening->notice }}</div>
                @endif
                @if (! $published)
                    <div class="draft" role="status">معاينة للإدارة فقط: هذه الوظيفة غير منشورة ولا تظهر للزوار.</div>
                @elseif (! $accepting)
                    @auth
                        <div class="draft" role="status">معاينة للإدارة: ما زال إعداد الوظيفة ناقصًا (الأقسام أو حقول الاستمارة)، فلا يُفتح التقديم للزوار.</div>
                    @endauth
                @endif
                @if ($opening->organization)
                    <a class="org-link" href="{{ route('careers.organizations.show', $opening->organization) }}">
                        @if ($opening->organization->logoDisplayUrl())
                            <img src="{{ $opening->organization->logoDisplayUrl() }}" alt="شعار {{ $opening->organization->name }}" class="org-logo">
                        @endif
                        <span>{{ $opening->organization->name }}</span>
                    </a>
                @endif
                <span class="eyebrow">{{ filled($opening->unit) ? $opening->unit : (collect([$opening->organization ? null : $opening->organizationName(), $opening->category?->label()])->filter()->implode(' · ') ?: ($opening->organization ? 'وظيفة' : 'مجموعة العيسري')) }}</span>
                <h1>{{ $opening->title }}</h1>
                @if (filled($opening->subtitle))
                    <p class="sub">{{ $opening->subtitle }}</p>
                @endif
                <ul class="chips">
                    @if ($opening->location)
                        <li><b>الموقع</b>{{ $opening->location->label() }}</li>
                    @endif
                    @foreach ($chips as $chip)
                        <li><b>{{ $chip['k'] }}</b>{{ $chip['v'] }}</li>
                    @endforeach
                    @if ($opening->expires_at)
                        <li><b>آخر موعد</b>{{ \App\Support\NumberLocalizer::eastern($opening->expires_at->format('Y-m-d')) }}</li>
                    @endif
                </ul>
                @if ($accepting)
                    <div class="hero-cta"><a class="btn btn-gold" href="#apply-form">قدّم الآن</a>@if ($hasBody)<a class="btn btn-ghost" href="#details">التفاصيل</a>@endif</div>
                @endif
                @if ($shareUrl)
                    @include('website.careers.partials.share', ['shareUrl' => $shareUrl])
                @endif
            </div>
        </header>

        <main>
            @if ($hasBody)
                <div class="wrap layout {{ $accepting ? '' : 'single' }}" id="details">
                    <div class="content">
                        @if (filled($opening->summary))
                            <section class="block"><p>{{ $opening->summary }}</p></section>
                        @endif

                        @foreach ($blocks as $block)
                            @php($data = $block['data'])
                            <section class="block {{ $block['key'] === 'pay' ? 'pay' : '' }}" aria-labelledby="blk-{{ $block['key'] }}">
                                <h2 id="blk-{{ $block['key'] }}">{{ $block['title'] }}</h2>
                                @switch($block['kind'])
                                    @case('text')
                                        <p>{{ $data['text'] }}</p>
                                        @break
                                    @case('lines')
                                        @if ($block['key'] === 'growth')
                                            <ol class="path">@foreach (array_filter($data['items']) as $item)<li><span>{{ $item }}</span></li>@endforeach</ol>
                                        @elseif ($block['key'] === 'kpis')
                                            <ul class="kpis">@foreach (array_filter($data['items']) as $item)<li>{{ $item }}</li>@endforeach</ul>
                                        @else
                                            <ul class="ticks">@foreach (array_filter($data['items']) as $item)<li>{{ $item }}</li>@endforeach</ul>
                                        @endif
                                        @break
                                    @case('who')
                                        <div class="{{ count(array_filter($data['prefer'] ?? [])) ? 'two' : '' }}">
                                            @if (count(array_filter($data['must'] ?? [])))
                                                <div>@if (count(array_filter($data['prefer'] ?? [])))<h3>لا بدّ منه</h3>@endif<ul class="dots">@foreach (array_filter($data['must']) as $item)<li>{{ $item }}</li>@endforeach</ul></div>
                                            @endif
                                            @if (count(array_filter($data['prefer'] ?? [])))
                                                <div><h3>يُقدَّم من عنده</h3><ul class="dots">@foreach (array_filter($data['prefer']) as $item)<li>{{ $item }}</li>@endforeach</ul></div>
                                            @endif
                                        </div>
                                        @break
                                    @case('pairs')
                                        @if ($block['key'] === 'process')
                                            <ol class="steps">@foreach ($data['items'] as $item)@if (filled($item['k'] ?? null))<li><b>{{ $item['k'] }}</b>@if (filled($item['v'] ?? null))<span>{{ $item['v'] }}</span>@endif</li>@endif @endforeach</ol>
                                        @else
                                            <dl class="rows">@foreach ($data['items'] as $item)@if (filled($item['k'] ?? null))<div><dt>{{ $item['k'] }}</dt><dd>{{ $item['v'] ?? '' }}</dd></div>@endif @endforeach</dl>
                                        @endif
                                        @break
                                    @case('rows')
                                        <dl class="rows">@foreach ($data['rows'] as $row)@if (filled($row['name'] ?? null))<div><dt>{{ $row['name'] }}</dt><dd>@if (filled($row['seats'] ?? null)){{ \App\Support\NumberLocalizer::eastern($row['seats']) }} مقعد@endif</dd></div>@endif @endforeach</dl>
                                        @break
                                    @case('values')
                                        <div class="pillars">
                                            @foreach ($pillars as $pillar)
                                                @php($emphasised = in_array($pillar['id'], $data['emph'] ?? [], true))
                                                <div class="pillar {{ $emphasised ? 'on' : '' }}" style="--c: {{ $pillar['color'] }}"><b>{{ $pillar['name'] }}</b>@if ($emphasised)<em>محور أساسي لهذه الوظيفة</em>@endif<small>{{ implode(' · ', $pillar['sub']) }}</small></div>
                                            @endforeach
                                        </div>
                                        @if (filled($data['note'] ?? null))<p class="pillars-note">{{ $data['note'] }}</p>@endif
                                        @break
                                @endswitch
                            </section>
                        @endforeach

                        @if (filled($opening->description))
                            <section class="block"><div class="rich-content">{!! str($opening->description)->sanitizeHtml() !!}</div></section>
                        @endif
                    </div>

                    @if ($accepting)
                        <aside class="aside" aria-label="ملخص الوظيفة">
                            <div class="card">
                                <h3>ملخّص سريع</h3>
                                <dl class="rows">
                                    @if ($opening->organizationName())<div><dt>الجهة</dt><dd>@if ($opening->organization)<a href="{{ route('careers.organizations.show', $opening->organization) }}">{{ $opening->organization->name }}</a>@else{{ $opening->organizationName() }}@endif</dd></div>@endif
                                    @if ($opening->location)<div><dt>الموقع</dt><dd>{{ $opening->location->label() }}</dd></div>@endif
                                    @foreach ($chips as $chip)<div><dt>{{ $chip['k'] }}</dt><dd>{{ $chip['v'] }}</dd></div>@endforeach
                                    @if ($opening->expires_at)<div><dt>آخر موعد</dt><dd>{{ \App\Support\NumberLocalizer::eastern($opening->expires_at->format('Y-m-d')) }}</dd></div>@endif
                                </dl>
                                <a class="btn btn-gold" href="#apply-form">قدّم الآن</a>
                            </div>
                        </aside>
                    @endif
                </div>
            @endif

            <section id="apply-form" class="wrap apply" tabindex="-1">
                <div class="form-card">
                    <h2>{{ $opening->form_title ?: 'نموذج التقديم' }}</h2>
                    @if (filled($opening->form_lead) && $accepting)
                        <p class="lead">{{ $opening->form_lead }}</p>
                    @endif

                    @if (session('application_success'))
                        <div class="success-card" role="status">
                            <div class="tick" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="#1C463C" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                            </div>
                            <h3>وصلَنا طلبُك</h3>
                            <p>{{ $opening->success_message ?: 'تم استلام طلبكم بنجاح، وسيتواصل معكم الفريق عند الحاجة.' }}</p>
                            @if (session('application_files_count'))
                                <p class="attachments-ok">تم إرفاق ملفاتك بنجاح ({{ session('application_files_count') }}).</p>
                            @endif
                            @if (session('application_reference'))
                                <p class="reference">رقم طلبك: <bdi dir="ltr">{{ session('application_reference') }}</bdi></p>
                            @endif
                        </div>
                    @elseif ($accepting)
                        <noscript><style>.career-page .application-step[hidden]{display:block!important}.career-page [data-career-submit][hidden]{display:inline-flex!important}</style></noscript>
                        @if ($stepsCount > 1)
                            <ol class="stepper" aria-label="مراحل الاستمارة">
                                @foreach ($stepTitles as $i => $stepTitle)
                                    <li data-stepper-item class="{{ $i === 0 ? 'cur' : '' }}"><span>{{ $stepTitle }}</span></li>
                                @endforeach
                            </ol>
                        @endif

                        <form method="POST" action="{{ route('careers.apply', $opening) }}" enctype="multipart/form-data" class="job-application-form" data-career-form data-gates="{{ $gates->toJson(JSON_UNESCAPED_UNICODE) }}" novalidate>
                            @csrf
                            <input type="hidden" name="form_rendered_at" value="{{ time() }}">
                            <div class="hidden" aria-hidden="true">
                                <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                            </div>

                            @unless ($inline)
                                <section data-career-step class="application-step" aria-label="البيانات الأساسية">
                                    <div class="application-step-head">
                                        <span class="application-step-kicker">{{ \App\Support\NumberLocalizer::eastern(1) }}</span>
                                        <div><h3>البيانات الأساسية</h3><p>لنتمكّن من التواصل معك.</p></div>
                                    </div>
                                    <div class="application-field-grid">
                                        @foreach ($coreDefaults as $coreKey => $coreDefault)
                                            @include('website.careers.partials.field', ['field' => ['key' => $coreKey, 'type' => 'core', 'label' => $opening->coreLabel($coreKey, $coreDefault)]])
                                        @endforeach
                                    </div>
                                </section>
                            @endunless

                            @foreach ($sections as $index => $section)
                                <section data-career-step class="application-step" @if ($index > 0 || ! $inline) hidden @endif aria-label="{{ $section['title'] }}">
                                    <div class="application-step-head">
                                        <span class="application-step-kicker">{{ \App\Support\NumberLocalizer::eastern($inline ? $index + 1 : $index + 2) }}</span>
                                        <div>
                                            <h3>{{ $section['title'] }}</h3>
                                            @if (filled($section['description'] ?? null))<p>{{ $section['description'] }}</p>@endif
                                        </div>
                                    </div>
                                    <div class="application-field-grid">
                                        @if ($inline && $index === 0)
                                            @foreach ($missingCore as $coreKey)
                                                @include('website.careers.partials.field', ['field' => ['key' => $coreKey, 'type' => 'core', 'label' => $opening->coreLabel($coreKey, $coreDefaults[$coreKey])]])
                                            @endforeach
                                        @endif
                                        @foreach ($section['fields'] ?? [] as $field)
                                            @include('website.careers.partials.field', ['field' => $field])
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach

                            <div class="blocked" data-career-blocked hidden aria-live="polite"></div>

                            <div class="application-actions">
                                <button type="button" data-career-prev class="application-nav-button" hidden>السابق</button>
                                <button type="button" data-career-next class="application-submit-button">التالي</button>
                                <button type="submit" data-career-submit class="application-submit-button" hidden>إرسال الطلب</button>
                            </div>
                        </form>

                        @include('website.careers.partials.form-script')
                    @elseif ($published)
                        <p class="notice" role="status">سيُفتح باب التقديم على هذه الوظيفة قريبًا، وسنعلن التفاصيل والشروط عند اعتمادها.</p>
                    @else
                        <p class="notice">لا يمكن التقديم على هذه الوظيفة حاليًا.</p>
                    @endif
                </div>
            </section>
        </main>

        @if ($accepting)
            <div class="ctabar"><span>{{ $opening->title }}</span><a class="btn btn-gold" href="#apply-form">قدّم الآن</a></div>
        @endif
    </div>
</x-website.layout>
