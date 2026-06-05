<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    users: { type: Array, default: () => [] },
});

const roleLabel = {
    admin: 'ඇඩ්මින්',
    manager: 'කළමනාකරු',
    cashier: 'කැශියර්',
};

const roleClass = {
    admin: 'bg-red-100 text-red-700',
    manager: 'bg-blue-100 text-blue-700',
    cashier: 'bg-green-100 text-green-700',
};

function deleteUser(id) {
    if (confirm('මෙම පරිශීලකයා මකා දැමීමට ඔබට විශ්වාසද?')) {
        router.delete(route('users.destroy', id));
    }
}
</script>

<template>
    <Head title="පරිශීලකයන්" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-gray-800">පරිශීලකයන්</h1>
        </template>

        <div class="flex justify-end mb-4">
            <Link
                :href="route('users.create')"
                class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors min-h-[44px]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                නව පරිශීලක
            </Link>
        </div>

        <!-- Mobile cards -->
        <div class="md:hidden space-y-3 mb-4">
            <div v-if="users.length === 0" class="bg-white rounded-xl p-6 text-center text-gray-400">
                පරිශීලකයන් නොමැත
            </div>
            <div
                v-for="user in users"
                :key="user.id"
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-4"
            >
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-sm font-bold text-white">{{ user.name?.charAt(0)?.toUpperCase() }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-900 truncate">{{ user.name }}</p>
                        <p class="text-sm text-gray-500 truncate">{{ user.email }}</p>
                    </div>
                    <span
                        class="text-xs font-medium px-2 py-1 rounded-full flex-shrink-0"
                        :class="roleClass[user.role] || 'bg-gray-100 text-gray-600'"
                    >
                        {{ roleLabel[user.role] || user.role }}
                    </span>
                </div>
                <div class="flex gap-2">
                    <Link
                        :href="route('users.edit', user.id)"
                        class="flex-1 text-center bg-blue-50 hover:bg-blue-100 text-blue-600 py-2 rounded-lg text-sm font-medium transition-colors min-h-[44px] flex items-center justify-center"
                    >
                        සංස්කරණය
                    </Link>
                    <button
                        @click="deleteUser(user.id)"
                        class="flex-1 bg-red-50 hover:bg-red-100 text-red-600 py-2 rounded-lg text-sm font-medium transition-colors min-h-[44px]"
                    >
                        මකන්න
                    </button>
                </div>
            </div>
        </div>

        <!-- Desktop table -->
        <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50 border-b border-gray-100">
                        <th class="px-4 py-3">පරිශීලකයා</th>
                        <th class="px-4 py-3">ඊමේල්</th>
                        <th class="px-4 py-3">භූමිකාව</th>
                        <th class="px-4 py-3 text-right">ක්‍රියා</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="users.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center text-gray-400">පරිශීලකයන් නොමැත</td>
                    </tr>
                    <tr
                        v-for="user in users"
                        :key="user.id"
                        class="hover:bg-gray-50 transition-colors"
                    >
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-white">{{ user.name?.charAt(0)?.toUpperCase() }}</span>
                                </div>
                                <span class="font-medium text-gray-900">{{ user.name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ user.email }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="text-xs font-medium px-2 py-1 rounded-full"
                                :class="roleClass[user.role] || 'bg-gray-100 text-gray-600'"
                            >
                                {{ roleLabel[user.role] || user.role }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <Link
                                    :href="route('users.edit', user.id)"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium px-3 py-1.5 rounded hover:bg-blue-50 min-h-[36px] flex items-center"
                                >
                                    සංස්කරණය
                                </Link>
                                <button
                                    @click="deleteUser(user.id)"
                                    class="text-red-600 hover:text-red-800 text-sm font-medium px-3 py-1.5 rounded hover:bg-red-50 min-h-[36px]"
                                >
                                    මකන්න
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AuthenticatedLayout>
</template>
