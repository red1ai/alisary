<?php

namespace App\Models;

use App\Support\CareerShare;
use Database\Factories\CareerOrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class CareerOrganization extends Model
{
    /** @use HasFactory<CareerOrganizationFactory> */
    use HasFactory;

    protected $guarded = [];

    public function openings(): HasMany
    {
        return $this->hasMany(CareerOpening::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Published openings only (status and publish window).
     *
     * @return Collection<int, CareerOpening>
     */
    public function publishedOpenings(): Collection
    {
        return $this->openings()->latest('published_at')->get()
            ->filter(fn (CareerOpening $opening): bool => $opening->isPublished())
            ->values();
    }

    /**
     * Absolute logo URL built from APP_URL, or null when absent / not publicly reachable.
     */
    public function logoUrl(): ?string
    {
        if (blank($this->logo_path)) {
            return null;
        }

        return CareerShare::absoluteUrl('/storage/'.ltrim($this->logo_path, '/'));
    }

    /**
     * Logo URL for on-page display; relative so it works on any host serving the site.
     */
    public function logoDisplayUrl(): ?string
    {
        return filled($this->logo_path) ? '/storage/'.ltrim($this->logo_path, '/') : null;
    }

    public function shareUrl(): ?string
    {
        return CareerShare::absoluteUrl(route('careers.organizations.show', $this, false));
    }
}
