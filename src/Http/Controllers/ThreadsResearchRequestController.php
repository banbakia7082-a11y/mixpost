<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inovector\Mixpost\Models\ThreadsResearchRequest;

class ThreadsResearchRequestController
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'keywords' => ['required', 'string', 'max:1000'],
            'posts_per_account' => ['required', 'integer', 'min:3', 'max:20'],
            'candidate_limit' => ['required', 'integer', 'min:1', 'max:10'],
            'frequency' => ['required', Rule::in(['manual', 'daily', 'weekly'])],
        ]);

        $keywords = collect(preg_split('/[,、\r\n]+/u', $validated['keywords']))
            ->map(fn ($keyword) => trim($keyword))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($keywords === []) {
            return back()->withErrors(['keywords' => '検索語を1つ以上入力してください。']);
        }

        ThreadsResearchRequest::query()->create([
            ...$validated,
            'keywords' => $keywords,
            'status' => 'queued',
            'next_run_at' => now(),
        ]);

        return back()->with('success', 'Threads調査を処理待ちに追加しました。');
    }

    public function run(ThreadsResearchRequest $threadsResearchRequest): RedirectResponse
    {
        $threadsResearchRequest->update(['status' => 'queued', 'next_run_at' => now(), 'last_error' => null]);

        return back()->with('success', 'Threads調査を再実行待ちにしました。');
    }
}
