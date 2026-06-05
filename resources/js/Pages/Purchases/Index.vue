<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    purchases: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters?.search || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');

let searchTimer = null;
watch([search, dateFrom, dateTo], () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route('purchases.index'), {
            search: search.value,
            date_from: dateFrom.value,
            date_to: dateTo.value,
        }, { preserveState: true, replace: true });
    }, 400);
});

function formatCurrency(value) {
    return 'Rs. ' + Number(value || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('si-LK');
}
</script>

<template>
    <Head title="මිලදී ගැනීම්" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-gray-800">මිලදී ගැනීම්</h1>
        </template>

        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <div class="flex-1 relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    v-model="search"
                    type="text"
                    placeholder="GRN අංකය සොයන්න..."
                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                />
            </div>
            <input
                v-model="dateFrom"
                type="date"
                class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
            />
            <input
                v-model="dateTo"
                type="date"
                class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
            />
            <Link
                :href="route('purchases.create')"
                class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors min-h-[44px] whitespace-nowrap"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                නව මිලදී ගැනීම
            </Link>
        </div>

        <!-- Mobile cards -->
        <div class="md:hidden space-y-3 mb-4">
            <div v-if="purchases.data?.length === 0" class="bg-white rounded-xl p-6 text-center text-gray-400">
                මිලදී ගැනීම් නොමැත
            </div>
            <div
                v-for="purchase in purchases.data"
                :key="purchase.id"
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-4"
            >
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="font-semibold text-gray-900">{{ purchase.grn_no }}</p>
                        <p class="text-sm text-gray-500">{{ purchase.supplier?.name }}</p>
                        <p class="text-xs text-gray-400">{{ formatDate(purchase.created_at) }}</p>
                    </div>
                    <p class="font-bold text-blue-600">{{ formatCurrency(purchase.total) }}</p>
                </div>
                <Link
                    :href="route('purchases.show', purchase.id)"
                    class="block text-center bg-gray-50 hover:bg-gray-100 text-gray-600 py-2 rounded-lg text-sm font-medium transition-colors min-h-[44px] leading-[44px]"
                >
                    බලන්න
                </Link>
            </div>
        </div>

        <!-- Desktop table -->
        <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-4">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50 border-b border-gray-100">
                            <th class="px-4 py-3">GRN අංකය</th>
                            <th class="px-4 py-3">සැපයුම්කරු</th>
                            <th class="px-4 py-3">භාණ්ඩ ගණන</th>
                            <th class="px-4 py-3">මුළු මුදල</th>
                            <th class="px-4 py-3">දිනය</th>
                            <th class="px-4 py-3 text-right">ක්‍රියා</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="purchases.data?.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">මිලදී ගැනීම් නොමැත</td>
                        </tr>
                        <tr
                            v-for="purchase in purchases.data"
                            :key="purchase.id"
                            class="hover:bg-gray-50 transition-colors"
                        >
                            <td class="px-4 py-3 font-medium text-blue-600">{{ purchase.grn_no }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ purchase.supplier?.name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ purchase.items_count || purchase.items?.length || '-' }}</td>
                            <td class="px-4 py-3 font-semibold text-blue-600">{{ formatCurrency(purchase.total) }}</td>
                            <td class="px-4 py-3 text-gray-500 text-sm">{{ formatDate(purchase.created_at) }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="route('purchases.show', purchase.id)"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium px-3 py-1.5 rounded hover:bg-blue-50 min-h-[36px] inline-flex items-center"
                                >
                                    බලන්න
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="purchases.links?.length > 3" class="flex flex-wrap justify-center gap-1">
            <template v-for="link in purchases.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="px-3 py-2 text-sm rounded-lg border transition-colors min-h-[44px] flex items-center"
                    :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="px-3 py-2 text-sm rounded-lg border border-gray-200 text-gray-400 min-h-[44px] flex items-center"
                    v-html="link.label"
                />
            </template>
        </div>
    </AuthenticatedLayout>
</template>
