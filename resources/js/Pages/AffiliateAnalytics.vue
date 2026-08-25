<script setup>
import {Head, useForm} from '@inertiajs/vue3';
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";

defineProps({
    summary: Object,
    posts: Array,
});

const form = useForm({file: null});

const submit = () => {
    form.post(route('mixpost.affiliate-analytics.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => form.reset(),
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

        <div class="row-px mb-xl">
            <form @submit.prevent="submit" class="bg-white border border-gray-200 rounded-lg p-lg">
                <h2 class="font-semibold mb-xs">CSV取り込み</h2>
                <p class="text-sm text-gray-500 mb-md">
                    必須列: platform, post_url, captured_at。複数回取り込むと最新値を更新します。
                </p>
                <div class="flex flex-wrap items-center gap-md">
                    <input type="file" accept=".csv,text/csv" @change="form.file = $event.target.files[0]"/>
                    <button type="submit" :disabled="form.processing || !form.file"
                            class="px-lg py-sm rounded-md bg-black text-white disabled:opacity-50">
                        {{ form.processing ? '取り込み中…' : '取り込む' }}
                    </button>
                </div>
                <p v-if="form.errors.file" class="text-red-500 text-sm mt-sm">{{ form.errors.file }}</p>
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
                            まだデータがありません。CSVを取り込むと分析を開始できます。
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
