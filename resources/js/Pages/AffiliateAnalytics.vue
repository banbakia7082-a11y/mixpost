<script setup>
import {Head, useForm} from '@inertiajs/vue3';
import PageHeader from "@/Components/DataDisplay/PageHeader.vue";

defineProps({
    summary: Object,
    posts: Array,
    referenceAccounts: Array,
    researchRequests: Array,
    affiliateProducts: Array,
    affiliateDrafts: Array,
});

const csvForm = useForm({file: null});
const threadsForm = useForm({payload: ''});
const referenceForm = useForm({handle: '', display_name: ''});
const researchForm = useForm({
    topic: '暮らしを少し楽にする愛用品',
    keywords: '買ってよかった、愛用品、便利グッズ、美容、日用品',
    posts_per_account: 10,
    candidate_limit: 5,
    frequency: 'weekly',
});
const productForm = useForm({
    name: '', category: '', network: 'Amazon', affiliate_url: '', actual_scene: '', benefit: '', drawback: '',
});

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

const submitReference = () => {
    referenceForm.post(route('mixpost.affiliate-analytics.reference-accounts.store'), {
        preserveScroll: true,
        onSuccess: () => referenceForm.reset(),
    });
};

const submitResearch = () => {
    researchForm.post(route('mixpost.affiliate-analytics.research-requests.store'), {preserveScroll: true});
};

const runResearch = (request) => {
    useForm({}).post(route('mixpost.affiliate-analytics.research-requests.run', request.uuid), {preserveScroll: true});
};

const submitProduct = () => {
    productForm.post(route('mixpost.affiliate-analytics.products.store'), {
        preserveScroll: true,
        onSuccess: () => productForm.reset('name', 'category', 'affiliate_url', 'actual_scene', 'benefit', 'drawback'),
    });
};

const generateDrafts = (product) => {
    useForm({}).post(route('mixpost.affiliate-analytics.products.drafts.generate', product.uuid), {preserveScroll: true});
};

const updateDraft = (draft, status) => {
    useForm({parent_post: draft.parent_post, reply_post: draft.reply_post, status})
        .put(route('mixpost.affiliate-analytics.drafts.update', draft.uuid), {preserveScroll: true});
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
            <section class="bg-white border border-gray-200 rounded-lg p-lg xl:col-span-2">
                <h2 class="font-semibold mb-xs">アフィリエイト投稿案を作る</h2>
                <p class="text-sm text-gray-500 mb-md">確認できる実体験だけを登録します。リンクとPR表記は返信案へ分離し、自動公開はしません。</p>
                <form @submit.prevent="submitProduct" class="grid grid-cols-1 md:grid-cols-2 gap-md">
                    <input v-model="productForm.name" required placeholder="商品名" class="rounded-md border-gray-300"/>
                    <input v-model="productForm.category" placeholder="カテゴリ（任意）" class="rounded-md border-gray-300"/>
                    <input v-model="productForm.network" required placeholder="Amazonなど" class="rounded-md border-gray-300"/>
                    <input v-model="productForm.affiliate_url" required type="url" placeholder="アフィリエイトリンク" class="rounded-md border-gray-300"/>
                    <textarea v-model="productForm.actual_scene" required rows="3" placeholder="実際に使った場面（事実のみ）" class="rounded-md border-gray-300"/>
                    <textarea v-model="productForm.benefit" required rows="3" placeholder="実際に便利だった点" class="rounded-md border-gray-300"/>
                    <textarea v-model="productForm.drawback" required rows="3" placeholder="正直な欠点・向かない人" class="rounded-md border-gray-300"/>
                    <button type="submit" :disabled="productForm.processing" class="px-lg py-sm rounded-md bg-black text-white disabled:opacity-50">商品と実体験を登録</button>
                </form>
                <div class="mt-lg flex flex-wrap gap-sm">
                    <button v-for="product in affiliateProducts" :key="product.uuid" @click="generateDrafts(product)"
                            class="rounded-lg bg-gray-100 px-md py-sm text-sm hover:bg-gray-200">
                        「{{ product.name }}」の投稿案を3件作る
                    </button>
                    <span v-if="!affiliateProducts.length" class="text-sm text-gray-500">商品を登録すると投稿案を作れます。</span>
                </div>
            </section>

            <section v-if="affiliateDrafts.length" class="bg-white border border-gray-200 rounded-lg p-lg xl:col-span-2">
                <h2 class="font-semibold mb-md">確認待ちの投稿案</h2>
                <div class="space-y-lg">
                    <article v-for="draft in affiliateDrafts" :key="draft.uuid" class="border rounded-lg p-md">
                        <div class="flex flex-wrap justify-between gap-sm mb-sm">
                            <strong>{{ draft.product_name }}／{{ draft.angle }}</strong>
                            <span class="text-xs text-gray-500">{{ draft.status }}</span>
                        </div>
                        <label class="text-xs text-gray-500">親投稿（リンクなし）</label>
                        <textarea v-model="draft.parent_post" rows="4" class="mt-xs w-full rounded-md border-gray-300"/>
                        <label class="mt-md block text-xs text-gray-500">返信（PR表記・リンク）</label>
                        <textarea v-model="draft.reply_post" rows="4" class="mt-xs w-full rounded-md border-gray-300"/>
                        <p class="mt-sm text-xs text-gray-500">{{ draft.generation_reason }}</p>
                        <div v-if="draft.evidence_posts?.length" class="mt-sm text-xs text-gray-500">
                            根拠候補：
                            <a v-for="(post, index) in draft.evidence_posts" :key="post.url" :href="post.url" target="_blank" class="hover:underline mr-sm">
                                {{ index + 1 }}. {{ number(post.views) }}表示
                            </a>
                        </div>
                        <div class="mt-sm flex flex-wrap gap-xs text-xs">
                            <span v-for="(score, key) in draft.scores" :key="key" class="rounded bg-gray-100 px-sm py-xs">{{ key }} {{ score }}/10</span>
                        </div>
                        <div class="mt-md flex gap-sm">
                            <button @click="updateDraft(draft, 'draft')" class="rounded-md bg-gray-100 px-md py-xs">編集を保存</button>
                            <button @click="updateDraft(draft, 'approved')" class="rounded-md bg-green-700 text-white px-md py-xs">採用</button>
                            <button @click="updateDraft(draft, 'rejected')" class="rounded-md bg-red-50 text-red-700 px-md py-xs">不採用</button>
                        </div>
                    </article>
                </div>
            </section>

            <section class="bg-white border border-gray-200 rounded-lg p-lg xl:col-span-2">
                <h2 class="font-semibold mb-xs">参考アカウントを自動調査</h2>
                <p class="text-sm text-gray-500 mb-md">テーマから候補を探し、各アカウントの直近投稿を比較する調査依頼を作成します。</p>
                <form @submit.prevent="submitResearch" class="grid grid-cols-1 md:grid-cols-2 gap-md">
                    <input v-model="researchForm.topic" required placeholder="調査テーマ" class="rounded-md border-gray-300"/>
                    <input v-model="researchForm.keywords" required placeholder="検索語（読点区切り）" class="rounded-md border-gray-300"/>
                    <label class="text-sm">1アカウントの投稿数
                        <input v-model="researchForm.posts_per_account" type="number" min="3" max="20" class="mt-xs w-full rounded-md border-gray-300"/>
                    </label>
                    <label class="text-sm">候補アカウント数
                        <input v-model="researchForm.candidate_limit" type="number" min="1" max="10" class="mt-xs w-full rounded-md border-gray-300"/>
                    </label>
                    <select v-model="researchForm.frequency" class="rounded-md border-gray-300">
                        <option value="manual">手動のみ</option><option value="daily">毎日</option><option value="weekly">週1回</option>
                    </select>
                    <button type="submit" :disabled="researchForm.processing" class="px-lg py-sm rounded-md bg-black text-white disabled:opacity-50">調査を登録</button>
                </form>
                <div class="mt-lg space-y-sm">
                    <div v-for="request in researchRequests" :key="request.uuid" class="flex flex-wrap items-center justify-between gap-sm border-t pt-sm text-sm">
                        <div>
                            <strong>{{ request.topic }}</strong><span class="text-gray-500 ml-sm">{{ request.keywords.join('・') }}／{{ request.status }}</span>
                            <div v-if="request.last_error" class="text-red-600 text-xs mt-xs">{{ request.last_error }}（試行 {{ request.attempts }}/3）</div>
                        </div>
                        <button @click="runResearch(request)" class="rounded-md bg-gray-100 px-md py-xs">今すぐ実行待ちにする</button>
                    </div>
                </div>
            </section>

            <section class="bg-white border border-gray-200 rounded-lg p-lg">
                <h2 class="font-semibold mb-xs">参考アカウント</h2>
                <p class="text-sm text-gray-500 mb-md">分析したいThreadsアカウントを登録します。</p>
                <form @submit.prevent="submitReference" class="flex flex-wrap gap-sm">
                    <input v-model="referenceForm.handle" required placeholder="@threads"
                           class="rounded-md border-gray-300"/>
                    <input v-model="referenceForm.display_name" placeholder="表示名（任意）"
                           class="rounded-md border-gray-300"/>
                    <button type="submit" :disabled="referenceForm.processing"
                            class="px-lg py-sm rounded-md bg-black text-white disabled:opacity-50">追加</button>
                </form>
                <p v-if="referenceForm.errors.handle" class="text-red-500 text-sm mt-sm">{{ referenceForm.errors.handle }}</p>
                <div class="flex flex-wrap gap-sm mt-md">
                    <a v-for="account in referenceAccounts" :key="account.uuid" :href="account.profile_url" target="_blank"
                       class="rounded-lg bg-gray-100 px-md py-sm text-sm hover:bg-gray-200">
                        <strong>@{{ account.handle }}</strong>（{{ number(account.posts_count) }}件）<br>
                        <span class="text-xs text-gray-500">中央値 {{ account.median_views == null ? '—' : number(account.median_views) }}表示・{{ account.median_engagement_rate == null ? '—' : account.median_engagement_rate + '%' }}／{{ account.assessment }}</span>
                    </a>
                    <span v-if="!referenceAccounts.length" class="text-sm text-gray-500">まだ登録されていません。</span>
                </div>
            </section>

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

            <form @submit.prevent="submitCsv" class="bg-white border border-gray-200 rounded-lg p-lg xl:col-span-2">
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
                        <th class="text-right p-md">反応率</th>
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
                            <div v-if="post.reference_account" class="text-xs text-gray-500 mb-xs">
                                @{{ post.reference_account.handle }}
                            </div>
                            <a :href="post.post_url" target="_blank" class="font-medium hover:underline">
                                {{ post.title || post.content || post.post_url }}
                            </a>
                        </td>
                        <td class="p-md">{{ post.product_name || '—' }}</td>
                        <td class="p-md text-right">{{ number(post.metrics?.views) }}</td>
                        <td class="p-md text-right">{{ number(post.metrics?.reactions) }}</td>
                        <td class="p-md text-right">
                            {{ post.metrics?.engagement_rate == null ? '—' : `${post.metrics.engagement_rate}%` }}
                        </td>
                        <td class="p-md text-right">{{ number(post.metrics?.link_clicks) }}</td>
                        <td class="p-md text-right">{{ number(post.metrics?.sales) }}</td>
                        <td class="p-md text-right">{{ money(post.metrics?.revenue) }}</td>
                    </tr>
                    <tr v-if="!posts.length">
                        <td colspan="8" class="p-xl text-center text-gray-500">
                            まだデータがありません。Threads JSONまたはCSVを取り込むと分析を開始できます。
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
