<template>
  <Head title="Product Movement Sales Optimization Report" />

  <AppLayout>
    <!-- Main Container -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 p-6">
      <!-- Header Section with Navigation and Actions -->
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
          <!-- Back to Dashboard Button -->
          <button
            @click="goToReportsTab"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-4xl font-bold text-gray-800">Sales Optimization Report</h1>
        </div>
        <!-- Filter Controls -->
        <div
          class="flex items-center gap-2 bg-white rounded-lg p-3 shadow-sm border border-gray-200"
        >
          <input
            type="date"
            v-model="startDate"
            class="px-3 py-2 bg-white text-gray-800 text-sm border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
          />
          <span class="text-gray-400">to</span>
          <input
            type="date"
            v-model="endDate"
            class="px-3 py-2 bg-white text-gray-800 text-sm border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
          />
          <select
            v-model="selectedClassification"
            class="px-3 py-2 bg-white text-gray-800 text-sm border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
          >
            <option value="">All Classifications</option>
            <option value="Fast Moving">Fast Moving</option>
            <option value="Medium Moving">Medium Moving</option>
            <option value="Slow Moving">Slow Moving</option>
            <option value="No Sales">No Sales</option>
          </select>
          <button
            @click="filterReports"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-[5px] transition-all duration-200"
          >
            Apply
          </button>
          <button
            @click="resetFilter"
            class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-[5px] transition-all duration-200"
          >
            Reset
          </button>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8" v-if="summary">
        <div class="bg-white rounded-2xl border border-gray-200 p-6 text-center">
          <div class="text-sm font-medium text-gray-500 mb-2">Total Products</div>
          <div class="text-3xl font-bold text-gray-900">{{ summary.total_products }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-6 text-center">
          <div class="text-sm font-medium text-gray-500 mb-2">Fast Moving</div>
          <div class="text-3xl font-bold text-green-600">{{ summary.fast_moving }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-6 text-center">
          <div class="text-sm font-medium text-gray-500 mb-2">Medium Moving</div>
          <div class="text-3xl font-bold text-blue-600">{{ summary.medium_moving }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-6 text-center">
          <div class="text-sm font-medium text-gray-500 mb-2">Slow Moving</div>
          <div class="text-3xl font-bold text-orange-600">{{ summary.slow_moving }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-6 text-center">
          <div class="text-sm font-medium text-gray-500 mb-2">No Sales</div>
          <div class="text-3xl font-bold text-red-600">{{ summary.no_sales }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 p-6 text-center">
          <div class="text-sm font-medium text-gray-500 mb-2">Total Revenue</div>
          <div class="text-2xl font-bold text-purple-600">{{ currency }} {{ summary.total_revenue }}</div>
        </div>

      </div>

      <!-- Products Table Container -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-semibold text-gray-800">Product Analysis & Optimization</h3>
          <div class="flex gap-2">
            <button
              @click="exportPdf"
              class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-[5px] hover:scale-105 transition-all duration-300 flex items-center gap-2"
            >
              📄 Export PDF
            </button>
            <button
              @click="exportCsv"
              class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-[5px] hover:scale-105 transition-all duration-300 flex items-center gap-2"
            >
                Export CSV
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <!-- Table Header -->
            <thead>
              <tr class="border-b-2 border-blue-600">
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">#</th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Product</th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-right">Stock</th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-right">Sales Qty</th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-right">Sales Amount</th>

                <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">Classification</th>

              </tr>
            </thead>
            <!-- Table Body - Product Rows -->
            <tbody>
              <tr
                v-for="(product, idx) in filteredProducts"
                :key="product.id"
                class="border-b border-gray-200 hover:bg-blue-50/50 transition-colors duration-200"
              >
                <td class="px-4 py-4">
                  <span
                    class="inline-flex items-center justify-center w-8 h-8 rounded-[10px] bg-blue-100 text-blue-700 font-bold text-sm"
                  >
                    {{ idx + 1 }}
                  </span>
                </td>
                <td class="px-4 py-4">
                  <div class="font-semibold text-gray-900">{{ product.name }}</div>

                </td>
                <td class="px-4 py-4 text-right">
                  <span
                    class="inline-flex items-center justify-center px-3 py-1 bg-gray-100 text-gray-700 rounded-lg font-bold text-sm"
                  >
                    {{ product.current_stock }}
                    <template v-if="product.sales_unit_symbol"> {{ product.sales_unit_symbol }}</template>
                  </span>
                </td>
                <td class="px-4 py-4 text-right">
                  <span class="text-sm font-semibold text-gray-900">
                    {{ product.sales_quantity }}
                    <template v-if="product.sales_unit_symbol"> {{ product.sales_unit_symbol }}</template>
                  </span>
                </td>
                <td class="px-4 py-4 text-right">
                  <span class="text-sm font-semibold text-green-600">{{ currency }} {{ product.sales_amount }}</span>
                </td>

                <td class="px-4 py-4 text-center">
                  <span
                    class="inline-flex px-3 py-1 text-xs font-semibold rounded-full"
                    :class="{
                      'bg-green-100 text-green-800': product.classification === 'Fast Moving',
                      'bg-blue-100 text-blue-800': product.classification === 'Medium Moving',
                      'bg-orange-100 text-orange-800': product.classification === 'Slow Moving',
                      'bg-red-100 text-red-800': product.classification === 'No Sales'
                    }"
                  >
                    {{ product.classification }}
                  </span>
                </td>


              </tr>
              <!-- Empty State Message -->
              <tr v-if="!filteredProducts || filteredProducts.length === 0">
                <td colspan="9" class="px-6 py-8 text-center text-gray-500 font-medium">
                  No products found for the selected filters
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { logActivity } from "@/composables/useActivityLog";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const { goToReportsTab } = useDashboardNavigation();

const props = defineProps({
  products: { type: Array, default: () => [] },
  summary: { type: Object, default: () => null },
  startDate: String,
  endDate: String,
  currencySymbol: Object,
  currency: String
});

// If sales_unit_symbol is not present, fallback to 'unit' or blank
const withUnitSymbolFallback = (product) => {
  return {
    ...product,
    sales_unit_symbol: product.sales_unit_symbol || product.unit || '',
  };
};

const filteredProducts = computed(() => {
  const productsWithSymbol = props.products.map(withUnitSymbolFallback);
  if (!selectedClassification.value) {
    return productsWithSymbol;
  }
  return productsWithSymbol.filter(product =>
    product.classification === selectedClassification.value
  );
});

const startDate = ref(props.startDate || "");
const endDate = ref(props.endDate || "");
const selectedClassification = ref("");

// ...existing code...

const exportPdfUrl = computed(() =>
  route("reports.export.product-movement-sales-optimization.pdf", {
    start_date: startDate.value,
    end_date: endDate.value,
    classification: selectedClassification.value,
  })
);
const exportCsvUrl = computed(() =>
  route("reports.export.product-movement-sales-optimization.csv", {
    start_date: startDate.value,
    end_date: endDate.value,
    classification: selectedClassification.value,
  })
);

const exportPdf = async () => {
  await logActivity("export", "sales_optimization_report", {
    action: "export_pdf",
    total: filteredProducts.value.length,
    start_date: startDate.value,
    end_date: endDate.value,
    classification: selectedClassification.value,
  });
  try {
    const response = await fetch(exportPdfUrl.value, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/pdf',
      },
      credentials: 'include',
    });
    if (!response.ok) throw new Error('Failed to download PDF');
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'sales optimization report.pdf');
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (e) {
    alert('Failed to download PDF.');
  }
};

const exportCsv = async () => {
  await logActivity("export", "sales_optimization_report", {
    action: "export_csv",
    total: filteredProducts.value.length,
    start_date: startDate.value,
    end_date: endDate.value,
    classification: selectedClassification.value,
  });
  try {
    const response = await fetch(exportCsvUrl.value, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'text/csv',
      },
      credentials: 'include',
    });
    if (!response.ok) throw new Error('Failed to download CSV');
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'sales optimization report.csv');
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (e) {
    alert('Failed to download CSV.');
  }
};

const filterReports = () => {
  const params = {};
  if (startDate.value) params.start_date = startDate.value;
  if (endDate.value) params.end_date = endDate.value;
  if (selectedClassification.value) params.classification = selectedClassification.value;
  router.get(route("reports.product-movement-sales-optimization"), params, {
    preserveState: true,
    preserveScroll: true,
  });
};

const resetFilter = () => {
  startDate.value = "";
  endDate.value = "";
  selectedClassification.value = "";
  router.get(
    route("reports.product-movement-sales-optimization"),
    {},
    { preserveState: false, preserveScroll: false }
  );
};
</script>

<style scoped>
/* Add any additional styles as needed */
</style>
