<template>
  <Modal :show="open" @close="closeModal" max-width="6xl">
    <div class="p-6 bg-gray-50">
      <div
        ref="modalContainer"
        @wheel="handleWheel"
        @touchmove.stop
        class="relative w-full max-h-[80vh] overflow-y-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]"
      >
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-blue-600">Edit Product</h2>
          <button
            @click="closeModal"
            class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-200 rounded-full transition-all duration-200"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <form @submit.prevent="handleSubmit">
          <!-- Basic Information Section -->
          <div class="mb-6">
            <h3 class="mb-4 text-lg font-semibold text-blue-600 flex items-center gap-2">
              📋 Basic Information
            </h3>
            <div class="p-4 bg-white rounded-xl shadow-sm border border-gray-200">
              <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <!-- Product Name -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Product Name <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.name"
                    type="text"
                    required
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter product name"
                  />
                  <span v-if="errors.name" class="text-sm text-red-500">{{
                    errors.name
                  }}</span>
                </div>

                <!-- Barcode -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700"
                    >Barcode</label
                  >
                  <input
                    v-model="form.barcode"
                    type="text"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter or scan barcode"
                  />
                  <span v-if="errors.barcode" class="text-sm text-red-500">{{
                    errors.barcode
                  }}</span>
                </div>

                <!-- Brand -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700"
                    >Brand</label
                  >
                  <select
                    v-model="form.brand_id"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option value="">Select Brand</option>
                    <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                      {{ brand.name }}
                    </option>
                  </select>
                </div>

                <!-- Category -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700"
                    >Category</label
                  >
                  <select
                    v-model="form.category_id"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option value="">Select Category</option>
                    <option
                      v-for="category in categories"
                      :key="category.id"
                      :value="category.id"
                    >
                      {{
                        category.hierarchy_string
                          ? category.hierarchy_string + " → " + category.name
                          : category.name
                      }}
                    </option>
                  </select>
                </div>

                <!-- Type -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">Type</label>
                  <select
                    v-model="form.type_id"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option value="">Select Type</option>
                    <option v-for="type in types" :key="type.id" :value="type.id">
                      {{ type.name }}
                    </option>
                  </select>
                </div>

                <!-- Status -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700"
                    >Status</label
                  >
                  <select
                    v-model="form.status"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Pricing Section -->
          <div class="mb-6">
            <h3 class="mb-4 text-lg font-semibold text-green-600 flex items-center gap-2">
              💰 Pricing Information ({{ page.props.currency || "" }})
            </h3>
            <div class="p-4 bg-white rounded-xl shadow-sm border border-gray-200">
              <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <!-- Purchase Price -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700"
                    >Purchase Price </label
                  >
                  <input
                    v-model.number="form.purchase_price"
                    type="number"
                    step="0.01"
                    required
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="0.00"
                    readonly
                  />
                </div>

                <!-- Wholesale Price -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700"
                    >Wholesale Price</label
                  >
                  <input
                    v-model.number="form.wholesale_price"
                    type="number"
                    step="0.01"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="0.00"
                  />
                </div>

                <!-- Retail Price -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Retail Price
                  </label>
                  <input
                    v-model.number="form.retail_price"
                    type="number"
                    step="0.01"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="0.00"
                  />
                  <span v-if="errors.retail_price" class="text-sm text-red-500">{{
                    errors.retail_price
                  }}</span>
                </div>

                 <!-- Tax -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">Tax</label>
                  <select
                    v-model="form.tax_id"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option value="">No Tax</option>
                    <option v-for="tax in taxes" :key="tax.id" :value="tax.id">
                      {{ tax.name }} - {{ tax.percentage }}%
                    </option>
                  </select>
                </div>

                <!-- Discount -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700"
                    >Discount Type</label
                  >
                  <select
                    v-model="form.discount_id"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option value="">No Discount</option>
                    <option
                      v-for="discount in discounts"
                      :key="discount.id"
                      :value="discount.id"
                    >
                      <!-- {{ discount.name }}
                      {{ discount.value }}
                      {{ discount.type === 0 ? "%" : currencySymbol || page.props.currency || "" }} -->
                          {{ discount.name }} -
                    {{ discount.value }} {{ discount.type === 0 ? '%' : (page.props.currency || '') }}
                    </option>
                  </select>
                </div>


              </div>
            </div>
          </div>

          <!-- Inventory Section -->
          <div class="mb-6">
            <h3
              class="mb-4 text-lg font-semibold text-orange-600 flex items-center gap-2"
            >
              📦 Inventory & Units
            </h3>
            <div class="p-4 bg-white rounded-xl shadow-sm border border-gray-200">
              <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <!-- Purchase Unit -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700"
                    >Purchase Unit</label
                  >
                  <select
                    v-model="form.purchase_unit_id"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                </div>

                <!-- Transfer Unit -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700"
                    >Transfer Unit</label
                  >
                  <select
                    v-model="form.transfer_unit_id"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                </div>
                <!-- Sales Unit -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700"
                    >Sales Unit</label
                  >
                  <select
                    v-model="form.sales_unit_id"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                </div>

                <!-- Storage Stock Quantity (now: Store Quantity) -->
                <!-- <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Store Quantity
                    <span v-if="form.purchase_unit_id" class="text-blue-600">
                      ({{ getPurchaseUnitName(form.purchase_unit_id) }})
                    </span>
                  </label>
                  <input
                    v-model.number="form.store_quantity_in_purchase_unit"
                    type="number"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="0"
                    readonly
                  />
                  <span class="text-xs text-gray-500">Reserved stock in store (purchase units)</span>
                </div> -->

                <!-- Store Low Stock Alert -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700"
                    >Store Low Stock Alert
                    <span v-if="form.purchase_unit_id" class="text-blue-600">
                      ({{ getPurchaseUnitName(form.purchase_unit_id) }})
                    </span>
                  </label>
                  <input
                    v-model.number="form.store_low_stock_margin"
                    type="number"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="0"
                  />
                </div>

                <div></div>

                <!-- Shop Quantity -->
                <!-- <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700"
                    >Shop Quantity
                    <span v-if="form.sales_unit_id" class="text-blue-600">
                      ({{ getSalesUnitName(form.sales_unit_id) }})
                    </span>
                  </label>
                  <input
                    v-model.number="form.shop_quantity"
                    type="number"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="0"
                    readonly
                  />
                </div> -->

                <!-- Shop Low Stock Alert -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700"
                    >Shop Low Stock Alert
                    <span v-if="form.sales_unit_id" class="text-blue-600">
                      ({{ getSalesUnitName(form.sales_unit_id) }})
                    </span>
                  </label>
                  <input
                    v-model.number="form.shop_low_stock_margin"
                    type="number"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="0"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Conversion Rates Section -->
          <div class="mb-6">
            <h3
              class="mb-4 text-lg font-semibold text-purple-600 flex items-center gap-2"
            >
              🔄 Unit Conversion Rates
            </h3>
            <div class="p-4 bg-white rounded-xl shadow-sm border border-gray-200">
              <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <!-- Purchase to Transfer Rate -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Purchase → Transfer Rate
                    <span
                      v-if="form.purchase_unit_id && form.transfer_unit_id"
                      class="text-blue-600"
                    >
                      (1 {{ getPurchaseUnitName(form.purchase_unit_id) }} = ?
                      {{ getTransferUnitName(form.transfer_unit_id) }})
                    </span>
                  </label>
                  <input
                    v-model.number="form.purchase_to_transfer_rate"
                    type="number"
                    step="0.01"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="1.00"
                  />
                  <span class="text-xs text-gray-500"
                    >How many transfer units in one purchase unit</span
                  >
                </div>

                <!-- Transfer to Sales Rate -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Transfer → Sales Rate
                    <span
                      v-if="form.transfer_unit_id && form.sales_unit_id"
                      class="text-blue-600"
                    >
                      (1 {{ getTransferUnitName(form.transfer_unit_id) }} = ?
                      {{ getSalesUnitName(form.sales_unit_id) }})
                    </span>
                  </label>
                  <input
                    v-model.number="form.transfer_to_sales_rate"
                    type="number"
                    step="0.01"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="1.00"
                  />
                  <span class="text-xs text-gray-500"
                    >How many sales units in one transfer unit</span
                  >
                </div>
              </div>
            </div>
          </div>

          <!-- Additional Options Section -->
          <div class="mb-6">
            <h3
              class="mb-4 text-lg font-semibold text-indigo-600 flex items-center gap-2"
            >
              ⚙️ Additional Options
            </h3>
            <div class="p-4 bg-white rounded-xl shadow-sm border border-gray-200">
              <div class="space-y-4">
                <!-- Return Product Checkbox -->
                <div
                  class="flex items-center p-3 bg-white rounded-lg border border-gray-200"
                >
                  <input
                    v-model="form.return_product"
                    type="checkbox"
                    id="return-product-edit"
                    class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500"
                  />
                  <label
                    for="return-product-edit"
                    class="ml-3 text-sm font-medium text-gray-700"
                  >
                    Allow Product Returns
                  </label>
                </div>
                <!-- Current Image Display -->
                <div
                  v-if="product?.image"
                  class="p-3 bg-white rounded-lg border border-gray-200"
                >
                  <p class="mb-2 text-sm font-medium text-gray-700">Current Image:</p>
                  <img
                    :src="`/storage/${product.image}`"
                    :alt="product.name"
                    class="h-24 rounded-lg shadow-sm"
                  />
                </div>

                <!-- Image Upload -->
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    {{ product?.image ? "Replace Product Image" : "Product Image" }}
                  </label>
                  <input
                    @input="form.image = $event.target.files[0]"
                    type="file"
                    accept="image/*"
                    class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700"
                  />
                  <span v-if="errors.image" class="text-sm text-red-500">{{
                    errors.image
                  }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end gap-3 pt-4 mt-4 border-t border-gray-300">
            <button
              type="button"
              @click="closeModal"
              class="px-8 py-2.5 rounded-[5px] font-semibold text-sm bg-gradient-to-r from-gray-500 to-gray-600 text-white hover:from-gray-600 hover:to-gray-700 hover:shadow-sm transition-all duration-200"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="processing"
              class="px-8 py-2.5 rounded-[5px] font-semibold text-sm bg-gradient-to-r from-blue-600 to-blue-700 text-white hover:from-blue-700 hover:to-blue-800 hover:shadow-sm transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ processing ? "Updating..." : "Update Product" }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, watch, onUnmounted, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";

const page = usePage();

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  product: {
    type: Object,
    required: true,
  },
  brands: {
    type: Array,
    required: true,
  },
  categories: {
    type: Array,
    required: true,
  },
  types: {
    type: Array,
    required: true,
  },
  measurementUnits: {
    type: Array,
    required: true,
  },
  suppliers: {
    type: Array,
    required: true,
  },
  customers: {
    type: Array,
    required: true,
  },
  discounts: {
    type: Array,
    required: true,
  },
  taxes: {
    type: Array,
    required: true,
  },
  currencySymbol: {
    type: String,
    default: "",
  },
});

const emit = defineEmits(["update:open"]);

const form = ref({
  name: "",
  barcode: "",
  brand_id: "",
  category_id: "",
  type_id: "",
  discount_id: "",
  tax_id: "",
  shop_quantity_in_sales_unit: 0,
  store_quantity_in_purchase_unit: 0,
  low_stock_margin: 0,
  store_low_stock_margin: 0,
  shop_low_stock_margin: 0,
  purchase_price: "",
  wholesale_price: "",
  retail_price: "",
  return_product: false,
  purchase_unit_id: "",
  sales_unit_id: "",
  transfer_unit_id: "",
  purchase_to_transfer_rate: "",
  transfer_to_sales_rate: "",
  status: 1,
  image: null,
});

const errors = ref({});
const processing = ref(false);
const modalContainer = ref(null);

const isPriceLocked = computed(() => {
  return !!form.value.discount_id || !!form.value.tax_id;
});

// Handle wheel event to prevent background scroll
const handleWheel = (e) => {
  const container = modalContainer.value;
  if (!container) return;

  const scrollTop = container.scrollTop;
  const scrollHeight = container.scrollHeight;
  const clientHeight = container.clientHeight;
  const wheelDelta = e.deltaY;

  // If scrolling up and already at top, prevent default
  if (wheelDelta < 0 && scrollTop === 0) {
    e.preventDefault();
    return;
  }

  // If scrolling down and already at bottom, prevent default
  if (wheelDelta > 0 && scrollTop + clientHeight >= scrollHeight) {
    e.preventDefault();
    return;
  }

  // Otherwise stop propagation to prevent background scroll
  e.stopPropagation();
};

// Helper functions to get unit names
const getPurchaseUnitName = (unitId) => {
  if (unitId === null || unitId === undefined || unitId === "") return "";
  const unit = props.measurementUnits.find((u) => String(u.id) === String(unitId));
  return unit ? unit.name : "";
};

const getSalesUnitName = (unitId) => {
  if (unitId === null || unitId === undefined || unitId === "") return "";
  const unit = props.measurementUnits.find((u) => String(u.id) === String(unitId));
  return unit ? unit.name : "";
};

const getTransferUnitName = (unitId) => {
  if (unitId === null || unitId === undefined || unitId === "") return "";
  const unit = props.measurementUnits.find((u) => String(u.id) === String(unitId));
  return unit ? unit.name : "";
};

// Watch for modal open state to control body scroll
const stopWatcher = watch(
  () => props.open,
  (newVal) => {
    if (newVal) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "";
    }
  },
  { immediate: true }
);

// Cleanup on component unmount
onUnmounted(() => {
  document.body.style.overflow = "";
  if (stopWatcher) stopWatcher();
});

// Watch for product changes and populate form
watch(
  () => [props.open, props.product],
  ([isOpen, product]) => {
    if (isOpen && product) {
      form.value = {
        name: product.name || "",
        barcode: product.barcode || "",
        brand_id: product.brand_id || "",
        category_id: product.category_id || "",
        type_id: product.type_id || "",
        discount_id: product.discount_id || "",
        tax_id: product.tax_id || "",
        shop_quantity_in_sales_unit: product.shop_quantity_in_sales_unit || 0,
        store_quantity_in_purchase_unit: product.store_quantity_in_purchase_unit || 0,
        low_stock_margin: product.low_stock_margin || 0,
        store_low_stock_margin: product.store_low_stock_margin || 0,
        shop_low_stock_margin: product.shop_low_stock_margin || 0,
        purchase_price: product.purchase_price || "",
        wholesale_price: product.wholesale_price || "",
        retail_price: product.retail_price || "",
        return_product: product.return_product || false,
        purchase_unit_id: product.purchase_unit_id || "",
        sales_unit_id: product.sales_unit_id || "",
        transfer_unit_id: product.transfer_unit_id || "",
        purchase_to_transfer_rate: product.purchase_to_transfer_rate || "",
        transfer_to_sales_rate: product.transfer_to_sales_rate || "",
        status: product.status ?? 1,
        image: null,
      };
      errors.value = {};
    }
  },
  { immediate: true }
);

// const selectedDiscount = computed(() => {
//   if (!form.value.discount_id) return null;
//   return props.discounts.find((d) => d.id == form.value.discount_id) || null;
// });

// const selectedTax = computed(() => {
//   if (!form.value.tax_id) return null;
//   return props.taxes.find((t) => t.id == form.value.tax_id) || null;
// });

// const originalWholesalePrice = ref(null);
// const originalRetailPrice = ref(null);

// watch(() => form.value.wholesale_price, (newVal) => {
//     if (!form.value.discount_id && !form.value.tax_id) {
//         originalWholesalePrice.value = parseFloat(newVal) || 0;
//     }
// });

// watch(() => form.value.retail_price, (newVal) => {
//     if (!form.value.discount_id && !form.value.tax_id) {
//         originalRetailPrice.value = parseFloat(newVal) || 0;
//     }
// });

// watch(() => form.value.discount_id, (newVal) => {
//   calculatePrices();
// });

// watch(() => form.value.tax_id, (newVal) => {
//   calculatePrices();
// });

//const calculatePrices = () => {
  // const discount = selectedDiscount.value;
  // const tax = selectedTax.value;

  // if (originalWholesalePrice.value === null && form.value.wholesale_price) {
  //   originalWholesalePrice.value = parseFloat(form.value.wholesale_price) || 0;
  // }
  // if (originalRetailPrice.value === null && form.value.retail_price) {
  //   originalRetailPrice.value = parseFloat(form.value.retail_price) || 0;
  // }

  // let wholesale = originalWholesalePrice.value || 0;
  // let retail = originalRetailPrice.value || 0;

  // if (discount) {
  //   if (discount.type === 0) { // Percentage
  //     wholesale -= wholesale * (discount.value / 100);
  //     retail -= retail * (discount.value / 100);
  //   } else { // Fixed amount
  //     wholesale -= discount.value;
  //     retail -= discount.value;
  //   }
  // }

  // if (tax) {
  //   wholesale += wholesale * (tax.percentage / 100);
  //   retail += retail * (tax.percentage / 100);
  // }

  // if (discount || tax) {
  //     form.value.wholesale_price = wholesale > 0 ? wholesale.toFixed(2) : "0.00";
  //     form.value.retail_price = retail > 0 ? retail.toFixed(2) : "0.00";
  // } else {
  //     if (originalWholesalePrice.value !== null) {
  //         form.value.wholesale_price = originalWholesalePrice.value.toFixed(2);
  //     }
  //     if (originalRetailPrice.value !== null) {
  //         form.value.retail_price = originalRetailPrice.value.toFixed(2);
  //     }
  // }
//};

const closeModal = () => {
  emit("update:open", false);
  errors.value = {};
  processing.value = false;
};

const handleSubmit = () => {
  processing.value = true;
  errors.value = {};

  router.post(
    route("products.update", props.product.id),
    {
      ...form.value,
      _method: "PUT",
    },
    {
      preserveScroll: true,
      forceFormData: true,
      onSuccess: () => {
        closeModal();
        processing.value = false;
      },
      onError: (err) => {
        errors.value = err;
        processing.value = false;
      },
    }
  );
};

/**
 * Fetch purchase price from goods_received_notes_products table
 * based on product_id and batch_number
 */
const fetchPurchasePriceByBatch = async (productId, batchNumber) => {
  if (!productId || !batchNumber) {
    form.value.purchase_price = 0;
    return;
  }

  try {
    const response = await fetch('/products/pricing-by-batch', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
      },
      body: JSON.stringify({
        product_id: productId,
        batch_number: batchNumber,
      }),
    });

    if (response.ok) {
      const data = await response.json();
      if (data.success && data.purchase_price) {
        form.value.purchase_price = parseFloat(data.purchase_price).toFixed(2);
      } else {
        form.value.purchase_price = 0;
      }
    } else {
      form.value.purchase_price = 0;
    }
  } catch (error) {
    console.error('Error fetching purchase price:', error);
    form.value.purchase_price = 0;
  }
};

// Watch for modal open and fetch purchase price if product has batch
const watchOpen = watch(() => props.open, (newVal) => {
  if (newVal && props.product && props.product.current_batch) {
    fetchPurchasePriceByBatch(props.product.id, props.product.current_batch.batch_number);
  }
});
</script>
