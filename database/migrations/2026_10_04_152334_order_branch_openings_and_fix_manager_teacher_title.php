<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The /jobs list is sorted by published_at descending, and the four branch openings share one timestamp.
 * Give them distinct timestamps (first listed = newest) and correct the title wording "معلم مدير" to "معلمة مديرة".
 * Only these four slugs are touched.
 */
return new class extends Migration
{
    private const ORDER = [
        'cycle-one-manager-ibra',
        'early-ed-manager-bawshar',
        'domain-one-teacher-udhaibah',
        'english-teacher-bawshar',
    ];

    public function up(): void
    {
        $openings = DB::table('career_openings')->whereIn('slug', self::ORDER)->get()->keyBy('slug');

        $base = $openings->pluck('published_at')->filter()->map(fn ($value) => Carbon::parse($value))->max();

        foreach (self::ORDER as $position => $slug) {
            $opening = $openings->get($slug);

            if ($opening === null) {
                continue;
            }

            $updates = [];

            foreach (['title', 'subtitle', 'summary', 'description', 'chips', 'blocks'] as $column) {
                $value = $opening->{$column};

                if (is_string($value) && str_contains($value, 'معلم مدير')) {
                    $updates[$column] = str_replace('معلم مدير', 'معلمة مديرة', $value);
                }
            }

            if ($base !== null && $opening->published_at !== null) {
                $updates['published_at'] = $base->copy()->subSeconds($position);
            }

            if ($updates !== []) {
                DB::table('career_openings')->where('id', $opening->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        // Irreversible by design.
    }
};
