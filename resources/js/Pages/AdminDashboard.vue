
<script setup>
/**
 * Admin Dashboard Component Script
 *
 * Imports AuthenticatedLayout for consistent navigation and header
 * Uses Inertia's Head component for page title
 * Uses Inertia's Link component for SPA-style navigation
 */
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <!-- Page Title for Browser Tab -->
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <!-- Main Dashboard Container with Gradient Background -->
        <div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6">

            <!-- Dashboard Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-white mb-2">Admin Dashboard</h1>
                <p class="text-gray-400">Manage your inventory, purchases, and sales</p>
            </div>

            <!-- SECTION 1: Inventory Management -->
            <div class="mb-10">
                <h3 class="text-2xl font-bold text-white mb-4 pb-2 border-b border-blue-600">
                    📦 Inventory Management
                </h3>
                <!-- 5-column responsive grid for inventory items -->
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                    <!-- Products Card - Manage all products in the system -->
                    <Link
                        :href="route('products.index')"
                        class="group bg-gradient-to-br from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2">📦</div>
                        <div class="font-semibold text-lg">Products</div>
                        <div class="text-sm text-blue-200 group-hover:text-blue-100">Manage products</div>
                    </Link>

                    <!-- Brands Card - Manage product brands -->
                    <Link
                        :href="route('brands.index')"
                        class="group bg-gradient-to-br from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2">🏷️</div>
                        <div class="font-semibold text-lg">Brands</div>
                        <div class="text-sm text-purple-200 group-hover:text-purple-100">Manage brands</div>
                    </Link>

                    <!-- Categories Card - Organize products into categories -->
                    <Link
                        :href="route('categories.index')"
                        class="group bg-gradient-to-br from-pink-600 to-pink-700 hover:from-pink-700 hover:to-pink-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2">📂</div>
                        <div class="font-semibold text-lg">Categories</div>
                        <div class="text-sm text-pink-200 group-hover:text-pink-100">Manage categories</div>
                    </Link>

                    <!-- Types Card - Manage product types -->
                    <Link
                        :href="route('types.index')"
                        class="group bg-gradient-to-br from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2">🔹</div>
                        <div class="font-semibold text-lg">Types</div>
                        <div class="text-sm text-indigo-200 group-hover:text-indigo-100">Manage types</div>
                    </Link>

                    <!-- Measurement Units Card - Define units for product measurements -->
                    <Link
                        :href="route('measurement-units.index')"
                        class="group bg-gradient-to-br from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2">📏</div>
                        <div class="font-semibold text-lg">Units</div>
                        <div class="text-sm text-cyan-200 group-hover:text-cyan-100">nits</div>
                    </Link>
                </div>
            </div>

            <!-- SECTION 2: Purchase & Stock Management -->
            <div class="mb-10">
                <h3 class="text-2xl font-bold text-white mb-4 pb-2 border-b border-green-600">
                    🛒 Purchasing & Stock
                </h3>
                <!-- Purchasing modules -->
                <div class="grid gap-4 md:grid-cols-3">
                    <!-- Goods Received Notes (GRN) Card -->
                    <Link
                        :href="route('good-receive-notes.index')"
                        class="group bg-gradient-to-br from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2">📦</div>
                        <div class="font-semibold text-lg">Goods Received</div>
                        <div class="text-sm text-emerald-200 group-hover:text-emerald-100">Track received goods</div>
                    </Link>

                    <!-- Suppliers Card -->
                    <Link
                        :href="route('suppliers.index')"
                        class="group bg-gradient-to-br from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2">🏢</div>
                        <div class="font-semibold text-lg">Suppliers</div>
                        <div class="text-sm text-teal-200 group-hover:text-teal-100">Manage suppliers</div>
                    </Link>

                    <!-- Product Transfer Requests (PTR) Card -->
                    <Link
                        :href="route('product-transfer-requests.index')"
                        class="group bg-gradient-to-br from-lime-600 to-lime-700 hover:from-lime-700 hover:to-lime-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2">📤</div>
                        <div class="font-semibold text-lg">Product Transfer</div>
                        <div class="text-sm text-lime-200 group-hover:text-lime-100">Transfer products</div>
                    </Link>
                </div>
            </div>

            <!-- SECTION 3: Sales Management -->
            <div class="mb-10">
                <h3 class="text-2xl font-bold text-white mb-4 pb-2 border-b border-orange-600">
                    💰 Sales Management
                </h3>
                <!-- 3-column grid for sales modules -->
                <div class="grid gap-4 md:grid-cols-3">
                    <!-- Customers Card -->
                    <Link
                        :href="route('customers.index')"
                        class="group bg-gradient-to-br from-orange-600 to-orange-700 hover:from-orange-700 hover:to-orange-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2">👥</div>
                        <div class="font-semibold text-lg">Customers</div>
                        <div class="text-sm text-orange-200 group-hover:text-orange-100">Manage customers</div>
                    </Link>

                    <!-- Discounts Card -->
                    <Link
                        :href="route('discounts.index')"
                        class="group bg-gradient-to-br from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2">🏷️</div>
                        <div class="font-semibold text-lg">Discounts</div>
                        <div class="text-sm text-red-200 group-hover:text-red-100">Manage discounts</div>
                    </Link>

                    <!-- Taxes Card -->
                    <Link
                        :href="route('taxes.index')"
                        class="group bg-gradient-to-br from-rose-600 to-rose-700 hover:from-rose-700 hover:to-rose-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2"> </div>
                        <div class="font-semibold text-lg">Taxes</div>
                        <div class="text-sm text-rose-200 group-hover:text-rose-100">Manage tax rates</div>
                    </Link>
                </div>
            </div>

            <!-- SECTION 4: System Management -->
            <div class="mb-10">
                <h3 class="text-2xl font-bold text-white mb-4 pb-2 border-b border-slate-600">
                    ⚙️ System Management
                </h3>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                    <!-- Users Card - User management (admin only) -->
                    <Link
                        :href="route('users.index')"
                        class="group bg-gradient-to-br from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2">👤</div>
                        <div class="font-semibold text-lg">Users</div>
                        <div class="text-sm text-slate-200 group-hover:text-slate-100">Manage system users</div>
                    </Link>
                </div>
            </div>

            <!-- SECTION 5: Settings -->
            <div>
                <h3 class="text-2xl font-bold text-white mb-4 pb-2 border-b border-slate-600">
                    🔧 Settings
                </h3>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                    <!-- Company Information Card - Manages company profile and branding -->
                    <Link
                        :href="route('settings.company')"
                        class="group bg-gradient-to-br from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2">🏢</div>
                        <div class="font-semibold text-lg">Company Info</div>
                        <div class="text-sm text-slate-200 group-hover:text-slate-100">Company information & settings</div>
                    </Link>

                    <!-- App Settings Card - Application-level configuration -->
                    <Link
                        :href="route('settings.app')"
                        class="group bg-gradient-to-br from-slate-600 to-slate-700 hover:from-slate-700 hover:to-slate-800 p-6 rounded-lg text-white transition transform hover:scale-105 shadow-lg"
                    >
                        <div class="text-3xl mb-2">⚙️</div>
                        <div class="font-semibold text-lg">App Settings</div>
                        <div class="text-sm text-slate-200 group-hover:text-slate-100">Application preferences & configuration</div>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/**
 * Custom Styles for Dashboard
 *
 * Smooth transitions for all link hover effects
 * Transform and scale animations are handled via Tailwind classes
 */
a {
    @apply transition-all duration-300 ease-in-out;
}
</style>
