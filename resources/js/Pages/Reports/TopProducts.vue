<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    topProducts: { type: Array,  default: () => [] },
    from:        { type: String, default: '' },
    to:          { type: String, default: '' },
});

function fmt(v) {
    return 'Rs. ' + Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>

<template>
    <Head title="වැඩියෙන් විකිණෙන භාණ්ඩ" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('reports.index')" class="text-slate-400 hover:text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <h1 class="text-xl font-bold" style="color:#0F172A;">වැඩියෙන් විකිණෙන භාණ්ඩ</h1>
                <span class="text-sm text-slate-400">{{ from }} — {{ to }}</span>
            </div>
        </template>

        <div class="bg-white rounded-xl shadow-sm" style="border:1px solid #E2E8F0;">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-xs text-slate-500 uppercase" style="background:#F8FAFC; border-bottom:1px solid #E2E8F0;">
                            <th class="px-4 py-3 text-center w-10">#</th>
                            <th class="px-4 py-3 text-left">භාණ්ඩය</th>
                            <th class="px-4 py-3 text-center">විකිණි ගණ</th>
                            <th class="px-4 py-3 text-center">ගනුදෙනු</th>
                            <th class="px-4 py-3 text-right">ආදායම</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="topProducts.length === 0"><td colspan="5" class="px-4 py-8 text-center text-slate-400">දත්ත නොමැත</td></tr>
                        <tr v-for="(p, i) in topProducts" :key="p.product_id" class="hover:bg-slate-50" style="border-bottom:1px solid #F1F5F9;">
                            <td class="px-4 py-2.5 text-center">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold text-white"
                                    :style="i < 3 ? 'background-color:#2563EB' : 'background-color:#94A3B8'">
                                    {{ i + 1 }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 font-medium" style="color:#0F172A;">{{ p.product_name }}</td>
                            <td class="px-4 py-2.5 text-center font-semibold" style="color:#2563EB;">{{ Number(p.total_qty).toFixed(2) }}</td>
                            <td class="px-4 py-2.5 text-center text-slate-500">{{ p.sale_count }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold" style="color:#16A34A;">{{ fmt(p.total_revenue) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
