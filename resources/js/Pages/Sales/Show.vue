<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    sale:     { type: Object, required: true },
    settings: { type: Object, default: () => ({}) },
});

const shopName    = computed(() => props.settings.shop_name    || 'LUMAC POS');
const shopAddress = computed(() => props.settings.shop_address || '');
const shopPhone   = computed(() => props.settings.shop_phone   || '');
const footer      = computed(() => props.settings.receipt_footer || 'ගෙවීම් සඳහා ස්තූතියි!');
const currency    = computed(() => props.settings.currency     || 'Rs.');

const paymentLabel = computed(() => {
    const map = { cash: 'මුදල්', card: 'කාඩ්', qr: 'QR', credit: 'ණය' };
    return props.sale.payments?.[0]
        ? (map[props.sale.payments[0].method] || props.sale.payments[0].method)
        : '';
});

function fmtDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('si-LK', { year: 'numeric', month: 'long', day: 'numeric' });
}

function fmtTime(d) {
    if (!d) return '';
    return new Date(d).toLocaleTimeString('en-LK', { hour: '2-digit', minute: '2-digit' });
}

function n(val) {
    return Number(val || 0).toFixed(2);
}

const profit = computed(() => {
    if (!props.sale.items?.length) return 0;
    return props.sale.items.reduce((sum, item) => {
        const revenue = Number(item.total || 0);
        const cost    = Number(item.cost_price || 0) * Number(item.qty || 0);
        return sum + (revenue - cost);
    }, 0);
});

function printReceipt() {
    window.print();
}
</script>

<template>
    <Head :title="`ඉන්වොයිස් ${sale.invoice_no}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('sales.index')" class="text-slate-400 hover:text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-bold" style="color:#0F172A;">{{ sale.invoice_no }}</h1>
                <div class="ml-auto flex gap-2">
                    <button
                        type="button"
                        @click="printReceipt"
                        class="flex items-center gap-2 text-white px-4 py-2 rounded-lg text-sm font-semibold"
                        style="background-color:#2563EB;"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        මුද්‍රණය
                    </button>
                    <Link
                        :href="route('sales.create')"
                        class="flex items-center gap-2 text-white px-4 py-2 rounded-lg text-sm font-semibold"
                        style="background-color:#16A34A;"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        නව විකුණුම්
                    </Link>
                </div>
            </div>
        </template>

        <!-- Receipt centred on screen -->
        <div id="receipt-wrapper" class="flex justify-center py-6">
            <div id="receipt-card" class="bg-white rounded-xl shadow-sm p-8" style="border:1px solid #E2E8F0; width:340px;">

                <!-- Shop header -->
                <div class="text-center mb-4">
                    <p class="shop-title font-bold text-[15px]" style="color:#0F172A;">{{ shopName }}</p>
                    <p v-if="shopAddress" class="text-[12px] text-slate-500 mt-0.5">{{ shopAddress }}</p>
                    <p v-if="shopPhone" class="text-[12px] text-slate-500">{{ shopPhone }}</p>
                </div>

                <div class="divider" style="border-top:1px dashed #CBD5E1; margin:10px 0;"></div>

                <!-- Invoice meta -->
                <div class="text-[12px] space-y-1 mb-1" style="color:#334155;">
                    <div class="flex justify-between">
                        <span>ඉන්වොයිස්</span>
                        <span class="font-bold">{{ sale.invoice_no }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>දිනය</span>
                        <span>{{ fmtDate(sale.created_at) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>වේලාව</span>
                        <span>{{ fmtTime(sale.created_at) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>කැශියර්</span>
                        <span>{{ sale.user?.name }}</span>
                    </div>
                    <div v-if="sale.customer" class="flex justify-between">
                        <span>ගනුදෙනුකරු</span>
                        <span>{{ sale.customer.name }}</span>
                    </div>
                </div>

                <div class="items-section divider" style="border-top:1px dashed #CBD5E1; margin:10px 0;"></div>

                <!-- Items -->
                <table class="items-section" style="width:100%; border-collapse:collapse; font-size:12px; color:#334155;">
                    <thead>
                        <tr style="border-bottom:1px solid #E2E8F0;">
                            <th style="text-align:left; padding:4px 0; font-weight:600; color:#64748B;">භාණ්ඩය</th>
                            <th style="text-align:center; width:30px; padding:4px 0; font-weight:600; color:#64748B;">ගණ</th>
                            <th style="text-align:right; width:60px; padding:4px 0; font-weight:600; color:#64748B;">මිල</th>
                            <th style="text-align:right; width:64px; padding:4px 4px 4px 0; font-weight:600; color:#64748B;">එකතුව</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in sale.items" :key="item.id" style="border-bottom:1px dashed #F1F5F9;">
                            <td style="padding:5px 4px 5px 0; word-break:break-word;">
                                <div>{{ item.product_name }}</div>
                                <div v-if="item.product?.name_si" style="font-size:11px; color:#64748B;">{{ item.product.name_si }}</div>
                            </td>
                            <td style="text-align:center; padding:5px 0;">{{ item.qty }}</td>
                            <td style="text-align:right; padding:5px 0;">{{ n(item.unit_price) }}</td>
                            <td style="text-align:right; padding:5px 4px 5px 0; font-weight:500;">{{ n(item.total) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="items-section divider" style="border-top:1px dashed #CBD5E1; margin:10px 0;"></div>

                <!-- Totals -->
                <div class="space-y-1.5 text-[12px]" style="color:#334155;">
                    <div class="flex justify-between">
                        <span>අතු ශේෂය</span>
                        <span>{{ n(sale.subtotal) }}</span>
                    </div>
                    <div v-if="Number(sale.discount) > 0" class="flex justify-between" style="color:#F59E0B;">
                        <span>වට්ටම</span>
                        <span>-{{ n(sale.discount) }}</span>
                    </div>
                    <div class="total-row flex justify-between font-bold text-[15px] pt-1" style="color:#0F172A; border-top:1px solid #E2E8F0;">
                        <span>මුළු</span>
                        <span style="color:#2563EB;">{{ currency }} {{ n(sale.total) }}</span>
                    </div>
                    <div class="flex justify-between" style="color:#16A34A;">
                        <span>ගෙවීම ({{ paymentLabel }})</span>
                        <span class="font-medium">{{ n(sale.payments?.[0]?.amount) }}</span>
                    </div>
                    <div v-if="Number(sale.balance) < 0" class="flex justify-between text-slate-500">
                        <span>ඉතුරු</span>
                        <span>{{ n(Math.abs(sale.balance)) }}</span>
                    </div>
                </div>

                <div class="divider" style="border-top:1px dashed #CBD5E1; margin:10px 0;"></div>

                <!-- Profit -->
                <div class="flex justify-between text-[12px] font-semibold" style="color:#16A34A;">
                    <span>ඔබ ලද ලාභය</span>
                    <span>{{ currency }} {{ n(profit) }}</span>
                </div>

                <div class="divider" style="border-top:1px dashed #CBD5E1; margin:10px 0;"></div>

                <!-- Footer -->
                <p class="text-center font-bold text-[13px]" style="color:#0F172A; white-space: pre-line;">{{ footer }}</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    @page {
        size: 80mm auto;
        margin: 0;
    }

    /* Remove screen-only card chrome, add explicit print padding */
    #receipt-card {
        width: 100% !important;
        padding: 4mm 5mm !important;
        margin: 0 !important;
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    }

    /* Center wrapper — switch to block so card takes full width */
    #receipt-wrapper {
        display: block !important;
        padding: 0 !important;
    }

    #receipt-card {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    #receipt-card .shop-title {
        font-size: 15px !important;
        font-weight: 700 !important;
    }

    #receipt-card .total-row span {
        font-size: 14px !important;
        font-weight: 700 !important;
    }

    #receipt-card .divider {
        border-top: 1px dashed #555 !important;
        margin: 5px 0 !important;
    }

    /* Hide items table on print */
    #receipt-card .items-section {
        display: none !important;
    }
}
</style>
