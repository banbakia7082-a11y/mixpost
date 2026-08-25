<script setup>
import {Head, useForm} from '@inertiajs/vue3';
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";

defineProps({
    summary: Object,
    posts: Array,
});

const csvForm = useForm({file: null});
const threadsForm = useForm({payload: ''});

const submitCsv = () => {
    csvForm.post(route('mixpost.affiliate-analytics.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => csvForm.reset(),
    });
};

const submitThreads = () => {
    threadsForm.post(route('mixpost.affiliate-analytics.threads-browser.store'), {
        preserveScroll: true,
        onSuccess: () => threadsForm.reset(),
    });
};

const number = (value) => new Intl.NumberFormat('ja-JP').format(value || 0);
const money = (value) => new Intl.NumberFormat('ja-JP', {
    style: 'currency',
    currency: 'JPY',
    maximumFractionDigits: 0,
}).format(value || 0);
</script>

<template>
    <Head title="Affiliate Analytics"/>

    <div class="row-py w-full mx-auto">
        <PageHeader title="Affiliate Analytics">
            <template #description>
                Threads・note・アフィリエイト成果を、投稿単位で比較します。
            </template>
        </PageHeader>

        <div class="row-px grid grid-cols-2 xl:grid-cols-6 gap-md mb-xl">
            <div v-for="item in [
                ['投稿', number(summary.posts)],
                ['表示', number(summary.views)],
                ['反応', number(summary.reactions)],
                ['クリック', number(summary.link_clicks)],
                ['成果', number(summary.sales)],
                ['報酬', money(summary.revenue)],
            ]" :key="item[0]" class="bg-white border border-gray-200 rounded-lg p-lg">
                <div class="text-sm text-gray-500">{{ item[0] }}</div>
                <div class="text-2xl font-semibold mt-xs">{{ item[1] }}</div>
            </div>
        </div>

        <div class="row-px grid grid-cols-1 xl:grid-cols-2 gap-lg mb-xl">
            <form @submit.prevent="submitThreads" class="bg-white border border-gray-200 rounded-lg p-lg">
                <h2 class="font-semibold mb-xs">Threadsブラウザ取り込み</h2>
                <p class="text-sm text-gray-500 mb-md">
                    CodexがThreads画面から収集したJSONを貼り付けます。同じ計測日時は重複せず更新されます。
                </p>
                <textarea v-model="threadsForm.payload" rows="7"
                          placeholder='{"captured_at":"2026-08-25T21:00:00+09:00","posts":[...]}'
                          class="w-full rounded-md border-gray-300 font-mono text-sm"/>
                <button type="submit" :disabled="threadsForm.processing || !threadsForm.payload"
                        class="mt-md px-lg py-sm rounded-md bg-black text-white disabled:opacity-50">
                    {{ threadsForm.processing ? '取り込み中…' : 'Threadsデータを取り込む' }}
                </button>
                <p v-if="threadsForm.errors.payload" class="text-red-500 text-sm mt-sm">
                    {{ threadsForm.errors.payload }}
                </p>
            </form>

            <form @submit.prevent="submitCsv" class="bg-white border border-gray-200 rounded-lg p-lg">
                <h2 class="font-semibold mb-xs">CSV取り込み</h2>
                <p class="text-sm text-gray-500 mb-md">
                    必須列: platform, post_url, captured_at。noteや成果データの取り込みに使用できます。
                </p>
                <div class="flex flex-wrap items-center gap-md">
                    <input type="file" accept=".csv,text/csv" @change="csvForm.file = $event.target.files[0]"/>
                    <button type="submit" :disabled="csvForm.processing || !csvForm.file"
                            class="px-lg py-sm rounded-md bg-black text-white disabled:opacity-50">
                        {{ csvForm.processing ? '取り込み中…' : 'CSVを取り込む' }}
                    </button>
                </div>
                <p v-if="csvForm.errors.file" class="text-red-500 text-sm mt-sm">{{ csvForm.errors.file }}</p>
            </form>
        </div>

        <div class="row-px">
            <div class="bg-white border border-gray-200 rounded-lg overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="text-left p-md">投稿</th>
                        <th class="text-left p-md">商品</th>
                        <th class="text-right p-md">表示</th>
                        <th class="text-right p-md">反応</th>
                        <th class="text-right p-md">クリック</th>
                        <th class="text-right p-md">成果</th>
                        <th class="text-right p-md">報酬</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="post in posts" :key="post.uuid" class="border-t border-gray-100">
                        <td class="p-md min-w-[18rem]">
                            <div class="flex items-center gap-sm">
                                <span class="uppercase text-xs font-semibold text-gray-500">{{ post.platform }}</span>
                                <span v-if="post.disclosure_present" class="text-xs text-green-700">PR表記あり</span>
                            </div>
                            <a :href="post.post_url" target="_blank" class="font-medium hover:underline">
                                {{ post.title || post.content || post.post_url }}
                            </a>
                        </td>
                        <td class="p-md">{{ post.product_name || '—' }}</td>
                        <td class="p-md text-right">{{ number(post.metrics?.views) }}</td>
                        <td class="p-md text-right">{{ number(post.metrics?.reactions) }}</td>
                        <td class="p-md text-right">{{ number(post.metrics?.link_clicks) }}</td>
                        <td class="p-md text-right">{{ number(post.metrics?.sales) }}</td>
                        <td class="p-md text-right">{{ money(post.metrics?.revenue) }}</td>
                    </tr>
                    <tr v-if="!posts.length">
                        <td colspan="7" class="p-xl text-center text-gray-500">
                            まだデータがありません。Threads JSONまたはCSVを取り込むと分析を開始できます。
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
