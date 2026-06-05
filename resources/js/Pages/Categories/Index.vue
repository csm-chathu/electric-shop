<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    categories: { type: Array, default: () => [] },
});

function deleteCategory(id) {
    if (confirm('මෙම කාණ්ඩය මකා දැමීමට ඔබට විශ්වාසද?')) {
        router.delete(route('categories.destroy', id));
    }
}
</script>

<template>
    <Head title="කාණ්ඩ" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-gray-800">කාණ්ඩ</h1>
        </template>

        <div class="flex justify-end mb-4">
            <Link
                :href="route('categories.create')"
                class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors min-h-[44px]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                නව කාණ්ඩ
            </Link>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50 border-b border-gray-100">
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">නම (English)</th>
                        <th class="px-4 py-3">නම (සිංහල)</th>
                        <th class="px-4 py-3 text-right">ක්‍රියා</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-if="categories.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center text-gray-400">කාණ්ඩ නොමැත</td>
                    </tr>
                    <tr
                        v-for="(category, idx) in categories"
                        :key="category.id"
                        class="hover:bg-gray-50 transition-colors"
                    >
                        <td class="px-4 py-3 text-gray-400 text-sm">{{ idx + 1 }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ category.name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ category.name_si || '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <Link
                                    :href="route('categories.edit', category.id)"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium px-3 py-1.5 rounded hover:bg-blue-50 min-h-[36px] flex items-center"
                                >
                                    සංස්කරණය
                                </Link>
                                <button
                                    @click="deleteCategory(category.id)"
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
