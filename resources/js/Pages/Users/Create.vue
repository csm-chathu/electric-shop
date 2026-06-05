<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'cashier',
});

const showPassword = ref(false);

function submit() {
    form.post(route('users.store'));
}
</script>

<template>
    <Head title="නව පරිශීලක" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2">
                <Link :href="route('users.index')" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h1 class="text-xl font-bold text-gray-800">නව පරිශීලක</h1>
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
                        placeholder="පූර්ණ නම"
                        autofocus
                    />
                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ඊමේල් <span class="text-red-500">*</span></label>
                    <input
                        v-model="form.email"
                        type="email"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                        :class="{ 'border-red-500': form.errors.email }"
                        placeholder="user@example.com"
                    />
                    <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">රහස් වචනය <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                            :class="{ 'border-red-500': form.errors.password }"
                            placeholder="••••••••"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        >
                            <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">රහස් වචනය තහවුරු කරන්න <span class="text-red-500">*</span></label>
                    <input
                        v-model="form.password_confirmation"
                        :type="showPassword ? 'text' : 'password'"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                        placeholder="••••••••"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">භූමිකාව <span class="text-red-500">*</span></label>
                    <select
                        v-model="form.role"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px]"
                        :class="{ 'border-red-500': form.errors.role }"
                    >
                        <option value="cashier">කැශියර්</option>
                        <option value="manager">කළමනාකරු</option>
                        <option value="admin">ඇඩ්මින්</option>
                    </select>
                    <p v-if="form.errors.role" class="text-red-500 text-xs mt-1">{{ form.errors.role }}</p>
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
                        :href="route('users.index')"
                        class="flex-1 text-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-6 rounded-lg transition-colors min-h-[44px] flex items-center justify-center"
                    >
                        ඉවත්වෙන්න  කරන්න
                    </Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
