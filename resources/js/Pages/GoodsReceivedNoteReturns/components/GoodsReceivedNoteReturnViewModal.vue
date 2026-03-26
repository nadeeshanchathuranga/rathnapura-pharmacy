<template>
  <Modal :show="open" @close="close" max-width="6xl">
    <div class="p-6 bg-gray-50">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
        <h2 class="text-2xl font-bold text-blue-600">GRN Return Details</h2>
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
            <span class="text-gray-900 font-semibold">{{ getGrnNumber() }}</span>
          </div>
          <div class="flex flex-col gap-1">
            <span class="text-sm font-medium text-gray-600">Date:</span>
            <span class="text-gray-900 font-semibold">{{ formatDate(ret?.date) }}</span>
          </div>
        </div>
      </div>

      <!-- Products -->
      <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
        <h3 class="mb-3 text-lg font-semibold text-blue-600 flex items-center gap-2">
          📦 Returned Products
        </h3>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b-2 border-blue-600">
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Product</th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Unit</th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">
                  Original Qty
                </th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Return Qty</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in getReturnProducts()"
                :key="item.id"
                class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200"
              >
                <td class="px-4 py-4 text-gray-900">{{ getProductName(item) }}</td>
                <td class="px-4 py-4 text-gray-900">{{ getUnitName(item) }}</td>
                <td class="px-4 py-4 text-gray-900">
                  {{ formatNumber(getOriginalQty(item)) }}
                </td>
                <td class="px-4 py-4 text-gray-900 font-semibold">
                  {{ formatNumber(item.qty || item.quantity) }}
                </td>
              </tr>
              <tr v-if="getReturnProducts().length === 0">
                <td colspan="4" class="px-6 py-8 text-center text-gray-500 font-medium">
                  No returned products
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import Modal from "@/Components/Modal.vue";

const props = defineProps({
  open: Boolean,
  ret: Object,
  measurementUnits: { type: Array, default: () => [] },
});

const emit = defineEmits(["update:open"]);

const close = () => {
  emit("update:open", false);
};

const formatDate = (date) => {
  if (!date) return "N/A";
  return new Date(date).toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
};

const formatNumber = (number) => {
  const num = parseFloat(number || 0);
  if (isNaN(num)) return "N/A";
  return num.toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

// Get GRN number robustly from all possible property names and fallback to goods_received_note_no
const getGrnNumber = () => {
  const ret = props.ret || {};
  // Try nested objects
  return (
    ret.goods_received_note?.grn_no ||
    ret.goods_received_note?.goods_received_note_no ||
    ret.goodsReceivedNote?.grn_no ||
    ret.goodsReceivedNote?.goods_received_note_no ||
    ret.grn?.grn_no ||
    ret.grn?.goods_received_note_no ||
    ret.goods_received_note_no ||
    ret.grn_no ||
    "N/A"
  );
};

// Get return products from various possible property names
const getReturnProducts = () => {
  const products =
    props.ret?.goods_received_note_return_products ||
    props.ret?.goodsReceivedNoteReturnProducts ||
    props.ret?.grn_return_products ||
    [];

  return Array.isArray(products) ? products : products.data || [];
};

// Get product name from item
const getProductName = (item) => {
  return item.product?.name || item.products?.name || "N/A";
};

// Get original quantity in the return unit (not in GRN unit)
const getOriginalQty = (item) => {
  try {
    // Get GRN and return product details
    const grn =
      props.ret?.goods_received_note ||
      props.ret?.goodsReceivedNote ||
      props.ret?.grn ||
      {};

    const grnProducts =
      grn.goods_received_note_products ||
      grn.goodsReceivedNoteProducts ||
      grn.grnProducts ||
      grn.grn_products ||
      [];

    const productId =
      item.products_id || item.product_id || item.product?.id || item.products?.id;

    if (!productId) return null;

    const productsArray = Array.isArray(grnProducts)
      ? grnProducts
      : grnProducts.data || [];

    const grnProduct = productsArray.find((gp) => Number(gp.product_id) === Number(productId));
    
    if (!grnProduct) return null;

    // Get the return unit ID and product info
    const returnUnitId = item.measurement_unit_id;
    const nestedProduct = item.product || {};
    
    const grnQty = grnProduct.qty || grnProduct.quantity || 0;
    const purchaseUnitId = nestedProduct.purchase_unit_id;
    const transferUnitId = nestedProduct.transfer_unit_id;
    const salesUnitId = nestedProduct.sales_unit_id;
    const purchaseToTransferRate = parseFloat(nestedProduct.purchase_to_transfer_rate) || 1;
    const transferToSalesRate = parseFloat(nestedProduct.transfer_to_sales_rate) || 1;

    // Convert GRN quantity (in purchase units) to the return unit
    if (returnUnitId == purchaseUnitId) {
      // Return is in purchase units, GRN is also in purchase units
      return grnQty;
    } else if (returnUnitId == transferUnitId) {
      // Return is in transfer units, convert GRN qty from purchase to transfer
      return grnQty * purchaseToTransferRate;
    } else if (returnUnitId == salesUnitId) {
      // Return is in sales units, convert GRN qty from purchase to sales
      return grnQty * purchaseToTransferRate * transferToSalesRate;
    }

    return grnQty; // Fallback
  } catch (e) {
    console.error("Error getting original quantity:", e);
    return null;
  }
};

// Get unit name for the returned item (by measurement_unit_id)
const getUnitName = (item) => {
  // 1. Try direct unit relationship on item first
  if (item.measurement_unit?.name) return item.measurement_unit.name;
  if (item.measurementUnit?.name) return item.measurementUnit.name;
  if (item.unit?.name) return item.unit.name;

  // 2. Look up by measurement_unit_id (this is the actual return unit)
  const unitId = item.measurement_unit_id;
  if (unitId && Array.isArray(props.measurementUnits)) {
    const found = props.measurementUnits.find((u) => Number(u.id) === Number(unitId));
    if (found) return found.name;
  }

  // Fallback to product-level unit relationship
  const productUnit =
    item.product?.measurement_unit?.name ||
    item.product?.measurementUnit?.name ||
    item.products?.measurement_unit?.name ||
    item.products?.measurementUnit?.name;

  if (productUnit) return productUnit;

  // Last resort: return N/A
  return "N/A";
};
</script>
