<x-website.layout :settings="$settings" :title="$label . ' - ' . $settings->site_name">
    @php
        $isJob = $type === 'jobs';
    @endphp

    @if ($isJob)
        <section class="relative overflow-hidden bg-alisary-ivory pt-24 pb-16">
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_85%_0%,rgba(200,162,75,.14),transparent_34%),radial-gradient(circle_at_10%_8%,rgba(31,77,69,.10),transparent_32%)]">
            </div>
            <div class="relative mx-auto max-w-5xl px-5 text-center lg:px-10">
                <span class="font-display text-xs font-bold tracking-[0.22em] text-alisary-gold md:text-sm">
                    مجموعةٌ قابضةٌ عُمانية · تخدم الطفل ومن يخدم الطفل
                </span>
                <h1 class="mt-5 font-display text-4xl leading-tight text-alisary-deep md:text-6xl">
                    اعرف تمامًا ما تتقدّم له
                    <span class="block text-alisary-gold">وانمُ معنا في الدنيا والآخرة</span>
                </h1>
                <p class="mx-auto mt-6 max-w-3xl text-lg leading-loose text-alisary-soft">
                    مهامُّ كلِّ وظيفةٍ وشروطُها ومؤشّرُ نجاحها معلنةٌ من أوّل نظرة، وراتبُك المتوقَّع نسألك عنه بوضوحٍ
                    واحترام. ابحث عن دورك في مؤسستك وفرعك الأقرب، وافتح صفحة الوظيفة لتقدّم بنموذجها الخاص، ونعِدُك بالردّ.
                </p>

                <div class="mt-6 flex flex-wrap justify-center gap-3 text-sm text-alisary-soft">
                    <span class="rounded-full border border-alisary-green/15 bg-white/70 px-4 py-2">
                        <b class="text-alisary-deep">{{ $listings->count() }}</b> وظيفة معلنة
                    </span>
                    <span class="rounded-full border border-alisary-green/15 bg-white/70 px-4 py-2">
                        <b class="text-alisary-deep">{{ $organizations->count() }}</b> مؤسسات
                    </span>
                    <span class="rounded-full border border-alisary-green/15 bg-white/70 px-4 py-2">
                        <b class="text-alisary-deep">{{ $listings->pluck('category')->filter()->unique()->count() }}</b> مسارات
                    </span>
                </div>

                <div class="mt-8 flex flex-wrap justify-center gap-4">
                    <a href="#vacancies"
                        class="inline-flex items-center gap-2 rounded-full bg-alisary-green px-8 py-4 font-bold text-white transition hover:bg-alisary-deep">
                        تصفّح الوظائف الشاغرة
                    </a>
                </div>

                <div
                    class="mx-auto mt-8 inline-flex max-w-4xl items-center gap-3 rounded-full border border-alisary-green/15 bg-white/60 px-5 py-3 font-display text-sm leading-relaxed text-alisary-green md:text-base">
                    <span class="size-2.5 flex-none rounded-full bg-alisary-gold"></span>
                    <span>﴿رَبَّنَا آتِنَا فِي الدُّنْيَا حَسَنَةً وَفِي الْآخِرَةِ حَسَنَةً﴾ · عملٌ يجمع لك الأثرَ
                        الطيّب والنماءَ العادل</span>
                </div>
            </div>
        </section>
    @else
        <section class="page-hero">
            <div class="page-hero-inner">
                <div class="page-kicker">
                    <x-icons.remix.file-list class="size-5" />
                    {{ $label }}
                </div>
                <h1 class="mt-5 font-display text-5xl leading-tight md:text-7xl">{{ $label }}</h1>
                <p class="mt-5 max-w-2xl text-lg leading-loose text-white/75">{{ $description }}</p>
            </div>
        </section>
    @endif

    <section class="section" id="vacancies">
        <div class="mx-auto max-w-7xl px-5 lg:px-10">
            @if ($isJob)
                <div class="mx-auto mb-10 max-w-3xl text-center">
                    <span class="font-display text-sm font-bold tracking-[0.22em] text-alisary-gold">
                        دليل الوظائف الشاغرة
                    </span>
                    <h2 class="mt-3 font-display text-3xl leading-tight text-alisary-deep md:text-4xl">
                        جِد دورَك: بالوظيفة، أو بالمؤسسة، أو بأقرب فرعٍ إليك
                    </h2>
                    <p class="mt-4 leading-loose text-alisary-soft">
                        ابحث باسم الوظيفة، أو صفِّ بالمؤسسة والمسار والفرع، ثم افتح أيّ وظيفةٍ لتقرأ مهامّها وشروطها
                        ونمط تعاقدها ومؤشّرَ نجاحها قبل أن تتقدّم.
                    </p>
                </div>

                <div class="mb-5 grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px]">
                    <label class="relative block">
                        <span class="sr-only">بحث في الوظائف</span>
                        <x-icons.remix.briefcase
                            class="pointer-events-none absolute right-4 top-1/2 size-5 -translate-y-1/2 text-alisary-soft" />
                        <input id="jobSearch" type="search"
                            class="w-full rounded-xl border border-alisary-green/15 bg-white py-3 pl-4 pr-12 text-alisary-deep shadow-sm outline-none transition placeholder:text-alisary-soft/75 focus:border-alisary-gold focus:ring-4 focus:ring-alisary-gold/15"
                            placeholder="ابحث بالعنوان، الرمز، المؤسسة، المسار، الموقع..." autocomplete="off"
                            oninput="applyJobFilters()">
                    </label>

                    {{-- <label class="block">
                        <span class="sr-only">مستوى الوظيفة</span>
                        <select id="jobLevelFilter" onchange="applyJobFilters()"
                            class="w-full rounded-xl border border-alisary-green/15 bg-white px-4 py-3 text-alisary-deep shadow-sm outline-none transition focus:border-alisary-gold focus:ring-4 focus:ring-alisary-gold/15">
                            <option value="all">كل المستويات</option>
                            @foreach (\App\Enums\JobLevel::cases() as $level)
                                <option value="{{ $level->value }}">{{ $level->label() }}</option>
                            @endforeach
                        </select>
                    </label> --}}
                </div>

                {{-- Organization filter chips --}}
                <div class="mb-4 flex flex-wrap justify-center gap-3">
                    <button type="button" data-filter="all"
                        class="filter-btn flex cursor-pointer items-center gap-2 rounded-full border border-alisary-green/20 bg-white px-5 py-2.5 font-bold text-alisary-deep ring-2 ring-alisary-gold transition hover:border-alisary-gold"
                        onclick="filterJobs('all')">
                        <span class="size-2.5 rounded-full bg-alisary-gold"></span>
                        الكل
                    </button>
                    @foreach ($organizations as $organizationName => $organizationKey)
                        <button type="button" data-filter="{{ $organizationKey }}"
                            class="filter-btn flex cursor-pointer items-center gap-2 rounded-full border border-alisary-green/20 bg-white px-5 py-2.5 font-bold text-alisary-deep transition hover:border-alisary-gold"
                            onclick="filterJobs('{{ $organizationKey }}')">
                            <span class="size-2.5 rounded-full bg-alisary-green"></span>
                            {{ $organizationName }}
                        </button>
                    @endforeach
                </div>

                <div class="mb-3 flex flex-wrap justify-center gap-2">
                    <button type="button" data-family-filter="all"
                        class="family-filter-btn rounded-full border border-alisary-green/15 bg-alisary-deep px-4 py-2 text-sm font-bold text-white transition hover:border-alisary-gold"
                        onclick="filterJobFamily('all')">
                        كل المسارات
                    </button>
                    @foreach ($categories as $category)
                        <button type="button" data-family-filter="{{ $category->value }}"
                            class="family-filter-btn rounded-full border border-alisary-green/15 bg-white px-4 py-2 text-sm font-bold text-alisary-deep transition hover:border-alisary-gold"
                            onclick="filterJobFamily('{{ $category->value }}')">
                            {{ $category->label() }}
                        </button>
                    @endforeach
                </div>

                @if ($schoolBranchOptions->isNotEmpty() && $schoolOrganizationKey !== null)
                    <div id="school-branch-filter" data-school-branch-filter class="mb-3 hidden justify-center">
                        <label class="block w-full max-w-xs">
                            <span class="sr-only">فرع المدرسة</span>
                            <select id="schoolBranchSelect" onchange="filterJobBranch(this.value)"
                                class="w-full rounded-xl border border-alisary-green/15 bg-white px-4 py-3 text-sm font-bold text-alisary-deep shadow-sm outline-none transition focus:border-alisary-gold focus:ring-4 focus:ring-alisary-gold/15">
                                <option value="all" data-branch-filter="all">كل الفروع</option>
                                @foreach ($schoolBranchOptions as $branch)
                                    <option value="branch-{{ $branch['value'] }}"
                                        data-branch-filter="branch-{{ $branch['value'] }}">
                                        {{ $branch['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                @endif

                <div id="jobs-count-line" class="mb-6 text-center text-sm text-alisary-soft"></div>
            @endif

            @if ($isJob)
                <div class="grid gap-6 md:grid-cols-2">
                    @forelse ($listings as $opening)
                        @php
                            $organizationName = $opening->organizationName() ?? 'مجموعة العيسري';
                            $organizationKey = $organizations->get($organizationName);
                            $openingUrl = route('jobs.show', $opening);
                            $openingSummary = filled($opening->summary) ? $opening->summary : $opening->subtitle;
                            $searchText = collect([
                                $opening->title,
                                $opening->subtitle,
                                $opening->summary,
                                $organizationName,
                                $opening->category?->label(),
                                $opening->location?->label(),
                            ])
                                ->filter()
                                ->implode(' ');
                        @endphp

                        <div class="group flex flex-col justify-between overflow-hidden rounded-2xl border border-alisary-green/10 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-alisary-green/5"
                            data-job-id="{{ $opening->id }}" data-company="{{ $organizationKey }}"
                            data-branch="{{ $opening->location?->value }}"
                            data-family="{{ $opening->category?->value }}" data-search="{{ e($searchText) }}">
                            <div class="flex h-2 w-full bg-alisary-green"></div>
                            <div class="flex-1 p-6">
                                <div class="mb-4 flex flex-wrap gap-2">
                                    <span
                                        class="rounded-full bg-alisary-ivory px-3 py-1 text-xs font-bold text-alisary-deep">{{ $organizationName }}</span>
                                    @if ($opening->category)
                                        <span
                                            class="rounded-full bg-alisary-green/10 px-3 py-1 text-xs font-bold text-alisary-green">{{ $opening->category->label() }}</span>
                                    @endif
                                </div>
                                <h2 class="font-display text-2xl leading-tight text-alisary-deep"
                                    id="job-title-{{ $opening->id }}">
                                    <a href="{{ $openingUrl }}">{{ $opening->title }}</a>
                                </h2>
                                @if (filled($openingSummary))
                                    <p class="mt-3 text-sm leading-loose text-alisary-soft">
                                        {{ \Illuminate\Support\Str::limit(strip_tags((string) $openingSummary), 180) }}
                                    </p>
                                @endif

                                <div class="mt-4 flex flex-wrap gap-4 text-xs text-alisary-soft">
                                    @if ($opening->location)
                                        <span class="flex items-center gap-1.5"><x-icons.remix.map-pin class="size-3.5" />
                                            {{ $opening->location->label() }}</span>
                                    @endif
                                    @if ($opening->expires_at)
                                        <span class="flex items-center gap-1.5"><x-icons.remix.calendar
                                                class="size-3.5 text-alisary-gold" /> ينتهي
                                            {{ \App\Support\NumberLocalizer::eastern($opening->expires_at->format('Y-m-d')) }}</span>
                                    @endif
                                </div>
                            </div>

                            <div id="job-share-menu-{{ $opening->id }}" data-job-share-menu="job-{{ $opening->id }}"
                                class="hidden flex-wrap gap-2 border-t border-alisary-green/10 px-4 py-3">
                                @foreach (['whatsapp' => 'واتساب', 'facebook' => 'فيسبوك', 'x' => 'X', 'linkedin' => 'لينكدإن'] as $platform => $platformLabel)
                                    <button type="button" data-share-platform="{{ $platform }}"
                                        onclick="shareJobTo('{{ $platform }}', {{ $opening->id }}, @js($openingUrl))"
                                        class="min-w-24 flex-1 rounded-lg border border-alisary-green/15 bg-alisary-ivory px-3 py-2 text-xs font-bold text-alisary-deep transition hover:border-alisary-gold hover:bg-white">
                                        {{ $platformLabel }}
                                    </button>
                                @endforeach
                            </div>

                            <div class="flex items-center border-t border-alisary-green/10">
                                <a href="{{ $openingUrl }}"
                                    class="flex flex-1 cursor-pointer items-center justify-center gap-2 px-6 py-4 font-bold text-alisary-deep transition hover:bg-alisary-ivory">
                                    <x-icons.remix.file-list class="size-4" />
                                    التفاصيل
                                </a>
                                <div class="h-8 w-px bg-alisary-green/10"></div>
                                <button type="button" data-job-share="job-{{ $opening->id }}"
                                    aria-controls="job-share-menu-{{ $opening->id }}" aria-expanded="false"
                                    onclick="toggleJobShareMenu({{ $opening->id }})"
                                    class="flex flex-1 cursor-pointer items-center justify-center gap-2 px-4 py-4 font-bold text-alisary-deep transition hover:bg-alisary-ivory">
                                    <x-icons.remix.share class="size-4" />
                                    مشاركة
                                </button>
                                <div class="h-8 w-px bg-alisary-green/10"></div>
                                <a href="{{ $openingUrl }}#apply-form"
                                    class="flex flex-1 cursor-pointer items-center justify-center gap-2 px-6 py-4 font-bold text-alisary-gold transition hover:bg-alisary-ivory hover:text-[#C5A359]">
                                    قدّم الآن
                                    <x-icons.remix.arrow-left class="size-4 rtl:rotate-180" />
                                </a>
                            </div>
                        </div>
                    @empty
                        <div
                            class="rounded-lg border border-alisary-green/10 bg-white/80 p-10 text-center text-alisary-soft shadow-xl shadow-alisary-green/5 md:col-span-2">
                            لا توجد وظائف منشورة حاليًا.</div>
                    @endforelse
                </div>
            @else
                <div class="grid gap-6 md:grid-cols-2">
                    @forelse ($listings as $listing)
                        @php
                            $organization = $listing->contractor;
                            $deadline = $listing->last_day_to_apply;
                        @endphp

                        {{-- Standard listing card (Tenders) --}}
                        <a href="{{ route('tenders.show', $listing) }}"
                            class="lux-card listing-card group block cursor-pointer">
                            <div class="listing-meta">
                                <span class="inline-flex items-center gap-2">
                                    <x-icons.remix.building class="size-4" />
                                    {{ $organization?->name }}
                                </span>
                                <span class="inline-flex items-center gap-2 text-alisary-soft">
                                    <x-icons.remix.map-pin class="size-4" />
                                    {{ $listing->location?->label() }}
                                </span>
                            </div>
                            <h2 class="mt-5 text-2xl font-bold leading-tight text-alisary-green">{{ $listing->title }}
                            </h2>
                            <p class="mt-4 leading-loose text-alisary-soft">{{ $listing->excerpt }}</p>
                            <div
                                class="mt-6 flex items-center justify-between gap-4 border-t border-alisary-green/10 pt-5 text-sm text-alisary-soft">
                                @if ($deadline)
                                    <span class="inline-flex items-center gap-2">
                                        <x-icons.remix.calendar class="size-4 text-alisary-gold" />
                                        آخر يوم {{ \App\Support\NumberLocalizer::eastern($deadline->format('Y-m-d')) }}
                                    </span>
                                @endif
                                <span class="inline-flex items-center gap-2 font-bold text-alisary-gold">
                                    عرض
                                    <x-icons.remix.arrow-left class="size-4 transition group-hover:-translate-x-1" />
                                </span>
                            </div>
                        </a>
                    @empty
                        <div
                            class="rounded-lg border border-alisary-green/10 bg-white/80 p-10 text-center text-alisary-soft shadow-xl shadow-alisary-green/5 md:col-span-2">
                            لا توجد إعلانات منشورة حاليًا.</div>
                    @endforelse
                </div>
            @endif

            @if ($isJob && $listings->isNotEmpty())
                <div id="jobs-empty-state"
                    class="hidden rounded-lg border border-alisary-green/10 bg-white/80 p-10 text-center text-alisary-soft shadow-xl shadow-alisary-green/5 md:col-span-2">
                    عذراً، لا توجد وظائف متاحة في هذه المؤسسة حالياً.
                </div>
            @endif

            {{-- @if ($listings->hasPages())
                <div class="mt-10">{{ $listings->fragment('vacancies')->links() }}</div>
            @endif --}}
        </div>
    </section>

    @if ($isJob)
        {{-- How we hire --}}
        <section class="section-deep text-white" id="how">
            <div class="mx-auto max-w-7xl px-5 py-20 lg:px-10 lg:py-28">
                <div class="mx-auto mb-14 max-w-3xl text-center">
                    <span class="font-display text-sm font-bold tracking-[0.22em] text-alisary-gold">شفافيةٌ من أوّل
                        خطوة</span>
                    <h2 class="mt-2 font-display text-4xl text-white">كيف نوظّف</h2>
                    <p class="mt-3 leading-loose text-white/80">مسارٌ موحّدٌ لكل مؤسسات المجموعة. التقديم عبر هذا
                        الموقع فقط، ونتواصل مع كل متقدّم.</p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-xl border border-white/10 bg-white/5 p-6">
                        <div class="mb-4 font-display text-4xl font-bold text-alisary-gold">١</div>
                        <h4 class="mb-2 font-display text-xl font-bold">التقديم</h4>
                        <p class="text-sm leading-relaxed text-white/70">تفتح صفحة الوظيفة التي تناسبك، وتملأ نموذجها
                            الخاص بها.</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-6">
                        <div class="mb-4 font-display text-4xl font-bold text-alisary-gold">٢</div>
                        <h4 class="mb-2 font-display text-xl font-bold">الفرز</h4>
                        <p class="text-sm leading-relaxed text-white/70">نقرأ طلبك بعناية بمساعدة أدوات الذكاء
                            الاصطناعي، ونقيس الاصطفاف القيمي والقابلية للتعلّم.</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-6">
                        <div class="mb-4 font-display text-4xl font-bold text-alisary-gold">٣</div>
                        <h4 class="mb-2 font-display text-xl font-bold">مقابلةٌ ومحاكاة</h4>
                        <p class="text-sm leading-relaxed text-white/70">مقابلةٌ سلوكيةٌ مهيكلة، وحالةٌ عمليةٌ تكشف
                            طريقة بنائك من الصفر.</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-6">
                        <div class="mb-4 font-display text-4xl font-bold text-alisary-gold">٤</div>
                        <h4 class="mb-2 font-display text-xl font-bold">العرض</h4>
                        <p class="text-sm leading-relaxed text-white/70">عرضُ عملٍ واضحٌ بتفاصيل العقد والمزايا
                            والمؤشّرات المتّفق عليها.</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-6">
                        <div class="mb-4 font-display text-4xl font-bold text-alisary-gold">٥</div>
                        <h4 class="mb-2 font-display text-xl font-bold">الانضمام والتأهيل</h4>
                        <p class="text-sm leading-relaxed text-white/70">برنامجُ تأهيلٍ مكثّفٌ عند الالتحاق، ومتابعةٌ
                            في فترة التجربة.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section bg-alisary-ivory" id="values">
            <div class="mx-auto max-w-7xl px-5 lg:px-10">
                <div class="mx-auto mb-10 max-w-3xl text-center">
                    <span class="font-display text-sm font-bold tracking-[0.22em] text-alisary-gold">
                        ثلاثُ قواعد تحكم كل وظيفةٍ عندنا
                    </span>
                    <h2 class="mt-3 font-display text-3xl leading-tight text-alisary-deep md:text-4xl">
                        لماذا نحن؟ لتعرف بيئتك قبل يومك الأوّل
                    </h2>
                </div>

                <div class="grid gap-5 md:grid-cols-3">
                    <article
                        class="relative overflow-hidden rounded-2xl border border-alisary-green/10 bg-white p-6 shadow-xl shadow-alisary-green/5">
                        <div class="absolute inset-y-0 right-0 w-1 bg-alisary-gold"></div>
                        <div class="font-display text-3xl text-alisary-gold">◷</div>
                        <h3 class="mt-4 font-display text-2xl text-alisary-deep">قيمةٌ تُقاس</h3>
                        <p class="mt-3 leading-loose text-alisary-soft">
                            نُحاسبك على ما تُنجزه من أثرٍ ونتائج، لا على ساعات حضورك. من يُعطي أكثر بموارد أقل، يتقدّم.
                        </p>
                    </article>
                    <article
                        class="relative overflow-hidden rounded-2xl border border-alisary-green/10 bg-white p-6 shadow-xl shadow-alisary-green/5">
                        <div class="absolute inset-y-0 right-0 w-1 bg-alisary-gold"></div>
                        <div class="font-display text-3xl text-alisary-gold">⚙</div>
                        <h3 class="mt-4 font-display text-2xl text-alisary-deep">أدواتُ العصر</h3>
                        <p class="mt-3 leading-loose text-alisary-soft">
                            ما تُنجزه التقنية لا يُسند لبشر. نُقدّر من يُتقن الأتمتة والذكاء الاصطناعي ويتعلّم بسرعة.
                        </p>
                    </article>
                    <article
                        class="relative overflow-hidden rounded-2xl border border-alisary-green/10 bg-white p-6 shadow-xl shadow-alisary-green/5">
                        <div class="absolute inset-y-0 right-0 w-1 bg-alisary-gold"></div>
                        <div class="font-display text-3xl text-alisary-gold">✦</div>
                        <h3 class="mt-4 font-display text-2xl text-alisary-deep">أثرٌ ونماء</h3>
                        <p class="mt-3 leading-loose text-alisary-soft">
                            رسالتُنا «نُعِدّهم لحياةٍ طيّبة»، ونؤمن أنّ صاحب الإتقان يستحقّ النماء: أجرٌ يُناقَش بوضوح،
                            وتأهيلٌ مدفوع، وخطةُ تطويرٍ تُنمّيك لا تُقصيك. والاصطفاف مع خماسية السكينة بوّابة عبور؛ لأنه
                            أساسُ بيئةٍ يطيب فيها عملُك.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section" id="grow">
            <div class="mx-auto max-w-7xl px-5 lg:px-10">
                <div class="mx-auto mb-10 max-w-3xl text-center">
                    <span class="font-display text-sm font-bold tracking-[0.22em] text-alisary-gold">انمُ معنا</span>
                    <h2 class="mt-3 font-display text-3xl leading-tight text-alisary-deep md:text-4xl">
                        أدلةٌ تختصر وقتك وترفع حظوظك
                    </h2>
                    <p class="mt-4 leading-loose text-alisary-soft">
                        نُصدر إرشاداتٍ عمليةً للمتقدّمين دوريًّا. ابدأ بهذه الثلاثة قبل التقديم.
                    </p>
                </div>

                <div class="mx-auto grid max-w-6xl gap-5 lg:grid-cols-3">
                    <details
                        class="rounded-2xl border border-alisary-green/10 bg-white p-6 shadow-xl shadow-alisary-green/5">
                        <summary class="cursor-pointer font-display text-xl text-alisary-deep">
                            كيف تكتب طلبًا قويًّا في ربع ساعة؟
                        </summary>
                        <ul class="mt-5 space-y-3 text-sm leading-loose text-alisary-soft">
                            <li>ابدأ بالوظيفة لا بالاستمارة: افتح بطاقة الوظيفة واقرأ «مؤشّر النجاح»؛ فهو ما سنقيسك
                                عليه.</li>
                            <li>في أسئلة الإنجاز، اذكر أرقامًا: «كانت المهمة تستغرق ساعتين فصارت عشرين دقيقةً بأداة
                                كذا».</li>
                            <li>رتّب أولوياتك الثلاث بصدق؛ الأولويةُ الأولى تُقرأ أوّلًا وبعنايةٍ أكبر.</li>
                            <li>اكتب راتبك المتوقّع بوضوح؛ فالوضوح عندنا يزيد فرصتك ولا يُنقصها.</li>
                        </ul>
                    </details>
                    <details
                        class="rounded-2xl border border-alisary-green/10 bg-white p-6 shadow-xl shadow-alisary-green/5">
                        <summary class="cursor-pointer font-display text-xl text-alisary-deep">
                            كيف نقرأ طلبك؟
                        </summary>
                        <ul class="mt-5 space-y-3 text-sm leading-loose text-alisary-soft">
                            <li>تُساعدنا أدواتُ الذكاء الاصطناعي على الفرز الأوّلي بثلاثة محاور: القابلية للتعلّم، وروح
                                المبادرة، والاصطفاف القيمي.</li>
                            <li>لا يصدر أيُّ اعتذارٍ إلا بمراجعةٍ بشريةٍ وسببٍ مكتوب — هذا التزامٌ معلن.</li>
                            <li>إجاباتك على «المواقف» لا تُقيَّم بصوابٍ وخطأ؛ نقرأ فيها طريقة تفكيرك.</li>
                        </ul>
                    </details>
                    <details
                        class="rounded-2xl border border-alisary-green/10 bg-white p-6 shadow-xl shadow-alisary-green/5">
                        <summary class="cursor-pointer font-display text-xl text-alisary-deep">
                            ماذا بعد الإرسال؟ وما الذي نقدّمه لنموّك؟
                        </summary>
                        <ul class="mt-5 space-y-3 text-sm leading-loose text-alisary-soft">
                            <li>يصلك رقمُ طلبٍ مرجعيّ فور الإرسال، ونتواصل مع كل متقدّمٍ بنتيجة طلبه.</li>
                            <li>من ينضمّ إلينا يجد: تأهيلًا مدفوعًا قبل بدء العمل، وخطةَ تطويرٍ تعالج أيَّ فجوة،
                                وأولويةً في الترقّي الداخلي بين مؤسسات المجموعة.</li>
                            <li>وإن لم يُوفَّق طلبُك هذه المرّة، فبإذنك يبقى ملفُّك لنرشّحك لأدوارٍ قادمةٍ تناسبك.</li>
                        </ul>
                    </details>
                </div>
            </div>
        </section>

        <section class="section bg-[#EFE8DA]" id="rights">
            <div class="mx-auto max-w-7xl px-5 lg:px-10">
                <div class="mx-auto mb-10 max-w-3xl text-center">
                    <span class="font-display text-sm font-bold tracking-[0.22em] text-alisary-gold">حماية بياناتك
                        الشخصية</span>
                    <h2 class="mt-3 font-display text-3xl leading-tight text-alisary-deep md:text-4xl">
                        حقوقك وكيف تمارسها
                    </h2>
                    <p class="mt-4 leading-loose text-alisary-soft">
                        بصفتك صاحبَ البيانات، يكفُل لك المرسومُ السلطاني ٦‏/٢٠٢٢ ولائحتُه التنفيذية ستّةَ حقوقٍ على
                        بياناتك التي تُعالَج في هذه الصفحة. وفيما يلي بيانُها وقناةُ ممارستها.
                    </p>
                </div>

                <div class="grid gap-6 lg:grid-cols-[1.15fr_.85fr]">
                    <div
                        class="overflow-hidden rounded-2xl border border-alisary-green/10 bg-white shadow-xl shadow-alisary-green/5">
                        @foreach ([['الوصول والعلم', 'أن تعرف ما إذا كنّا نعالج بياناتك، وأن تطّلع عليها وعلى الغرض من معالجتها.'], ['التصحيح والتحديث', 'أن تطلب تصحيح بياناتٍ غير دقيقةٍ أو ناقصة، أو تحديثها.'], ['المحو', 'أن تطلب محو بياناتك عند انتهاء الغرض من جمعها، أو عند سحبك للموافقة.'], ['الحصول على نسخة', 'أن تحصل على نسخةٍ من بياناتك في صيغةٍ واضحةٍ ومقروءة.'], ['النقل', 'أن تطلب نقل بياناتك إلى متحكّمٍ آخر متى أمكن ذلك تقنيًّا.'], ['سحب الموافقة', 'أن تسحب موافقتك على المعالجة في أيّ وقت، دون أثرٍ رجعيٍّ على ما تمّ قبل السحب، ولك أن تطلب وقفَ المعالجة لحين البتّ في طلبك.']] as $index => [$rightTitle, $rightBody])
                            <article class="flex gap-4 border-b border-alisary-green/10 p-5 last:border-b-0">
                                <span
                                    class="grid size-10 flex-none place-items-center rounded-xl bg-alisary-green/10 font-display font-bold text-alisary-green">
                                    {{ \App\Support\NumberLocalizer::eastern((string) ($index + 1)) }}
                                </span>
                                <div>
                                    <h3 class="font-display text-xl text-alisary-deep">{{ $rightTitle }}</h3>
                                    <p class="mt-2 leading-loose text-alisary-soft">{{ $rightBody }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <aside
                        class="rounded-2xl border border-alisary-green/10 border-r-4 border-r-alisary-gold bg-white p-6 shadow-xl shadow-alisary-green/5">
                        <h3 class="font-display text-2xl text-alisary-deep">كيف تمارس حقّك</h3>
                        <p class="mt-3 leading-loose text-alisary-soft">
                            أرسل طلبك عبر النموذج المختصر أدناه، فنتعهّد بما يلي:
                        </p>

                        <div
                            class="mt-5 flex items-center gap-3 rounded-xl border border-alisary-green/10 bg-alisary-ivory p-4">
                            <span class="font-display text-2xl text-alisary-gold">✉</span>
                            <span class="text-alisary-soft">
                                القناة المخصّصة:
                                <a href="mailto:privacy@alisary.com?subject=طلب%20ممارسة%20حقوق%20البيانات%20الشخصية"
                                    class="font-bold text-alisary-green underline underline-offset-4"
                                    dir="ltr">privacy@alisary.com</a>
                            </span>
                        </div>

                        <div class="mt-5 space-y-3 leading-loose text-alisary-soft">
                            <p class="rounded-xl bg-alisary-ivory p-4">نبتّ في طلبك خلال <b
                                    class="text-alisary-deep">خمسةٍ وأربعين يومًا</b> من تسلّمه، <b
                                    class="text-alisary-deep">بلا مقابل</b>.</p>
                            <p class="rounded-xl bg-alisary-ivory p-4">لك أن تطلب <b class="text-alisary-deep">وقف
                                    المعالجة</b> لحين البتّ في طلبك.</p>
                            <p class="rounded-xl bg-alisary-ivory p-4">نحفظ سجلَّ طلبك ومعالجتنا له <b
                                    class="text-alisary-deep">إثباتًا للامتثال</b>.</p>
                        </div>

                        <livewire:data-rights-request-form />
                    </aside>
                </div>
            </div>
        </section>

        <script>
            let activeCompanyFilter = 'all';
            let activeFamilyFilter = 'all';
            let activeBranchFilter = 'all';
            const schoolCompanyFilter = @js($schoolOrganizationKey);

            function filterJobs(filterId) {
                activeCompanyFilter = filterId;
                applyJobFilters();
            }

            function filterJobFamily(filterId) {
                activeFamilyFilter = filterId;
                applyJobFilters();
            }

            function filterJobBranch(filterId) {
                activeBranchFilter = filterId;
                applyJobFilters();
            }

            function closeJobShareMenus() {
                document.querySelectorAll('[data-job-share-menu]').forEach((menu) => {
                    menu.classList.add('hidden');
                    menu.classList.remove('flex');
                });

                document.querySelectorAll('[data-job-share]').forEach((button) => {
                    button.setAttribute('aria-expanded', 'false');
                });
            }

            function toggleJobShareMenu(jobId) {
                const shareMenu = document.getElementById(`job-share-menu-${jobId}`);
                const shareButton = document.querySelector(`[data-job-share="job-${jobId}"]`);

                if (!shareMenu || !shareButton) {
                    return;
                }

                const shouldOpen = shareMenu.classList.contains('hidden');

                closeJobShareMenus();

                if (shouldOpen) {
                    shareMenu.classList.remove('hidden');
                    shareMenu.classList.add('flex');
                    shareButton.setAttribute('aria-expanded', 'true');
                }
            }

            function shareJobTo(platform, jobId, jobUrl) {
                const jobTitle = document.getElementById(`job-title-${jobId}`)?.innerText.trim() ?? '';
                const encodedUrl = encodeURIComponent(jobUrl);
                const shareUrls = {
                    whatsapp: `https://wa.me/?text=${encodeURIComponent(`${jobTitle}\n${jobUrl}`)}`,
                    facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
                    x: `https://twitter.com/intent/tweet?text=${encodeURIComponent(jobTitle)}&url=${encodedUrl}`,
                    linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`,
                };

                if (!shareUrls[platform]) {
                    return;
                }

                window.open(shareUrls[platform], '_blank', 'noopener,noreferrer');
                closeJobShareMenus();
            }

            function normalizeJobText(value) {
                return (value || '')
                    .toString()
                    .toLowerCase()
                    .replace(/[أإآا]/g, 'ا')
                    .replace(/[ىي]/g, 'ي')
                    .replace(/ة/g, 'ه')
                    .trim();
            }

            function applyJobFilters() {
                const cards = document.querySelectorAll('[data-company]');
                const emptyState = document.getElementById('jobs-empty-state');
                const countLine = document.getElementById('jobs-count-line');
                const searchInput = document.getElementById('jobSearch');
                const levelFilter = document.getElementById('jobLevelFilter');
                const branchFilter = document.getElementById('school-branch-filter');
                const branchSelect = document.getElementById('schoolBranchSelect');
                const query = normalizeJobText(searchInput?.value);
                const level = levelFilter?.value || 'all';
                const isSchoolCompanySelected = schoolCompanyFilter !== null && activeCompanyFilter === schoolCompanyFilter;
                let visibleCount = 0;

                if (!isSchoolCompanySelected) {
                    activeBranchFilter = 'all';
                    if (branchSelect) {
                        branchSelect.value = 'all';
                    }
                }

                if (branchFilter) {
                    if (isSchoolCompanySelected) {
                        branchFilter.classList.remove('hidden');
                        branchFilter.classList.add('flex');
                    } else {
                        branchFilter.classList.add('hidden');
                        branchFilter.classList.remove('flex');
                    }
                }

                // Update active state on buttons
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    if (btn.getAttribute('data-filter') === activeCompanyFilter) {
                        btn.classList.add('ring-2', 'ring-alisary-gold', 'bg-alisary-ivory');
                    } else {
                        btn.classList.remove('ring-2', 'ring-alisary-gold', 'bg-alisary-ivory');
                    }
                });

                document.querySelectorAll('.family-filter-btn').forEach(btn => {
                    if (btn.getAttribute('data-family-filter') === activeFamilyFilter) {
                        btn.classList.add('bg-alisary-deep', 'text-white');
                        btn.classList.remove('bg-white', 'text-alisary-deep');
                    } else {
                        btn.classList.remove('bg-alisary-deep', 'text-white');
                        btn.classList.add('bg-white', 'text-alisary-deep');
                    }
                });

                cards.forEach(card => {
                    const matchesCompany = activeCompanyFilter === 'all' || card.getAttribute('data-company') ===
                        activeCompanyFilter;
                    const matchesFamily = activeFamilyFilter === 'all' || card.getAttribute('data-family') ===
                        activeFamilyFilter;
                    const matchesBranch = !isSchoolCompanySelected || activeBranchFilter === 'all' ||
                        (card.getAttribute('data-branch') || '').split(' ').includes(activeBranchFilter.replace('branch-', ''));
                    const matchesLevel = level === 'all' || card.getAttribute('data-level') === level;
                    const matchesSearch = query === '' || normalizeJobText(card.getAttribute('data-search')).includes(
                        query);

                    if (matchesCompany && matchesFamily && matchesBranch && matchesLevel && matchesSearch) {
                        card.style.display = 'flex';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (emptyState) {
                    if (visibleCount === 0) {
                        emptyState.style.display = 'block';
                    } else {
                        emptyState.style.display = 'none';
                    }
                }

                if (countLine) {
                    const parts = [`يُعرض الآن ${visibleCount} من ${cards.length} وظيفة`];

                    if (query !== '') {
                        parts.push(`مطابقة لبحث "${searchInput.value}"`);
                    }

                    if (level !== 'all') {
                        parts.push(`المستوى ${level}`);
                    }

                    countLine.textContent = parts.join(' · ');
                }
            }

            document.addEventListener('DOMContentLoaded', applyJobFilters);
        </script>
    @endif
</x-website.layout>
