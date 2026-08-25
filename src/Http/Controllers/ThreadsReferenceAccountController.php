<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inovector\Mixpost\Models\ThreadsReferenceAccount;

class ThreadsReferenceAccountController
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'handle' => ['required', 'string', 'max:255', 'regex:/^@?[A-Za-z0-9._]+$/'],
            'display_name' => ['nullable', 'string', 'max:255'],
        ]);

        $handle = ltrim(strtolower($validated['handle']), '@');

        validator(['handle' => $handle], [
            'handle' => [Rule::unique('mixpost_threads_reference_accounts', 'handle')],
        ])->validate();

        ThreadsReferenceAccount::query()->create([
            'handle' => $handle,
            'display_name' => $validated['display_name'] ?? null,
            'profile_url' => "https://www.threads.com/@{$handle}",
        ]);

        return back()->with('success', "@{$handle}を参考アカウントに追加しました。");
    }

    public function destroy(ThreadsReferenceAccount $threadsReferenceAccount): RedirectResponse
    {
        $threadsReferenceAccount->delete();

        return back()->with('success', '参考アカウントを削除しました。投稿データは残ります。');
    }
}
