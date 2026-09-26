<?php

use App\Models\Site;
use Database\Seeders\SiteSeeder;

test('the site seeder is idempotent', function () {
    $this->seed(SiteSeeder::class);

    $before = Site::query()->count();
    $links = Site::query()->whereNotNull('parent_id')->count();

    $this->seed(SiteSeeder::class);

    expect(Site::query()->count())->toBe($before)
        ->and(Site::query()->whereNotNull('parent_id')->count())->toBe($links)
        ->and($before)->toBeGreaterThan(0);
});

test('the site seeder builds a hierarchy rooted at a single company', function () {
    $this->seed(SiteSeeder::class);

    expect(Site::query()->where('type', 'company')->count())->toBe(1)
        ->and(Site::query()->where('type', 'company')->whereNull('parent_id')->count())->toBe(1)
        ->and(Site::query()->where('type', 'company')->exists())->toBeTrue();
});

test('the site seeder nests every non-company site under a valid parent', function () {
    $this->seed(SiteSeeder::class);

    $children = Site::query()->where('type', '!=', 'company')->get();

    expect($children)->not->toBeEmpty();

    $children->each(function (Site $site): void {
        expect($site->parent)->not->toBeNull()
            ->and(in_array($site->parent->type, Site::PARENT_TYPES[$site->type], true))->toBeTrue();
    });
});

test('the site seeder keeps one inactive site for reporting history', function () {
    $this->seed(SiteSeeder::class);

    expect(Site::query()->where('is_active', false)->count())->toBe(1);
});

test('the site seeder gives every site a unique code', function () {
    $this->seed(SiteSeeder::class);

    $codes = Site::query()->pluck('code');

    expect($codes->unique())->toHaveCount($codes->count());
});
