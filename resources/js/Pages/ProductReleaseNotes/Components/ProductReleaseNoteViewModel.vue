<template>
  <Modal :show="open" @close="close" max-width="5xl">
    <div class="p-6 bg-white">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-blue-700">
        View Product Release Note Details
        </h2>
        <button
          @click="close"
          class="text-gray-400 hover:text-gray-600 transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            ></path>
          </svg>
        </button>
      </div>

      <!-- PRODUCT RELEASE NOTE DETAILS -->
      <div class="bg-gray-50 rounded-xl p-5 mb-6 border border-gray-200">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Release Note Information</h3>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-gray-500 text-sm font-medium mb-1"
              >Release Date</label
            >
            <p class="text-gray-800 font-semibold">
              {{ formatDate(productReleaseNote.release_date) }}
            </p>
          </div>

          <div>
            <label class="block text-gray-500 text-sm font-medium mb-1">Status</label>
            <span
              :class="[
                'px-4 py-1.5 rounded-[5px] text-xs font-medium inline-block',
                productReleaseNote.status === 0
                  ? 'bg-yellow-500 text-white'
                  : 'bg-green-500 text-white',
              ]"
            >
              {{ getStatusLabel(productReleaseNote.status) }}
            </span>
          </div>

          <div v-if="productReleaseNote.user">
            <label class="block text-gray-500 text-sm font-medium mb-1">User</label>
            <p class="text-gray-800 font-semibold">{{ productReleaseNote.user.name }}</p>
          </div>

          <div v-if="productReleaseNote.product_transfer_request">
            <label class="block text-gray-500 text-sm font-medium mb-1">PTR No</label>
            <p class="text-gray-800 font-semibold">
              {{
                productReleaseNote.product_transfer_request.product_transfer_request_no
              }}
            </p>
          </div>

          <div class="col-span-2" v-if="productReleaseNote.remark">
            <label class="block text-gray-500 text-sm font-medium mb-1">Remark</label>
            <p class="text-gray-800">{{ productReleaseNote.remark }}</p>
          </div>
        </div>
      </div>

      <!-- PRODUCTS TABLE -->
      <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Products</h3>

        <div class="overflow-x-auto bg-white rounded-lg border border-gray-200">
          <table class="w-full text-sm border-collapse">
            <thead>
              <tr class="border-b-2 border-blue-600">
                <th class="px-4 py-3 text-left text-blue-600 font-semibold text-sm">#</th>
                <th class="px-4 py-3 text-left text-blue-600 font-semibold text-sm">
                  Product Name
                </th>
                <th class="px-4 py-3 text-left text-blue-600 font-semibold text-sm">
                  Unit
                </th>
                <th class="px-4 py-3 text-right text-blue-600 font-semibold text-sm">
                  Quantity
                </th>
                <th class="px-4 py-3 text-right text-blue-600 font-semibold text-sm">
                  Unit Price ({{ currencySymbol }})
                </th>
                <th class="px-4 py-3 text-right text-blue-600 font-semibold text-sm">
                  Total ({{ currencySymbol }})
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, index) in getProducts()"
                :key="index"
                class="border-b border-gray-200 hover:bg-gray-50 transition-colors"
              >
                <td class="px-4 py-3 text-gray-700">{{ index + 1 }}</td>
                <td class="px-4 py-3 text-gray-800 font-medium">
                  {{ getProductName(item) }}
                </td>
                <td class="px-4 py-3 text-gray-800">
                  {{ getUnitName(item) }}
                </td>
                <td class="px-4 py-3 text-right text-gray-800">
                  {{ formatNumber(item.quantity) }}
                </td>
                <td class="px-4 py-3 text-right text-gray-800">
                  {{ formatNumber(getUnitPrice(item)) }}
                </td>
                <td class="px-4 py-3 text-right text-gray-800 font-semibold">
                  {{ formatNumber(getTotal(item)) }}
                </td>
              </tr>
              <tr v-if="getProducts().length === 0">
                <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                  No products found
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="bg-blue-50 border-t-2 border-blue-600">
                <td
                  colspan="4"
                  class="px-4 py-4 text-right font-semibold text-gray-800 text-base"
                >
                  Grand Total:
                </td>
                <td class="px-4 py-4 text-right">
                  <span class="font-bold text-blue-700 text-lg">{{
                    formatNumber(grandTotal)
                  }} ({{ currencySymbol }})</span>
                </td>
              </tr>
            </tfoot>
          </table>
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
  productReleaseNote: Object,
});

const emit = defineEmits(["update:open"]);

const close = () => {
  emit("update:open", false);
};

// Get currency symbol from page props
const currencySymbol = computed(() => {
  return page.props.currencySymbol?.currency_symbol || page.props.currency || "$";
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
  return parseFloat(number || 0).toFixed(2);
};

const getStatusLabel = (status) => {
  const labels = {
    0: "Pending",
    1: "Released",
  };
  return labels[status] || "Unknown";
};

// Get products array from product release note
const getProducts = () => {
  return props.productReleaseNote?.product_release_note_products || [];
};

// Get product name
const getProductName = (item) => {
  return item.product?.name || item.product_name || "N/A";
};

// Get unit name
const getUnitName = (item) => {
  return item.unit?.name || "N/A";
}; 


// Get unit price
const getUnitPrice = (item) => {
  return item.unit_price ?? item.price ?? 0;
};

// Get total
const getTotal = (item) => {
  if (item.total !== undefined) return item.total;
  const qty = parseFloat(item.quantity || 0);
  const price = parseFloat(getUnitPrice(item));
  return qty * price;
};

const grandTotal = computed(() => {
  return getProducts().reduce((sum, item) => sum + (parseFloat(getTotal(item)) || 0), 0);
});
</script>
