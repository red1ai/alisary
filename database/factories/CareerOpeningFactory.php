<?php

namespace Database\Factories;

use App\Enums\CareerCategory;
use App\Enums\ListingStatus;
use App\Models\CareerOpening;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CareerOpening>
 */
class CareerOpeningFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 99999),
            'title' => $title,
            'category' => CareerCategory::Light,
            'summary' => fake()->sentence(),
            'description' => '<p>'.fake()->paragraph().'</p>',
            'status' => ListingStatus::Published,
            'published_at' => now()->subDay(),
            'form_sections' => [[
                'title' => 'بيانات إضافية',
                'description' => null,
                'fields' => [
                    ['key' => 'years_experience', 'label' => 'سنوات الخبرة', 'type' => 'number', 'required' => true, 'options' => []],
                    ['key' => 'cover_note', 'label' => 'نبذة', 'type' => 'textarea', 'required' => false, 'options' => []],
                    ['key' => 'cv', 'label' => 'السيرة الذاتية', 'type' => 'file', 'required' => false, 'options' => []],
                    ['key' => 'acknowledge_truth', 'label' => 'أقرّ بصحة البيانات', 'type' => 'checkbox', 'required' => true, 'options' => []],
                ],
            ]],
            'eligibility_rules' => [],
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => ListingStatus::Draft]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rules
     */
    public function withEligibilityRules(array $rules): static
    {
        return $this->state(fn (): array => ['eligibility_rules' => $rules]);
    }
}
