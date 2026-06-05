<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    items:     { type: Array,  default: () => [] },
    summary:   { type: Object, default: () => ({}) },
    date_from: { type: String, default: '' },
    date_to:   { type: String, default: '' },
});

const from = ref(props.date_from);
const to   = ref(props.date_to);

function fmt(v) {
    return 'Rs. ' + Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function filter() {
    router.get(route('reports.profit'), { date_from: from.value, date_to: to.value }, { preserveScroll: true });
}
</script>

<template>
    <Head title="ලාභ වාර්තාව" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3 flex-wrap">
                <Link :href="route('reports.index')" class="text-slate-400 hover:text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <h1 class="text-xl font-bold" style="color:#0F172A;">ලාභ වාර්තාව</h1>
                <div class="ml-auto flex items-center gap-2">
                    <input type="date" v-model="from" class="rounded-lg px-3 py-1.5 text-sm outline-none" style="border:1px solid #E2E8F0;" />
                    <span class="text-slate-400">—</span>
                    <input type="date" v-model="to" class="rounded-lg px-3 py-1.5 text-sm outline-none" style="border:1px solid #E2E8F0;" />
                    <button @click="filter" class="px-4 py-1.5 text-sm text-white rounded-lg font-medium" style="background-color:#2563EB;">බලන්න</button>
                </div>
            </div>
        </template>

        <!-- Summary cards -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl p-4 shadow-sm" style="border:1px solid #E2E8F0;">
                <p class="text-xs text-slate-500 mb-1">මුළු ආදායම</p>
                <p class="text-xl font-bold" style="color:#2563EB;">{{ fmt(summary.total_revenue) }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm" style="border:1px solid #E2E8F0;">
                <p class="text-xs text-slate-500 mb-1">මුළු පිරිවැය</p>
                <p class="text-xl font-bold" style="color:#DC2626;">{{ fmt(summary.total_cost) }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm" style="border:1px solid #E2E8F0;">
                <p class="text-xs text-slate-500 mb-1">ලාභය</p>
                <p class="text-xl font-bold" :style="summary.gross_profit >= 0 ? 'color:#16A34A' : 'color:#DC2626'">
                    {{ fmt(summary.gross_profit) }}
                </p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm" style="border:1px solid #E2E8F0;">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-xs text-slate-500 uppercase" style="background:#F8FAFC; border-bottom:1px solid #E2E8F0;">
                            <th class="px-4 py-3 text-left">භාණ්ඩය</th>
                            <th class="px-4 py-3 text-center">ප්‍රමාණය</th>
                            <th class="px-4 py-3 text-right">ආදායම</th>
                            <th class="px-4 py-3 text-right">පිරිවැය</th>
                            <th class="px-4 py-3 text-right">ලාභය</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="items.length === 0"><td colspan="5" class="px-4 py-8 text-center text-slate-400">දත්ත නොමැත</td></tr>
                        <tr v-for="item in items" :key="item.product_id" class="hover:bg-slate-50" style="border-bottom:1px solid #F1F5F9;">
                            <td class="px-4 py-2.5 font-medium" style="color:#0F172A;">{{ item.product_name }}</td>
                            <td class="px-4 py-2.5 text-center text-slate-500">{{ Number(item.total_qty).toFixed(2) }}</td>
                            <td class="px-4 py-2.5 text-right" style="color:#2563EB;">{{ fmt(item.total_revenue) }}</td>
                            <td class="px-4 py-2.5 text-right" style="color:#DC2626;">{{ fmt(item.total_cost) }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold"
                                :style="item.gross_profit >= 0 ? 'color:#16A34A' : 'color:#DC2626'">
                                {{ fmt(item.gross_profit) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
