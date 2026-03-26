<template>
  <Modal :show="open" @close="closeModal" max-width="4xl">
    <div class="p-6 bg-gray-50">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-blue-600">Purchase Order Request Details</h2>
        <button
          @click="closeModal"
          class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-200 rounded-full transition-all duration-200"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-6 h-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <div v-if="porLocal" class="p-6">
        <!-- Order Information -->
        <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
          <h3 class="mb-3 text-lg font-semibold text-blue-600 flex items-center gap-2">
            📋 Order Information
          </h3>
          <div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div>
                <label class="block mb-1 text-sm font-medium text-gray-500"
                  >Order Number</label
                >
                <p class="text-base font-semibold text-gray-900">
                  {{ porLocal.order_number }}
                </p>
              </div>
              <div>
                <label class="block mb-1 text-sm font-medium text-gray-500"
                  >Order Date</label
                >
                <p class="text-base font-semibold text-gray-900">
                  {{ formatDate(porLocal.order_date) }}
                </p>
              </div>
              <div>
                <label class="block mb-1 text-sm font-medium text-gray-500">User</label>
                <p class="text-base font-semibold text-gray-900">
                  {{ porLocal.user?.name || "N/A" }}
                </p>
              </div>

              <div>
                <label class="block mb-1 text-sm font-medium text-gray-500">Status</label>
                <span :class="getStatusClass(porLocal.status)">
                  {{ porLocal.status.toUpperCase() }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Products -->
        <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
          <h3 class="mb-3 text-lg font-semibold text-blue-600 flex items-center gap-2">
            📦 Products
          </h3>
          <div>
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b-2 border-blue-600">
                    <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Product</th>
                    <th class="px-4 py-3 text-blue-600 font-semibold text-sm">
                      Quantity
                    </th>
                    <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Unit</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="item in porLocal.por_products"
                    :key="item.id"
                    class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200"
                  >
                    <td class="px-4 py-4 text-sm text-gray-900">
                      {{ item.product?.name || "N/A" }}
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-900">
                      {{ item.requested_quantity }}
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-700">
                      {{ getMeasurementUnitSymbol(item) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <!-- <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
          <button
            type="button"
            @click="closeModal"
            class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-[5px] hover:bg-gray-50 transition-all duration-200"
          >
            Close
          </button>
        </div> -->
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";

const page = usePage();

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  por: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["update:open"]);

// Local reactive copy so we can update UI immediately after change
const porLocal = ref(props.por);
  watch(() => props.por, (v) => {
  porLocal.value = v;
});

const closeModal = () => {
  emit("update:open", false);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
};

const formatNumber = (number) => {
  return parseFloat(number || 0).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const getStatusClass = (status) => {
  const classes = {
    active: "bg-[#22c55e] text-white px-4 py-1.5 rounded-[5px] font-medium text-xs",
    pending: "bg-yellow-500 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs",
    approved: "bg-yellow-500 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs",
    rejected: "bg-blue-500 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs",
    completed: "bg-blue-500 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs",
    inactive: "bg-red-600 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs",
  };
  return (
    classes[status] ||
    "bg-gray-500 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs"
  );
};

const getMeasurementUnitSymbol = (item) => {
  // Prefer the purchaseUnit relation loaded from backend
  if (item.product?.purchaseUnit?.symbol) {
    return item.product.purchaseUnit.symbol;
  }
  // Fallback to a snake_case key if the serializer used it
  if (item.product?.purchase_unit?.symbol) {
    return item.product.purchase_unit.symbol;
  }
  return "N/A";
};
</script>
