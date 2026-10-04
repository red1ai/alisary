<?php

namespace App\Support;

use App\Models\CareerOpening;

class CareerBlocks
{
    /**
     * Fixed page order. kind: text | lines | who | pairs | rows | values.
     *
     * @return array<string, array{title: string, kind: string}>
     */
    public static function definitions(): array
    {
        return [
            'about' => ['title' => 'عن الوظيفة', 'kind' => 'text'],
            'tasks' => ['title' => 'ماذا ستفعل؟', 'kind' => 'lines'],
            'who' => ['title' => 'من نبحث عنه؟', 'kind' => 'who'],
            'schedule' => ['title' => 'الوقت والالتزام', 'kind' => 'pairs'],
            'branches' => ['title' => 'الفروع والمقاعد المتاحة', 'kind' => 'rows'],
            'pay' => ['title' => 'الأجر', 'kind' => 'text'],
            'growth' => ['title' => 'مسار الترقّي', 'kind' => 'lines'],
            'kpis' => ['title' => 'كيف يُقاس نجاحك؟', 'kind' => 'lines'],
            'values' => ['title' => 'خماسية السكينة في هذه الوظيفة', 'kind' => 'values'],
            'process' => ['title' => 'ماذا بعد التقديم؟', 'kind' => 'pairs'],
        ];
    }

    /**
     * @return array<string, array{id: string, name: string, color: string, sub: array<int, string>}>
     */
    public static function pillars(): array
    {
        return [
            'ibada' => ['id' => 'ibada', 'name' => 'عبادة', 'color' => '#2E7D32', 'sub' => ['الإيمان', 'الإحسان', 'قول الحسن', 'الصلاة', 'الإنفاق']],
            'ilm' => ['id' => 'ilm', 'name' => 'علم', 'color' => '#B27800', 'sub' => ['القرآن', 'البيان', 'دراسة العلوم', 'القراءة', 'الكتابة']],
            'amal' => ['id' => 'amal', 'name' => 'عمل', 'color' => '#1F5FBF', 'sub' => ['الزراعة', 'العمل المنزلي', 'التجارة', 'الادخار والتثمير', 'العمل التطوعي']],
            'lab' => ['id' => 'lab', 'name' => 'لعب', 'color' => '#C2255C', 'sub' => ['الرتع', 'اللعب بالدمى والمجسمات', 'الألعاب الحركية', 'الألعاب التقنية', 'الألعاب التمثيلية']],
            'nawm' => ['id' => 'nawm', 'name' => 'نوم وصحة', 'color' => '#D9650B', 'sub' => ['ماذا يفعل قبل النوم؟', 'النوم', 'ما بعد الاستيقاظ', 'الغذاء', 'الصحة']],
        ];
    }

    /**
     * Enabled blocks with content, in page order. Nothing is invented: a block
     * appears only when switched on and filled in.
     *
     * @return array<int, array{key: string, title: string, kind: string, data: array<string, mixed>}>
     */
    public static function active(CareerOpening $opening): array
    {
        $stored = $opening->blocks ?? [];
        $active = [];

        foreach (self::definitions() as $key => $definition) {
            $data = $stored[$key] ?? [];

            if (! ($data['on'] ?? false) || ! self::hasContent($definition['kind'], $data)) {
                continue;
            }

            $active[] = [
                'key' => $key,
                'title' => filled($data['title'] ?? null) ? $data['title'] : $definition['title'],
                'kind' => $definition['kind'],
                'data' => $data,
            ];
        }

        return $active;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function hasContent(string $kind, array $data): bool
    {
        return match ($kind) {
            'text' => filled($data['text'] ?? null),
            'lines' => count(array_filter($data['items'] ?? [])) > 0,
            'who' => count(array_filter($data['must'] ?? [])) + count(array_filter($data['prefer'] ?? [])) > 0,
            'pairs' => count(array_filter($data['items'] ?? [], fn ($item) => filled($item['k'] ?? null))) > 0,
            'rows' => count(array_filter($data['rows'] ?? [], fn ($row) => filled($row['name'] ?? null))) > 0,
            'values' => count($data['emph'] ?? []) > 0,
            default => false,
        };
    }
}
