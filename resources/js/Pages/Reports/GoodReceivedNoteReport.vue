<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

const page = usePage();

const props = defineProps({
    grnRows: { type: Array, default: () => [] },
    grnTotals: { type: Object, default: () => ({}) },
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
    const hasStart = !!startDate.value;
    const hasEnd = !!endDate.value;

    // If only one date is provided, treat it as a single-day filter
    const payload = hasStart && hasEnd
        ? { start_date: startDate.value, end_date: endDate.value }
        : hasStart
            ? { start_date: startDate.value, end_date: startDate.value }
            : hasEnd
                ? { start_date: endDate.value, end_date: endDate.value }
                : {};

    router.get(route('reports.grn'), payload, { preserveScroll: true, preserveState: true });
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
        pdf: '/reports/export/grn/pdf' + (query ? `?${query}` : ''),
        excel: '/reports/export/grn/excel' + (query ? `?${query}` : ''),
    };
});

const logExportActivity = async (type) => {
    try {
        await axios.post('/products/log-activity', {
            action: 'export',
            module: 'grn report',
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

const statusBadge = (status) => {
    if (status === 1) return 'bg-green-600/80 text-white';
    if (status === 2) return 'bg-yellow-500/80 text-white';
    return 'bg-gray-600/80 text-white';
};

const displayDateRange = () => {
    if (startDate.value && endDate.value) {
        if (startDate.value === endDate.value) return startDate.value;
        return `${startDate.value} → ${endDate.value}`;
    }
    if (startDate.value) return startDate.value;
    if (endDate.value) return endDate.value;
    return 'All dates';
};

const formatQty = (qty) => Number(qty ?? 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

const itemDetails = (row) => {
    if (!row?.items || row.items.length === 0) return [];
    return row.items.map((item) => ({ name: item.name, quantity: formatQty(item.quantity) }));
};
</script>

<template>
    <Head title="Goods Received Note Report" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header with Date Filter -->
                <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <div class="flex items-center gap-4 mb-2">
                            <button
                                @click="$inertia.visit(route('dashboard'))"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition flex items-center gap-2"
                            >
                                Back
                            </button>
                            <h1 class="text-3xl font-bold text-white">📥 Goods Received Note Report</h1>
                        </div>
                        <p class="text-gray-400">Track received inventory within a date range</p>
                    </div>

                    <!-- Date Filter -->
                    <div class="flex items-center gap-2 bg-slate-800 rounded-lg p-3 shadow-lg">
                        <input
                            type="date"
                            v-model="startDate"
                            class="px-3 py-1.5 bg-slate-700 text-white text-sm rounded focus:ring-2 focus:ring-emerald-500"
                        />
                        <span class="text-gray-400">to</span>
                        <input
                            type="date"
                            v-model="endDate"
                            class="px-3 py-1.5 bg-slate-700 text-white text-sm rounded focus:ring-2 focus:ring-emerald-500"
                        />
                        <button
                            @click="filterReports"
                            class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded transition"
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
                    <div class="bg-gradient-to-br from-emerald-600 to-emerald-700 rounded-lg p-6 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-emerald-100 text-sm mb-1">Total GRN Value</p>
                                <h2 class="text-3xl font-bold text-white">{{ page.props.currency || 'Rs.' }} {{ grnTotals.net_total ?? '0.00' }}</h2>
                            </div>
                            <div class="text-5xl">📥</div>
                        </div>
                        <p class="text-emerald-50 text-sm mt-2">{{ grnTotals.count ?? 0 }} GRNs</p>
                    </div>

                    <div class="bg-gradient-to-br from-cyan-600 to-cyan-700 rounded-lg p-6 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-cyan-100 text-sm mb-1">Total Items Received</p>
                                <h2 class="text-3xl font-bold text-white">{{ grnTotals.items_count ?? 0 }}</h2>
                            </div>
                            <div class="text-5xl">📦</div>
                        </div>
                        <p class="text-cyan-50 text-sm mt-2">Across all GRNs</p>
                    </div>

                    <div class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-lg p-6 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-slate-100 text-sm mb-1">Date Range</p>
                                <h2 class="text-lg font-semibold text-white">{{ displayDateRange() }}</h2>
                            </div>
                            <div class="text-5xl">🗓️</div>
                        </div>
                        <p class="text-slate-200 text-sm mt-2">Filtered period</p>
                    </div>
                </div>

                <!-- GRN Table -->
                <div class="bg-slate-800 rounded-lg p-6 shadow-lg mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-white">Goods Received Notes</h3>
                        <div class="flex gap-2">
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
                            <!-- <div class="text-sm text-slate-300 flex gap-4">
                                <span>Gross: Rs. {{ grnTotals.gross_total ?? '0.00' }}</span>
                                <span>Tax: Rs. {{ grnTotals.tax_total ?? '0.00' }}</span>
                                <span>Discount: Rs. {{ grnTotals.discount_total ?? '0.00' }}</span>
                            </div> -->
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-300">GRN No</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-300">Supplier</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-300">Date</th>
                                    <!-- <th class="px-4 py-3 text-right text-sm font-semibold text-slate-300">Items</th> -->
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-slate-300">Products</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-300">Gross</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-300">Discount</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-300">Tax</th>
                                    <th class="px-4 py-3 text-right text-sm font-semibold text-slate-300">Net</th>
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-slate-300">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700">
                                <tr v-for="row in grnRows" :key="row.id" class="text-slate-200">
                                    <td class="px-4 py-3 font-semibold">{{ row.grn_no }}</td>
                                    <td class="px-4 py-3 text-slate-300">{{ row.supplier_name }}</td>
                                    <td class="px-4 py-3 text-slate-300">{{ row.date }}</td>
                                    <!-- <td class="px-4 py-3 text-right text-emerald-300 font-semibold">{{ row.items_count }}</td> -->
                                    <td class="px-4 py-3 text-slate-200">
                                        <div v-if="itemDetails(row).length" class="space-y-1">
                                            <div v-for="(item, idx) in itemDetails(row)" :key="idx" class="text-sm">
                                                {{ item.name }} - {{ item.quantity }}
                                            </div>
                                        </div>
                                        <span v-else class="text-slate-500 text-sm">N/A</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ page.props.currency || 'Rs.' }} {{ formatCurrency(row.gross_total) }}</td>
                                    <td class="px-4 py-3 text-right text-amber-300">{{ page.props.currency || 'Rs.' }} {{ formatCurrency(row.line_discount + row.header_discount) }}</td>
                                    <td class="px-4 py-3 text-right text-cyan-300">{{ page.props.currency || 'Rs.' }} {{ formatCurrency(row.tax_total) }}</td>
                                    <td class="px-4 py-3 text-right text-green-400 font-semibold">{{ page.props.currency || 'Rs.' }} {{ formatCurrency(row.net_total) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span :class="['px-3 py-1 rounded-full text-xs font-semibold', statusBadge(row.status)]">
                                            {{ row.status === 1 ? 'Active' : row.status === 2 ? 'Closed' : 'Inactive' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="grnRows.length === 0" class="text-center text-slate-400 py-8">
                        No GRNs found for the selected range.
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
