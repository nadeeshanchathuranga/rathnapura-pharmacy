<template>
  <Head title="Stock Report" />

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
          <h1 class="text-4xl font-bold text-gray-800">Stock Report</h1>
        </div>
      </div>

      <!-- Products Stock Report -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-semibold text-gray-800">Products Stock Details</h3>
          <div class="flex gap-2">
            <button
              @click="exportStockPdf"
              class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-[5px] hover:scale-105 transition-all duration-300 flex items-center gap-2"
            >
              📄 Export PDF
            </button>
            <button
              @click="exportStockExcel"
              class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-[5px] hover:scale-105 transition-all duration-300 flex items-center gap-2"
            >
                Export Excel
            </button>
          </div>
        </div>
        <table class="w-full text-left border-collapse">
          <!-- Table Header -->
          <thead>
            <tr class="border-b-2 border-blue-600">
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Product Name</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Shop Qty
              </th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Store Qty
              </th>
            </tr>
          </thead>
          <!-- Table Body - Product Rows -->
          <tbody>
            <tr
              v-for="product in productsStock.data"
              :key="product.id"
              class="border-b border-gray-200 hover:bg-blue-50/50 transition-colors duration-200"
            >
              <td class="px-4 py-4">
                <div class="font-semibold text-gray-900">{{ product.name }}</div>
              </td>
              <td class="px-4 py-4 text-center">
                <span
                  class="inline-flex items-center justify-center px-3 py-1 bg-orange-100 text-orange-700 rounded-lg font-bold text-sm"
                >
                  {{ product.shop_qty_display }}
                </span>
              </td>
              <td class="px-4 py-4 text-center">
                <div class="space-y-1">
                  <!-- Store Quantities -->
                <div class="flex flex-col gap-1">
                  <!-- Full Boxes (Purchase Unit) -->
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs">
                    <span class="text-[10px] font-semibold">Store ({{ product.purchase_unit?.symbol || 'Box' }}):</span>
                    <span>{{ product.store_qty_display }}</span>
                  </span>
                  
                  <!-- Loose Bundles (from opened boxes) -->
                 <span  
                    class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs">
                    <span class="text-[10px] font-semibold">
                      + Loose ({{ product.transfer_unit?.symbol || 'Bundle' }}):
                    </span>
                    <span>{{ product.loose_bundles || 0 }}</span>
                  </span>

                   <!-- Loose Bottle (from opened Bundle) -->
                 <span  
                    class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs">
                    <span class="text-[10px] font-semibold">
                      + Loose ({{ product.sales_unit?.symbol || 'N/A' }}):
                    </span>
                    <span>{{ product.loose_bottles || 0 }}</span>
                  </span>
                  

                </div>
              </div>
              </td>
            </tr>
            <!-- Empty State Message -->
            <tr v-if="!productsStock.data || productsStock.data.length === 0">
              <td colspan="3" class="px-6 py-8 text-center text-gray-500 font-medium">
                No products found
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div
          v-if="productsStock.data?.length > 0"
          class="flex items-center justify-between px-6 py-4 mt-4"
        >
          <div class="text-sm text-gray-600 font-medium">
            Showing {{ productsStock.from }} to {{ productsStock.to }} of
            {{ productsStock.total }} results
          </div>
          <div class="flex gap-2">
            <template v-for="(link, index) in productsStock.links" :key="index">
              <a
                v-if="link.url"
                :href="link.url"
                @click.prevent="
                  router.visit(link.url, { preserveState: true, preserveScroll: true })
                "
                :class="[
                  'px-4 py-2 text-sm rounded-[5px] font-medium transition-all duration-200',
                  link.active
                    ? 'bg-blue-600 text-white'
                    : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50',
                ]"
                v-html="link.label"
              ></a>
              <span
                v-else
                :class="[
                  'px-4 py-2 text-sm rounded-[5px]',
                  'bg-gray-100 text-gray-400 cursor-not-allowed',
                ]"
                v-html="link.label"
              ></span>
            </template>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { computed } from "vue";
import { logActivity } from "@/composables/useActivityLog";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const { goToReportsTab } = useDashboardNavigation();

const props = defineProps({
  productsStock: Object,
});

const page = usePage();

const inStockCount = computed(() => {
  if (!props.productsStock.data) return 0;
  return props.productsStock.data.filter(
    (p) => p.shop_quantity > 0 || p.store_quantity > 0
  ).length;
});

const lowStockCount = computed(() => {
  if (!props.productsStock.data) return 0;
  return props.productsStock.data.filter(
    (p) =>
      (p.shop_quantity > 0 && p.shop_quantity < 10) ||
      (p.store_quantity > 0 && p.store_quantity < 10)
  ).length;
});

const outOfStockCount = computed(() => {
  if (!props.productsStock.data) return 0;
  return props.productsStock.data.filter(
    (p) => p.shop_quantity === 0 && p.store_quantity === 0
  ).length;
});

const exportProductStockPdfUrl = computed(() => {
  return route("reports.export.product-stock.pdf", {
    currency: page.props.currency || "",
  });
});

const exportProductStockExcelUrl = computed(() => {
  return route("reports.export.product-stock.excel", {
    currency: page.props.currency || "",
  });
});

const exportStockPdf = async () => {
  await logActivity("create", "stock_report", {
    action: "export_pdf",
    total_products: props.productsStock.total || 0,
  });
  window.location.href = exportProductStockPdfUrl.value;
};

const exportStockExcel = async () => {
  await logActivity("create", "stock_report", {
    action: "export_excel",
    total_products: props.productsStock.total || 0,
  });
  window.location.href = exportProductStockExcelUrl.value;
};

const getStockStatusColor = (status) => {
  if (status === "Out of Stock") return "text-red-500";
  if (status === "Low Stock") return "text-orange-500";
  return "text-green-500";
};
</script>
