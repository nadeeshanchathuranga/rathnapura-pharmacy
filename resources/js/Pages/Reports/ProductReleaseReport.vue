<template>
    <Head title="Product Release Report" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header with Date Filter -->
                <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <div class="flex items-center gap-4 mb-2">
                            <button
                                @click="$inertia.visit(route('dashboard'))"
                                class="px-4 py-2 bg-purple-700 hover:bg-purple-800 text-white rounded-lg transition flex items-center gap-2"
                            >
                                Back
                            </button>
                            <h1 class="text-3xl font-bold text-white flex items-center gap-2">
                                <span>📦</span> Product Release Notes Report
                            </h1>
                        </div>
                        <p class="text-gray-400">Track product releases from warehouse to shops</p>
                    </div>
                    <!-- Compact Date Filter -->
                    <div class="flex items-center gap-2 bg-gray-800 rounded-lg p-3 shadow-lg">
                        <input
                            type="date"
                            v-model="startDate"
                            class="px-3 py-1.5 bg-gray-700 text-white text-sm rounded focus:ring-2 focus:ring-purple-500"
                        />
                        <span class="text-gray-400">to</span>
                        <input
                            type="date"
                            v-model="endDate"
                            class="px-3 py-1.5 bg-gray-700 text-white text-sm rounded focus:ring-2 focus:ring-purple-500"
                        />
                        <button
                            @click="filterReports"
                            class="px-4 py-1.5 bg-purple-700 hover:bg-purple-800 text-white text-sm font-semibold rounded transition"
                        >
                            Apply
                        </button>
                        <button
                            @click="resetFilter"
                            class="px-4 py-1.5 bg-gray-700 hover:bg-gray-800 text-white text-sm font-semibold rounded transition"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-gradient-to-br from-purple-700 to-purple-900 rounded-xl p-6 shadow-lg flex items-center gap-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-200 text-sm font-medium">Total Releases</p>
                                <p class="text-3xl font-bold text-white mt-2">{{ totalReleases }}</p>
                            </div>
                            <div class="text-purple-200 text-4xl">📦</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-green-700 to-green-900 rounded-xl p-6 shadow-lg flex items-center gap-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-200 text-sm font-medium">Released</p>
                                <p class="text-3xl font-bold text-white mt-2">{{ releasedCount }}</p>
                            </div>
                            <div class="text-green-200 text-4xl">✅</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-yellow-700 to-yellow-900 rounded-xl p-6 shadow-lg flex items-center gap-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-yellow-200 text-sm font-medium">Pending</p>
                                <p class="text-3xl font-bold text-white mt-2">{{ pendingCount }}</p>
                            </div>
                            <div class="text-yellow-200 text-4xl">⏳</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-blue-700 to-blue-900 rounded-xl p-6 shadow-lg flex items-center gap-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-200 text-sm font-medium">Total Items</p>
                                <p class="text-3xl font-bold text-white mt-2">{{ totalItems }}</p>
                            </div>
                            <div class="text-blue-200 text-4xl"> </div>
                        </div>
                    </div>
                </div>

                <!-- Release Notes Details Table -->
                <div class="bg-gray-800 rounded-lg p-6 shadow-lg mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-white">Release Notes Details</h3>
                        <div class="flex gap-2">
                            <button
                                @click="exportPdf"
                                class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2"
                            >
                                📄 Export PDF
                            </button>
                            <button
                                @click="exportExcel"
                                class="px-4 py-2 bg-green-700 hover:bg-green-800 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2"
                            >
                                  Export Excel
                            </button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Release Date</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Product Transfer Request No</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Released By</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Status</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Products</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Quantity</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <tr v-for="release in releases" :key="release.id" class="text-gray-300">
                                    <td class="px-4 py-3">{{ formatDate(release.release_date) }}</td>
                                    <td class="px-4 py-3 text-cyan-300">{{ release.product_transfer_request_no || 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ release.user_name }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="getStatusClass(release.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                                            {{ release.status == 1 ? 'Released' : 'Pending' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div v-if="release.products && release.products.length > 0" class="space-y-1">
                                            <div v-for="(product, index) in release.products" :key="index" class="text-sm">
                                                <span class="text-blue-300">{{ product.product_name }}</span>
                                            </div>
                                        </div>
                                        <span v-else class="text-gray-500">No products</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-green-300 font-semibold">
                                        <div v-if="release.products && release.products.length > 0" class="space-y-1">
                                            <div v-for="(product, index) in release.products" :key="index" class="text-sm">
                                                {{ product.quantity }}
                                            </div>
                                        </div>
                                        <span v-else class="text-gray-500">-</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="releases.length === 0" class="text-center text-gray-400 py-8">
                        No product release notes found for selected date range
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { logActivity } from '@/composables/useActivityLog';

const props = defineProps({
    releases: Array,
    startDate: String,
    endDate: String,
});

const startDate = ref(props.startDate);
const endDate = ref(props.endDate);

const totalReleases = computed(() => props.releases.length);
const releasedCount = computed(() => props.releases.filter(r => r.status == 1).length);
const pendingCount = computed(() => props.releases.filter(r => r.status == 0).length);
const totalItems = computed(() => props.releases.reduce((sum, r) => sum + (parseInt(r.total_items) || 0), 0));

const filterReports = () => {
    router.get(route('reports.product-release'), {
        start_date: startDate.value,
        end_date: endDate.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilter = () => {
    router.get(route('reports.product-release'), {}, {
        preserveState: false,
        preserveScroll: false,
    });
};

const exportPdf = async () => {
    await logActivity('create', 'product_release_report', {
        action: 'export_pdf',
        start_date: startDate.value,
        end_date: endDate.value,
        total_releases: totalReleases.value
    });
    window.location.href = route('reports.export.product-release.pdf', {
        start_date: startDate.value,
        end_date: endDate.value,
    });
};

const exportExcel = async () => {
    await logActivity('create', 'product_release_report', {
        action: 'export_excel',
        start_date: startDate.value,
        end_date: endDate.value,
        total_releases: totalReleases.value
    });
    window.location.href = route('reports.export.product-release.excel', {
        start_date: startDate.value,
        end_date: endDate.value,
    });
};

const getStatusClass = (status) => {
    return status == 1
        ? 'bg-green-600 text-green-100'
        : 'bg-yellow-600 text-yellow-100';
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>
