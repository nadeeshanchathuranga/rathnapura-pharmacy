<template>
    <Head title="Stock Transfer Return Report" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header with Date Filter -->
                <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <div class="flex items-center gap-4 mb-2">
                            <button
                                @click="$inertia.visit(route('dashboard'))"
                                class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white rounded-lg transition flex items-center gap-2"
                            >
                                Back
                            </button>
                            <h1 class="text-3xl font-bold text-white flex items-center gap-2">
                                <span>🔄</span> Stock Transfer Return Report
                            </h1>
                        </div>
                        <p class="text-gray-400">Track product returns from shops to warehouse</p>
                    </div>
                    <!-- Compact Date Filter -->
                    <div class="flex items-center gap-2 bg-gray-800 rounded-lg p-3 shadow-lg">
                        <input
                            type="date"
                            v-model="startDate"
                            class="px-3 py-1.5 bg-gray-700 text-white text-sm rounded focus:ring-2 focus:ring-red-500"
                        />
                        <span class="text-gray-400">to</span>
                        <input
                            type="date"
                            v-model="endDate"
                            class="px-3 py-1.5 bg-gray-700 text-white text-sm rounded focus:ring-2 focus:ring-red-500"
                        />
                        <button
                            @click="filterReports"
                            class="px-4 py-1.5 bg-red-700 hover:bg-red-800 text-white text-sm font-semibold rounded transition"
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
                    <div class="bg-gradient-to-br from-red-700 to-red-900 rounded-xl p-6 shadow-lg flex items-center gap-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-red-200 text-sm font-medium">Total Returns</p>
                                <p class="text-3xl font-bold text-white mt-2">{{ totalReturns }}</p>
                            </div>
                            <div class="text-red-200 text-4xl">🔄</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-green-700 to-green-900 rounded-xl p-6 shadow-lg flex items-center gap-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-200 text-sm font-medium">Completed</p>
                                <p class="text-3xl font-bold text-white mt-2">{{ completedCount }}</p>
                            </div>
                            <div class="text-green-200 text-4xl">✅</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-blue-700 to-blue-900 rounded-xl p-6 shadow-lg flex items-center gap-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-200 text-sm font-medium">Approved</p>
                                <p class="text-3xl font-bold text-white mt-2">{{ approvedCount }}</p>
                            </div>
                            <div class="text-blue-200 text-4xl">👍</div>
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
                </div>

                <!-- Return Details Table -->
                <div class="bg-gray-800 rounded-lg p-6 shadow-lg mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-white">Stock Transfer Return Details</h3>
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
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Return Date</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Return No</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Processed By</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Status</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Products</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Quantity</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <tr v-for="returnItem in returns" :key="returnItem.id" class="text-gray-300">
                                    <td class="px-4 py-3">{{ formatDate(returnItem.return_date) }}</td>
                                    <td class="px-4 py-3 text-cyan-300">{{ returnItem.return_no }}</td>
                                    <td class="px-4 py-3">{{ returnItem.user_name }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="getStatusClass(returnItem.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                                            {{ returnItem.status.charAt(0).toUpperCase() + returnItem.status.slice(1) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div v-if="returnItem.products && returnItem.products.length > 0" class="space-y-1">
                                            <div v-for="(product, index) in returnItem.products" :key="index" class="text-sm">
                                                <span class="text-blue-300">{{ product.product_name }}</span>
                                            </div>
                                        </div>
                                        <span v-else class="text-gray-500">No products</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-green-300 font-semibold">
                                        <div v-if="returnItem.products && returnItem.products.length > 0" class="space-y-1">
                                            <div v-for="(product, index) in returnItem.products" :key="index" class="text-sm">
                                                {{ product.stock_transfer_quantity }} <span class="text-gray-400">{{ product.measurement_unit }}</span>
                                            </div>
                                        </div>
                                        <span v-else class="text-gray-500">-</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="returns.length === 0" class="text-center text-gray-400 py-8">
                        No stock transfer returns found for selected date range
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
    returns: Array,
    startDate: String,
    endDate: String,
});

const startDate = ref(props.startDate);
const endDate = ref(props.endDate);

const totalReturns = computed(() => props.returns.length);
const completedCount = computed(() => props.returns.filter(r => r.status === 'completed').length);
const approvedCount = computed(() => props.returns.filter(r => r.status === 'approved').length);
const pendingCount = computed(() => props.returns.filter(r => r.status === 'pending').length);

const filterReports = () => {
    router.get(route('reports.stock-transfer-return'), {
        start_date: startDate.value,
        end_date: endDate.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilter = () => {
    router.get(route('reports.stock-transfer-return'), {}, {
        preserveState: false,
        preserveScroll: false,
    });
};

const exportPdf = async () => {
    await logActivity('create', 'stock_transfer_return_report', {
        action: 'export_pdf',
        start_date: startDate.value,
        end_date: endDate.value,
        total_returns: totalReturns.value
    });
    window.location.href = route('reports.export.stock-transfer-return.pdf', {
        start_date: startDate.value,
        end_date: endDate.value,
    });
};

const exportExcel = async () => {
    await logActivity('create', 'stock_transfer_return_report', {
        action: 'export_excel',
        start_date: startDate.value,
        end_date: endDate.value,
        total_returns: totalReturns.value
    });
    window.location.href = route('reports.export.stock-transfer-return.excel', {
        start_date: startDate.value,
        end_date: endDate.value,
    });
};

const getStatusClass = (status) => {
    const classes = {
        'completed': 'bg-green-600 text-green-100',
        'approved': 'bg-blue-600 text-blue-100',
        'pending': 'bg-yellow-600 text-yellow-100'
    };
    return classes[status] || 'bg-gray-600 text-gray-100';
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>
