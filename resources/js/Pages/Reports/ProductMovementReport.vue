<script setup>
import { computed, ref } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import axios from "axios";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const { goToReportsTab } = useDashboardNavigation();

const page = usePage();

const props = defineProps({
  movements: { type: Object, default: () => ({}) },
  summaryByType: { type: Array, default: () => [] },
  summaryByProduct: { type: Array, default: () => [] },
  totals: { type: Object, default: () => ({}) },
  products: { type: Array, default: () => [] },
  selectedProductId: { type: [String, Number, null], default: null },
  selectedMovementType: { type: [String, Number, null], default: null },
  startDate: { type: String, default: "" },
  endDate: { type: String, default: "" },
});

const startDate = ref(props.startDate);
const endDate = ref(props.endDate);
const selectedProductId = ref(props.selectedProductId);
const selectedMovementType = ref(props.selectedMovementType);
const expandedProduct = ref(null);

const movementTypes = [
  { id: 0, name: "Purchase (GRN)" },
  { id: 1, name: "Purchase Return (PRN)" },
  { id: 2, name: "Transfer (PTR)" },
  { id: 3, name: "Sale" },
  { id: 4, name: "Sale Return" },
  { id: 5, name: "GRN Return" },
  { id: 6, name: "Stock Transfer Return" },
];

const formatCurrency = (value) =>
  new Intl.NumberFormat("en-LK", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number(value ?? 0));

const getMovementColor = (typeId) => {
  const colors = {
    0: "bg-green-600/20 text-green-300 border-l-4 border-green-500",
    1: "bg-orange-600/20 text-orange-300 border-l-4 border-orange-500",
    2: "bg-blue-600/20 text-blue-300 border-l-4 border-blue-500",
    3: "bg-red-600/20 text-red-300 border-l-4 border-red-500",
    4: "bg-amber-600/20 text-amber-300 border-l-4 border-amber-500",
    5: "bg-purple-600/20 text-purple-300 border-l-4 border-purple-500",
    6: "bg-pink-600/20 text-pink-300 border-l-4 border-pink-500",
  };
  return colors[typeId] || "bg-slate-600/20 text-slate-300 border-l-4 border-slate-500";
};

const getMovementIcon = (typeId) => {
  const icons = {
    0: "📥",
    1: "📤",
    2: "🔄",
    3: "🛒",
    4: "↩️",
    5: "🔙",
    6: "⚠️",
  };
  return icons[typeId] || "📦";
};

const filterReport = () => {
  const params = {
    start_date: startDate.value,
    end_date: endDate.value,
  };
  if (selectedProductId.value) {
    params.product_id = selectedProductId.value;
  }
  if (selectedMovementType.value !== null && selectedMovementType.value !== "") {
    params.movement_type = selectedMovementType.value;
  }
  router.get(route("reports.product-movements"), params, {
    preserveScroll: true,
    preserveState: true,
  });
};

const resetFilter = () => {
  startDate.value = props.startDate;
  endDate.value = props.endDate;
  selectedProductId.value = null;
  selectedMovementType.value = null;
  router.get(route("reports.product-movements"), {}, { preserveScroll: true });
};

const inboundMovements = computed(() =>
  props.movements.data
    ? props.movements.data.filter((m) => [0, 4, 5].includes(m.movement_type_id))
    : []
);
const outboundMovements = computed(() =>
  props.movements.data
    ? props.movements.data.filter((m) => [1, 2, 3, 6].includes(m.movement_type_id))
    : []
);

const exportLinks = computed(() => {
  const params = new URLSearchParams();
  if (startDate.value) params.append("start_date", startDate.value);
  if (endDate.value) params.append("end_date", endDate.value);
  if (selectedProductId.value) params.append("product_id", selectedProductId.value);
  if (selectedMovementType.value !== null && selectedMovementType.value !== "")
    params.append("movement_type", selectedMovementType.value);
  const query = params.toString();
  return {
    pdf: "/reports/export/product-movements/pdf" + (query ? `?${query}` : ""),
    excel: "/reports/export/product-movements/excel" + (query ? `?${query}` : ""),
  };
});

const logExportActivity = async (type) => {
  try {
    await axios.post("/products/log-activity", {
      action: "export",
      module: "product movement report",
      details: {
        export_type: type,
        start_date: startDate.value,
        end_date: endDate.value,
        product_id: selectedProductId.value,
      },
    });
  } catch (e) {
    // Optionally handle/log error
    console.error("Activity log failed", e);
  }
};
</script>

<template>
  <Head title="Product Movements Report" />

  <AppLayout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 p-6">
      <!-- Header Section with Navigation and Actions -->
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
          <button
            @click="goToReportsTab"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-4xl font-bold text-gray-800">Product Movement Report</h1>
        </div>

        <!-- Filters -->
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
            v-model="selectedMovementType"
            class="px-3 py-2 bg-white text-gray-800 text-sm border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">All Movement Types</option>
            <option v-for="type in movementTypes" :key="type.id" :value="type.id">
              {{ type.name }}
            </option>
          </select>
          <button
            @click="filterReport"
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

      <!-- Detailed Movements List -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-semibold text-gray-800">All Movements</h3>
          <div class="flex gap-2">
            <a
              :href="exportLinks.pdf"
              class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-[5px] hover:scale-105 transition-all duration-300 flex items-center gap-2"
              @click="logExportActivity('pdf')"
            >
              📄 Export PDF
            </a>
            <a
              :href="exportLinks.excel"
              class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-[5px] hover:scale-105 transition-all duration-300 flex items-center gap-2"
              @click="logExportActivity('excel')"
            >
                Export Excel
            </a>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b-2 border-blue-600">
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Date</th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">
                  Product Name
                </th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">
                  Movement Type
                </th>
                <th class="px-4 py-3 text-right text-blue-600 font-semibold text-sm">
                  Quantity
                </th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Unit</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="movement in movements.data"
                :key="movement.id"
                class="border-b border-gray-200 hover:bg-blue-50/50 transition-colors duration-200"
              >
                <td class="px-4 py-4 text-gray-700">{{ movement.date }}</td>
                <td class="px-4 py-4">
                  <div class="font-semibold text-gray-900">
                    {{ movement.product_name }}
                  </div>
                  <div class="text-xs text-gray-500">{{ movement.product_code }}</div>
                </td>
                <td class="px-4 py-4">
                  <div class="flex items-center gap-2">
                    <span class="text-lg">{{
                      getMovementIcon(movement.movement_type_id)
                    }}</span>
                    <span class="text-gray-800">{{ movement.movement_type }}</span>
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                    Ref: {{ movement.reference }}
                  </div>
                </td>
                <td class="px-4 py-4 text-right">
                  <span
                    class="text-lg font-bold"
                    :class="
                      [0, 4, 5].includes(movement.movement_type_id)
                        ? 'text-green-600'
                        : 'text-red-600'
                    "
                  >
                    {{ [0, 4, 5].includes(movement.movement_type_id) ? "+" : "-"
                    }}{{ movement.quantity }}
                  </span>
                </td>
                <td class="px-4 py-4 text-gray-700">{{ movement.unit || "Units" }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div
          v-if="movements.data && movements.data.length === 0"
          class="text-center text-gray-500 py-8 font-medium"
        >
          No movements found for the selected criteria
        </div>

        <!-- Pagination -->
        <div
          v-if="movements.data && movements.data.length > 0"
          class="flex items-center justify-between px-6 py-4 mt-4"
        >
          <div class="text-sm text-gray-600 font-medium">
            Showing {{ movements.from }} to {{ movements.to }} of
            {{ movements.total }} results
          </div>
          <div class="flex gap-2">
            <button
              v-for="link in movements.links"
              :key="link.label"
              @click="link.url ? $inertia.get(link.url) : null"
              :disabled="!link.url"
              :class="[
                'px-4 py-2 text-sm rounded-[5px] font-medium transition-all duration-200',
                link.active
                  ? 'bg-blue-600 text-white'
                  : link.url
                  ? 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
                  : 'bg-gray-100 text-gray-400 cursor-not-allowed',
              ]"
              v-html="link.label"
            ></button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
