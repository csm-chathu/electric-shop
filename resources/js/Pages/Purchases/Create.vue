<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    suppliers: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
});

const form = useForm({
    supplier_id: '',
    note: '',
    items: [
        { product_id: '', qty: 1, cost_price: 0 }
    ],
});

function addRow() {
    form.items.push({ product_id: '', qty: 1, cost_price: 0 });
}

function removeRow(index) {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
}

function onProductChange(index) {
    const item = form.items[index];
    const product = props.products.find(p => p.id == item.product_id);
    if (product) {
        item.cost_price = product.cost_price || 0;
    }
}

function getProductName(id) {
    const product = props.products.find(p => p.id == id);
    return product ? product.name : '';
}

const grandTotal = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + (Number(item.qty || 0) * Number(item.cost_price || 0));
    }, 0);
});

function formatCurrency(value) {
    return 'Rs. ' + Number(value || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function submit() {
    form.post(route('purchases.store'));
}
</script>

<template>
    <Head title="නව මිලදී ගැනීම" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('purchases.index')" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-bold text-gray-800">නව මිලදී ගැනීම</h1>
            </div>
        </template>

        <div class="max-w-4xl">
            <form @submit.prevent="submit" class="space-y-4">

                <!-- Header Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wider">සාමාන්‍ය තොරතුරු</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">සැපයුම්කරු <span class="text-red-500">*</span></label>
                            <select
                                v-model="form.supplier_id"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                                :class="{ 'border-red-500': form.errors.supplier_id }"
                            >
                                <option value="">සැපයුම්කරුවෙකු තෝරන්න</option>
                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                    {{ supplier.name }}{{ supplier.company ? ` - ${supplier.company}` : '' }}
                                </option>
                            </select>
                            <p v-if="form.errors.supplier_id" class="text-red-500 text-xs mt-1">{{ form.errors.supplier_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">සටහන</label>
                            <input
                                v-model="form.note"
                                type="text"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                                placeholder="විකල්ප සටහනක්..."
                            />
                        </div>
                    </div>
                </div>

                <!-- Items Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">භාණ්ඩ</h2>
                        <button
                            type="button"
                            @click="addRow"
                            class="inline-flex items-center bg-green-50 hover:bg-green-100 text-green-700 px-3 py-2 rounded-lg text-sm font-medium transition-colors min-h-[44px]"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            රේඛාව එකතු කරන්න
                        </button>
                    </div>

                    <!-- Desktop table-like layout -->
                    <div class="hidden md:block">
                        <div class="grid grid-cols-12 gap-2 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <div class="col-span-5">භාණ්ඩය</div>
                            <div class="col-span-2 text-center">ප්‍රමාණය</div>
                            <div class="col-span-3">ඒකක මිල (Rs.)</div>
                            <div class="col-span-2 text-right">එකතුව</div>
                        </div>
                        <div
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="grid grid-cols-12 gap-2 mb-2 items-center"
                        >
                            <div class="col-span-5">
                                <select
                                    v-model="item.product_id"
                                    @change="onProductChange(index)"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                                    :class="{ 'border-red-500': form.errors[`items.${index}.product_id`] }"
                                >
                                    <option value="">භාණ්ඩ තෝරන්න</option>
                                    <option v-for="product in products" :key="product.id" :value="product.id">
                                        {{ product.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <input
                                    v-model="item.qty"
                                    type="number"
                                    min="1"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm text-center focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                                />
                            </div>
                            <div class="col-span-3">
                                <input
                                    v-model="item.cost_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                                />
                            </div>
                            <div class="col-span-1 text-right font-medium text-gray-700 text-sm">
                                {{ formatCurrency(item.qty * item.cost_price) }}
                            </div>
                            <div class="col-span-1 flex justify-end">
                                <button
                                    type="button"
                                    @click="removeRow(index)"
                                    :disabled="form.items.length === 1"
                                    class="text-red-400 hover:text-red-600 disabled:opacity-30 p-1"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile card layout for items -->
                    <div class="md:hidden space-y-3">
                        <div
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="border border-gray-200 rounded-lg p-3 space-y-3"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-600">රේඛාව {{ index + 1 }}</span>
                                <button
                                    type="button"
                                    @click="removeRow(index)"
                                    :disabled="form.items.length === 1"
                                    class="text-red-400 hover:text-red-600 disabled:opacity-30"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">භාණ්ඩය</label>
                                <select
                                    v-model="item.product_id"
                                    @change="onProductChange(index)"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                                >
                                    <option value="">භාණ්ඩ තෝරන්න</option>
                                    <option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }}</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">ප්‍රමාණය</label>
                                    <input
                                        v-model="item.qty"
                                        type="number"
                                        min="1"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1">ඒකක මිල</label>
                                    <input
                                        v-model="item.cost_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                                    />
                                </div>
                            </div>
                            <div class="text-right font-semibold text-blue-600">
                                {{ formatCurrency(item.qty * item.cost_price) }}
                            </div>
                        </div>
                    </div>

                    <p v-if="form.errors.items" class="text-red-500 text-xs mt-2">{{ form.errors.items }}</p>
                </div>

                <!-- Totals & Submit Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <span class="text-lg font-semibold text-gray-800">මුළු එකතුව</span>
                        <span class="text-2xl font-bold text-blue-600">{{ formatCurrency(grandTotal) }}</span>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-medium py-3 px-6 rounded-lg transition-colors min-h-[44px] flex items-center justify-center gap-2"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ form.processing ? 'සුරකිමින්...' : 'මිලදී ගැනීම සුරකින්න' }}
                        </button>
                        <Link
                            :href="route('purchases.index')"
                            class="flex-1 text-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-6 rounded-lg transition-colors min-h-[44px] flex items-center justify-center"
                        >
                            ඉවත්වෙන්න  කරන්න
                        </Link>
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
