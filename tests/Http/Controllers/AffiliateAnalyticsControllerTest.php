<?php

use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Inovector\Mixpost\Models\AffiliatePost;
use Inovector\Mixpost\Models\User;

beforeEach(function () {
    test()->user = User::factory()->create();
});

test('imports affiliate analytics from csv', function () {
    $csv = implode("\n", [
        'platform,post_url,captured_at,title,product_name,disclosure_present,views,reactions,link_clicks,sales,revenue',
        'threads,https://www.threads.net/@example/post/abc,2026-08-25 21:00:00,Test post,Test product,true,1520,61,38,2,960',
    ]);

    $this->actingAs(test()->user)
        ->post(route('mixpost.affiliate-analytics.store'), [
            'file' => UploadedFile::fake()->createWithContent('analytics.csv', $csv),
        ])
        ->assertSessionHasNoErrors();

    $post = AffiliatePost::query()->first();

    expect($post)
        ->platform->toBe('threads')
        ->disclosure_present->toBeTrue()
        ->and($post->latestSnapshot->views)->toBe(1520)
        ->and($post->latestSnapshot->link_clicks)->toBe(38)
        ->and((float) $post->latestSnapshot->revenue)->toBe(960.0);
});

test('updates a snapshot when the same csv is imported again', function () {
    $header = 'platform,post_url,captured_at,views,reactions';
    $url = 'https://note.com/example/n/n123';

    foreach ([[$header, "note,$url,2026-08-25 21:00:00,100,5"], [$header, "note,$url,2026-08-25 21:00:00,180,9"]] as $rows) {
        $this->actingAs(test()->user)
            ->post(route('mixpost.affiliate-analytics.store'), [
                'file' => UploadedFile::fake()->createWithContent('analytics.csv', implode("\n", $rows)),
            ])
            ->assertSessionHasNoErrors();
    }

    expect(AffiliatePost::query()->count())->toBe(1)
        ->and(AffiliatePost::query()->first()->snapshots()->count())->toBe(1)
        ->and(AffiliatePost::query()->first()->latestSnapshot->views)->toBe(180);
});

test('renders affiliate analytics summary', function () {
    $post = AffiliatePost::query()->create([
        'platform' => 'threads',
        'post_url' => 'https://www.threads.net/@example/post/abc',
        'title' => 'Test post',
    ]);

    $post->snapshots()->create([
        'captured_at' => '2026-08-25 21:00:00',
        'views' => 120,
        'reactions' => 8,
        'link_clicks' => 3,
        'sales' => 1,
        'revenue' => 500,
    ]);

    $this->actingAs(test()->user);
    $this->publishAssets();

    $this->get(route('mixpost.affiliate-analytics.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('AffiliateAnalytics')
            ->where('summary.posts', 1)
            ->where('summary.views', 120)
            ->where('summary.link_clicks', 3)
            ->has('posts', 1)
        );
});
