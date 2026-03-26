<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="User Dashboard" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-white mb-2">User Dashboard</h1>
                <p class="text-gray-400">Welcome to your dashboard</p>
            </div>

            <!-- Inventory Section -->
            <div class="mb-10">
                <h3 class="text-2xl font-bold text-white mb-4 pb-2 border-b border-blue-600">
                    📦 Inventory
                </h3>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Link
                        :href="route('products.index')"
                        class="group bg-gradient-to-br from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2">📦</div>
                        <div class="font-semibold text-lg">Products</div>
                        <div class="text-sm text-blue-200 group-hover:text-blue-100">View products</div>
                    </Link>

                    <Link
                        :href="route('brands.index')"
                        class="group bg-gradient-to-br from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2">🏷️</div>
                        <div class="font-semibold text-lg">Brands</div>
                        <div class="text-sm text-purple-200 group-hover:text-purple-100">View brands</div>
                    </Link>

                    <Link
                        :href="route('categories.index')"
                        class="group bg-gradient-to-br from-pink-600 to-pink-700 hover:from-pink-700 hover:to-pink-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2">📂</div>
                        <div class="font-semibold text-lg">Categories</div>
                        <div class="text-sm text-pink-200 group-hover:text-pink-100">View categories</div>
                    </Link>
                </div>
            </div>

            <!-- Sales Section -->
            <div class="mb-10">
                <h3 class="text-2xl font-bold text-white mb-4 pb-2 border-b border-orange-600">
                    💰 Sales
                </h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <Link
                        :href="route('customers.index')"
                        class="group bg-gradient-to-br from-orange-600 to-orange-700 hover:from-orange-700 hover:to-orange-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2">👥</div>
                        <div class="font-semibold text-lg">Customers</div>
                        <div class="text-sm text-orange-200 group-hover:text-orange-100">View customers</div>
                    </Link>

                    <Link
                        :href="route('discounts.index')"
                        class="group bg-gradient-to-br from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2">🏷️</div>
                        <div class="font-semibold text-lg">Discounts</div>
                        <div class="text-sm text-red-200 group-hover:text-red-100">View discounts</div>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Smooth transitions */
a {
    @apply transition-all duration-300 ease-in-out;
}
</style>
