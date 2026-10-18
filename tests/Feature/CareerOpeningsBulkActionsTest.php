<?php

use App\Enums\ListingStatus;
use App\Filament\Resources\CareerOpenings\Pages\ListCareerOpenings;
use App\Models\CareerOpening;
use App\Models\Company;
use App\Models\User;
use App\Support\RamoushOpenings;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Company::factory()->create(['slug' => 'g-reader-school']);
    RamoushOpenings::install();

    $this->old = CareerOpening::factory()->count(4)->create([
        'status' => ListingStatus::Published,
        'published_at' => Carbon::parse('2026-09-01 08:00:00', 'UTC'),
        'expires_at' => null,
    ]);
    $this->ramoush = CareerOpening::whereIn('slug', RamoushOpenings::SLUGS)->get();
    $this->expiryBefore = CareerOpening::where('slug', 'ramoush-teacher')->value('expires_at');
});

function snapshotOpenings($openings): array
{
    return CareerOpening::whereIn('id', $openings->pluck('id'))->orderBy('id')->get()
        ->mapWithKeys(fn ($o) => [$o->id => [$o->status->value, $o->published_at?->toDateTimeString(), $o->expires_at?->toDateTimeString()]])->all();
}

it('shows the publish date column in Muscat time with a dash when empty', function () {
    Livewire::test(ListCareerOpenings::class)
        ->assertSee('2026-09-01 12:00')
        ->assertSee('—');
});

it('publishes only selected openings, filling missing publish dates and keeping existing dates', function () {
    $oldBefore = snapshotOpenings($this->old);
    $withDate = $this->ramoush->first();
    $withDate->update(['published_at' => Carbon::parse('2026-12-01 00:00:00', 'UTC')]);

    Livewire::test(ListCareerOpenings::class)
        ->callTableBulkAction('publishSelected', $this->ramoush)
        ->assertNotified();

    foreach ($this->ramoush->fresh() as $opening) {
        expect($opening->status)->toBe(ListingStatus::Published)->and($opening->published_at)->not->toBeNull();
    }
    expect($withDate->fresh()->published_at->toDateTimeString())->toBe('2026-12-01 00:00:00');
    expect(CareerOpening::where('slug', 'ramoush-teacher')->value('expires_at'))->toEqual($this->expiryBefore);
    expect(snapshotOpenings($this->old))->toBe($oldBefore);
});

it('converts selected openings to draft and keeps both dates', function () {
    $target = $this->old->take(2);
    $target->each->update(['expires_at' => Carbon::parse('2027-01-01 00:00:00', 'UTC')]);
    $others = $this->old->skip(2);
    $targetBefore = snapshotOpenings($target);
    $othersBefore = snapshotOpenings($others);

    Livewire::test(ListCareerOpenings::class)->callTableBulkAction('draftSelected', $target);

    $after = snapshotOpenings($target);
    foreach ($targetBefore as $id => $row) {
        expect($after[$id])->toBe(['draft', $row[1], $row[2]]);
    }
    expect(snapshotOpenings($others))->toBe($othersBefore);
});

it('sets only the publish date, converting Muscat time to UTC', function () {
    Livewire::test(ListCareerOpenings::class)
        ->callTableBulkAction('editDates', $this->ramoush, [
            'published_mode' => 'set', 'published_at' => '2026-10-01 10:00:00', 'expires_mode' => 'keep',
        ])->assertHasNoTableBulkActionErrors();

    foreach ($this->ramoush->fresh() as $opening) {
        expect($opening->published_at->toDateTimeString())->toBe('2026-10-01 06:00:00');
    }
    expect(CareerOpening::where('slug', 'ramoush-teacher')->value('expires_at'))->toEqual($this->expiryBefore);
});

it('sets only the expiry date and leaves publish dates', function () {
    Livewire::test(ListCareerOpenings::class)
        ->callTableBulkAction('editDates', $this->old, [
            'published_mode' => 'keep', 'expires_mode' => 'set', 'expires_at' => '2027-03-01 23:59:00',
        ]);

    foreach ($this->old->fresh() as $opening) {
        expect($opening->expires_at->toDateTimeString())->toBe('2027-03-01 19:59:00')
            ->and($opening->published_at->toDateTimeString())->toBe('2026-09-01 08:00:00');
    }
});

it('clears dates only when explicitly requested', function () {
    $this->old->each->update(['expires_at' => Carbon::parse('2027-01-01', 'UTC')]);

    Livewire::test(ListCareerOpenings::class)
        ->callTableBulkAction('editDates', $this->old, ['published_mode' => 'clear', 'expires_mode' => 'keep']);

    foreach ($this->old->fresh() as $opening) {
        expect($opening->published_at)->toBeNull()->and($opening->expires_at)->not->toBeNull();
    }
});

it('requires a date when setting and rejects expiry before publish without saving anything', function () {
    Livewire::test(ListCareerOpenings::class)
        ->callTableBulkAction('editDates', $this->old, ['published_mode' => 'set', 'published_at' => null, 'expires_mode' => 'keep'])
        ->assertHasTableBulkActionErrors(['published_at']);

    $before = snapshotOpenings($this->old);

    Livewire::test(ListCareerOpenings::class)
        ->callTableBulkAction('editDates', $this->old, [
            'published_mode' => 'set', 'published_at' => '2026-12-01 10:00:00',
            'expires_mode' => 'set', 'expires_at' => '2026-11-01 10:00:00',
        ]);

    expect(snapshotOpenings($this->old))->toBe($before);
});
