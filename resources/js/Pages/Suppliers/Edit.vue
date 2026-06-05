<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    supplier: { type: Object, required: true },
});

const form = useForm({
    name: props.supplier.name || '',
    company: props.supplier.company || '',
    phone: props.supplier.phone || '',
    email: props.supplier.email || '',
    address: props.supplier.address || '',
    active: props.supplier.active ?? true,
});

function submit() {
    form.put(route('suppliers.update', props.supplier.id));
}
</script>

<template>
    <Head title="සැපයුම්කරු සංස්කරණය" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('suppliers.index')" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-bold text-gray-800">සැපයුම්කරු සංස්කරණය</h1>
            </div>
        </template>

        <div class="max-w-lg">
            <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">නම <span class="text-red-500">*</span></label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                        :class="{ 'border-red-500': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">සමාගම</label>
                    <input
                        v-model="form.company"
                        type="text"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">දුරකථනය</label>
                        <input
                            v-model="form.phone"
                            type="tel"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                            :class="{ 'border-red-500': form.errors.phone }"
                        />
                        <p v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ඊමේල්</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                            :class="{ 'border-red-500': form.errors.email }"
                        />
                        <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ලිපිනය</label>
                    <textarea
                        v-model="form.address"
                        rows="3"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    ></textarea>
                </div>

                <div class="flex items-center justify-between py-2">
                    <div>
                        <p class="text-sm font-medium text-gray-700">ක්‍රියාත්මකද?</p>
                    </div>
                    <button
                        type="button"
                        @click="form.active = !form.active"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                        :class="form.active ? 'bg-blue-600' : 'bg-gray-300'"
                    >
                        <span
                            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow"
                            :class="form.active ? 'translate-x-6' : 'translate-x-1'"
                        ></span>
                    </button>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-medium py-3 px-6 rounded-lg transition-colors min-h-[44px]"
                    >
                        {{ form.processing ? 'සුරකිමින්...' : 'සුරකින්න' }}
                    </button>
                    <Link
                        :href="route('suppliers.index')"
                        class="flex-1 text-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-6 rounded-lg transition-colors min-h-[44px] flex items-center justify-center"
                    >
                        ඉවත්වෙන්න  කරන්න
                    </Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
