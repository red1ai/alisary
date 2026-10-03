<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('career_organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::table('career_openings', function (Blueprint $table) {
            $table->foreignId('career_organization_id')->nullable()->after('company_id')
                ->constrained('career_organizations')->nullOnDelete();
        });

        // Additive backfill: company_id is kept untouched, only the new link is filled.
        $companyIds = DB::table('career_openings')->whereNotNull('company_id')->distinct()->pluck('company_id');

        foreach ($companyIds as $companyId) {
            $company = DB::table('companies')->where('id', $companyId)->first();

            if (! $company) {
                continue;
            }

            $slug = Str::slug((string) ($company->slug ?? '')) ?: 'org-'.$company->id;

            $organizationId = DB::table('career_organizations')->insertGetId([
                'name' => $company->name,
                'slug' => DB::table('career_organizations')->where('slug', $slug)->exists() ? $slug.'-'.$company->id : $slug,
                'logo_path' => $company->logo_path ?? null,
                'description' => $company->description ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('career_openings')
                ->where('company_id', $companyId)
                ->update(['career_organization_id' => $organizationId]);
        }
    }

    public function down(): void
    {
        Schema::table('career_openings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('career_organization_id');
        });

        Schema::dropIfExists('career_organizations');
    }
};
