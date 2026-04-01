<template>
  <Modal :show="open" @close="close" max-width="6xl">
    <div class="p-6 bg-gray-50">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
        <h2 class="text-2xl font-bold text-blue-600">✨ Create New GRN</h2>
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

      <form @submit.prevent="submitForm">
        <!-- LINK FROM PURCHASE ORDER (optional) -->
        <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-blue-100">
          <h3 class="mb-3 text-lg font-semibold text-blue-600 flex items-center gap-2">
            🛒 Link to Purchase Order (Optional)
          </h3>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Select Purchase Order</label>
              <select
                v-model="selectedPoId"
                @change="onPoSelect"
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">-- None (Direct GRN) --</option>
                <option v-for="po in purchaseOrders" :key="po.id" :value="po.id">
                  {{ po.order_number }} — {{ po.supplier?.name }}
                </option>
              </select>
            </div>
            <div v-if="selectedPoId" class="flex items-end">
              <p class="text-xs text-green-600 font-medium">
                ✅ PO linked. Supplier and products pre-filled. You can adjust quantities below.
              </p>
            </div>
          </div>
        </div>

        <!-- GRN DETAILS -->
        <div
          class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200"
        >
          <h3 class="mb-3 text-lg font-semibold text-blue-600 flex items-center gap-2">
            📋 GRN Details
          </h3>

          <div class="grid grid-cols-2 gap-3 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2"
                >GRN Number <span class="text-red-500">*</span></label
              >
              <input
                v-model="form.goods_received_note_no"
                type="text"
                class="w-full px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-lg focus:outline-none cursor-not-allowed font-medium"
                readonly
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2"
                >Supplier <span class="text-red-500">*</span></label
              >
              <select
                v-model="form.supplier_id"
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required
              >
                <option value="">Select Supplier</option>
                <option
                  v-for="supplier in suppliers"
                  :key="supplier.id"
                  :value="supplier.id"
                >
                  {{ supplier.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Supplier Due Date</label>
              <input
                v-model="form.supplier_due_date"
                type="date"
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2"
                >GRN Date <span class="text-red-500">*</span></label
              >
              <input
                  type="date"
                  class="w-full px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-lg focus:outline-none cursor-not-allowed font-medium"
                  :value="form.goods_received_note_date"
                  readonly
                  disabled
                />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Batch Number</label>
              <input
                v-model="form.batch_number"
                type="text"
                readonly
                class="w-full px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-lg focus:outline-none cursor-not-allowed font-medium"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Discount</label>
              <div class="flex gap-2">
                <select
                  v-model="form.discount_type"
                  class="w-44 px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="percentage">Percentage (%)</option>
                  <option value="amount">Fixed Amount ({{ page.props.currency || '' }})</option>
                </select>
                <input
                  v-model.number="form.discount"
                  type="number"
                  step="0.01"
                  class="flex-1 px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2"
                >Tax Total({{ page.props.currency || '' }})</label
              >
              <input
                v-model.number="form.tax_total"
                type="number"
                step="0.01"
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div></div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
            <textarea
              v-model="form.remarks"
              rows="3"
              class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            ></textarea>
          </div>
        </div>

        <!-- PRODUCTS SECTION -->
        <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold text-blue-600 flex items-center gap-2">
              📦 Products
            </h3>
            <button
              type="button"
              @click="addProduct"
              class="px-4 py-2 text-xs font-medium text-white bg-green-600 rounded-[5px] hover:bg-green-700 transition-all duration-200"
            >
              + Add Product
            </button>
          </div>
          <div class="overflow-x-auto" data-tab-skip="true">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b-2 border-blue-600">
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Product</th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Unit</th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">
                    Requested Qty
                  </th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">
                    Issued Qty<span class="text-red-500">*</span>
                  </th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">
                    Purchase Price ({{ page.props.currency || "" }})<span class="text-red-500">*</span>
                  </th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Discount (%)</th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Expire Date</th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">
                    Total ({{ page.props.currency || "" }})
                  </th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                    Actions
                  </th>

                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="(product, index) in products"
                  :key="index"
                  class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200"
                >
                  <td class="px-4 py-4">
                    <select
                      v-model.number="product.product_id"
                      @change="onProductSelect(index)"
                      class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                      <option :value="null">Select Product</option>
                      <option
                        v-for="prod in availableProducts"
                        :key="prod.id"
                        :value="prod.id"
                        :disabled="isProductSelectedInOtherRows(prod.id, index)"
                      >
                        {{ prod.name }}
                      </option>
                    </select>
                  </td>

                  <td class="px-4 py-4">
                    {{ product.unit }}
                  </td>

                  <!-- <td class="px-4 py-4">
                    <select
                      v-model="product.measurement_unit_id"
                      class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" disabled
                    >
                      <option value="">Select Unit</option>
                      <option
                        v-for="unit in measurementUnits"
                        :key="unit.id"
                        :value="unit.id"
                      >
                        {{ unit.name }}
                      </option>
                    </select>
                  </td> -->

                  <td class="px-4 py-4">
                    <input
                      v-model.number="product.requested_quantity"
                      type="number"
                      step="0.01"
                      min="0.01"
                      @input="validateIssuedQty(product); calculateTotal(index)"
                      class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                  </td>

                  <td class="px-4 py-4">
                    <input
                      v-model.number="product.issued_quantity"
                      type="number"
                      min="1"
                      step="1"
                      @input="validateIssuedQty(product); calculateTotal(index)"
                      class="w-full px-3 py-2 text-sm border rounded-lg"
                      :class="product.error ? 'border-red-500' : 'border-gray-300'"
                    />

                    <!-- Error message -->
                    <p v-if="product.error" class="text-red-500 text-xs mt-1">
                      {{ product.error }}
                    </p>
                  </td>


                  <td class="px-4 py-4">
                    <input
                      v-model.number="product.purchase_price"
                      type="number"
                      step="0.01"
                      min="0.01"
                      required
                      @input="calculateTotal(index)"
                      class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                    />
                  </td>

                  <td class="px-4 py-4">
                    <div class="space-y-1">
                      <div class="relative">
                        <input
                          v-model.number="product.discount_percentage"
                          type="number"
                          step="0.01"
                          min="0"
                          max="100"
                          @input="calculateTotal(index)"
                          class="w-full pr-8 px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-500">%</span>
                      </div>
                      <p class="text-xs text-gray-500">
                        Amount: {{ formatNumber(product.discount) }} {{ page.props.currency || "" }}
                      </p>
                    </div>
                  </td>

                  <td class="px-4 py-4">
                    <input
                      v-model="product.expire_date"
                      type="date"
                      class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                  </td>

                  <td class="px-4 py-4">
                    <span class="font-semibold text-gray-900">
                      {{ formatNumber(product.total) }}
                    </span>
                  </td>

                  <td class="px-4 py-4 text-center">
                    <button
                      type="button"
                      @click="removeProduct(index)"
                      :disabled="products.length <= 1"
                      class="px-4 py-2 text-xs font-medium text-white bg-red-600 rounded-[5px] hover:bg-red-700 disabled:opacity-50 transition-all duration-200"
                    >
                      Remove
                    </button>
                  </td>


                </tr>

                <tr v-if="products.length === 0">
                  <td colspan="9" class="px-6 py-8 text-center text-gray-500 font-medium">
                    No products added yet. Click "Add Product" to start.
                  </td>
                </tr>
              </tbody>

              <tfoot
                v-if="products.length > 0"
                class="bg-gray-100 border-t-2 border-gray-300"
              >
                <tr class="border-b border-gray-300">
                  <td
                    colspan="6"
                    class="px-4 py-3 text-right font-semibold text-gray-900"
                  >
                    Subtotal:
                  </td>
                  <td class="px-4 py-3 font-bold text-gray-900">
                    {{ formatNumber(productsGrossTotal) }} ({{ page.props.currency || "" }})
                  </td>
                  <td></td>
                </tr>
                <tr class="border-b border-gray-300">
                  <td
                    colspan="6"
                    class="px-4 py-3 text-right font-semibold text-gray-900"
                  >
                    Discount ({{ form.discount_type === 'percentage' ? '%' : (page.props.currency || '') }}):
                  </td>
                  <td class="px-4 py-3 font-semibold text-red-600">
                    -{{ formatNumber(productsDiscountTotal + headerDiscountAmount) }} ({{ page.props.currency || "" }})
                  </td>
                  <td></td>
                </tr>
                <tr class="border-b border-gray-300">
                  <td
                    colspan="6"
                    class="px-4 py-3 text-right font-semibold text-gray-900"
                  >
                    Tax:
                  </td>
                  <td class="px-4 py-3 font-semibold text-green-600">
                    +{{ formatNumber(form.tax_total) }} ({{ page.props.currency || "" }})
                  </td>
                  <td></td>
                </tr>
                <tr class="bg-blue-50">
                  <td
                    colspan="6"
                    class="px-4 py-3 text-right font-bold text-lg text-gray-900"
                  >
                    Grand Total:
                  </td>
                  <td class="px-4 py-3 font-bold text-lg text-blue-600">
                    {{ formatNumber(grandTotal) }} ({{ page.props.currency || "" }})
                  </td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
          <button
            type="button"
            @click="close"
            class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-[5px] hover:bg-gray-50 transition-all duration-200"
          >
            Cancel
          </button>

          <button
            type="submit"
            :disabled="products.length === 0"
            class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-[5px] hover:bg-blue-700 disabled:opacity-50 transition-all duration-200"
          >
            Create GRN
          </button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, watch, nextTick } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
const page = usePage();

import { logActivity } from "@/composables/useActivityLog";

const props = defineProps({
  open: Boolean,
  suppliers: Array,
  measurementUnits: Array,
  availableProducts: Array,
  purchaseOrders: { type: Array, default: () => [] },
  grnNumber: {
    type: String,
    default: "",
  },
});

const emit = defineEmits(["update:open"]);

const selectedPoId = ref("");

const createEmptyProductRow = () => ({
  product_id: null,
  measurement_unit_id: "",
  batch_number: "",
  requested_quantity: 1,
  issued_quantity: 1,
  purchase_price: 0,
  discount_percentage: 0,
  discount: 0,
  expire_date: "",
  unit: "N/A",
  product_name: "",
  total: 0,
  error: null,
});

const form = ref({
  goods_received_note_no: props.grnNumber,
  supplier_id: "",
  supplier_due_date: "",
  goods_received_note_date: new Date().toISOString().split("T")[0],
  batch_number: "",
  discount: 0,
  discount_type: "amount",
  tax_total: 0,
  remarks: "",
  purchase_order_id: null,
});

const products = ref([createEmptyProductRow()]);

const roundToWhole = (value) => {
  const numericValue = Number(value) || 0;
  return Math.round(numericValue);
};

const productsGrossTotal = computed(() => {
  return products.value.reduce((sum, product) => {
    const qty = parseFloat(product.issued_quantity ?? product.requested_quantity) || 0;
    const price = parseFloat(product.purchase_price) || 0;
    return sum + qty * price;
  }, 0);
});

const productsDiscountTotal = computed(() => {
  return products.value.reduce((sum, product) => sum + (parseFloat(product.discount) || 0), 0);
});

const productsNetTotal = computed(() => {
  return roundToWhole(productsGrossTotal.value - productsDiscountTotal.value);
});

const headerDiscountAmount = computed(() => {
  return calculateEffectiveDiscount(productsNetTotal.value);
});

const grandTotal = computed(() => {
  const taxTotal = parseFloat(form.value.tax_total) || 0;
  return roundToWhole(productsNetTotal.value - headerDiscountAmount.value + taxTotal);
});

const calculateEffectiveDiscount = (productsTotal) => {
  const discountValue = parseFloat(form.value.discount) || 0;

  if (form.value.discount_type === "percentage") {
    return roundToWhole((productsTotal * discountValue) / 100);
  }

  return roundToWhole(discountValue);
};

// Generate auto batch number in format: BATCH-YYYYMMDD-XXXX
const generateBatchNumber = () => {
  const date = new Date();
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const dateStr = `${year}${month}${day}`;
  
  // Generate random 4-digit number
  const randomNum = String(Math.floor(Math.random() * 10000)).padStart(4, '0');
  
  return `BATCH-${dateStr}-${randomNum}`;
};

const close = () => {
  emit("update:open", false);
  resetForm();
};

const resetForm = () => {
  selectedPoId.value = "";
  form.value = {
    goods_received_note_no: props.grnNumber,
    supplier_id: "",
    supplier_due_date: "",
    goods_received_note_date: new Date().toISOString().split("T")[0],
    batch_number: generateBatchNumber(),
    discount: 0,
    discount_type: "amount",
    tax_total: 0,
    remarks: "",
    purchase_order_id: null,
  };
  products.value = [createEmptyProductRow()];
};

// Pre-fill GRN from a Purchase Order
const onPoSelect = () => {
  const poId = Number(selectedPoId.value);
  if (!poId) {
    form.value.purchase_order_id = null;
    form.value.supplier_id = "";
    products.value = [createEmptyProductRow()];
    return;
  }

  const po = props.purchaseOrders.find((p) => p.id === poId);
  if (!po) return;

  form.value.purchase_order_id = poId;
  form.value.supplier_id = po.supplier_id;

  // Pre-fill products from the PO line items
  if (po.products && po.products.length > 0) {
    products.value = po.products.map((item) => {
      const prod = props.availableProducts.find((p) => p.id === item.product_id);
      return {
        product_id: item.product_id,
        measurement_unit_id: item.measurement_unit_id ?? prod?.measurement_unit_id ?? null,
        requested_quantity: parseFloat(item.quantity) || 1,
        issued_quantity: parseFloat(item.quantity) || 1,
        purchase_price: parseFloat(item.purchase_price) || 0,
        discount_percentage: parseFloat(item.discount_percentage) || 0,
        discount: 0,
        unit:
          item.measurement_unit?.name ||
          prod?.purchaseUnit?.name ||
          prod?.measurement_unit?.name ||
          "N/A",
        product_name: item.product?.name || prod?.name || "",
        total: 0,
        error: null,
      };
    });
    // Recalculate totals for each pre-filled row
    products.value.forEach((_, idx) => calculateTotal(idx));
  }
};

const onProductSelect = (index) => {  const product = products.value[index];
  if (isProductSelectedInOtherRows(product.product_id, index)) {
    alert("This product is already added. Each product can be selected only once per GRN.");
    products.value[index] = createEmptyProductRow();
    return;
  }

  const selectedProduct = props.availableProducts.find(
    (p) => p.id === product.product_id
  );

  if (selectedProduct) {
    product.purchase_price =
      parseFloat(selectedProduct.purchase_price ?? selectedProduct.price) || 0;
    product.measurement_unit_id = selectedProduct.measurement_unit_id;
    product.batch_number = "";
    product.product_name = selectedProduct.name || "";
    product.unit =
      selectedProduct.purchaseUnit?.name ||
      selectedProduct.measurement_unit?.name ||
      (props.measurementUnits.find((u) => u.id === selectedProduct.measurement_unit_id)?.name || "N/A");

    if (!product.requested_quantity || Number(product.requested_quantity) <= 0) {
      product.requested_quantity = 1;
    }
    if (!product.issued_quantity || Number(product.issued_quantity) <= 0) {
      product.issued_quantity = 1;
    }

    calculateTotal(index);
  } else {
    products.value[index] = createEmptyProductRow();
  }
};

const addProduct = () => {
  products.value.push(createEmptyProductRow());
};

const removeProduct = (index) => {
  if (products.value.length <= 1) return;
  products.value.splice(index, 1);
};

const isProductSelectedInOtherRows = (productId, currentIndex) => {
  if (!productId) return false;

  return products.value.some(
    (row, rowIndex) => rowIndex !== currentIndex && Number(row.product_id) === Number(productId)
  );
};

const calculateTotal = (index) => {
  const p = products.value[index];
  // Use issued_quantity for GRN line totals (actual received amount), fallback to requested_quantity
  const qty = parseFloat(p.issued_quantity ?? p.requested_quantity) || 0;
  const price = parseFloat(p.purchase_price) || 0;
  const discountPercentage = Math.min(100, Math.max(0, parseFloat(p.discount_percentage) || 0));
  const discountAmount = roundToWhole((qty * price * discountPercentage) / 100);
  p.discount = discountAmount;

  p.total = roundToWhole(qty * price - discountAmount);
};

const formatNumber = (number) => {
  return roundToWhole(number).toLocaleString("en-US", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  });
};

const focusFirstElementInOpenDialog = () => {
  const openDialog = document.querySelector("dialog[open]");
  if (!(openDialog instanceof HTMLElement)) return;

  const selectors = [
    'input:not([type="hidden"]):not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    'button:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
    '[contenteditable="true"]',
  ].join(", ");

  const firstFocusable = Array.from(openDialog.querySelectorAll(selectors)).find(
    (element) => element instanceof HTMLElement && element.offsetParent !== null
  );

  if (firstFocusable instanceof HTMLElement) {
    firstFocusable.focus();
  }
};

// Reset form when modal opens
watch(
  () => props.open,
  (newVal) => {
    if (newVal) {
      resetForm();
      nextTick(() => {
        focusFirstElementInOpenDialog();
      });
    }
  }
);

const submitForm = () => {
  const validProducts = products.value.filter(
    (product) =>
      product.product_id &&
      Number(product.requested_quantity) > 0 &&
      Number(product.issued_quantity) > 0
  );

  if (validProducts.length === 0) {
    alert("Please add at least one product with quantity.");
    return;
  }

  const hasInvalidIssuedQty = validProducts.some(
    (product) => Number(product.issued_quantity) > Number(product.requested_quantity)
  );

  if (hasInvalidIssuedQty) {
    alert("Issued quantity cannot exceed requested quantity.");
    return;
  }

  const selectedProductIds = validProducts.map((product) => Number(product.product_id));
  if (new Set(selectedProductIds).size !== selectedProductIds.length) {
    alert("Duplicate products are not allowed. Please keep each product only once per GRN.");
    return;
  }

  const subtotal = validProducts.reduce(
    (sum, product) => sum + (parseFloat(product.total) || 0),
    0
  );

  const payload = {
    ...form.value,
    subtotal: roundToWhole(subtotal),
    products: validProducts.map((product) => ({
      ...product,
      discount_percentage: parseFloat(product.discount_percentage || 0),
      discount: parseFloat(product.discount || 0),
    })),
  };

  router.post(route("good-receive-notes.store"), payload, {
    onSuccess: async () => {
      // Log create activity
      await logActivity("create", "goods_received_notes", {
        grn_number: form.value.goods_received_note_no,
        grn_date: form.value.goods_received_note_date,
        supplier_id: form.value.supplier_id,
        products_count: products.value.length,
      });

      close();
    },
    onError: (e) => console.error("GRN create error:", e),
  });
};

const validateIssuedQty = (product) => {
  if (
    product.issued_quantity !== null &&
    product.requested_quantity !== null &&
    Number(product.issued_quantity) > Number(product.requested_quantity)
  ) {
    product.error = 'Issued quantity cannot exceed requested quantity';
    return false;
  }

  product.error = null;
  return true;
};

</script>
