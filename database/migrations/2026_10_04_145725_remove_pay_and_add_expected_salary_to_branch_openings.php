<?php

use Database\Seeders\BranchOpeningsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Manager directive: the four branch openings no longer show pay publicly and instead ask
 * applicants for their expected salary. Only the four slugs in BranchOpeningsSeeder::SLUGS are touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('career_openings')->whereIn('slug', BranchOpeningsSeeder::SLUGS)->get() as $opening) {
            $blocks = json_decode((string) $opening->blocks, true) ?: [];
            unset($blocks['pay']);

            $sections = json_decode((string) $opening->form_sections, true) ?: [];
            $exists = collect($sections)->flatMap(fn (array $section): array => $section['fields'] ?? [])
                ->contains(fn (array $field): bool => ($field['key'] ?? null) === BranchOpeningsSeeder::EXPECTED_SALARY_KEY);

            if (! $exists && isset($sections[0]['fields'])) {
                $sections[0]['fields'][] = [
                    'key' => BranchOpeningsSeeder::EXPECTED_SALARY_KEY,
                    'label' => BranchOpeningsSeeder::EXPECTED_SALARY_LABEL,
                    'type' => 'text',
                    'required' => true,
                    'options' => [],
                    'max_length' => 100,
                ];
            }

            DB::table('career_openings')->where('id', $opening->id)->update([
                'blocks' => json_encode($blocks, JSON_UNESCAPED_UNICODE),
                'form_sections' => json_encode($sections, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    public function down(): void
    {
        // Irreversible by design: the removed pay text is not retained.
    }
};
