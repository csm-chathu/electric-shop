<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    plans:   { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');

let searchTimer = null;
watch([search, status], () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route('installments.index'), {
            search: search.value,
            status: status.value,
        }, { preserveState: true, replace: true });
    }, 400);
});

const statusMeta = {
    active:    { label: 'Active',    cls: 'bg-blue-100 text-blue-700' },
    completed: { label: 'Completed', cls: 'bg-green-100 text-green-700' },
    defaulted: { label: 'Defaulted', cls: 'bg-red-100 text-red-700' },
    cancelled: { label: 'Cancelled', cls: 'bg-gray-100 text-gray-500' },
};

function fmt(v) {
    return 'Rs. ' + Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function paidAmount(plan) {
    return plan.payments?.reduce((s, p) => s + Number(p.amount_paid || 0), 0) ?? 0;
}

function nextDue(plan) {
    const pending = (plan.payments || [])
        .filter(p => p.status !== 'paid')
        .sort((a, b) => a.installment_no - b.installment_no)[0];
    return pending ? pending.due_date : null;
}

function fmtDate(val) {
    if (!val) return null;
    const d = new Date(val);
    if (isNaN(d)) return String(val).slice(0, 10);
    return d.toLocaleDateString('en-LK', { year: 'numeric', month: 'short', day: '2-digit' });
}

function isOverdue(dateStr) {
    return dateStr && new Date(dateStr) < new Date(new Date().toDateString());
}
</script>

<template>
    <Head title="Installment Plans" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h1 class="text-xl font-bold text-gray-800">Installment Plans</h1>
                <Link :href="route('installments.create')"
                    class="flex items-center gap-2 text-white px-4 py-2 rounded-lg text-sm font-semibold"
                    style="background-color:#2563EB;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Plan
                </Link>
            </div>
        </template>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 mb-4">
            <input
                v-model="search"
                type="text"
                placeholder="Search plan no or customer…"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 w-64"
            />
            <select v-model="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="completed">Completed</option>
                <option value="defaulted">Defaulted</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden" style="border:1px solid #E2E8F0;">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left" style="background:#F8FAFC; border-color:#E2E8F0;">
                        <th class="px-4 py-3 font-semibold text-slate-600">Plan No</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">Customer</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-right">Total</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-right">Paid</th>
                        <th class="px-4 py-3 font-semibold text-slate-600 text-right">Balance</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">Next Due</th>
                        <th class="px-4 py-3 font-semibold text-slate-600">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color:#F8FAFC;">
                    <tr v-if="plans.data.length === 0">
                        <td colspan="8" class="px-4 py-10 text-center text-slate-400">No installment plans found.</td>
                    </tr>
                    <tr v-for="plan in plans.data" :key="plan.id" class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 font-mono font-semibold text-blue-700">{{ plan.plan_no }}</td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ plan.customer?.name }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ fmt(plan.total) }}</td>
                        <td class="px-4 py-3 text-right text-green-700 font-semibold">{{ fmt(paidAmount(plan)) }}</td>
                        <td class="px-4 py-3 text-right font-semibold" :class="plan.total - paidAmount(plan) > 0 ? 'text-red-600' : 'text-slate-400'">
                            {{ fmt(Math.max(0, plan.total - paidAmount(plan))) }}
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="nextDue(plan)" :class="isOverdue(nextDue(plan)) ? 'text-red-600 font-semibold' : 'text-slate-600'">
                                {{ fmtDate(nextDue(plan)) }}
                                <span v-if="isOverdue(nextDue(plan))" class="ml-1 text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full font-bold">Overdue</span>
                            </span>
                            <span v-else class="text-slate-400">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full" :class="(statusMeta[plan.status] || statusMeta.active).cls">
                                {{ (statusMeta[plan.status] || statusMeta.active).label }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <Link :href="route('installments.show', plan.id)" class="text-blue-600 hover:underline text-xs font-semibold">View</Link>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="plans.links?.length > 3" class="px-4 py-3 border-t flex gap-1 flex-wrap" style="border-color:#E2E8F0;">
                <Link
                    v-for="link in plans.links" :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    class="px-3 py-1 rounded text-xs border transition-colors"
                    :class="link.active
                        ? 'bg-blue-600 text-white border-blue-600'
                        : link.url ? 'border-gray-200 text-slate-600 hover:bg-slate-50' : 'border-gray-100 text-slate-300 cursor-default'"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
