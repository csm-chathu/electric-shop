<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    summary:         { type: Object, default: () => ({}) },
    byPaymentMethod: { type: Array,  default: () => [] },
    sales:           { type: Array,  default: () => [] },
    date:            { type: String, default: '' },
});

function fmt(v) {
    return 'Rs. ' + Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function fmtTime(d) {
    return d ? new Date(d).toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' }) : '';
}
const methodLabel = { cash: 'මුදල්', card: 'කාඩ්', qr: 'QR', credit: 'ණය' };
</script>

<template>
    <Head title="අද විකුණුම් වාර්තාව" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('reports.index')" class="text-slate-400 hover:text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <h1 class="text-xl font-bold" style="color:#0F172A;">අද විකුණුම් — {{ date }}</h1>
            </div>
        </template>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl p-4 shadow-sm" style="border:1px solid #E2E8F0;">
                <p class="text-xs text-slate-500 mb-1">මුළු විකුණුම්</p>
                <p class="text-2xl font-bold" style="color:#2563EB;">{{ summary.total_sales }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm" style="border:1px solid #E2E8F0;">
                <p class="text-xs text-slate-500 mb-1">මුළු ආදායම</p>
                <p class="text-xl font-bold" style="color:#16A34A;">{{ fmt(summary.total_revenue) }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm" style="border:1px solid #E2E8F0;">
                <p class="text-xs text-slate-500 mb-1">මුළු වට්ටම</p>
                <p class="text-xl font-bold" style="color:#F59E0B;">{{ fmt(summary.total_discount) }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm" style="border:1px solid #E2E8F0;">
                <p class="text-xs text-slate-500 mb-1">ගෙවීම් ක්‍රම</p>
                <p class="text-xl font-bold" style="color:#0F172A;">{{ byPaymentMethod.length }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Payment method breakdown -->
            <div class="bg-white rounded-xl shadow-sm" style="border:1px solid #E2E8F0;">
                <div class="px-4 py-3" style="border-bottom:1px solid #E2E8F0;">
                    <h2 class="font-semibold" style="color:#0F172A;">ගෙවීම් ක්‍රම අනුව</h2>
                </div>
                <div class="p-4 space-y-3">
                    <div v-if="byPaymentMethod.length === 0" class="text-center text-slate-400 py-6">විකුණුම් නොමැත</div>
                    <div v-for="pm in byPaymentMethod" :key="pm.method" class="flex justify-between items-center py-2" style="border-bottom:1px solid #F1F5F9;">
                        <div>
                            <p class="font-medium" style="color:#0F172A;">{{ methodLabel[pm.method] || pm.method }}</p>
                            <p class="text-xs text-slate-500">{{ pm.count }} ගනුදෙනු</p>
                        </div>
                        <p class="font-bold" style="color:#16A34A;">{{ fmt(pm.total) }}</p>
                    </div>
                </div>
            </div>

            <!-- Sales list -->
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm" style="border:1px solid #E2E8F0;">
                <div class="px-4 py-3" style="border-bottom:1px solid #E2E8F0;">
                    <h2 class="font-semibold" style="color:#0F172A;">විකුණුම් ලැයිස්තුව</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-xs text-slate-500 uppercase" style="background:#F8FAFC; border-bottom:1px solid #E2E8F0;">
                                <th class="px-4 py-3 text-left">ඉන්වොයිස්</th>
                                <th class="px-4 py-3 text-left">කාෂියර්</th>
                                <th class="px-4 py-3 text-right">මුළු</th>
                                <th class="px-4 py-3 text-right">වේලාව</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="sales.length === 0"><td colspan="4" class="px-4 py-8 text-center text-slate-400">විකුණුම් නොමැත</td></tr>
                            <tr v-for="sale in sales" :key="sale.id" class="hover:bg-slate-50" style="border-bottom:1px solid #F1F5F9;">
                                <td class="px-4 py-2.5 font-medium" style="color:#2563EB;">
                                    <Link :href="route('sales.show', sale.id)">{{ sale.invoice_no }}</Link>
                                </td>
                                <td class="px-4 py-2.5 text-slate-600">{{ sale.user?.name }}</td>
                                <td class="px-4 py-2.5 text-right font-semibold" style="color:#16A34A;">{{ fmt(sale.total) }}</td>
                                <td class="px-4 py-2.5 text-right text-slate-400 text-sm">{{ fmtTime(sale.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
