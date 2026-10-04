<x-filament-panels::page>
    @php($previews = $this->previews())

    <x-filament::section heading="منشئ صفحات الوظائف" description="أداة لبناء صفحة وظيفة مستقلة: اختر النوع، عدّل الأقسام والحقول، عاين النتيجة، ثم انسخ الإعداد أو نزّل ملف HTML.">
        <x-filament::button tag="a" :href="route('job-pages.builder')" target="_blank" icon="heroicon-o-wrench-screwdriver">
            فتح منشئ صفحات الوظائف
        </x-filament::button>
    </x-filament::section>

    <x-filament::section heading="معاينة صفحات الوظائف الجاهزة" description="الملفات الموجودة في public/job-pages، وتُفتح هنا للمسؤولين فقط.">
        @forelse ($previews as $page => $url)
            <div class="flex items-center justify-between gap-4 py-2" wire:key="preview-{{ $page }}">
                <span dir="ltr">{{ $page }}.html</span>
                <x-filament::button tag="a" :href="$url" target="_blank" color="gray" size="sm">معاينة</x-filament::button>
            </div>
        @empty
            <p>لا توجد صفحات جاهزة بعد.</p>
        @endforelse
    </x-filament::section>
</x-filament-panels::page>
