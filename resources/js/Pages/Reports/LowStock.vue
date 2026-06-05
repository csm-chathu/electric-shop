<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    products: { type: Object, default: () => ({ data: [] }) },
});
</script>

<template>
    <Head title="අඩු තොග වාර්තාව" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('reports.index')" class="text-slate-400 hover:text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <h1 class="text-xl font-bold" style="color:#0F172A;">අඩු තොග — {{ products.total }} භාණ්ඩ</h1>
            </div>
        </template>

        <div class="bg-white rounded-xl shadow-sm" style="border:1px solid #E2E8F0;">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-xs text-slate-500 uppercase" style="background:#F8FAFC; border-bottom:1px solid #E2E8F0;">
                            <th class="px-4 py-3 text-left">භාණ්ඩය</th>
                            <th class="px-4 py-3 text-left">ගණය</th>
                            <th class="px-4 py-3 text-center">ඇති ප්‍රමාණය</th>
                            <th class="px-4 py-3 text-center">අනතුරු ප්‍රමාණය</th>
                            <th class="px-4 py-3 text-center">තත්ත්වය</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="products.data.length === 0"><td colspan="5" class="px-4 py-8 text-center text-slate-400">අඩු තොග නොමැත</td></tr>
                        <tr v-for="p in products.data" :key="p.id" class="hover:bg-slate-50" style="border-bottom:1px solid #F1F5F9;">
                            <td class="px-4 py-2.5">
                                <p class="font-medium" style="color:#0F172A;">{{ p.name }}</p>
                                <p v-if="p.name_si" class="text-xs text-slate-400">{{ p.name_si }}</p>
                            </td>
                            <td class="px-4 py-2.5 text-slate-500">{{ p.category?.name || '—' }}</td>
                            <td class="px-4 py-2.5 text-center font-bold" style="color:#DC2626;">
                                {{ Number(p.stock_qty).toFixed(2) }} {{ p.unit }}
                            </td>
                            <td class="px-4 py-2.5 text-center text-slate-500">
                                {{ Number(p.alert_qty).toFixed(2) }} {{ p.unit }}
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <span v-if="p.stock_qty <= 0"
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold text-white"
                                    style="background-color:#DC2626;">
                                    තොග නැත
                                </span>
                                <span v-else
                                    class="px-2 py-0.5 rounded-full text-xs font-semibold text-white"
                                    style="background-color:#F59E0B;">
                                    අඩුයි
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="products.last_page > 1" class="px-4 py-3 flex gap-2 flex-wrap" style="border-top:1px solid #E2E8F0;">
                <Link
                    v-for="link in products.links" :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    class="px-3 py-1 rounded text-sm"
                    :class="link.active ? 'text-white' : 'text-slate-600 hover:bg-slate-100'"
                    :style="link.active ? 'background-color:#2563EB' : ''"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
