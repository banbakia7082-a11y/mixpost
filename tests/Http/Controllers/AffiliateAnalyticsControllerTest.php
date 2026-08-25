<?php

use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Inovector\Mixpost\Models\AffiliatePost;
use Inovector\Mixpost\Models\AffiliateDraft;
use Inovector\Mixpost\Models\AffiliateProduct;
use Inovector\Mixpost\Models\User;
use Inovector\Mixpost\Models\ThreadsReferenceAccount;
use Inovector\Mixpost\Models\ThreadsResearchRequest;
use Inovector\Mixpost\Services\AffiliateContentAnalyzer;

beforeEach(function () {
    test()->user = User::factory()->create();
});

test('classifies a Threads post with inspectable evidence', function () {
    $analysis = app(AffiliateContentAnalyzer::class)->analyze("駅に入ったとき片手で畳めました。\nただ、普通の傘より少し重いです。");

    expect($analysis['hook'])->toBe('statement')
        ->and($analysis['angle'])->toBe('comparison')
        ->and($analysis['structure']['has_drawback'])->toBeTrue()
        ->and($analysis['evidence'])->toHaveCount(2)
        ->and($analysis['method'])->toBe('deterministic-rules');
});

test('creates reviewable affiliate drafts without placing the link in the parent post', function () {
    $this->actingAs(test()->user)->post(route('mixpost.affiliate-analytics.products.store'), [
        'name' => '折りたたみ傘',
        'category' => '日用品',
        'network' => 'Amazon',
        'affiliate_url' => 'https://example.com/affiliate',
        'actual_scene' => '駅に入ったとき片手で畳めました。',
        'benefit' => '移動を止めずにしまえます。',
        'drawback' => '一般的な傘より少し重いです。',
    ])->assertSessionHasNoErrors();

    $product = AffiliateProduct::query()->firstOrFail();
    $this->actingAs(test()->user)
        ->post(route('mixpost.affiliate-analytics.products.drafts.generate', $product))
        ->assertSessionHasNoErrors();

    expect(AffiliateDraft::query()->count())->toBe(3);
    AffiliateDraft::query()->each(function (AffiliateDraft $draft) {
        expect($draft->parent_post)->not->toContain('https://example.com/affiliate')
            ->and($draft->reply_post)->toContain('［PR・Amazonアフィリエイト］')
            ->and($draft->reply_post)->toContain('https://example.com/affiliate')
            ->and($draft->scores['disclosure'])->toBe(10);
    });
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


test('imports Threads data collected by Codex browser', function () {
    $payload = [
        'captured_at' => '2026-08-25T21:00:00+09:00',
        'posts' => [
            [
                'post_url' => 'https://www.threads.net/@example/post/abc',
                'external_post_id' => 'abc',
                'content' => 'Test Threads post',
                'published_at' => '2026-08-24T08:30:00+09:00',
                'disclosure_present' => true,
                'views' => 1520,
                'reactions' => 61,
                'replies' => 7,
                'reposts' => 4,
                'quotes' => 1,
            ],
        ],
    ];

    $this->actingAs(test()->user)
        ->post(route('mixpost.affiliate-analytics.threads-browser.store'), [
            'payload' => json_encode($payload),
        ])
        ->assertSessionHasNoErrors();

    $post = AffiliatePost::query()->first();

    expect($post)
        ->platform->toBe('threads')
        ->external_post_id->toBe('abc')
        ->disclosure_present->toBeTrue()
        ->and($post->latestSnapshot->views)->toBe(1520)
        ->and($post->latestSnapshot->reactions)->toBe(61)
        ->and($post->latestSnapshot->raw_metrics['source'])->toBe('codex_browser');
});

test('rejects invalid Threads browser payload', function () {
    $this->actingAs(test()->user)
        ->post(route('mixpost.affiliate-analytics.threads-browser.store'), [
            'payload' => json_encode([
                'captured_at' => '2026-08-25T21:00:00+09:00',
                'posts' => [['post_url' => 'not-a-url']],
            ]),
        ])
        ->assertSessionHasErrors('payload');

    expect(AffiliatePost::query()->count())->toBe(0);
});

test('registers a Threads reference account and links imported posts', function () {
    $this->actingAs(test()->user)
        ->post(route('mixpost.affiliate-analytics.reference-accounts.store'), [
            'handle' => '@Example.Account',
            'display_name' => 'Example',
        ])
        ->assertSessionHasNoErrors();

    $account = ThreadsReferenceAccount::query()->first();

    expect($account->handle)->toBe('example.account')
        ->and($account->profile_url)->toBe('https://www.threads.com/@example.account');

    $payload = [
        'captured_at' => '2026-08-25T21:00:00+09:00',
        'posts' => [[
            'post_url' => 'https://www.threads.com/@example.account/post/abc',
            'author_handle' => '@EXAMPLE.ACCOUNT',
            'views' => 1000,
            'reactions' => 50,
            'replies' => 10,
            'reposts' => 5,
        ]],
    ];

    $this->post(route('mixpost.affiliate-analytics.threads-browser.store'), [
        'payload' => json_encode($payload),
    ])->assertSessionHasNoErrors();

    expect(AffiliatePost::query()->first()->threads_reference_account_id)->toBe($account->id);

    $this->publishAssets();
    $this->get(route('mixpost.affiliate-analytics.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('referenceAccounts.0.handle', 'example.account')
            ->where('posts.0.reference_account.handle', 'example.account')
            ->where('posts.0.metrics.engagement_rate', 6.5)
        );
});

test('queues a Threads research request', function () {
    $this->actingAs(test()->user)
        ->post(route('mixpost.affiliate-analytics.research-requests.store'), [
            'topic' => '暮らしの愛用品',
            'keywords' => "買ってよかった、愛用品\n便利グッズ",
            'posts_per_account' => 10,
            'candidate_limit' => 5,
            'frequency' => 'weekly',
        ])->assertSessionHasNoErrors();

    $request = ThreadsResearchRequest::query()->first();
    expect($request->status)->toBe('queued')
        ->and($request->keywords)->toBe(['買ってよかった', '愛用品', '便利グッズ'])
        ->and($request->next_run_at)->not->toBeNull();
});
