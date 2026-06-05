<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    customers:   { type: Object, default: () => ({ data: [] }) },
    totalCredit: { type: Number, default: 0 },
});

const flash = computed(() => usePage().props.flash);
const toast = ref(null);
let toastTimer = null;
watch(() => flash.value?.success, msg => {
    if (!msg) return;
    toast.value = msg;
    settleModal.value = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.value = null; }, 3000);
});

function fmt(v) {
    return 'Rs. ' + Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ── Settle modal ──────────────────────────────────────────────────────────────
const settleModal    = ref(false);
const settleCustomer = ref(null);
const settleForm     = useForm({ amount: '' });

function openSettle(customer) {
    settleCustomer.value = customer;
    settleForm.amount    = '';
    settleModal.value    = true;
}

function submitSettle() {
    settleForm.post(route('customers.settle-credit', settleCustomer.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            settleForm.reset();
        },
    });
}
</script>

<template>
    <Head title="ණය පාරිභෝගිකයන්" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('reports.index')" class="text-slate-400 hover:text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </Link>
                <h1 class="text-xl font-bold" style="color:#0F172A;">ණය පාරිභෝගිකයන්</h1>
                <span class="ml-auto text-sm font-semibold px-3 py-1 rounded-lg text-white" style="background-color:#DC2626;">
                    මුළු ණය: {{ fmt(totalCredit) }}
                </span>
            </div>
        </template>

        <!-- Toast -->
        <Transition name="toast">
            <div v-if="toast" class="fixed top-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-semibold text-white" style="background:#16A34A;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                {{ toast }}
            </div>
        </Transition>

        <div class="bg-white rounded-xl shadow-sm" style="border:1px solid #E2E8F0;">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-xs text-slate-500 uppercase" style="background:#F8FAFC; border-bottom:1px solid #E2E8F0;">
                            <th class="px-4 py-3 text-left">නම</th>
                            <th class="px-4 py-3 text-left">දුරකථනය</th>
                            <th class="px-4 py-3 text-right">ණය ශේෂය</th>
                            <th class="px-4 py-3 text-center w-32">ගෙවීම</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="customers.data.length === 0">
                            <td colspan="4" class="px-4 py-8 text-center text-slate-400">ණය පාරිභෝගිකයන් නොමැත</td>
                        </tr>
                        <tr v-for="c in customers.data" :key="c.id" class="hover:bg-slate-50" style="border-bottom:1px solid #F1F5F9;">
                            <td class="px-4 py-2.5 font-medium" style="color:#0F172A;">{{ c.name }}</td>
                            <td class="px-4 py-2.5 text-slate-500">{{ c.phone || '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-bold" style="color:#DC2626;">{{ fmt(c.credit_balance) }}</td>
                            <td class="px-4 py-2.5 text-center">
                                <button
                                    type="button"
                                    @click="openSettle(c)"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition-colors"
                                    style="background-color:#16A34A;"
                                >
                                    ගෙවන්න
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="customers.last_page > 1" class="px-4 py-3 flex gap-2 flex-wrap" style="border-top:1px solid #E2E8F0;">
                <Link
                    v-for="link in customers.links" :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    class="px-3 py-1 rounded text-sm"
                    :class="link.active ? 'text-white' : 'text-slate-600 hover:bg-slate-100'"
                    :style="link.active ? 'background-color:#2563EB' : ''"
                />
            </div>
        </div>

        <!-- Settle Modal -->
        <Teleport to="body">
            <div v-if="settleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
                <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 space-y-4">
                    <h2 class="text-lg font-bold" style="color:#0F172A;">ණය ගෙවීම</h2>

                    <div class="rounded-xl px-4 py-3 space-y-1" style="background:#FEF2F2; border:1px solid #FECACA;">
                        <p class="text-sm font-semibold" style="color:#0F172A;">{{ settleCustomer?.name }}</p>
                        <p class="text-xs" style="color:#64748B;">ණය ශේෂය: <span class="font-bold" style="color:#DC2626;">{{ fmt(settleCustomer?.credit_balance) }}</span></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5" style="color:#334155;">ගෙවන මුදල (Rs.)</label>
                        <input
                            v-model="settleForm.amount"
                            type="number"
                            min="0.01"
                            :max="settleCustomer?.credit_balance"
                            step="0.01"
                            placeholder="0.00"
                            class="w-full border-2 border-green-300 rounded-xl px-4 py-3 text-xl font-bold text-green-800 focus:outline-none focus:ring-2 focus:ring-green-400"
                            autofocus
                            @keydown.enter="submitSettle"
                        />
                        <p v-if="settleForm.errors.amount" class="text-red-500 text-xs mt-1">{{ settleForm.errors.amount }}</p>

                        <!-- Quick amount buttons -->
                        <div class="flex gap-2 mt-2">
                            <button
                                v-for="amt in [500, 1000, 2000, 5000]"
                                :key="amt"
                                type="button"
                                @click="settleForm.amount = Math.min(amt, settleCustomer?.credit_balance)"
                                class="flex-1 text-xs py-1.5 rounded-lg border font-medium transition-colors"
                                style="border-color:#E2E8F0; color:#64748B;"
                            >{{ amt }}</button>
                            <button
                                type="button"
                                @click="settleForm.amount = settleCustomer?.credit_balance"
                                class="flex-1 text-xs py-1.5 rounded-lg font-semibold transition-colors"
                                style="background:#FEF2F2; border:1px solid #FECACA; color:#DC2626;"
                            >සම්පූර්ණ</button>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="submitSettle"
                            :disabled="settleForm.processing || !settleForm.amount"
                            class="flex-1 text-white font-semibold py-3 rounded-xl transition-colors disabled:opacity-50"
                            style="background-color:#16A34A;"
                        >
                            {{ settleForm.processing ? 'සටහන් කරමින්...' : 'ගෙවීම සටහන් කරන්න' }}
                        </button>
                        <button
                            type="button"
                            @click="settleModal = false"
                            class="flex-1 font-semibold py-3 rounded-xl transition-colors"
                            style="background:#F1F5F9; color:#64748B;"
                        >
                            ඉවත්වෙන්න 
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(-12px); }
</style>
