<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    phone: '',
    email: '',
    address: '',
    credit_limit: 0,
});

function submit() {
    form.post(route('customers.store'));
}
</script>

<template>
    <Head title="නව පාරිභෝගිකයා" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('customers.index')" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-bold text-gray-800">නව පාරිභෝගිකයා</h1>
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
                        placeholder="පාරිභෝගික නම"
                        autofocus
                    />
                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">දුරකථනය</label>
                        <input
                            v-model="form.phone"
                            type="tel"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                            :class="{ 'border-red-500': form.errors.phone }"
                            placeholder="07X XXX XXXX"
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
                            placeholder="email@example.com"
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
                        placeholder="ලිපිනය..."
                    ></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ණය සීමාව (Rs.)</label>
                    <input
                        v-model="form.credit_limit"
                        type="number"
                        step="0.01"
                        min="0"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                        :class="{ 'border-red-500': form.errors.credit_limit }"
                    />
                    <p v-if="form.errors.credit_limit" class="text-red-500 text-xs mt-1">{{ form.errors.credit_limit }}</p>
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
                        :href="route('customers.index')"
                        class="flex-1 text-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-6 rounded-lg transition-colors min-h-[44px] flex items-center justify-center"
                    >
                        ඉවත්වෙන්න  කරන්න
                    </Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
