<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    product: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
});

const form = useForm({
    category_id: props.product.category_id || '',
    name: props.product.name || '',
    name_si: props.product.name_si || '',
    barcode: props.product.barcode || '',
    sku: props.product.sku || '',
    cost_price: props.product.cost_price || '',
    selling_price: props.product.selling_price || '',
    wholesale_price: props.product.wholesale_price || '',
    stock_qty: props.product.stock_qty || 0,
    alert_qty: props.product.alert_qty || 5,
    unit: props.product.unit || 'pcs',
    description: props.product.description || '',
    active: props.product.active ?? true,
});

function submit() {
    form.put(route('products.update', props.product.id));
}
</script>

<template>
    <Head title="භාණ්ඩ සංස්කරණය" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('products.index')" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-bold text-gray-800">භාණ්ඩ සංස්කරණය</h1>
            </div>
        </template>

        <div class="max-w-2xl">
            <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">කාණ්ඩය <span class="text-red-500">*</span></label>
                    <select
                        v-model="form.category_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                        :class="{ 'border-red-500': form.errors.category_id }"
                    >
                        <option value="">කාණ්ඩයක් තෝරන්න</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                    <p v-if="form.errors.category_id" class="text-red-500 text-xs mt-1">{{ form.errors.category_id }}</p>
                </div>

                <!-- Names -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">නම (English) <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                            :class="{ 'border-red-500': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">නම (සිංහල)</label>
                        <input
                            v-model="form.name_si"
                            type="text"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                        />
                    </div>
                </div>

                <!-- Barcode / SKU -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">බාකෝඩ්</label>
                        <input
                            v-model="form.barcode"
                            type="text"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px] font-mono"
                        />
                        <p v-if="form.errors.barcode" class="text-red-500 text-xs mt-1">{{ form.errors.barcode }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                        <input
                            v-model="form.sku"
                            type="text"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                        />
                    </div>
                </div>

                <!-- Prices -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">මිලදී ගැනීමේ මිල (Rs.)</label>
                        <input
                            v-model="form.cost_price"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                            :class="{ 'border-red-500': form.errors.cost_price }"
                        />
                        <p v-if="form.errors.cost_price" class="text-red-500 text-xs mt-1">{{ form.errors.cost_price }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">විකුණුම් මිල (Rs.) <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.selling_price"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                            :class="{ 'border-red-500': form.errors.selling_price }"
                        />
                        <p v-if="form.errors.selling_price" class="text-red-500 text-xs mt-1">{{ form.errors.selling_price }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">තොග මිල (Rs.)</label>
                        <input
                            v-model="form.wholesale_price"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                            :class="{ 'border-red-500': form.errors.wholesale_price }"
                            placeholder="0.00"
                        />
                        <p v-if="form.errors.wholesale_price" class="text-red-500 text-xs mt-1">{{ form.errors.wholesale_price }}</p>
                    </div>
                </div>

                <!-- Stock -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">තොගය</label>
                        <input
                            v-model="form.stock_qty"
                            type="number"
                            min="0"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">අඩු සීමාව</label>
                        <input
                            v-model="form.alert_qty"
                            type="number"
                            min="0"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ඒකකය</label>
                        <select
                            v-model="form.unit"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                        >
                            <option value="pcs">pcs</option>
                            <option value="kg">kg</option>
                            <option value="g">g</option>
                            <option value="l">l</option>
                            <option value="ml">ml</option>
                            <option value="m">m</option>
                            <option value="box">box</option>
                            <option value="pack">pack</option>
                        </select>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">විස්තරය</label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    ></textarea>
                </div>

                <!-- Active toggle -->
                <div class="flex items-center justify-between py-2">
                    <div>
                        <p class="text-sm font-medium text-gray-700">ක්‍රියාත්මකද?</p>
                        <p class="text-xs text-gray-400">භාණ්ඩය විකිණීමට ලබා දෙන්නද?</p>
                    </div>
                    <button
                        type="button"
                        @click="form.active = !form.active"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="form.active ? 'bg-blue-600' : 'bg-gray-300'"
                    >
                        <span
                            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow"
                            :class="form.active ? 'translate-x-6' : 'translate-x-1'"
                        ></span>
                    </button>
                </div>

                <!-- Buttons -->
                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-medium py-3 px-6 rounded-lg transition-colors min-h-[44px]"
                    >
                        {{ form.processing ? 'සුරකිමින්...' : 'සුරකින්න' }}
                    </button>
                    <Link
                        :href="route('products.index')"
                        class="flex-1 text-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-6 rounded-lg transition-colors min-h-[44px] flex items-center justify-center"
                    >
                        ඉවත්වෙන්න  කරන්න
                    </Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
