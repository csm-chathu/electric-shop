<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, inject, onMounted } from 'vue';
import { getPrinters, isElectronRuntime } from '@/utils/printClient.js';
import { SIDEBAR_PRESETS, PRIMARY_PRESETS, useTheme } from '@/composables/useTheme.js';

const t             = inject('t');
const locale        = inject('locale');
const setLocale     = inject('setLocale');
const billLocale    = inject('billLocale');
const setBillLocale = inject('setBillLocale');

const { setSidebarPreset, setPrimaryPreset } = useTheme();

const LANGS = [
    { code: 'si', label: 'සිංහල' },
    { code: 'en', label: 'English' },
    { code: 'ta', label: 'தமிழ்' },
];

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
});

// Printer (Electron only)
const isElectron    = isElectronRuntime();
const electronAPI   = window.electronAPI ?? null;
const printers      = ref([]);
const selectedPrinter = ref(localStorage.getItem('pos_printer') || '');

async function loadPrinters() {
    if (!isElectron) return;
    printers.value = await getPrinters();
    if (!selectedPrinter.value) {
        const def = printers.value.find(p => p.isDefault);
        if (def) selectedPrinter.value = def.name;
    }
}

function savePrinter() {
    localStorage.setItem('pos_printer', selectedPrinter.value);
    toast.value = { type: 'success', message: 'Printer saved' };
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toast.value = null; }, 2000);
}

// Sync DB settings to composables on mount
onMounted(() => {
    if (props.settings.ui_language)   setLocale(props.settings.ui_language);
    if (props.settings.bill_language) setBillLocale(props.settings.bill_language);
    if (props.settings.sidebar_theme) setSidebarPreset(props.settings.sidebar_theme);
    if (props.settings.primary_color) setPrimaryPreset(props.settings.primary_color);
    loadPrinters();
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
        ui_language:    props.settings.ui_language    || 'si',
        bill_language:  props.settings.bill_language  || 'si',
        sidebar_theme:  props.settings.sidebar_theme  || 'slate',
        primary_color:  props.settings.primary_color  || 'blue',
    },
    logo_file:   null,
    remove_logo: false,
});

// Logo preview
const logoPreview   = ref(props.settings.logo_url || null);
const logoInput     = ref(null);

function onLogoChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.logo_file   = file;
    form.remove_logo = false;
    logoPreview.value = URL.createObjectURL(file);
}

function removeLogo() {
    form.logo_file    = null;
    form.remove_logo  = true;
    logoPreview.value = null;
    if (logoInput.value) logoInput.value.value = '';
}

// Language buttons — update form AND apply immediately
function selectUILang(code) {
    form.settings.ui_language = code;
    setLocale(code);
}

function selectBillLang(code) {
    form.settings.bill_language = code;
    setBillLocale(code);
}

function selectSidebarTheme(id) {
    form.settings.sidebar_theme = id;
    setSidebarPreset(id);
}

function selectPrimaryColor(id) {
    form.settings.primary_color = id;
    setPrimaryPreset(id);
}

function save() {
    form.post(route('settings.update'), {
        preserveScroll: true,
        forceFormData:  true,
    });
}
</script>

<template>
    <Head :title="t('page.settings')" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold" style="color:#0F172A;">{{ t('page.settings') }}</h1>
        </template>

        <!-- Toast -->
        <Transition name="toast">
            <div
                v-if="toast"
                class="fixed top-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-semibold"
                :style="toast.type === 'success' ? 'background:#16A34A;color:#fff;' : 'background:#DC2626;color:#fff;'"
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

        <form @submit.prevent="save" class="max-w-5xl">

            <!-- ── 2-column grid ── -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">

                <!-- ══ LEFT: Shop Info + Receipt ══ -->
                <div class="space-y-5">

                    <!-- Shop Info card -->
                    <div class="bg-white rounded-xl shadow-sm p-5 space-y-4" style="border:1px solid #E2E8F0;">
                        <h2 class="font-semibold text-[15px]" style="color:#0F172A; border-bottom:1px solid #E2E8F0; padding-bottom:10px;">
                            {{ t('set.shop_info') }}
                        </h2>

                        <!-- Logo -->
                        <div>
                            <label class="block mb-2 text-sm font-medium" style="color:#334155;">{{ t('set.logo') }}</label>
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-16 h-16 rounded-lg flex items-center justify-center flex-shrink-0 overflow-hidden"
                                    style="border:2px dashed #E2E8F0; background:#F8FAFC;"
                                >
                                    <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-contain" alt="Logo" />
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" style="color:#CBD5E1;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="cursor-pointer inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium" style="border:1px solid #2563EB; color:#2563EB; background:#EFF6FF;">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        {{ t('btn.upload') }}
                                        <input ref="logoInput" type="file" accept="image/*" class="hidden" @change="onLogoChange" />
                                    </label>
                                    <button v-if="logoPreview" type="button" @click="removeLogo" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium" style="border:1px solid #E2E8F0; color:#64748B; background:#F8FAFC;">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        {{ t('btn.delete') }}
                                    </button>
                                    <p class="text-xs" style="color:#94A3B8;">PNG / JPG · max 2 MB</p>
                                </div>
                            </div>
                            <p v-if="form.errors.logo_file" class="mt-1 text-xs" style="color:#DC2626;">{{ form.errors.logo_file }}</p>
                        </div>

                        <!-- Shop Name -->
                        <div>
                            <label class="block mb-1 text-sm font-medium" style="color:#334155;">{{ t('set.shop_name') }}</label>
                            <input v-model="form.settings.shop_name" type="text" class="w-full rounded-lg px-3 py-2 text-sm outline-none" style="border:1px solid #E2E8F0; color:#0F172A;" placeholder="LMUC Convenience Store" />
                            <p v-if="form.errors['settings.shop_name']" class="mt-1 text-xs" style="color:#DC2626;">{{ form.errors['settings.shop_name'] }}</p>
                        </div>

                        <!-- Address -->
                        <div>
                            <label class="block mb-1 text-sm font-medium" style="color:#334155;">{{ t('set.address') }}</label>
                            <textarea v-model="form.settings.shop_address" rows="2" class="w-full rounded-lg px-3 py-2 text-sm outline-none resize-none" style="border:1px solid #E2E8F0; color:#0F172A;" placeholder="No. 1, Main Street, Colombo"></textarea>
                        </div>

                        <!-- Phone + Email side by side -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block mb-1 text-sm font-medium" style="color:#334155;">{{ t('set.phone') }}</label>
                                <input v-model="form.settings.shop_phone" type="text" class="w-full rounded-lg px-3 py-2 text-sm outline-none" style="border:1px solid #E2E8F0; color:#0F172A;" placeholder="0112345678" />
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium" style="color:#334155;">{{ t('set.email') }}</label>
                                <input v-model="form.settings.shop_email" type="email" class="w-full rounded-lg px-3 py-2 text-sm outline-none" style="border:1px solid #E2E8F0; color:#0F172A;" placeholder="info@store.lk" />
                            </div>
                        </div>
                    </div>

                    <!-- Receipt Settings card -->
                    <div class="bg-white rounded-xl shadow-sm p-5 space-y-4" style="border:1px solid #E2E8F0;">
                        <h2 class="font-semibold text-[15px]" style="color:#0F172A; border-bottom:1px solid #E2E8F0; padding-bottom:10px;">
                            {{ t('set.receipt_settings') }}
                        </h2>

                        <!-- Currency + Tax side by side -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block mb-1 text-sm font-medium" style="color:#334155;">{{ t('set.currency') }}</label>
                                <input v-model="form.settings.currency" type="text" class="w-full rounded-lg px-3 py-2 text-sm outline-none" style="border:1px solid #E2E8F0; color:#0F172A;" placeholder="Rs." />
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium" style="color:#334155;">{{ t('set.tax_rate') }}</label>
                                <input v-model="form.settings.tax_rate" type="number" min="0" max="100" step="0.01" class="w-full rounded-lg px-3 py-2 text-sm outline-none" style="border:1px solid #E2E8F0; color:#0F172A;" placeholder="0" />
                            </div>
                        </div>

                        <!-- Receipt Footer -->
                        <div>
                            <label class="block mb-1 text-sm font-medium" style="color:#334155;">{{ t('set.receipt_footer') }}</label>
                            <textarea v-model="form.settings.receipt_footer" rows="3" class="w-full rounded-lg px-3 py-2 text-sm outline-none resize-none" style="border:1px solid #E2E8F0; color:#0F172A;"></textarea>
                        </div>
                    </div>
                </div>

                <!-- ══ RIGHT: Language + Appearance ══ -->
                <div class="space-y-5">

                    <!-- Language card -->
                    <div class="bg-white rounded-xl shadow-sm p-5 space-y-4" style="border:1px solid #E2E8F0;">
                        <h2 class="font-semibold text-[15px]" style="color:#0F172A; border-bottom:1px solid #E2E8F0; padding-bottom:10px;">
                            {{ t('set.ui_language') }}
                        </h2>

                        <!-- UI Language -->
                        <div>
                            <label class="block mb-2 text-xs font-semibold uppercase tracking-wide" style="color:#64748B;">{{ t('set.ui_language') }}</label>
                            <div class="flex gap-2 flex-wrap">
                                <button
                                    v-for="lang in LANGS"
                                    :key="lang.code"
                                    type="button"
                                    @click="selectUILang(lang.code)"
                                    class="px-4 py-2 rounded-lg border-2 text-sm font-semibold transition-colors"
                                    :style="form.settings.ui_language === lang.code
                                        ? 'border-color:#2563EB; background-color:#EFF6FF; color:#1D4ED8;'
                                        : 'border-color:#E2E8F0; background-color:#F8FAFC; color:#334155;'"
                                >{{ lang.label }}</button>
                            </div>
                        </div>

                        <!-- Bill Language -->
                        <div>
                            <label class="block mb-2 text-xs font-semibold uppercase tracking-wide" style="color:#64748B;">{{ t('set.bill_language') }}</label>
                            <div class="flex gap-2 flex-wrap">
                                <button
                                    v-for="lang in LANGS"
                                    :key="lang.code"
                                    type="button"
                                    @click="selectBillLang(lang.code)"
                                    class="px-4 py-2 rounded-lg border-2 text-sm font-semibold transition-colors"
                                    :style="form.settings.bill_language === lang.code
                                        ? 'border-color:#16A34A; background-color:#F0FDF4; color:#15803D;'
                                        : 'border-color:#E2E8F0; background-color:#F8FAFC; color:#334155;'"
                                >{{ lang.label }}</button>
                            </div>
                        </div>
                    </div>

                    <!-- Appearance card -->
                    <div class="bg-white rounded-xl shadow-sm p-5 space-y-5" style="border:1px solid #E2E8F0;">
                        <h2 class="font-semibold text-[15px]" style="color:#0F172A; border-bottom:1px solid #E2E8F0; padding-bottom:10px;">
                            {{ t('set.appearance') }}
                        </h2>

                        <!-- Sidebar Theme -->
                        <div>
                            <label class="block mb-3 text-xs font-semibold uppercase tracking-wide" style="color:#64748B;">{{ t('set.sidebar_theme') }}</label>
                            <div class="flex gap-3 flex-wrap">
                                <button
                                    v-for="preset in SIDEBAR_PRESETS"
                                    :key="preset.id"
                                    type="button"
                                    @click="selectSidebarTheme(preset.id)"
                                    class="relative w-10 h-10 rounded-full transition-all duration-150 focus:outline-none"
                                    :style="{
                                        backgroundColor: preset.bg,
                                        boxShadow: form.settings.sidebar_theme === preset.id
                                            ? '0 0 0 3px #fff, 0 0 0 5px ' + preset.active
                                            : '0 1px 3px rgba(0,0,0,0.3)',
                                        transform: form.settings.sidebar_theme === preset.id ? 'scale(1.18)' : 'scale(1)',
                                    }"
                                    :title="preset.label"
                                >
                                    <span
                                        class="absolute bottom-0.5 right-0.5 w-2.5 h-2.5 rounded-full border border-white"
                                        :style="{ backgroundColor: preset.active }"
                                    ></span>
                                </button>
                            </div>
                            <p class="mt-2 text-xs font-medium" style="color:#64748B;">
                                {{ SIDEBAR_PRESETS.find(p => p.id === form.settings.sidebar_theme)?.label }}
                            </p>
                        </div>

                        <!-- Primary Color -->
                        <div>
                            <label class="block mb-3 text-xs font-semibold uppercase tracking-wide" style="color:#64748B;">{{ t('set.primary_color') }}</label>
                            <div class="flex gap-3 flex-wrap">
                                <button
                                    v-for="preset in PRIMARY_PRESETS"
                                    :key="preset.id"
                                    type="button"
                                    @click="selectPrimaryColor(preset.id)"
                                    class="w-10 h-10 rounded-full transition-all duration-150 focus:outline-none flex items-center justify-center"
                                    :style="{
                                        backgroundColor: preset.color,
                                        boxShadow: form.settings.primary_color === preset.id
                                            ? '0 0 0 3px #fff, 0 0 0 5px ' + preset.color
                                            : '0 1px 3px rgba(0,0,0,0.25)',
                                        transform: form.settings.primary_color === preset.id ? 'scale(1.18)' : 'scale(1)',
                                    }"
                                    :title="preset.label"
                                >
                                    <svg v-if="form.settings.primary_color === preset.id" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-2 text-xs font-medium" style="color:#64748B;">
                                {{ PRIMARY_PRESETS.find(p => p.id === form.settings.primary_color)?.label }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Printer selector (Electron only) -->
            <div v-if="isElectron" class="mt-5 p-4 rounded-xl" style="background:#F8FAFC; border:1px solid #E2E8F0;">
                <h3 class="text-sm font-semibold mb-3" style="color:#334155;">🖨 Receipt Printer</h3>
                <div class="flex items-center gap-3">
                    <select
                        v-model="selectedPrinter"
                        class="flex-1 rounded-lg px-3 py-2 text-sm outline-none"
                        style="border:1px solid #E2E8F0; color:#0F172A;"
                    >
                        <option value="">— Select printer —</option>
                        <option v-for="p in printers" :key="p.name" :value="p.name">
                            {{ p.name }}{{ p.isDefault ? ' (Default)' : '' }}
                        </option>
                    </select>
                    <button
                        type="button"
                        @click="savePrinter"
                        class="px-4 py-2 rounded-lg text-white text-sm font-semibold"
                        style="background-color:#2563EB;"
                    >Save Printer</button>
                    <button
                        type="button"
                        @click="loadPrinters"
                        class="px-3 py-2 rounded-lg text-sm font-semibold"
                        style="background:#E2E8F0; color:#475569;"
                        title="Refresh printer list"
                    >↺</button>
                </div>
                <p v-if="selectedPrinter" class="mt-2 text-xs" style="color:#64748B;">
                    Selected: <strong>{{ selectedPrinter }}</strong>
                </p>
            </div>

            <!-- License key (Electron only) -->
            <div v-if="isElectron" class="mt-5 p-4 rounded-xl flex items-center justify-between gap-4" style="background:#F8FAFC; border:1px solid #E2E8F0;">
                <div>
                    <p class="text-sm font-semibold" style="color:#0F172A;">License Key</p>
                    <p class="text-xs mt-0.5" style="color:#64748B;">Received a new license key after payment? Enter it here.</p>
                </div>
                <button
                    type="button"
                    @click="electronAPI?.changeLicenseKey()"
                    class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white shrink-0"
                    style="background:#2563EB;"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    Enter New Key
                </button>
            </div>

            <!-- Save button — full width below both columns -->
            <div class="mt-5">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-8 py-2.5 rounded-lg text-white font-semibold text-sm transition-opacity"
                    style="background-color:#2563EB;"
                    :style="form.processing ? 'opacity:0.7' : ''"
                >
                    {{ form.processing ? t('lbl.loading') : t('set.save') }}
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(-12px); }
</style>
