<template>
    <Head title="Reports" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-black p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header with Date Filter -->
                <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-100 mb-2">📈 Sales & Income Reports</h1>
                        <p class="text-gray-400">View detailed reports and analytics</p>
                    </div>

                    <!-- Compact Date Filter -->
                    <div class="flex items-center gap-2 bg-gray-800 rounded-lg p-3 shadow-lg">
                        <input
                            type="date"
                            v-model="startDate"
                            class="px-3 py-1.5 bg-gray-900 text-gray-100 text-sm rounded focus:ring-2 focus:ring-blue-500 border border-gray-700"
                        />
                        <span class="text-gray-400">to</span>
                        <input
                            type="date"
                            v-model="endDate"
                            class="px-3 py-1.5 bg-gray-900 text-gray-100 text-sm rounded focus:ring-2 focus:ring-blue-500 border border-gray-700"
                        />
                        <button
                            @click="filterReports"
                            class="px-4 py-1.5 bg-blue-900 hover:bg-blue-800 text-gray-100 text-sm font-semibold rounded transition"
                        >
                            Apply
                        </button>
                        <button
                            @click="resetFilter"
                            class="px-4 py-1.5 bg-gray-900 hover:bg-gray-800 text-gray-100 text-sm font-semibold rounded transition"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Quick Links to Reports -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <Link
                        :href="route('reports.product-release')"
                        class="bg-gradient-to-br from-purple-900 to-purple-800 hover:from-purple-800 hover:to-purple-900 rounded-lg p-6 shadow-lg transition transform hover:scale-105 border border-purple-900"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-200 text-sm font-medium">Product Release</p>
                                <p class="text-white text-lg font-bold mt-1">Notes Report</p>
                            </div>
                            <div class="text-purple-200 text-4xl">📦</div>
                        </div>
                    </Link>

                    <Link
                        :href="route('reports.stock-transfer-return')"
                        class="bg-gradient-to-br from-red-900 to-red-800 hover:from-red-800 hover:to-red-900 rounded-lg p-6 shadow-lg transition transform hover:scale-105 border border-red-900"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-red-200 text-sm font-medium">Stock Transfer</p>
                                <p class="text-white text-lg font-bold mt-1">Return Report</p>
                            </div>
                            <div class="text-red-200 text-4xl">🔄</div>
                        </div>
                    </Link>

                    <Link
                        :href="route('reports.expenses')"
                        class="bg-gradient-to-br from-orange-900 to-orange-800 hover:from-orange-800 hover:to-orange-900 rounded-lg p-6 shadow-lg transition transform hover:scale-105 border border-orange-900"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-200 text-sm font-medium">Expenses</p>
                                <p class="text-white text-lg font-bold mt-1">Report</p>
                            </div>
                            <div class="text-orange-200 text-4xl">💸</div>
                        </div>
                    </Link>

                    <Link
                        :href="route('reports.income')"
                        class="bg-gradient-to-br from-green-900 to-green-800 hover:from-green-800 hover:to-green-900 rounded-lg p-6 shadow-lg transition transform hover:scale-105 border border-green-900"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-200 text-sm font-medium">Income</p>
                                <p class="text-white text-lg font-bold mt-1">Report</p>
                            </div>
                            <div class="text-green-200 text-4xl">💰</div>
                        </div>
                    </Link>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-gradient-to-br from-green-600 to-green-700 rounded-lg p-6 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-sm mb-1">Total Income</p>
                                <h2 class="text-3xl font-bold text-white">Rs. {{ totalIncome }}</h2>
                            </div>
                            <div class="text-5xl">💰</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-red-600 to-red-700 rounded-lg p-6 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-red-100 text-sm mb-1">Total Expenses</p>
                                <h2 class="text-3xl font-bold text-white">Rs. {{ totalExpenses }}</h2>
                            </div>
                            <div class="text-5xl">💸</div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg p-6 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm mb-1">Total Sales</p>
                                <h2 class="text-3xl font-bold text-white">{{ totalSalesCount }}</h2>
                            </div>
                            <div class="text-5xl">🛒</div>
                        </div>
                    </div>
                </div>

                <!-- Income by Payment Type -->
                <div class="bg-gray-800 rounded-lg p-6 shadow-lg mb-6">
                    <h3 class="text-xl font-semibold text-white mb-4">Income by Payment Type</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div v-for="income in incomeSummary" :key="income.payment_type"
                            :class="getPaymentTypeColor(income.payment_type)"
                            class="rounded-lg p-6 text-white">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="text-lg font-semibold">{{ income.payment_type_name }}</h4>
                                <span class="text-2xl">
                                    {{ income.payment_type === 0 ? '💵' : income.payment_type === 1 ? '💳' : '📝' }}
                                </span>
                            </div>
                            <p class="text-2xl font-bold mb-1">Rs. {{ income.total_amount }}</p>
                            <p class="text-sm opacity-80">{{ income.transaction_count }} transactions</p>
                        </div>
                    </div>
                    <div v-if="incomeSummary.length === 0" class="text-center text-gray-400 py-8">
                        No income data for selected date range
                    </div>
                </div>

                <!-- Sales by Type -->
                <div class="bg-gray-800 rounded-lg p-6 shadow-lg mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-white">Sales by Type</h3>
                        <div class="flex gap-2">
                            <a
                                :href="exportPdfUrl"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2"
                            >
                                📄 Export PDF
                            </a>
                            <a
                                :href="exportExcelUrl"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2"
                            >
                                  Export Excel
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Type</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Sales Count</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Gross Total</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Discount</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Net Total</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Returns</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Net After Returns</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Balance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <tr v-for="sale in salesSummary" :key="sale.type" class="text-gray-300">
                                    <td class="px-4 py-3">
                                        <span :class="getSaleTypeColor(sale.type)" class="px-3 py-1 rounded-full text-white text-sm font-medium">
                                            {{ sale.type_name }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold">{{ sale.total_sales }}</td>
                                    <td class="px-4 py-3 text-right">Rs. {{ sale.gross_total }}</td>
                                    <td class="px-4 py-3 text-right text-red-400">Rs. {{ sale.total_discount }}</td>
                                    <td class="px-4 py-3 text-right text-green-400 font-semibold">Rs. {{ sale.net_total }}</td>
                                    <td class="px-4 py-3 text-right text-orange-400">Rs. {{ sale.total_returns }}</td>
                                    <td class="px-4 py-3 text-right text-cyan-400 font-bold">Rs. {{ sale.net_total_after_returns }}</td>
                                    <td class="px-4 py-3 text-right text-yellow-400">Rs. {{ sale.total_balance }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="salesSummary.length === 0" class="text-center text-gray-400 py-8">
                        No sales data for selected date range
                    </div>
                </div>

                <!-- Products Stock Report -->
                <div class="bg-gray-800 rounded-lg p-6 shadow-lg mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-white">Product Sales & Returns Report</h3>
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

                <!-- Products Stock Report -->
                <div class="bg-gray-800 rounded-lg p-6 shadow-lg mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-white">Products Stock Report</h3>
                        <div class="flex gap-2">
                            <a
                                :href="exportProductStockPdfUrl"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2"
                            >
                                📄 Export PDF
                            </a>
                            <a
                                :href="exportProductStockExcelUrl"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2"
                            >
                                  Export Excel
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Product Name</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Current Stock</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Retail Price</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Wholesale Price</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-300">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <tr v-for="product in productsStock" :key="product.id" class="text-gray-300">
                                    <td class="px-4 py-3">{{ product.name }}</td>
                                    <td class="px-4 py-3 text-right font-semibold">{{ product.stock }}</td>
                                    <td class="px-4 py-3 text-right">Rs. {{ product.retail_price }}</td>
                                    <td class="px-4 py-3 text-right">Rs. {{ product.wholesale_price }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span :class="getStockStatusColor(product.stock_status)" class="font-semibold">
                                            {{ product.stock_status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="productsStock.length === 0" class="text-center text-gray-400 py-8">
                        No products found
                    </div>
                </div>

                <!-- Expenses Summary by Payment Type -->
                <div class="bg-gray-800 rounded-lg p-6 shadow-lg mb-6">
                    <h3 class="text-xl font-semibold text-white mb-4">Expenses by Payment Type</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div v-for="(expense, index) in expensesSummary" :key="index"
                            :class="getPaymentTypeColor(expense.payment_type)"
                            class="rounded-lg p-6 text-white">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="text-lg font-semibold">{{ expense.payment_type_name }}</h4>
                                <span class="text-2xl">
                                    {{ expense.payment_type === 0 ? '💵' : expense.payment_type === 1 ? '💳' : '📝' }}
                                </span>
                            </div>
                            <p class="text-2xl font-bold mb-1">Rs. {{ expense.total_amount }}</p>
                            <p class="text-sm opacity-80">{{ expense.transaction_count }} transactions</p>
                        </div>
                    </div>
                    <div v-if="expensesSummary.length === 0" class="text-center text-gray-400 py-8">
                        No expenses data for selected date range
                    </div>
                </div>

                <!-- Expenses Details -->
                <div class="bg-gray-800 rounded-lg p-6 shadow-lg mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-white">Expenses Details</h3>
                        <div class="flex gap-2">
                            <a
                                :href="exportExpensesPdfUrl"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2"
                            >
                                📄 Export PDF
                            </a>
                            <a
                                :href="exportExpensesExcelUrl"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2"
                            >
                                  Export Excel
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">#</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Title</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Payment</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Supplier</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Reference</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Amount</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-300">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <tr v-for="expense in expensesList" :key="expense.id" class="text-gray-300">
                                    <td class="px-4 py-3">{{ expense.id }}</td>
                                    <td class="px-4 py-3">{{ expense.title }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-3 py-1 rounded-full text-white text-sm font-medium"
                                            :class="getPaymentTypeColor(expense.payment_type)">
                                            {{ expense.payment_type_name }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-400">{{ expense.supplier_name }}</td>
                                    <td class="px-4 py-3 text-gray-400">{{ expense.reference || 'N/A' }}</td>
                                    <td class="px-4 py-3 text-right text-red-400 font-semibold">Rs. {{ expense.amount }}</td>
                                    <td class="px-4 py-3 text-center">{{ formatDate(expense.expense_date) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="expensesList.length === 0" class="text-center text-gray-400 py-8">
                        No expenses for selected date range
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    incomeSummary: Array,
    salesSummary: Array,
    productsStock: Array,
    productSalesReport: Array,
    expensesSummary: Array,
    expensesList: Array,
    totalIncome: String,
    totalExpenses: String,
    totalSalesCount: Number,
    startDate: String,
    endDate: String,
});

const startDate = ref(props.startDate);
const endDate = ref(props.endDate);

const exportPdfUrl = computed(() => {
    return route('reports.export.pdf', {
        start_date: startDate.value,
        end_date: endDate.value,
    });
});

const exportExcelUrl = computed(() => {
    return route('reports.export.excel', {
        start_date: startDate.value,
        end_date: endDate.value,
    });
});

const exportProductStockPdfUrl = computed(() => {
    return route('reports.export.product-stock.pdf');
});

const exportProductStockExcelUrl = computed(() => {
    return route('reports.export.product-stock.excel');
});

const exportExpensesPdfUrl = computed(() => {
    return route('reports.export.expenses.pdf', {
        start_date: startDate.value,
        end_date: endDate.value,
    });
});

const exportExpensesExcelUrl = computed(() => {
    return route('reports.export.expenses.excel', {
        start_date: startDate.value,
        end_date: endDate.value,
    });
});

const filterReports = () => {
    router.get(route('reports.index'), {
        start_date: startDate.value,
        end_date: endDate.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilter = () => {
    router.get(route('reports.index'), {}, {
        preserveState: false,
        preserveScroll: false,
    });
};

const getPaymentTypeColor = (type) => {
    const colors = {
        0: 'bg-green-600',
        1: 'bg-blue-600',
        2: 'bg-orange-600',
    };
    return colors[type] || 'bg-gray-600';
};

const getSaleTypeColor = (type) => {
    return type === 1 ? 'bg-blue-600' : 'bg-purple-600';
};

const getStockStatusColor = (status) => {
    if (status === 'Out of Stock') return 'text-red-500';
    if (status === 'Low Stock') return 'text-orange-500';
    return 'text-green-500';
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>
