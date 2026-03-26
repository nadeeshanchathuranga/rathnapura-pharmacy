<template>
  <Head title="Low Stock Shop Report" />

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
          <h1 class="text-4xl font-bold text-gray-800">Shop Low Stock Report</h1>
        </div>
        <!-- Filter Controls -->
        <!-- <div
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
        </div> -->
      </div>
      <!-- Low Stock Table Container -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-semibold text-gray-800">Low Stock Products</h3>
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

        <table class="w-full text-left border-collapse">
          <!-- Table Header -->
          <thead>
            <tr class="border-b-2 border-blue-600">
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">#</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Item Name</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-right">
                Shop Qty
              </th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-right">
                Low Stock Margin
              </th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Sales Unit
              </th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Symbol
              </th>
            </tr>
          </thead>
          <!-- Table Body - Product Rows -->
          <tbody>
            <tr
              v-for="(p, idx) in products"
              :key="p.id"
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
                <div class="font-semibold text-gray-900">{{ p.name }}</div>
              </td>
              <td class="px-4 py-4 text-right">
                <span
                  class="inline-flex items-center justify-center px-3 py-1 bg-orange-100 text-orange-700 rounded-lg font-bold text-sm"
                >
                  {{ p.shop_quantity }}
                </span>
              </td>
              <td class="px-4 py-4 text-right">
                <span class="text-sm font-semibold text-red-600">{{
                  p.shop_low_stock_margin
                }}</span>
              </td>
              <td class="px-4 py-4 text-center">
                <span class="text-sm text-gray-700">{{ p.sales_unit }}</span>
              </td>
              <td class="px-4 py-4 text-center">
                <span class="text-sm text-gray-700">{{ p.symbol }}</span>
              </td>
            </tr>
            <!-- Empty State Message -->
            <tr v-if="!products || products.length === 0">
              <td colspan="6" class="px-6 py-8 text-center text-gray-500 font-medium">
                No low stock products found
              </td>
            </tr>
          </tbody>
        </table>
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
  startDate: String,
  endDate: String,
});

const startDate = ref(props.startDate || "");
const endDate = ref(props.endDate || "");

const exportPdfUrl = computed(() =>
  route("reports.export.low-stock-shop.pdf", {
    start_date: startDate.value,
    end_date: endDate.value,
  })
);
const exportCsvUrl = computed(() =>
  route("reports.export.low-stock-shop.csv", {
    start_date: startDate.value,
    end_date: endDate.value,
  })
);

const exportPdf = async () => {
  await logActivity("create", "low_stock_shop_report", {
    action: "export_pdf",
    total: props.products.length,
    start_date: startDate.value,
    end_date: endDate.value,
  });
  window.location.href = exportPdfUrl.value;
};

const exportCsv = async () => {
  await logActivity("create", "low_stock_shop_report", {
    action: "export_csv",
    total: props.products.length,
    start_date: startDate.value,
    end_date: endDate.value,
  });
  window.location.href = exportCsvUrl.value;
};

const filterReports = () => {
  const params = {};
  if (startDate.value) params.start_date = startDate.value;
  if (endDate.value) params.end_date = endDate.value;
  router.get(route("reports.low-stock-shop"), params, {
    preserveState: true,
    preserveScroll: true,
  });
};

const resetFilter = () => {
  startDate.value = "";
  endDate.value = "";
  router.get(
    route("reports.low-stock-shop"),
    {},
    { preserveState: false, preserveScroll: false }
  );
};
</script>
