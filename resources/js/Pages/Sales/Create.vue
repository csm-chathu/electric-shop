<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, inject, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';

// ─── Props ────────────────────────────────────────────────────────────────────
const props = defineProps({
    customers:       { type: Array, default: () => [] },
    popularProducts: { type: Array, default: () => [] },
});

// ─── Fullscreen ───────────────────────────────────────────────────────────────
const posFullscreen = inject('posFullscreen');

function toggleFullscreen() {
    if (!posFullscreen?.value) {
        if (posFullscreen) posFullscreen.value = true;
        document.documentElement.requestFullscreen?.().catch(() => {});
    } else {
        exitFullscreen();
    }
}

function exitFullscreen() {
    if (posFullscreen) posFullscreen.value = false;
    if (document.fullscreenElement) {
        document.exitFullscreen?.().catch(() => {});
    }
}

function onFullscreenChange() {
    if (!document.fullscreenElement && posFullscreen) {
        posFullscreen.value = false;
    }
}

// ─── Auth / Permissions ───────────────────────────────────────────────────────
const page        = usePage();
const authUser    = computed(() => page.props.auth?.user);
const canDiscount = computed(() =>
    ['admin', 'manager'].includes(authUser.value?.role)
);

// ─── Refs ─────────────────────────────────────────────────────────────────────
const searchInput   = ref(null);
const dropdownList  = ref(null);
const searchQuery   = ref('');
const searchResults = ref([]);
const searching     = ref(false);
const searchTimer   = ref(null);
const showDropdown  = ref(false);
const activeIndex   = ref(-1);   // highlighted row in dropdown (-1 = none)

const cart             = ref([]);
const selectedCustomer = ref(null);
const priceMode        = ref('retail'); // 'retail' | 'wholesale'
const paymentMethod    = ref('cash');
const cashPaid         = ref('');
const billDiscount     = ref('');   // bill-level discount (Rs.)
const discountType     = ref('amount'); // 'amount' | 'percent'
const holdNote         = ref('');
const showHoldModal    = ref(false);
const submitting       = ref(false);
const errorMsg         = ref('');

// ─── Inertia form ─────────────────────────────────────────────────────────────
const form = useForm({
    items:          [],
    customer_id:    null,
    payment_method: 'cash',
    paid:           0,
    subtotal:       0,
    discount:       0,
    tax:            0,
    total:          0,
});

// ─── Computed totals ──────────────────────────────────────────────────────────
const subtotal = computed(() =>
    cart.value.reduce((s, i) => s + i.qty * i.unit_price, 0)
);
const lineDiscount = computed(() =>
    cart.value.reduce((s, i) => s + Number(i.discount || 0), 0)
);
const billDiscountAmt = computed(() => {
    const v = parseFloat(billDiscount.value) || 0;
    if (discountType.value === 'percent') {
        return Math.min((subtotal.value - lineDiscount.value) * v / 100, subtotal.value - lineDiscount.value);
    }
    return Math.min(v, subtotal.value - lineDiscount.value);
});
const totalDiscount = computed(() => lineDiscount.value + billDiscountAmt.value);
const tax     = computed(() => 0);
const total   = computed(() => Math.max(0, subtotal.value - totalDiscount.value + tax.value));
const balance = computed(() => (parseFloat(cashPaid.value) || 0) - total.value);

// ─── Dropdown items: popular when query empty, search results otherwise ───────
const dropdownItems = computed(() =>
    searchQuery.value.trim() === '' ? props.popularProducts : searchResults.value
);

// ─── Product search ───────────────────────────────────────────────────────────
function onSearchFocus() {
    activeIndex.value  = -1;
    showDropdown.value = dropdownItems.value.length > 0;
}

function onSearchInput() {
    activeIndex.value = -1;
    clearTimeout(searchTimer.value);
    const q = searchQuery.value.trim();
    if (!q) {
        searchResults.value = [];
        showDropdown.value  = props.popularProducts.length > 0;
        return;
    }
    searching.value = true;
    searchTimer.value = setTimeout(async () => {
        try {
            const res = await axios.get('/api/products/search', { params: { q } });
            searchResults.value = res.data;
            showDropdown.value  = res.data.length > 0;
        } catch {
            searchResults.value = [];
            showDropdown.value  = false;
        } finally {
            searching.value = false;
        }
    }, 200);
}

function onArrowDown(e) {
    if (!showDropdown.value) { showDropdown.value = dropdownItems.value.length > 0; return; }
    e.preventDefault();
    activeIndex.value = Math.min(activeIndex.value + 1, dropdownItems.value.length - 1);
    scrollActiveIntoView();
}

function onArrowUp(e) {
    if (!showDropdown.value) return;
    e.preventDefault();
    activeIndex.value = Math.max(activeIndex.value - 1, 0);
    scrollActiveIntoView();
}

function scrollActiveIntoView() {
    nextTick(() => {
        const list = dropdownList.value;
        if (!list) return;
        const item = list.children[activeIndex.value];
        item?.scrollIntoView({ block: 'nearest' });
    });
}

function onSearchEnter(e) {
    e.preventDefault();
    // If an item is highlighted via arrow keys, add it
    if (activeIndex.value >= 0 && dropdownItems.value[activeIndex.value]) {
        addToCart(dropdownItems.value[activeIndex.value]);
        return;
    }
    // No highlight: fall back to text/barcode match
    const q = searchQuery.value.trim();
    if (!q) return;
    const items = dropdownItems.value;
    let found = items.find(p => p.barcode && p.barcode.toLowerCase() === q.toLowerCase())
             || items.find(p => p.name.toLowerCase().includes(q.toLowerCase()))
             || (items.length === 1 ? items[0] : null);
    if (found) addToCart(found);
}

function setPriceMode(mode) {
    priceMode.value = mode;
    // Re-price all items already in cart
    cart.value.forEach(item => {
        const product = [...props.popularProducts, ...searchResults.value]
            .find(p => p.id === item.product_id);
        if (product) {
            const wsPrice = parseFloat(product.wholesale_price) || 0;
            item.unit_price = mode === 'wholesale' && wsPrice > 0
                ? wsPrice
                : parseFloat(product.selling_price) || 0;
            recalcLine(item);
        }
    });
}

function addToCart(product) {
    const isWeightUnit = ['kg', 'g', 'l', 'ml', 'liter'].includes((product.unit || '').toLowerCase());
    const existing = cart.value.find(i => i.product_id === product.id);

    searchQuery.value   = '';
    searchResults.value = [];
    showDropdown.value  = false;
    activeIndex.value   = -1;

    if (existing) {
        if (isWeightUnit) {
            nextTick(() => {
                const idx = cart.value.indexOf(existing);
                const inputs = document.querySelectorAll('.cart-qty-input');
                if (inputs[idx]) { inputs[idx].focus(); inputs[idx].select(); }
            });
            return; // skip refocusSearch for weight items
        } else {
            existing.qty += 1;
            recalcLine(existing);
        }
    } else {
        const wsPrice = parseFloat(product.wholesale_price) || 0;
        const unitPrice = priceMode.value === 'wholesale' && wsPrice > 0
            ? wsPrice
            : parseFloat(product.selling_price) || 0;

        cart.value.push({
            product_id:      product.id,
            name:            product.name_si ? `${product.name} / ${product.name_si}` : product.name,
            barcode:         product.barcode || '',
            qty:             isWeightUnit ? null : 1,
            unit_price:      unitPrice,
            selling_price:   parseFloat(product.selling_price) || 0,
            wholesale_price: wsPrice,
            discount:        0,
            total:           0,
            unit:            product.unit || 'pcs',
            stock_qty:       product.stock_qty || 0,
        });
        if (isWeightUnit) {
            nextTick(() => {
                const inputs = document.querySelectorAll('.cart-qty-input');
                const last = inputs[inputs.length - 1];
                if (last) { last.focus(); last.select(); }
            });
            return; // skip refocusSearch for weight items
        } else {
            recalcLine(cart.value[cart.value.length - 1]);
        }
    }
    refocusSearch();
}

function recalcLine(item) {
    item.total = item.qty * item.unit_price - Number(item.discount || 0);
}

function removeFromCart(index) {
    cart.value.splice(index, 1);
}

function updateQty(item, val) {
    const n = parseFloat(val);
    if (!isNaN(n) && n > 0) {
        item.qty = n;
        recalcLine(item);
    }
}

function updateDiscount(item, val) {
    item.discount = parseFloat(val) || 0;
    recalcLine(item);
}

function updatePrice(item, val) {
    const p = parseFloat(val);
    if (!isNaN(p) && p >= 0) {
        item.unit_price = p;
        recalcLine(item);
    }
}

// ─── Customer ─────────────────────────────────────────────────────────────────
function selectCustomer(e) {
    const id = parseInt(e.target.value);
    selectedCustomer.value = props.customers.find(c => c.id === id) || null;
}

// ─── Payment methods ──────────────────────────────────────────────────────────
function setPaymentMethod(method) {
    paymentMethod.value = method;
    if (method === 'cash') {
        nextTick(() => {
            document.getElementById('cash-paid-input')?.focus();
        });
    }
}

// ─── Submit sale ──────────────────────────────────────────────────────────────
function submitSale() {
    errorMsg.value = '';
    if (cart.value.length === 0) { errorMsg.value = 'කරත්තය හිස්ය.'; return; }
    if (total.value <= 0)        { errorMsg.value = 'මුළු මුදල් ශුන්‍ය විය නොහැක.'; return; }
    if (paymentMethod.value === 'credit' && !selectedCustomer.value) {
        errorMsg.value = 'ණය විකුණුමකට ගනුදෙනුකරු අවශ්‍ය.'; return;
    }
    if (paymentMethod.value === 'cash') {
        const paid = parseFloat(cashPaid.value) || 0;
        if (paid < total.value) { errorMsg.value = 'ගෙවූ මුදල ප්‍රමාණවත් නොවේ.'; return; }
    }
    submitting.value = true;
    form.items          = cart.value.map(i => ({
        product_id: i.product_id,
        name:       i.name,
        qty:        i.qty,
        unit_price: i.unit_price,
        discount:   i.discount,
        total:      i.total,
    }));
    form.customer_id    = selectedCustomer.value?.id || null;
    form.payment_method = paymentMethod.value;
    form.paid           = paymentMethod.value === 'cash'
        ? parseFloat(cashPaid.value) || 0
        : paymentMethod.value === 'credit'
            ? parseFloat(cashPaid.value) || 0
            : total.value;
    form.subtotal       = subtotal.value;
    form.discount       = totalDiscount.value;
    form.tax            = tax.value;
    form.total          = total.value;

    form.post(route('sales.store'), {
        onError: (errors) => {
            submitting.value = false;
            errorMsg.value   = Object.values(errors)[0] || 'දෝෂයකි.';
        },
    });
}

// ─── Hold bill ────────────────────────────────────────────────────────────────
function holdBill() {
    if (cart.value.length === 0) return;
    showHoldModal.value = true;
}

function confirmHold() {
    // Store held bill in localStorage for later retrieval
    const held = JSON.parse(localStorage.getItem('heldBills') || '[]');
    held.push({
        id:         Date.now(),
        note:       holdNote.value,
        cart:       cart.value,
        customer:   selectedCustomer.value,
        createdAt:  new Date().toISOString(),
    });
    localStorage.setItem('heldBills', JSON.stringify(held));
    cart.value             = [];
    selectedCustomer.value = null;
    cashPaid.value         = '';
    billDiscount.value     = '';
    holdNote.value         = '';
    showHoldModal.value    = false;
    refocusSearch();
}

// ─── Keyboard shortcuts ───────────────────────────────────────────────────────
function handleKeyboard(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
        // Allow Enter on search input to bubble to onSearchEnter
        return;
    }
    switch (e.key) {
        case 'F2': e.preventDefault(); setPaymentMethod('cash');   break;
        case 'F3': e.preventDefault(); setPaymentMethod('card');   break;
        case 'F4': e.preventDefault(); setPaymentMethod('qr');     break;
        case 'F5': e.preventDefault(); holdBill();                 break;
        case 'Enter': e.preventDefault(); submitSale();            break;
    }
}

function handleGlobalKeyboard(e) {
    switch (e.key) {
        case 'F1':     e.preventDefault(); refocusSearch();          break;
        case 'F2':     e.preventDefault(); setPaymentMethod('cash'); break;
        case 'F3':     e.preventDefault(); setPaymentMethod('card'); break;
        case 'F4':     e.preventDefault(); setPaymentMethod('credit'); break;
        case 'F5':     e.preventDefault(); holdBill();               break;
        case 'F10':    e.preventDefault(); submitSale();             break;
        case 'Escape':
            if (posFullscreen?.value && !showDropdown.value && !showHoldModal.value) {
                e.preventDefault();
                exitFullscreen();
            }
            break;
    }
}

function refocusSearch() {
    nextTick(() => searchInput.value?.focus());
}

onMounted(() => {
    window.addEventListener('keydown', handleGlobalKeyboard);
    document.addEventListener('fullscreenchange', onFullscreenChange);
    refocusSearch();
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeyboard);
    document.removeEventListener('fullscreenchange', onFullscreenChange);
    // Restore sidebar if navigating away while in fullscreen
    if (posFullscreen?.value) exitFullscreen();
});

// ─── Formatting ───────────────────────────────────────────────────────────────
function fmt(val) {
    return 'Rs. ' + Number(val || 0).toLocaleString('en-LK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}
</script>

<template>
    <Head title="නව විකුණුම" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2 w-full">
                <Link :href="route('sales.index')" class="text-gray-400 hover:text-gray-600 flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-bold text-gray-800">නව විකුණුම / POS Billing</h1>
                <!-- Retail / Wholesale toggle -->
                <div class="ml-auto flex items-center rounded-lg overflow-hidden text-sm font-semibold" style="border:1px solid #E2E8F0;">
                    <button
                        type="button"
                        @click="setPriceMode('retail')"
                        class="px-3 py-1.5 transition-colors"
                        :style="priceMode === 'retail'
                            ? 'background-color:#2563EB; color:#fff;'
                            : 'background-color:#F8FAFC; color:#64748B;'"
                    >සිල්ලර</button>
                    <button
                        type="button"
                        @click="setPriceMode('wholesale')"
                        class="px-3 py-1.5 transition-colors"
                        :style="priceMode === 'wholesale'
                            ? 'background-color:#16A34A; color:#fff;'
                            : 'background-color:#F8FAFC; color:#64748B;'"
                    >තොග</button>
                </div>

                <span class="text-xs text-gray-400 hidden sm:block">F1=Search · F2=Cash · F3=Card · F4=Credit · F5=Hold · F10=Complete</span>

                <!-- Fullscreen toggle button -->
                <button
                    type="button"
                    @click="toggleFullscreen"
                    class="flex-shrink-0 ml-2 p-2 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                    :title="posFullscreen ? 'Exit Fullscreen (Esc)' : 'Fullscreen'"
                >
                    <!-- Enter fullscreen icon -->
                    <svg v-if="!posFullscreen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                    <!-- Exit fullscreen icon -->
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25" />
                    </svg>
                </button>
            </div>
        </template>

        <!-- Error banner -->
        <div v-if="errorMsg" class="mb-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center justify-between">
            <span class="text-sm font-medium">{{ errorMsg }}</span>
            <button @click="errorMsg = ''" class="text-red-500 hover:text-red-700 ml-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <!-- ── Main two-column layout ── -->
        <div class="flex flex-col lg:flex-row gap-4 h-full">

            <!-- ══════════════════════════════════════════
                 LEFT PANEL: Search + Cart
            ═══════════════════════════════════════════ -->
            <div class="flex-1 lg:w-[60%] flex flex-col gap-3 min-w-0">

                <!-- Barcode / Product Search -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            ref="searchInput"
                            v-model="searchQuery"
                            type="text"
                            placeholder="භාණ්ඩ සොයන්න / Barcode"
                            autocomplete="off"
                            class="w-full pl-12 pr-4 py-4 text-lg border-2 border-blue-200 rounded-xl focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 min-h-[60px] font-medium"
                            @focus="onSearchFocus"
                            @input="onSearchInput"
                            @keydown.enter="onSearchEnter"
                            @keydown.arrow-down="onArrowDown"
                            @keydown.arrow-up="onArrowUp"
                            @keydown.escape="showDropdown = false; searchQuery = ''; activeIndex = -1"
                            @blur="setTimeout(() => { showDropdown = false; activeIndex = -1 }, 150)"
                        />
                        <div v-if="searching" class="absolute inset-y-0 right-0 pr-4 flex items-center">
                            <svg class="animate-spin h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>

                        <!-- Dropdown -->
                        <div
                            v-if="showDropdown && dropdownItems.length"
                            ref="dropdownList"
                            class="absolute top-full left-0 right-0 z-50 bg-white border border-gray-200 rounded-xl shadow-xl mt-1 max-h-80 overflow-y-auto"
                        >
                            <!-- Header label when showing popular items -->
                            <div v-if="!searchQuery.trim()" class="px-4 py-1.5 bg-gray-50 border-b border-gray-100 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                                ජනප්‍රිය භාණ්ඩ
                            </div>

                            <button
                                v-for="(product, idx) in dropdownItems"
                                :key="product.id"
                                type="button"
                                class="w-full text-left px-4 py-3 flex items-center justify-between border-b border-gray-50 last:border-b-0 transition-colors"
                                :class="idx === activeIndex
                                    ? 'bg-blue-600 text-white'
                                    : 'hover:bg-blue-50'"
                                @mousedown.prevent="addToCart(product)"
                                @mouseover="activeIndex = idx"
                            >
                                <div>
                                    <p class="font-medium text-sm" :class="idx === activeIndex ? 'text-white' : 'text-gray-800'">{{ product.name }}</p>
                                    <p v-if="product.name_si" class="text-xs" :class="idx === activeIndex ? 'text-blue-200' : 'text-gray-500'">{{ product.name_si }}</p>
                                    <p class="text-xs font-mono" :class="idx === activeIndex ? 'text-blue-300' : 'text-gray-400'">{{ product.barcode }}</p>
                                </div>
                                <div class="text-right ml-4 flex-shrink-0">
                                    <p class="font-bold" :class="idx === activeIndex ? 'text-white' : 'text-blue-700'">{{ fmt(product.selling_price) }}</p>
                                    <p class="text-xs" :class="idx === activeIndex ? 'text-blue-200' : (product.stock_qty > 0 ? 'text-green-600' : 'text-red-500')">
                                        තොගය: {{ product.stock_qty }} {{ product.unit }}
                                    </p>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Cart Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex-1 flex flex-col overflow-hidden">
                    <!-- Cart header -->
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                        <h2 class="font-bold text-gray-700 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            ගණකය
                        </h2>
                        <span class="text-sm text-gray-500">{{ cart.length }} භාණ්ඩ</span>
                    </div>

                    <!-- Empty state -->
                    <div v-if="cart.length === 0" class="flex-1 flex flex-col items-center justify-center py-16 text-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <p class="text-sm">කරත්තය හිස්ය</p>
                        <p class="text-xs mt-1">භාණ්ඩ සොයා ඇතුළත් කරන්න</p>
                    </div>

                    <!-- Cart items table (desktop) -->
                    <div v-else class="overflow-auto flex-1">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="px-4 py-2.5 text-left">භාණ්ඩය</th>
                                    <th class="px-3 py-2.5 text-center w-24">ප්‍රමාණය</th>
                                    <th class="px-3 py-2.5 text-right w-28">මිල</th>
                                    <th v-if="canDiscount" class="px-3 py-2.5 text-right w-28">වට්ටම</th>
                                    <th class="px-3 py-2.5 text-right w-28">මුළු</th>
                                    <th class="px-3 py-2.5 w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(item, idx) in cart"
                                    :key="item.product_id"
                                    class="border-b border-gray-50 transition-colors"
                                    :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50/50'"
                                >
                                    <td class="px-4 py-2.5">
                                        <p class="font-medium text-gray-800 leading-tight">{{ item.name }}</p>
                                        <p class="text-xs text-gray-400 font-mono">{{ item.barcode }}</p>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <input
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            :value="item.qty ?? ''"
                                            :placeholder="['kg','g','l','ml','liter'].includes(item.unit) ? 'kg' : '1'"
                                            @input="e => updateQty(item, e.target.value)"
                                            class="cart-qty-input w-20 text-center border border-gray-300 rounded-lg py-1.5 px-2 focus:outline-none focus:ring-2 focus:ring-blue-300 text-sm font-medium"
                                        />
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            :value="item.unit_price"
                                            @change="e => updatePrice(item, e.target.value)"
                                            class="w-24 text-right border border-gray-200 rounded-lg py-1.5 px-2 focus:outline-none focus:ring-2 focus:ring-blue-300 text-sm font-medium text-gray-700"
                                        />
                                    </td>
                                    <td v-if="canDiscount" class="px-3 py-2.5">
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            :value="item.discount"
                                            @change="e => updateDiscount(item, e.target.value)"
                                            class="w-24 text-right border border-gray-300 rounded-lg py-1.5 px-2 focus:outline-none focus:ring-2 focus:ring-orange-300 text-sm"
                                        />
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-bold text-blue-700">
                                        {{ fmt(item.total) }}
                                    </td>
                                    <td class="px-2 py-2.5 text-center">
                                        <button
                                            type="button"
                                            @click="removeFromCart(idx)"
                                            class="text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg p-1.5 transition-colors"
                                            title="ඉවත් කරන්න"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Cart footer totals (inline summary) -->
                    <div v-if="cart.length > 0" class="border-t border-gray-100 px-4 py-3 bg-gray-50 flex flex-wrap gap-4 justify-end text-sm">
                        <span class="text-gray-500">අතු ශේෂය: <strong class="text-gray-800">{{ fmt(subtotal) }}</strong></span>
                        <span v-if="totalDiscount > 0" class="text-orange-600">වට්ටම: <strong>-{{ fmt(totalDiscount) }}</strong></span>
                        <span class="text-blue-700 font-bold text-base">ගෙවිය යුතු: {{ fmt(total) }}</span>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════
                 RIGHT PANEL: Customer + Payment
            ═══════════════════════════════════════════ -->
            <div class="lg:w-[40%] flex flex-col gap-3">

                <!-- Customer selector -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        ලෙඩා / Customer
                    </label>
                    <select
                        @change="selectCustomer"
                        class="w-full border border-gray-300 rounded-lg px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 min-h-[48px] bg-white"
                    >
                        <option value="">සාමාන්‍ය ගනුදෙනුකරු</option>
                        <option v-for="c in customers" :key="c.id" :value="c.id">
                            {{ c.name }} {{ c.phone ? `· ${c.phone}` : '' }}
                        </option>
                    </select>
                    <div v-if="selectedCustomer" class="mt-2 flex items-center gap-2 text-xs text-blue-700 bg-blue-50 rounded-lg px-3 py-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="font-medium">{{ selectedCustomer.name }}</span>
                        <span v-if="selectedCustomer.phone" class="text-blue-500">{{ selectedCustomer.phone }}</span>
                    </div>
                </div>

                <!-- Totals summary + bill discount -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 space-y-2">
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>අතු ශේෂය</span>
                        <span class="font-medium">{{ fmt(subtotal) }}</span>
                    </div>

                    <!-- Bill-level discount row -->
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-orange-600 flex-shrink-0">වට්ටම</span>
                        <div class="flex flex-1 gap-1">
                            <input
                                v-model="billDiscount"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="0"
                                :disabled="cart.length === 0"
                                class="flex-1 min-w-0 border border-orange-300 rounded-lg px-2 py-1.5 text-sm text-right focus:outline-none focus:ring-2 focus:ring-orange-300 disabled:bg-gray-50 disabled:text-gray-400"
                            />
                            <!-- Rs / % toggle -->
                            <button
                                type="button"
                                @click="discountType = discountType === 'amount' ? 'percent' : 'amount'"
                                class="flex-shrink-0 w-10 rounded-lg border text-xs font-bold transition-colors"
                                :class="discountType === 'percent'
                                    ? 'border-orange-400 bg-orange-500 text-white'
                                    : 'border-orange-300 bg-orange-50 text-orange-600'"
                            >
                                {{ discountType === 'percent' ? '%' : 'Rs' }}
                            </button>
                        </div>
                        <span class="text-sm font-medium text-orange-600 flex-shrink-0 w-20 text-right">
                            -{{ fmt(billDiscountAmt) }}
                        </span>
                    </div>

                    <div v-if="lineDiscount > 0" class="flex justify-between text-xs text-orange-400">
                        <span>රේඛා වට්ටම</span>
                        <span>-{{ fmt(lineDiscount) }}</span>
                    </div>
                    <div v-if="tax > 0" class="flex justify-between text-sm text-gray-600">
                        <span>බදු</span>
                        <span class="font-medium">{{ fmt(tax) }}</span>
                    </div>
                    <div class="border-t border-gray-100 pt-2 flex justify-between items-baseline">
                        <span class="billing-total-label font-bold text-gray-800">ගෙවිය යුතු</span>
                        <span class="billing-total-amount font-bold text-blue-700">{{ fmt(total) }}</span>
                    </div>
                </div>

                <!-- Payment method buttons -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">ගෙවීමේ ක්‍රමය</p>
                    <div class="grid grid-cols-3 gap-2">
                        <!-- Cash F2 -->
                        <button
                            type="button"
                            @click="setPaymentMethod('cash')"
                            class="flex flex-col items-center justify-center gap-1 rounded-xl border-2 py-3 min-h-[68px] transition-all font-semibold text-sm"
                            :class="paymentMethod === 'cash'
                                ? 'border-green-500 bg-green-50 text-green-700 shadow-sm shadow-green-100'
                                : 'border-gray-200 bg-white text-gray-500 hover:border-green-300 hover:bg-green-50/50'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="text-xs">මුදල් [F2]</span>
                        </button>

                        <!-- Card F3 -->
                        <button
                            type="button"
                            @click="setPaymentMethod('card')"
                            class="flex flex-col items-center justify-center gap-1 rounded-xl border-2 py-3 min-h-[68px] transition-all font-semibold text-sm"
                            :class="paymentMethod === 'card'
                                ? 'border-blue-500 bg-blue-50 text-blue-700 shadow-sm shadow-blue-100'
                                : 'border-gray-200 bg-white text-gray-500 hover:border-blue-300 hover:bg-blue-50/50'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <span class="text-xs">කාඩ් [F3]</span>
                        </button>

                        <!-- Credit F4 -->
                        <button
                            type="button"
                            @click="setPaymentMethod('credit')"
                            class="flex flex-col items-center justify-center gap-1 rounded-xl border-2 py-3 min-h-[68px] transition-all font-semibold text-sm"
                            :class="paymentMethod === 'credit'
                                ? 'border-red-500 bg-red-50 text-red-700 shadow-sm shadow-red-100'
                                : 'border-gray-200 bg-white text-gray-500 hover:border-red-300 hover:bg-red-50/50'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            <span class="text-xs">ණය [F4]</span>
                        </button>
                    </div>

                    <!-- Credit warning -->
                    <div v-if="paymentMethod === 'credit' && !selectedCustomer" class="mt-2 text-xs text-red-600 bg-red-50 rounded-lg px-3 py-2 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        ණය විකුණුමකට ඉහළින් ගනුදෙනුකරු තෝරන්න
                    </div>
                </div>

                <!-- Cash / Credit payment: amount paid + balance -->
                <div v-if="paymentMethod === 'cash' || paymentMethod === 'credit'" class="bg-white rounded-xl shadow-sm border p-4 space-y-3" :class="paymentMethod === 'credit' ? 'border-red-200' : 'border-green-200'">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            {{ paymentMethod === 'credit' ? 'අත්තිකාරම් (ඇතිනම්)' : 'ගෙවූ මුදල (Amount Paid)' }}
                        </label>
                        <input
                            id="cash-paid-input"
                            v-model="cashPaid"
                            type="number"
                            min="0"
                            step="0.01"
                            :placeholder="paymentMethod === 'credit' ? '0.00 (optional)' : '0.00'"
                            class="w-full border-2 rounded-xl px-4 py-3 text-xl font-bold focus:outline-none min-h-[56px]"
                            :class="paymentMethod === 'credit'
                                ? 'border-red-300 text-red-800 focus:ring-2 focus:ring-red-400 focus:border-red-500'
                                : 'border-green-300 text-green-800 focus:ring-2 focus:ring-green-400 focus:border-green-500'"
                        />
                    </div>
                    <div v-if="cashPaid" class="flex items-center justify-between rounded-lg px-4 py-3"
                        :class="balance >= 0 ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'"
                    >
                        <span class="font-semibold text-sm" :class="balance >= 0 ? 'text-green-700' : 'text-red-700'">ශේෂය</span>
                        <span class="text-xl font-bold" :class="balance >= 0 ? 'text-green-700' : 'text-red-600'">
                            {{ fmt(Math.abs(balance)) }}
                            <span class="text-sm font-normal ml-1">{{ balance < 0 ? '(ඉතිරි)' : '(ඉතුරු)' }}</span>
                        </span>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="flex flex-col gap-2 mt-auto">
                    <!-- Complete Sale -->
                    <button
                        type="button"
                        @click="submitSale"
                        :disabled="cart.length === 0 || submitting || form.processing"
                        class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold text-lg py-4 rounded-xl transition-colors min-h-[64px] shadow-lg shadow-blue-100"
                    >
                        <svg v-if="!submitting && !form.processing" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <svg v-else class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span>{{ submitting || form.processing ? 'සකසමින්...' : 'ගෙවීම සම්පූර්ණ කරන්න' }}</span>
                        <span v-if="!submitting && !form.processing" class="text-sm font-normal opacity-70">[F10]</span>
                    </button>

                    <!-- Hold Bill -->
                    <button
                        type="button"
                        @click="holdBill"
                        :disabled="cart.length === 0"
                        class="w-full flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed text-white font-semibold py-3.5 rounded-xl transition-colors min-h-[56px]"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>බිල රඳවන්න</span>
                        <span class="text-sm font-normal opacity-70">[F5]</span>
                    </button>

                    <!-- Clear cart -->
                    <button
                        v-if="cart.length > 0"
                        type="button"
                        @click="cart = []; selectedCustomer = null; cashPaid = ''; errorMsg = ''; refocusSearch()"
                        class="w-full text-center text-sm text-gray-400 hover:text-red-500 py-2 transition-colors"
                    >
                        කරත්තය හිස් කරන්න
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ Hold Bill Modal ══ -->
        <Teleport to="body">
            <div v-if="showHoldModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
                <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 space-y-4">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        බිල රඳවන්න
                    </h2>
                    <p class="text-sm text-gray-500">{{ cart.length }} භාණ්ඩ ({{ fmt(total) }}) රඳවනු ලැබේ.</p>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">සටහන (ऐच्छिक)</label>
                        <input
                            v-model="holdNote"
                            type="text"
                            placeholder="ගනුදෙනුකරු / හේතු..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 min-h-[44px]"
                        />
                    </div>
                    <div class="flex gap-3">
                        <button
                            type="button"
                            @click="confirmHold"
                            class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3 rounded-xl transition-colors min-h-[48px]"
                        >
                            රඳවන්න
                        </button>
                        <button
                            type="button"
                            @click="showHoldModal = false"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 rounded-xl transition-colors min-h-[48px]"
                        >
                            ඉවත්වෙන්න 
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
