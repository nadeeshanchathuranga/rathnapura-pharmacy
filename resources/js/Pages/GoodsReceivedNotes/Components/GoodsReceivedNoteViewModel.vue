<template>
  <Modal :show="open" @close="close" max-width="6xl">
    <div class="p-6 bg-gray-50">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
        <h2 class="text-2xl font-bold text-blue-600">GRN Details</h2>
        <button
          type="button"
          @click="close"
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
      <!-- GRN Details -->
      <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
        <h3 class="mb-3 text-lg font-semibold text-blue-600 flex items-center gap-2">
          📋 Information
        </h3>
        <div class="grid grid-cols-2 gap-4">
          <div class="flex flex-col gap-1">
            <span class="text-sm font-medium text-gray-600">GRN Number:</span>
            <span class="text-gray-900 font-semibold">{{
              grn.goods_received_note_no
            }}</span>
          </div>
          <div class="flex flex-col gap-1">
            <span class="text-sm font-medium text-gray-600">Supplier:</span>
            <span class="text-gray-900 font-semibold">{{ grn.supplier?.name }}</span>
          </div>
          <div class="flex flex-col gap-1">
            <span class="text-sm font-medium text-gray-600">Date:</span>
            <span class="text-gray-900 font-semibold">{{
              formatDate(grn.goods_received_note_date)
            }}</span>
          </div>
          <div class="flex flex-col gap-1">
            <span class="text-sm font-medium text-gray-600">Status:</span>
            <span
              :class="getStatusColor(grn.status)"
              class="px-4 py-1.5 rounded-[5px] text-white font-semibold text-xs inline-block w-fit"
            >
              {{ getStatusText(grn.status) }}
            </span>
          </div>
          <div class="flex flex-col gap-1">
            <span class="text-sm font-medium text-gray-600">Discount:</span>
            <span class="text-gray-900 font-semibold">{{
              formatNumber(totalDiscountAmount)
            }}</span>
          </div>
          <div class="flex flex-col gap-1">
            <span class="text-sm font-medium text-gray-600">Tax:</span>
            <span class="text-gray-900 font-semibold">{{
              formatNumber(grn.tax_total)
            }}</span>
          </div>
          <div class="flex flex-col gap-1">
            <span class="text-sm font-medium text-gray-600">Remarks:</span>
            <span class="text-gray-900">{{ grn.remarks || "-" }}</span>
          </div>
        </div>
      </div>
      <!-- Products -->
      <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
        <h3 class="mb-3 text-lg font-semibold text-blue-600 flex items-center gap-2">
          📦 Products
        </h3>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b-2 border-blue-600">
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Product</th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Qty</th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Unit</th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Discount Amount</th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">
                  Purchase Price ({{ page.props.currency || "" }})
                </th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">
                  Total ({{ page.props.currency || "" }})
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="product in grn.goods_received_note_products"
                :key="product.id"
                class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200"
              >
                <td class="px-4 py-4 text-gray-900">{{ product.product?.name }}</td>
                <td class="px-4 py-4 text-gray-900">{{ product.quantity }}</td>
                <td class="px-4 py-4 text-gray-900">
                  {{ product.product?.measurement_unit?.name || product.unit || "No" }}
                </td>
                <td class="px-4 py-4 text-gray-900">
                  {{ formatNumber(product.discount) }}
                </td>
                <td class="px-4 py-4 text-gray-900">
                  {{ formatNumber(product.purchase_price) }}
                </td>
                <td class="px-4 py-4 text-gray-900 font-semibold">
                  {{ formatNumber(product.total) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Grand Total -->
      <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
        <div class="space-y-2">
          <div class="flex justify-end gap-4 pb-2 border-b border-gray-300">
            <span class="font-semibold text-gray-900">Subtotal:</span>
            <span class="font-semibold text-gray-900">{{ formatNumber(displaySubtotal) }} ({{ page.props.currency || "" }})</span>
          </div>
          <div class="flex justify-end gap-4 pb-2 border-b border-gray-300">
            <span class="font-semibold text-red-600">Discount:</span>
            <span class="font-semibold text-red-600">-{{ formatNumber(totalDiscountAmount) }} ({{ page.props.currency || "" }})</span>
          </div>
          <div class="flex justify-end gap-4 pb-2 border-b border-gray-300">
            <span class="font-semibold text-green-600">Tax:</span>
            <span class="font-semibold text-green-600">+{{ formatNumber(grn.tax_total) }} ({{ page.props.currency || "" }})</span>
          </div>
          <div class="flex justify-end gap-4 pt-2 bg-blue-50 -mx-4 -mb-4 px-4 py-3 rounded-b-xl">
            <span class="font-bold text-lg text-gray-900">Grand Total:</span>
            <span class="font-bold text-lg text-blue-600">{{ formatNumber(grandTotal) }} ({{ page.props.currency || "" }})</span>
          </div>
        </div>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
const page = usePage();

const props = defineProps({
  open: Boolean,
  grn: Object,
});

const emit = defineEmits(["update:open"]);

const roundToWhole = (value) => {
  const numericValue = Number(value) || 0;
  return Math.round(numericValue);
};

const close = () => {
  emit("update:open", false);
};

const itemDiscountAmount = computed(() => {
  const products = props.grn?.goods_received_note_products || [];
  return products.reduce((sum, product) => sum + (parseFloat(product?.discount) || 0), 0);
});

const headerDiscountAmount = computed(() => {
  return parseFloat(props.grn?.discount) || 0;
});

const totalDiscountAmount = computed(() => {
  return itemDiscountAmount.value + headerDiscountAmount.value;
});

const displaySubtotal = computed(() => {
  const storedSubtotal = parseFloat(props.grn?.subtotal) || 0;
  return storedSubtotal + itemDiscountAmount.value;
});

const grandTotal = computed(() => {
  const subtotal = displaySubtotal.value;
  const discount = totalDiscountAmount.value;
  const taxTotal = parseFloat(props.grn?.tax_total) || 0;
  return roundToWhole(subtotal - discount + taxTotal);
});

const formatDate = (date) => {
  if (!date) return "N/A";
  return new Date(date).toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
};

const formatNumber = (number) => {
  return roundToWhole(number).toLocaleString("en-US", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  });
};

const getStatusText = (status) => {
  const statuses = { 0: "INACTIVE", 1: "ACTIVE", 2: "DEFAULT" };
  return statuses[status] || "UNKNOWN";
};

const getStatusColor = (status) => {
  const colors = {
    0: "bg-red-600",
    1: "bg-[#22c55e]",
    2: "bg-gray-600",
  };
  return colors[status] || "bg-gray-500";
};
</script>
