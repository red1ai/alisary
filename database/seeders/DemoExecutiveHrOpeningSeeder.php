<?php

namespace Database\Seeders;

use App\Enums\ListingStatus;
use App\Models\CareerOpening;
use Illuminate\Database\Seeder;

/**
 * Demo opening that mirrors the reference template job-executive.html ("مدير الموارد البشرية").
 * Local/testing only: it never runs in other environments and never touches the Ibra record.
 */
class DemoExecutiveHrOpeningSeeder extends Seeder
{
    public const SLUG = 'exec-hr-director';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        CareerOpening::query()->updateOrCreate(['slug' => self::SLUG], $this->attributes());
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        $governorates = ['مسقط', 'ظفار', 'مسندم', 'البريمي', 'الداخلية', 'شمال الباطنة', 'جنوب الباطنة', 'شمال الشرقية', 'جنوب الشرقية', 'الظاهرة', 'الوسطى'];

        return [
            'title' => 'مدير الموارد البشرية',
            'subtitle' => 'تبني رأس المال البشري لمدرسة تنمو، وتجعل الأثر مقياسَ النجاح',
            'unit' => 'مدرسة القارئ العبقري · الإدارة التنفيذية',
            'notice' => 'نسخة تجريبية — راجِع النصوص والشروط قبل النشر',
            'category' => 'leadership',
            'status' => ListingStatus::Published,
            'published_at' => now()->subMinute(),
            'chips' => [
                ['k' => 'الدوام', 'v' => 'كامل — المركز المؤسسي مع زيارات للفروع'],
                ['k' => 'يتبع', 'v' => 'الرئيس التنفيذي'],
                ['k' => 'الخبرة', 'v' => '٧–١٠ سنوات'],
            ],
            'blocks' => [
                'about' => ['on' => true, 'title' => 'الهدف العام للوظيفة', 'text' => 'بناء وتطوير رأس المال البشري، وجذب الكفاءات والاحتفاظ بها، ودعم تنفيذ خطة تعظيم الأثر بتطوير الموظفين وتعزيز ثقافة خماسية السكينة.'],
                'tasks' => ['on' => true, 'title' => 'ما تقوده', 'items' => [
                    'منظومة الاستقطاب والاختيار والتعيين والتهيئة.',
                    'إدارة الأداء ونظام الأجور والمزايا، بالتنسيق مع المالية.',
                    'التطوير والتدريب والمسارات المهنية والتعاقب.',
                    'لوحة مؤشرات رأس المال البشري وتقاريرها الدورية.',
                ]],
                'who' => ['on' => true, 'title' => 'من نبحث عنه؟', 'must' => [
                    'بكالوريوس أو ماجستير في الموارد البشرية أو إدارة الأعمال.',
                    'لا تقلّ خبرته عن ٧–١٠ سنوات في إدارة الموارد البشرية.',
                    'قيادة وتطوير فرق، ومعرفة عميقة بقوانين العمل العمانية.',
                    'القدرة على بناء ثقافة تربوية إيمانية.',
                ], 'prefer' => ['خبرة في قطاع التعليم.']],
                'schedule' => ['on' => false, 'items' => []],
                'branches' => ['on' => false, 'rows' => []],
                'pay' => ['on' => true, 'title' => 'الأجر', 'text' => 'وفق سلّم المجموعة للمستوى القيادي، ويُناقَش مع المرشحين في مرحلة العرض لا في الاستمارة.'],
                'growth' => ['on' => false, 'items' => []],
                'kpis' => ['on' => true, 'title' => 'كيف يُقاس نجاحك؟', 'items' => [
                    'خفض نسبة الشغور',
                    'نسبة الاحتفاظ بالموظفين',
                    'رضا الموظفين',
                    'ساعات التدريب السنوية لكل موظف',
                    'نسبة تحقيق المعلمات للحد الأدنى في حفظ القرآن والبيان',
                ]],
                'values' => ['on' => true, 'title' => 'خماسية السكينة في هذه الوظيفة', 'emph' => ['ibada', 'amal'], 'note' => 'الوظيفة تحمل قيم العبادة وقيم العمل، مع دعم قوي لجميع أعمدة الخماسية.'],
                'process' => ['on' => true, 'title' => 'كيف نختار؟', 'items' => [
                    ['k' => 'فرزٌ أوليّ', 'v' => 'بحسب الوصف الوظيفي المعتمد'],
                    ['k' => 'اختبار عملي (٩٠ دقيقة)', 'v' => 'تحليلٌ وأداةٌ وحالة، يُطبَّق على الجميع بالدرجات نفسها'],
                    ['k' => 'جلسة عمل استكشافية', 'v' => 'نناقش فيها تحديات حقيقية، لا أسئلة محفوظة'],
                    ['k' => 'القرار', 'v' => 'يصلك الردّ مهما كانت النتيجة'],
                ]],
            ],
            'form_title' => 'استمارةُ التقدّم',
            'form_lead' => 'أربع خطوات. نقرأ كل طلب كاملًا، فخذ وقتك.',
            'success_message' => 'نقرأ كل طلبٍ كاملًا. سيصلك الردّ بعد الفرز الأولي، قُبلتَ أو لم تُقبَل.',
            'identity_in_first_step' => false,
            'core_field_labels' => null,
            'eligibility_rules' => [],
            'form_sections' => [
                [
                    'title' => 'هويتك',
                    'description' => null,
                    'fields' => [
                        $this->field('full_name', 'الاسم الثلاثي', 'core', true),
                        $this->field('nid', 'رقم البطاقة الشخصية', 'text', true, ['pattern' => '^\d{6,10}$', 'pattern_message' => 'أدخل رقم البطاقة أرقامًا فقط.', 'inputmode' => 'numeric']),
                        $this->field('dob', 'تاريخ الميلاد', 'date', true),
                        $this->field('nationality', 'الجنسية', 'radio', true, ['options' => $this->options(['عُماني', 'غير عُماني'])]),
                        $this->field('phone', 'رقم الواتساب', 'core', true, ['pattern' => '^(\+?968)?\s?\d{8}$', 'pattern_message' => 'أدخل رقمًا عُمانيًّا من ٨ أرقام.', 'hint' => 'مثال: 91234567']),
                        $this->field('email', 'البريد الإلكتروني', 'core', true),
                        $this->field('gov', 'المحافظة', 'select', true, ['options' => $this->options($governorates)]),
                    ],
                ],
                [
                    'title' => 'مسيرتك',
                    'description' => null,
                    'fields' => [
                        $this->field('edu', 'المؤهل العلمي', 'select', true, ['options' => $this->options(['ثانوية عامة', 'دبلوم', 'بكالوريوس', 'ماجستير', 'دكتوراه'])]),
                        $this->field('major', 'التخصص', 'text', true),
                        $this->field('exp', 'سنوات الخبرة في المجال', 'select', true, ['options' => $this->options(['لا خبرة', 'أقل من سنة', '١–٣ سنوات', '٤–٦ سنوات', '٧–١٠ سنوات', 'أكثر من ١٠ سنوات'])]),
                        $this->field('employer', 'جهة العمل الحالية أو الأخيرة', 'text', true),
                        $this->field('cv', 'السيرة الذاتية', 'file', true, ['accepted_file_types' => ['pdf', 'doc', 'docx'], 'max_file_size_kb' => 5120, 'hint' => 'PDF أو Word — حتى ٥ ميجابايت']),
                        $this->field('certs', 'الشهادات والمؤهلات', 'file', true, ['accepted_file_types' => ['pdf', 'jpg', 'jpeg', 'png'], 'max_file_size_kb' => 5120, 'multiple' => true, 'hint' => 'PDF أو صور — حتى ٥ ميجابايت للملف']),
                    ],
                ],
                [
                    'title' => 'رؤيتك',
                    'description' => null,
                    'fields' => [
                        $this->field('essay', 'تنضمّ إلى مجموعة تبني منظومتها من الصفر: ما أول ثلاثة قرارات ستسعى إلى حسمها في أول ٩٠ يومًا، ولماذا؟', 'textarea', true, ['max_length' => 1200, 'hint' => 'اكتب بما تراه عمليًّا، لا بما تراه مثاليًّا']),
                        $this->field('cover', 'ما تريد أن نعرفه عنك ولم تقله سيرتُك', 'textarea', false, ['max_length' => 500, 'hint' => 'بضعة أسطر تكفي']),
                    ],
                ],
                [
                    'title' => 'المزكّون',
                    'description' => null,
                    'fields' => [
                        $this->field('ref1', 'مُزكٍّ أول (الاسم والهاتف والصلة)', 'text', true),
                        $this->field('ref2', 'مُزكٍّ ثانٍ (الاسم والهاتف والصلة)', 'text', true),
                        $this->field('consent', 'أُقرّ بصحة ما أدخلتُ من بيانات ومرفقات، وأوافق على التحقق منها والتواصل مع المزكّين، واستخدامها لأغراض التوظيف فقط.', 'checkbox', true),
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function field(string $key, string $label, string $type, bool $required, array $extra = []): array
    {
        return array_merge(['key' => $key, 'label' => $label, 'type' => $type, 'required' => $required, 'options' => []], $extra);
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, array{label: string, value: string}>
     */
    private function options(array $values): array
    {
        return array_map(fn (string $value): array => ['label' => $value, 'value' => $value], $values);
    }
}
