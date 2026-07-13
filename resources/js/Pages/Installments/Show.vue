<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    plan:     { type: Object, required: true },
    settings: { type: Object, default: () => ({}) },
});

const page    = usePage();
const auth    = computed(() => page.props.auth);
const isAdmin = computed(() => auth.value?.role === 'admin' || auth.value?.user?.role === 'admin');
const canViewDocs = computed(() => {
    const r = auth.value?.role || auth.value?.user?.role;
    return r === 'admin' || r === 'manager';
});

// ── Pay modal ────────────────────────────────────────────────────────────────
const payModal    = ref(null);   // { payment: {...} }
const payAmount   = ref('');
const payMethod   = ref('cash');
const payRef      = ref('');
const payNotes    = ref('');
const paySubmitting = ref(false);

function openPay(payment) {
    payModal.value    = payment;
    payAmount.value   = payment.amount_due - payment.amount_paid;
    payMethod.value   = 'cash';
    payRef.value      = '';
    payNotes.value    = '';
}

function closePay() { payModal.value = null; }

function submitPay() {
    if (paySubmitting.value) return;
    paySubmitting.value = true;
    router.post(route('installments.pay', { plan: props.plan.id, payment: payModal.value.id }), {
        amount_paid:    payAmount.value,
        payment_method: payMethod.value,
        reference:      payRef.value,
        notes:          payNotes.value,
    }, {
        onSuccess: () => { closePay(); },
        onFinish: () => { paySubmitting.value = false; },
    });
}

// ── Document upload ───────────────────────────────────────────────────────────
const docType     = ref('nic_front');
const docLabel    = ref('');
const docFile      = ref(null);
const docUploading = ref(false);
const docError     = ref('');
const fileInput    = ref(null);
const docPreview   = ref(null);   // data URL for image preview
const dragOver     = ref(false);

const docTypes = [
    { value: 'nic_front',     label: 'NIC Front' },
    { value: 'nic_back',      label: 'NIC Back' },
    { value: 'photo',         label: 'Photo' },
    { value: 'address_proof', label: 'Address Proof' },
    { value: 'guarantor_nic', label: 'Guarantor NIC' },
    { value: 'agreement',     label: 'Agreement' },
    { value: 'other',         label: 'Other' },
];

function setDocFile(file) {
    if (!file) return;
    docFile.value  = file;
    docError.value = '';
    docPreview.value = null;
    if (/\.(jpe?g|png|webp|gif)$/i.test(file.name)) {
        const reader = new FileReader();
        reader.onload = e => { docPreview.value = e.target.result; };
        reader.readAsDataURL(file);
    }
}
function onFileChange(e)  { setDocFile(e.target.files[0]); }
function onDrop(e)        { dragOver.value = false; setDocFile(e.dataTransfer.files[0]); }
function clearDocFile()   { docFile.value = null; docPreview.value = null; if (fileInput.value) fileInput.value.value = ''; }

function uploadDoc() {
    if (!docFile.value) { docError.value = 'Please select a file.'; return; }
    docError.value   = '';
    docUploading.value = true;

    const form = new FormData();
    form.append('type',  docType.value);
    form.append('label', docLabel.value);
    form.append('file',  docFile.value);

    router.post(route('installments.documents.upload', props.plan.id), form, {
        forceFormData: true,
        onSuccess: () => {
            clearDocFile();
            docLabel.value = '';
        },
        onError: (errs) => { docError.value = Object.values(errs).flat().join(' '); },
        onFinish: () => { docUploading.value = false; },
    });
}

// ── Delete plan ───────────────────────────────────────────────────────────────
function deletePlan() {
    if (!confirm(`Cancel plan ${props.plan.plan_no}? This will restore stock and cannot be undone.`)) return;
    router.delete(route('installments.destroy', props.plan.id));
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function fmt(v) {
    return 'Rs. ' + Number(v || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function isOverdue(dateStr) {
    return dateStr && new Date(dateStr) < new Date(new Date().toDateString());
}

const statusMeta = {
    active:    { label: 'Active',    cls: 'bg-blue-100 text-blue-700' },
    completed: { label: 'Completed', cls: 'bg-green-100 text-green-700' },
    defaulted: { label: 'Defaulted', cls: 'bg-red-100 text-red-700' },
    cancelled: { label: 'Cancelled', cls: 'bg-gray-100 text-gray-500' },
};

const paymentStatus = {
    pending:  { label: 'Pending',  cls: 'bg-yellow-100 text-yellow-700' },
    paid:     { label: 'Paid',     cls: 'bg-green-100 text-green-700' },
    partial:  { label: 'Partial',  cls: 'bg-orange-100 text-orange-700' },
    overdue:  { label: 'Overdue',  cls: 'bg-red-100 text-red-700' },
};

const totalPaid = computed(() => props.plan.payments?.reduce((s, p) => s + Number(p.amount_paid || 0), 0) ?? 0);
const balance   = computed(() => Number(props.plan.total) - totalPaid.value);

const typeLabel = {
    nic_front:     'NIC Front',
    nic_back:      'NIC Back',
    photo:         'Photo',
    address_proof: 'Address Proof',
    guarantor_nic: 'Guarantor NIC',
    agreement:     'Agreement',
    other:         'Other',
};

function isImage(name) {
    return /\.(jpe?g|png|webp|gif)$/i.test(name || '');
}

// Append ImageKit transformation for a compact thumbnail
function thumbUrl(url) {
    if (!url) return '';
    // ImageKit URL transform: width 300, height 192, crop
    return url.includes('imagekit.io') ? url + '?tr=w-300,h-192,fo-auto' : url;
}
</script>

<template>
    <Head :title="`Plan ${plan.plan_no}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <Link :href="route('installments.index')" class="text-slate-400 hover:text-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">{{ plan.plan_no }}</h1>
                        <p class="text-sm text-slate-500">{{ plan.customer?.name }}</p>
                    </div>
                    <span class="text-xs font-semibold px-2 py-1 rounded-full" :class="(statusMeta[plan.status] || statusMeta.active).cls">
                        {{ (statusMeta[plan.status] || statusMeta.active).label }}
                    </span>
                </div>
                <button v-if="isAdmin" @click="deletePlan"
                    class="flex items-center gap-1.5 text-white text-xs font-semibold px-3 py-2 rounded-lg transition-colors"
                    style="background-color:#7F1D1D;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Cancel Plan
                </button>
            </div>
        </template>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success" class="mb-4 bg-green-50 border border-green-200 rounded-lg px-4 py-3 text-sm text-green-700">
            {{ $page.props.flash.success }}
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <!-- Left: summary + payments + items -->
            <div class="lg:col-span-2 space-y-4">

                <!-- Summary cards -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-white rounded-xl shadow-sm p-4 text-center" style="border:1px solid #E2E8F0;">
                        <p class="text-xs text-slate-500 mb-1">Total Value</p>
                        <p class="text-lg font-bold text-gray-800">{{ fmt(plan.total) }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4 text-center" style="border:1px solid #E2E8F0;">
                        <p class="text-xs text-slate-500 mb-1">Total Paid</p>
                        <p class="text-lg font-bold text-green-700">{{ fmt(totalPaid) }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4 text-center" style="border:1px solid #E2E8F0;">
                        <p class="text-xs text-slate-500 mb-1">Balance Due</p>
                        <p class="text-lg font-bold" :class="balance > 0 ? 'text-red-600' : 'text-slate-400'">{{ fmt(balance) }}</p>
                    </div>
                </div>

                <!-- Payment schedule -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden" style="border:1px solid #E2E8F0;">
                    <div class="px-4 py-3 border-b" style="border-color:#E2E8F0; background:#F8FAFC;">
                        <p class="text-sm font-semibold text-gray-700">Payment Schedule</p>
                    </div>
                    <div class="divide-y" style="border-color:#F8FAFC;">
                        <div v-for="payment in plan.payments" :key="payment.id"
                            class="flex items-center gap-4 px-4 py-3"
                            :class="{ 'bg-red-50': payment.status === 'overdue' }">

                            <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold text-white"
                                :style="payment.installment_no === 0 ? 'background:#2563EB;' : (payment.status === 'paid' ? 'background:#16a34a;' : payment.status === 'overdue' ? 'background:#dc2626;' : 'background:#64748B;')">
                                {{ payment.installment_no === 0 ? '↓' : payment.installment_no }}
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ payment.installment_no === 0 ? 'Down Payment' : `Installment ${payment.installment_no}` }}
                                </p>
                                <p class="text-xs" :class="isOverdue(payment.due_date) && payment.status !== 'paid' ? 'text-red-500 font-semibold' : 'text-slate-400'">
                                    Due: {{ payment.due_date }}
                                    <span v-if="isOverdue(payment.due_date) && payment.status !== 'paid'" class="ml-1">⚠ Overdue</span>
                                </p>
                                <p v-if="payment.amount_paid > 0" class="text-xs text-green-600">Paid: {{ fmt(payment.amount_paid) }}</p>
                                <p v-if="payment.paid_at" class="text-xs text-slate-400">{{ payment.paid_at?.substring(0, 10) }} via {{ payment.payment_method }}</p>
                            </div>

                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-bold text-gray-800">{{ fmt(payment.amount_due) }}</p>
                                <span class="text-xs font-semibold px-1.5 py-0.5 rounded-full" :class="(paymentStatus[payment.status] || paymentStatus.pending).cls">
                                    {{ (paymentStatus[payment.status] || paymentStatus.pending).label }}
                                </span>
                            </div>

                            <button v-if="payment.status !== 'paid'"
                                @click="openPay(payment)"
                                class="text-xs text-white font-semibold px-3 py-1.5 rounded-lg flex-shrink-0"
                                style="background-color:#2563EB;">
                                Pay
                            </button>
                            <div v-else class="w-14 flex-shrink-0"></div>
                        </div>
                    </div>
                </div>

                <!-- Items table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden" style="border:1px solid #E2E8F0;">
                    <div class="px-4 py-3 border-b" style="border-color:#E2E8F0; background:#F8FAFC;">
                        <p class="text-sm font-semibold text-gray-700">Items</p>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left" style="border-color:#E2E8F0;">
                                <th class="px-4 py-2 font-semibold text-slate-500">Item</th>
                                <th class="px-4 py-2 font-semibold text-slate-500 text-center w-16">Qty</th>
                                <th class="px-4 py-2 font-semibold text-slate-500 text-right w-28">Price</th>
                                <th class="px-4 py-2 font-semibold text-slate-500 text-right w-28">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color:#F8FAFC;">
                            <tr v-for="item in plan.items" :key="item.id">
                                <td class="px-4 py-2 font-medium text-gray-800">{{ item.product_name }}</td>
                                <td class="px-4 py-2 text-center text-slate-600">{{ item.qty }}</td>
                                <td class="px-4 py-2 text-right text-slate-600">{{ fmt(item.unit_price) }}</td>
                                <td class="px-4 py-2 text-right font-semibold text-gray-800">{{ fmt(item.total) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="border-t" style="border-color:#E2E8F0; background:#F8FAFC;">
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-right font-bold text-gray-700">Total</td>
                                <td class="px-4 py-2 text-right font-bold text-gray-800">{{ fmt(plan.total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Right: info + documents -->
            <div class="space-y-4">

                <!-- Plan info -->
                <div class="bg-white rounded-xl shadow-sm p-4" style="border:1px solid #E2E8F0;">
                    <p class="text-sm font-semibold text-gray-700 mb-3">Plan Details</p>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Plan No</span>
                            <span class="font-mono font-bold text-blue-700">{{ plan.plan_no }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Customer</span>
                            <span class="font-semibold text-gray-700">{{ plan.customer?.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Phone</span>
                            <span class="text-gray-700">{{ plan.customer?.phone || '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Down Payment</span>
                            <span class="font-semibold text-blue-700">{{ fmt(plan.down_payment) }} ({{ plan.down_payment_percent }}%)</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Balance</span>
                            <span class="font-semibold text-orange-600">{{ fmt(plan.balance) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Installments</span>
                            <span class="text-gray-700">{{ plan.installments_count }} × {{ fmt(plan.installment_amount) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Created by</span>
                            <span class="text-gray-700">{{ plan.user?.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Created</span>
                            <span class="text-gray-700">{{ plan.created_at?.substring(0, 10) }}</span>
                        </div>
                    </div>
                    <p v-if="plan.notes" class="mt-3 text-xs text-slate-500 italic border-t pt-2" style="border-color:#E2E8F0;">{{ plan.notes }}</p>
                </div>

                <!-- Documents -->
                <div class="bg-white rounded-xl shadow-sm p-4" style="border:1px solid #E2E8F0;">
                    <p class="text-sm font-semibold text-gray-700 mb-3">Documents</p>

                    <!-- Existing docs -->
                    <div v-if="plan.documents?.length > 0" class="grid grid-cols-2 gap-2 mb-4">
                        <a v-for="doc in plan.documents" :key="doc.id"
                            :href="canViewDocs ? route('installments.documents.serve', { plan: plan.id, document: doc.id }) : undefined"
                            :target="canViewDocs ? '_blank' : undefined"
                            class="block rounded-lg overflow-hidden border group relative"
                            style="border-color:#E2E8F0;"
                            :class="canViewDocs ? 'cursor-pointer' : 'cursor-default'">

                            <!-- Image preview -->
                            <img v-if="isImage(doc.original_name)"
                                :src="thumbUrl(doc.file_path)"
                                :alt="typeLabel[doc.type] || doc.type"
                                class="w-full h-24 object-cover bg-slate-100"
                                loading="lazy"
                            />

                            <!-- PDF placeholder -->
                            <div v-else class="w-full h-24 flex items-center justify-center bg-red-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    <text x="8" y="17" font-size="5" fill="#f87171" font-family="sans-serif" font-weight="bold">PDF</text>
                                </svg>
                            </div>

                            <!-- Label overlay -->
                            <div class="px-2 py-1.5 bg-white">
                                <p class="text-xs font-semibold text-gray-700 truncate">{{ typeLabel[doc.type] || doc.type }}</p>
                                <p class="text-xs text-slate-400 truncate">{{ doc.original_name }}</p>
                            </div>

                            <!-- Hover overlay -->
                            <div v-if="canViewDocs" class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                                <span class="opacity-0 group-hover:opacity-100 transition-opacity bg-white text-xs font-bold text-gray-800 px-2 py-1 rounded shadow">Open ↗</span>
                            </div>
                        </a>
                    </div>
                    <p v-else class="text-xs text-slate-400 mb-4">No documents uploaded yet.</p>

                    <!-- Upload form -->
                    <div class="border-t pt-3 space-y-2" style="border-color:#E2E8F0;">
                        <p class="text-xs font-semibold text-gray-600">Upload Document</p>

                        <!-- Type + label row -->
                        <div class="flex gap-2">
                            <select v-model="docType" class="flex-1 border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <option v-for="dt in docTypes" :key="dt.value" :value="dt.value">{{ dt.label }}</option>
                            </select>
                        </div>

                        <!-- Drop zone -->
                        <div
                            class="relative rounded-xl border-2 border-dashed transition-colors cursor-pointer"
                            :class="dragOver ? 'border-blue-400 bg-blue-50' : docFile ? 'border-green-300 bg-green-50' : 'border-gray-200 bg-gray-50 hover:border-blue-300 hover:bg-blue-50'"
                            @click="fileInput.click()"
                            @dragover.prevent="dragOver = true"
                            @dragleave="dragOver = false"
                            @drop.prevent="onDrop"
                        >
                            <!-- Preview thumbnail -->
                            <div v-if="docPreview" class="relative">
                                <img :src="docPreview" class="w-full h-28 object-cover rounded-xl" />
                                <button type="button" @click.stop="clearDocFile"
                                    class="absolute top-1.5 right-1.5 bg-black/50 hover:bg-black/70 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs transition-colors">
                                    ×
                                </button>
                            </div>

                            <!-- PDF selected state -->
                            <div v-else-if="docFile" class="flex items-center gap-3 px-3 py-3">
                                <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-gray-700 truncate">{{ docFile.name }}</p>
                                    <p class="text-xs text-slate-400">{{ (docFile.size / 1024).toFixed(0) }} KB</p>
                                </div>
                                <button type="button" @click.stop="clearDocFile"
                                    class="text-slate-400 hover:text-red-500 transition-colors flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Empty state -->
                            <div v-else class="flex flex-col items-center gap-1.5 py-5 px-3">
                                <div class="w-9 h-9 rounded-xl bg-blue-100 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <p class="text-xs font-semibold text-gray-600">Click or drag file here</p>
                                <p class="text-xs text-slate-400">JPG, PNG or PDF · max 5 MB</p>
                            </div>
                        </div>

                        <input ref="fileInput" type="file" accept=".jpg,.jpeg,.png,.pdf" class="hidden" @change="onFileChange" />

                        <p v-if="docError" class="text-xs text-red-600">{{ docError }}</p>

                        <button type="button" @click="uploadDoc" :disabled="docUploading || !docFile"
                            class="w-full py-2 text-xs font-bold text-white rounded-xl transition-all disabled:opacity-40"
                            :style="docFile && !docUploading ? 'background-color:#2563EB;' : 'background-color:#93C5FD;'">
                            <span v-if="docUploading" class="flex items-center justify-center gap-1.5">
                                <svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Uploading…
                            </span>
                            <span v-else>Upload {{ docTypes.find(d => d.value === docType)?.label }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pay modal -->
        <div v-if="payModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm mx-4">
                <h2 class="text-lg font-bold text-gray-800 mb-1">Record Payment</h2>
                <p class="text-sm text-slate-500 mb-4">
                    {{ payModal.installment_no === 0 ? 'Down Payment' : `Installment ${payModal.installment_no}` }}
                    — Due {{ payModal.due_date }}
                </p>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Amount (Rs.)</label>
                        <input v-model="payAmount" type="number" min="0.01" step="0.01"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300" />
                        <p class="text-xs text-slate-400 mt-0.5">Balance due: {{ fmt(payModal.amount_due - payModal.amount_paid) }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Payment Method</label>
                        <div class="flex gap-2">
                            <button v-for="m in ['cash', 'card', 'qr']" :key="m" type="button"
                                @click="payMethod = m"
                                class="flex-1 py-1.5 text-xs font-bold rounded-lg border transition-colors"
                                :class="payMethod === m ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-200 text-slate-600 hover:bg-slate-50'">
                                {{ m.toUpperCase() }}
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Reference (optional)</label>
                        <input v-model="payRef" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300" placeholder="Cheque No / Txn ID…" />
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Notes (optional)</label>
                        <textarea v-model="payNotes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300"></textarea>
                    </div>
                </div>

                <div class="flex gap-3 mt-5">
                    <button type="button" @click="closePay"
                        class="flex-1 py-2 text-sm font-semibold text-slate-600 border border-gray-200 rounded-xl hover:bg-slate-50 transition-colors">
                        Cancel
                    </button>
                    <button type="button" @click="submitPay" :disabled="paySubmitting"
                        class="flex-1 py-2 text-sm font-bold text-white rounded-xl transition-colors disabled:opacity-50"
                        style="background-color:#2563EB;">
                        {{ paySubmitting ? 'Saving…' : 'Record Payment' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
