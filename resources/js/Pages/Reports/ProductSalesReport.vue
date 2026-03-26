<template>
    <Head title="Product Sales Report" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header with Date Filter -->
                <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2">📦 Product Sales & Returns Report</h1>
                        <p class="text-gray-400">Detailed product-wise sales and returns analysis</p>
                    </div>

                    <!-- Compact Date Filter -->
                    <div class="flex items-center gap-2 bg-gray-800 rounded-lg p-3 shadow-lg">
                        <input
                            type="date"
                            v-model="startDate"
                            class="px-3 py-1.5 bg-gray-700 text-white text-sm rounded focus:ring-2 focus:ring-blue-500"
                        />
                        <span class="text-gray-400">to</span>
                        <input
                            type="date"
                            v-model="endDate"
                            class="px-3 py-1.5 bg-gray-700 text-white text-sm rounded focus:ring-2 focus:ring-blue-500"
                        />
                        <button
                            @click="filterReports"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded transition"
                        >
                            Apply
                        </button>
                        <button
                            @click="resetFilter"
                            class="px-4 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-sm font-semibold rounded transition"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg p-6 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm mb-1">Total Products</p>
                                <h2 class="text-3xl font-bold text-white">{{ productSalesReport.length }}</h2>
                            </div>
                            <div class="text-5xl">📦</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-green-600 to-green-700 rounded-lg p-6 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-sm mb-1">Total Sales Qty</p>
                                <h2 class="text-3xl font-bold text-white">{{ totalSalesQty }}</h2>
                            </div>
                            <div class="text-5xl">📈</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-orange-600 to-orange-700 rounded-lg p-6 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-100 text-sm mb-1">Total Returns Qty</p>
                                <h2 class="text-3xl font-bold text-white">{{ totalReturnsQty }}</h2>
                            </div>
                            <div class="text-5xl">↩️</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-cyan-600 to-cyan-700 rounded-lg p-6 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-cyan-100 text-sm mb-1">Net Sales Amount</p>
                                <h2 class="text-3xl font-bold text-white">Rs. {{ totalNetSalesAmount }}</h2>
                            </div>
                            <div class="text-5xl">💰</div>
                        </div>
                    </div>
                </div>

                <!-- Product Sales & Returns Report -->
                <div class="bg-gray-800 rounded-lg p-6 shadow-lg mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-white">Product Sales & Returns Details</h3>
                        <div class="flex gap-2">
                            <button
                                @click="exportPdf"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2"
                            >
                                📄 Export PDF
                            </button>
                            <button
                                @click="exportExcel"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2"
                            >
                                  Export Excel
                            </button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Product Name</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Barcode</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Sales Qty</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Sales Amount</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Returns Qty</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Returns Amount</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Net Sales Qty</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Net Sales Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <tr v-for="product in productSalesReport" :key="product.id" class="text-gray-300">
                                    <td class="px-4 py-3 font-medium">{{ product.name }}</td>
                                    <td class="px-4 py-3 text-gray-400">{{ product.barcode }}</td>
                                    <td class="px-4 py-3 text-right text-blue-400 font-semibold">{{ product.sales_quantity }}</td>
                                    <td class="px-4 py-3 text-right text-green-400">Rs. {{ product.sales_amount }}</td>
                                    <td class="px-4 py-3 text-right text-orange-400 font-semibold">{{ product.returns_quantity }}</td>
                                    <td class="px-4 py-3 text-right text-red-400">Rs. {{ product.returns_amount }}</td>
                                    <td class="px-4 py-3 text-right text-cyan-400 font-bold">{{ product.net_sales_quantity }}</td>
                                    <td class="px-4 py-3 text-right text-green-500 font-bold">Rs. {{ product.net_sales_amount }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="productSalesReport.length === 0" class="text-center text-gray-400 py-8">
                        No sales or returns data for selected date range
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

const props = defineProps({
    productSalesReport: Array,
    startDate: String,
    endDate: String,
});

const startDate = ref(props.startDate);
const endDate = ref(props.endDate);

const totalSalesQty = computed(() => {
    return props.productSalesReport.reduce((sum, product) => sum + product.sales_quantity, 0);
});

const totalReturnsQty = computed(() => {
    return props.productSalesReport.reduce((sum, product) => sum + product.returns_quantity, 0);
});

const totalNetSalesAmount = computed(() => {
    const total = props.productSalesReport.reduce((sum, product) => {
        const value = parseFloat(product.net_sales_amount.replace(/,/g, ''));
        return sum + (isNaN(value) ? 0 : value);
    }, 0);
    return total.toFixed(2);
});

const filterReports = () => {
    router.get(route('reports.product-sales'), {
        start_date: startDate.value,
        end_date: endDate.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilter = () => {
    router.get(route('reports.product-sales'), {}, {
        preserveState: false,
        preserveScroll: false,
    });
};

const exportPdf = () => {
    window.location.href = route('reports.export.product-sales.pdf', {
        start_date: startDate.value,
        end_date: endDate.value,
    });
};

const exportExcel = () => {
    window.location.href = route('reports.export.product-sales.excel', {
        start_date: startDate.value,
        end_date: endDate.value,
    });
};
</script>
