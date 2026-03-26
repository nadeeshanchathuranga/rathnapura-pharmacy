<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

const page = usePage();

const props = defineProps({
    returnRows: { type: Array, default: () => [] },
    returnTotals: { type: Object, default: () => ({}) },
    startDate: { type: String, default: '' },
    endDate: { type: String, default: '' },
});

const startDate = ref(props.startDate);
const endDate = ref(props.endDate);

const formatCurrency = (value) =>
    new Intl.NumberFormat('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(
        Number(value ?? 0),
    );

const filterReports = () => {
    router.get(
        route('reports.grn-returns'),
        { start_date: startDate.value, end_date: endDate.value },
        { preserveScroll: true, preserveState: true },
    );
};

const resetFilter = () => {
    startDate.value = props.startDate;
    endDate.value = props.endDate;
    filterReports();
};

const exportLinks = computed(() => {
    const params = new URLSearchParams();
    if (startDate.value) params.append('start_date', startDate.value);
    if (endDate.value) params.append('end_date', endDate.value);
    const query = params.toString();
    return {
        pdf: '/reports/export/grn-returns/pdf' + (query ? `?${query}` : ''),
        excel: '/reports/export/grn-returns/excel' + (query ? `?${query}` : ''),
    };
});

const logExportActivity = async (type) => {
    try {
        await axios.post('/products/log-activity', {
            action: 'export',
            module: 'grn return report',
            details: {
                export_type: type,
                start_date: startDate.value,
                end_date: endDate.value,
            },
        });
    } catch (e) {
        // Optionally handle/log error
        console.error('Activity log failed', e);
    }
};
</script>

<template>
    <Head title="GRN Return Report" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header with Date Filter -->
                <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <div class="flex items-center gap-4 mb-2">
                            <button
                                @click="$inertia.visit(route('dashboard'))"
                                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition flex items-center gap-2"
                            >
                                Back
                            </button>
                            <h1 class="text-3xl font-bold text-white">📤 Goods Received Note Return Report</h1>
                        </div>
                        <p class="text-gray-400">Review returned goods against GRNs within a date range</p>
                    </div>

                    <!-- Date Filter -->
                    <div class="flex items-center gap-2 bg-slate-800 rounded-lg p-3 shadow-lg">
                        <input
                            type="date"
                            v-model="startDate"
                            class="px-3 py-1.5 bg-slate-700 text-white text-sm rounded focus:ring-2 focus:ring-amber-500"
                        />
                        <span class="text-gray-400">to</span>
                        <input
                            type="date"
                            v-model="endDate"
                            class="px-3 py-1.5 bg-slate-700 text-white text-sm rounded focus:ring-2 focus:ring-amber-500"
                        />
                        <button
                            @click="filterReports"
                            class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded transition"
                        >
                            Apply
                        </button>
                        <button
                            @click="resetFilter"
                            class="px-4 py-1.5 bg-slate-600 hover:bg-slate-700 text-white text-sm font-semibold rounded transition"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-gradient-to-br from-amber-600 to-amber-700 rounded-lg p-6 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-amber-100 text-sm mb-1">Return Value (Est.)</p>
                                <h2 class="text-3xl font-bold text-white">{{ page.props.currency || 'Rs.' }} {{ returnTotals.estimated_value ?? '0.00' }}</h2>
                            </div>
                            <div class="text-5xl">📤</div>
                        </div>
                        <p class="text-amber-50 text-sm mt-2">{{ returnTotals.count ?? 0 }} returns</p>
                    </div>

                    <div class="bg-gradient-to-br from-cyan-600 to-cyan-700 rounded-lg p-6 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-cyan-100 text-sm mb-1">Total Quantity</p>
                                <h2 class="text-3xl font-bold text-white">{{ returnTotals.quantity ?? 0 }}</h2>
                            </div>
                            <div class="text-5xl">📦</div>
                        </div>
                        <p class="text-cyan-50 text-sm mt-2">Units returned</p>
                    </div>

                    <div class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-lg p-6 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-100 text-sm mb-1">Date Range</p>
                                <h2 class="text-lg font-semibold text-white">{{ startDate }} → {{ endDate }}</h2>
                            </div>
                            <div class="text-5xl">🗓️</div>
                        </div>
                        <p class="text-slate-200 text-sm mt-2">Filtered period</p>
                    </div>
                </div>

                <!-- Return Table -->
                <div class="bg-slate-800 rounded-lg p-6 shadow-lg">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-white">Goods Received Note Returns</h3>

                        <div class="text-sm text-slate-300 flex gap-4">
                            <a
                            :href="exportLinks.pdf"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2"
                            @click="logExportActivity('pdf')"
                        >
                           📄 Export PDF
                        </a>
                        <a
                            :href="exportLinks.excel"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2"
                            @click="logExportActivity('excel')"
                        >
                              Export Excel
                        </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-300">Date</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-300">GRN No</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-300">Handled By</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-300">Qty</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-300">Estimated Value</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-300">Items</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700">
                                <tr v-for="row in returnRows" :key="row.id" class="text-slate-200 align-top">
                                    <td class="px-4 py-3">{{ row.date }}</td>
                                    <td class="px-4 py-3">{{ row.grn_no ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ row.handled_by }}</td>
                                    <td class="px-4 py-3 text-right text-amber-300 font-semibold">{{ row.total_quantity }}</td>
                                    <td class="px-4 py-3 text-right text-red-300">{{ page.props.currency || 'Rs.' }} {{ formatCurrency(row.estimated_value) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="space-y-2">
                                            <div
                                                v-for="item in row.items"
                                                :key="item.product_name + item.quantity"
                                                class="flex justify-between text-sm text-slate-300 bg-slate-900/50 rounded p-2"
                                            >
                                                <span class="font-medium">{{ item.product_name }}</span>
                                                <span class="text-amber-200">{{ item.quantity }} pcs</span>
                                                <span class="text-slate-200">{{ page.props.currency || 'Rs.' }} {{ formatCurrency(item.estimated_value) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="returnRows.length === 0" class="text-center text-slate-400 py-8">
                        No returns recorded for the selected range.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
