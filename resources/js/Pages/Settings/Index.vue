<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
});

const flash = computed(() => usePage().props.flash);

const toast = ref(null);
let toastTimer = null;

watch(() => flash.value?.success, (msg) => {
    if (!msg) return;
    toast.value = { type: 'success', message: msg };
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.value = null; }, 3000);
});

watch(() => flash.value?.error, (msg) => {
    if (!msg) return;
    toast.value = { type: 'error', message: msg };
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.value = null; }, 4000);
});

const form = useForm({
    settings: {
        shop_name:      props.settings.shop_name      || '',
        shop_address:   props.settings.shop_address   || '',
        shop_phone:     props.settings.shop_phone     || '',
        shop_email:     props.settings.shop_email     || '',
        currency:       props.settings.currency       || 'Rs.',
        tax_rate:       props.settings.tax_rate       || '0',
        receipt_footer: props.settings.receipt_footer || '',
    },
});

function save() {
    form.post(route('settings.update'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="සැකසුම්" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold" style="color:#0F172A;">සැකසුම්</h1>
        </template>

        <!-- Toast -->
        <Transition name="toast">
            <div
                v-if="toast"
                class="fixed top-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-semibold"
                :style="toast.type === 'success'
                    ? 'background:#16A34A; color:#fff;'
                    : 'background:#DC2626; color:#fff;'"
            >
                <svg v-if="toast.type === 'success'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                {{ toast.message }}
                <button type="button" @click="toast = null" class="ml-2 opacity-75 hover:opacity-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </Transition>

        <form @submit.prevent="save" class="max-w-2xl">
            <div class="bg-white rounded-xl shadow-sm p-6 space-y-5" style="border:1px solid #E2E8F0;">

                <h2 class="font-semibold text-[15px]" style="color:#0F172A; border-bottom:1px solid #E2E8F0; padding-bottom:10px;">
                    කඩ තොරතුරු
                </h2>

                <!-- Shop Name -->
                <div>
                    <label class="block mb-1 font-medium" style="color:#334155;">කඩ නම</label>
                    <input
                        v-model="form.settings.shop_name"
                        type="text"
                        class="w-full rounded-lg px-3 py-2 outline-none"
                        style="border:1px solid #E2E8F0; color:#0F172A;"
                        placeholder="LMUC Convenience Store"
                    />
                    <p v-if="form.errors['settings.shop_name']" class="mt-1 text-xs" style="color:#DC2626;">
                        {{ form.errors['settings.shop_name'] }}
                    </p>
                </div>

                <!-- Address -->
                <div>
                    <label class="block mb-1 font-medium" style="color:#334155;">ලිපිනය</label>
                    <textarea
                        v-model="form.settings.shop_address"
                        rows="2"
                        class="w-full rounded-lg px-3 py-2 outline-none resize-none"
                        style="border:1px solid #E2E8F0; color:#0F172A;"
                        placeholder="No. 1, Main Street, Colombo"
                    ></textarea>
                </div>

                <!-- Phone -->
                <div>
                    <label class="block mb-1 font-medium" style="color:#334155;">දුරකථන අංකය</label>
                    <input
                        v-model="form.settings.shop_phone"
                        type="text"
                        class="w-full rounded-lg px-3 py-2 outline-none"
                        style="border:1px solid #E2E8F0; color:#0F172A;"
                        placeholder="0112345678"
                    />
                </div>

                <!-- Email -->
                <div>
                    <label class="block mb-1 font-medium" style="color:#334155;">විද්‍යුත් තැපෑල</label>
                    <input
                        v-model="form.settings.shop_email"
                        type="email"
                        class="w-full rounded-lg px-3 py-2 outline-none"
                        style="border:1px solid #E2E8F0; color:#0F172A;"
                        placeholder="info@lmucstore.lk"
                    />
                </div>

                <h2 class="font-semibold text-[15px]" style="color:#0F172A; border-bottom:1px solid #E2E8F0; padding-bottom:10px; padding-top:4px;">
                    රිසිට්පත සැකසුම්
                </h2>

                <!-- Currency -->
                <div>
                    <label class="block mb-1 font-medium" style="color:#334155;">මුදල් ලකුණ</label>
                    <input
                        v-model="form.settings.currency"
                        type="text"
                        class="rounded-lg px-3 py-2 outline-none"
                        style="border:1px solid #E2E8F0; color:#0F172A; width:120px;"
                        placeholder="Rs."
                    />
                </div>

                <!-- Tax Rate -->
                <div>
                    <label class="block mb-1 font-medium" style="color:#334155;">බදු අනුපාතය (%)</label>
                    <input
                        v-model="form.settings.tax_rate"
                        type="number"
                        min="0"
                        max="100"
                        step="0.01"
                        class="rounded-lg px-3 py-2 outline-none"
                        style="border:1px solid #E2E8F0; color:#0F172A; width:120px;"
                        placeholder="0"
                    />
                </div>

                <!-- Receipt Footer -->
                <div>
                    <label class="block mb-1 font-medium" style="color:#334155;">රිසිට්පත් පාදක පණිවිඩය</label>
                    <textarea
                        v-model="form.settings.receipt_footer"
                        rows="2"
                        class="w-full rounded-lg px-3 py-2 outline-none resize-none"
                        style="border:1px solid #E2E8F0; color:#0F172A;"
                        placeholder="ගෙවීම් සඳහා ස්තූතියි!"
                    ></textarea>
                </div>

                <!-- Save -->
                <div class="pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 rounded-lg text-white font-semibold text-sm"
                        style="background-color:#2563EB;"
                    >
                        {{ form.processing ? 'සුරකිමින්...' : 'සුරකින්න' }}
                    </button>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(-12px); }
</style>
