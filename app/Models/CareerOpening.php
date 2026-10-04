<?php

namespace App\Models;

use App\Enums\CareerCategory;
use App\Enums\ListingLocation;
use App\Enums\ListingStatus;
use App\Support\CareerBlocks;
use App\Support\CareerShare;
use App\Support\CustomFormFields;
use Database\Factories\CareerOpeningFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CareerOpening extends Model
{
    /** @use HasFactory<CareerOpeningFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'category' => CareerCategory::class,
            'location' => ListingLocation::class,
            'status' => ListingStatus::class,
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'form_sections' => 'array',
            'eligibility_rules' => 'array',
            'chips' => 'array',
            'blocks' => 'array',
            'core_field_labels' => 'array',
            'identity_in_first_step' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(CareerOrganization::class, 'career_organization_id');
    }

    /**
     * Organization name, falling back to the legacy company link.
     */
    public function organizationName(): ?string
    {
        return $this->organization?->name ?? $this->company?->name;
    }

    /**
     * @return array{title: string, description: string, url: ?string, image: ?string, type: string}
     */
    public function shareMeta(): array
    {
        $description = filled($this->summary) ? $this->summary : ($this->subtitle ?: $this->description);

        return CareerShare::meta(
            $this->title.' - '.($this->organizationName() ?? 'مجموعة العيسري'),
            Str::limit(trim(strip_tags((string) $description)), 200) ?: $this->title,
            $this->shareUrl(),
            $this->organization?->logoUrl(),
            'article',
        );
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CareerApplication::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function sections(): array
    {
        return CustomFormFields::sections($this->form_sections ?? []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fields(): array
    {
        return array_values(array_filter(
            CustomFormFields::flattenFields($this->sections()),
            fn (array $field): bool => ($field['type'] ?? null) !== 'core',
        ));
    }

    /**
     * Placement entries for the built-in applicant fields (full_name, phone, email),
     * keyed by field key. They let the form order identity fields among custom ones.
     *
     * @return array<string, array<string, mixed>>
     */
    public function coreFieldEntries(): array
    {
        return collect(CustomFormFields::flattenFields($this->sections()))
            ->filter(fn (array $field): bool => ($field['type'] ?? null) === 'core' && in_array($field['key'] ?? null, ['full_name', 'phone', 'email'], true))
            ->keyBy('key')
            ->all();
    }

    public function coreLabel(string $key, string $default): string
    {
        $label = $this->coreFieldEntries()[$key]['label'] ?? ($this->core_field_labels ?? [])[$key] ?? null;

        return filled($label) ? (string) $label : $default;
    }

    public function hasApprovedContent(): bool
    {
        return count($this->fields()) > 0
            && (filled($this->description) || count(CareerBlocks::active($this)) > 0);
    }

    /**
     * Visible to visitors: published and inside its window. Applications additionally
     * require approved content (see isAcceptingSubmissions).
     */
    /**
     * Canonical public URL built from APP_URL (never the current request host),
     * or null when it is not a real public address or the opening is unpublished.
     */
    public function shareUrl(): ?string
    {
        if (! $this->isPublished()) {
            return null;
        }

        $url = rtrim((string) config('app.url'), '/').route('jobs.show', $this, false);

        return CareerShare::isPublicUrl($url) ? $url : null;
    }

    public function isPublished(): bool
    {
        return $this->status === ListingStatus::Published
            && ($this->published_at === null || $this->published_at->isPast())
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isAcceptingSubmissions(): bool
    {
        return $this->isPublished() && $this->hasApprovedContent();
    }
}
