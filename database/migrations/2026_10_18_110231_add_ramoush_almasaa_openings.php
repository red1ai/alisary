<?php

use App\Support\RamoushOpenings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the eight "رموش المساء" openings as hidden drafts (insert-only, by slug). Publish them from the admin
 * panel once reviewed. Runs once, so an opening deleted later is not recreated; existing openings are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        // A fresh database (tests, new installs) has no school company yet; there is nothing to attach the openings to.
        if (! DB::table('companies')->where('slug', 'g-reader-school')->exists()) {
            return;
        }

        RamoushOpenings::install();
    }

    public function down(): void
    {
        // Only remove openings that are still untouched drafts with no applications.
        DB::table('career_openings')
            ->whereIn('slug', RamoushOpenings::SLUGS)
            ->where('status', 'draft')
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))->from('career_applications')->whereColumn('career_applications.career_opening_id', 'career_openings.id'))
            ->delete();
    }
};
