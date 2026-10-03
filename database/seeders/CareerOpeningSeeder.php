<?php

namespace Database\Seeders;

use App\Enums\ListingLocation;
use App\Enums\ListingStatus;
use App\Models\CareerOpening;
use App\Models\CareerOrganization;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CareerOpeningSeeder extends Seeder
{
    /**
     * Seeds the Ibra principal opening as an editable draft (title, entity and location only).
     *
     * Description, requirements, form fields, eligibility rules and category are
     * deliberately left empty until approved content is supplied; the opening
     * stays hidden from visitors and rejects applications until completed and published from the admin panel.
     */
    public function run(): void
    {
        $company = Company::query()->where('slug', 'g-reader-school')->first();
        $organization = $company
            ? CareerOrganization::query()->firstOrCreate(
                ['slug' => $company->slug],
                ['name' => $company->name, 'logo_path' => $company->logo_path, 'description' => $company->description],
            )
            : null;

        CareerOpening::query()->firstOrCreate(
            ['slug' => 'school-principal-ibra'],
            [
                'title' => 'مديرة مدرسة القارئ العبقري — فرع إبراء',
                'company_id' => $company?->id,
                'career_organization_id' => $organization?->id,
                'location' => ListingLocation::Ibra,
                'status' => ListingStatus::Draft,
            ],
        );
    }
}
