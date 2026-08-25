<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inovector\Mixpost\Models\AffiliatePost;
use Inovector\Mixpost\Models\ThreadsReferenceAccount;

class ImportThreadsBrowserDataController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payload' => ['required', 'string', 'max:1000000'],
        ]);

        $decoded = json_decode($validated['payload'], true);

        if (! is_array($decoded)) {
            return back()->withErrors(['payload' => 'JSONの形式を確認してください。']);
        }

        $validator = validator($decoded, [
            'captured_at' => ['required', 'date'],
            'posts' => ['required', 'array', 'min:1', 'max:250'],
            'posts.*.post_url' => ['required', 'url', 'max:500'],
            'posts.*.author_handle' => ['nullable', 'string', 'max:255'],
            'posts.*.external_post_id' => ['nullable', 'string', 'max:255'],
            'posts.*.content' => ['nullable', 'string'],
            'posts.*.published_at' => ['nullable', 'date'],
            'posts.*.product_name' => ['nullable', 'string', 'max:255'],
            'posts.*.product_category' => ['nullable', 'string', 'max:255'],
            'posts.*.affiliate_network' => ['nullable', 'string', 'max:255'],
            'posts.*.affiliate_url' => ['nullable', 'url'],
            'posts.*.post_type' => ['nullable', 'string', 'max:255'],
            'posts.*.image_type' => ['nullable', 'string', 'max:255'],
            'posts.*.disclosure_present' => ['nullable', 'boolean'],
            'posts.*.views' => ['nullable', 'integer', 'min:0'],
            'posts.*.reactions' => ['nullable', 'integer', 'min:0'],
            'posts.*.replies' => ['nullable', 'integer', 'min:0'],
            'posts.*.reposts' => ['nullable', 'integer', 'min:0'],
            'posts.*.quotes' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors(['payload' => $validator->errors()->first()]);
        }

        $data = $validator->validated();
        $capturedAt = Carbon::parse($data['captured_at']);
        $imported = 0;

        DB::transaction(function () use ($data, $capturedAt, &$imported) {
            foreach ($data['posts'] as $row) {
                $handle = ltrim(strtolower((string) ($row['author_handle'] ?? '')), '@');
                $referenceAccountId = $handle === '' ? null : ThreadsReferenceAccount::query()
                    ->where('handle', $handle)
                    ->value('id');

                $post = AffiliatePost::query()->updateOrCreate(
                    [
                        'threads_reference_account_id' => $referenceAccountId,
                        'platform' => 'threads',
                        'post_url' => $row['post_url'],
                    ],
                    [
                        'external_post_id' => $row['external_post_id'] ?? null,
                        'content' => $row['content'] ?? null,
                        'product_name' => $row['product_name'] ?? null,
                        'product_category' => $row['product_category'] ?? null,
                        'affiliate_network' => $row['affiliate_network'] ?? null,
                        'affiliate_url' => $row['affiliate_url'] ?? null,
                        'post_type' => $row['post_type'] ?? null,
                        'image_type' => $row['image_type'] ?? null,
                        'disclosure_present' => $row['disclosure_present'] ?? false,
                        'published_at' => isset($row['published_at'])
                            ? Carbon::parse($row['published_at'])
                            : null,
                    ]
                );

                $post->snapshots()->updateOrCreate(
                    ['captured_at' => $capturedAt],
                    [
                        'views' => $row['views'] ?? null,
                        'reactions' => $row['reactions'] ?? null,
                        'replies' => $row['replies'] ?? null,
                        'reposts' => $row['reposts'] ?? null,
                        'quotes' => $row['quotes'] ?? null,
                        'raw_metrics' => [
                            'source' => 'codex_browser',
                            'captured_at' => $capturedAt->toIso8601String(),
                        ],
                    ]
                );

                $imported++;
            }
        });

        return back()->with('success', "{$imported}件のThreads投稿データを取り込みました。");
    }
}
