<template>
  <TransitionRoot appear :show="open" as="template">
    <Dialog as="div" @close="closeModal" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/25" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-full p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel
              class="w-full max-w-4xl p-6 overflow-hidden text-left align-middle transition-all transform bg-gray-50 shadow-2xl rounded-2xl max-h-[85vh] overflow-y-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]"
            >
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-blue-600">✨ Create Stock Transfer Return</h2>
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
            ></path>
          </svg>
        </button>
      </div>

      <form @submit.prevent="submitForm">
        <!-- Return Information -->
        <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
          <h3 class="mb-3 text-lg font-semibold text-blue-600 flex items-center gap-2">
            📋 Return Information
          </h3>
          <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Return Number</label
              >
              <input
                type="text"
                class="w-full px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :value="returnNo"
                readonly
              />
            </div>
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Return Date <span class="text-red-500">*</span>
              </label>
              <input
                type="date"
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :class="form.errors.return_date ? 'border-red-500' : 'border-gray-300'"
                v-model="form.return_date"
              />
              <div v-if="form.errors.return_date" class="mt-1 text-sm text-red-500">
                {{ form.errors.return_date }}
              </div>
            </div>

            <div class="md:col-span-2">
              <label class="block mb-2 text-sm font-medium text-gray-700">Reason</label>
              <textarea
                rows="3"
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                v-model="form.reason"
                placeholder="e.g., Damaged, Expired, Quality issue..."
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Products -->
        <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
          <h3 class="mb-3 text-lg font-semibold text-green-600 flex items-center gap-2">
            📦 Products
          </h3>
          <div
            v-for="(product, index) in form.products"
            :key="index"
            class="pb-4 mb-4 border-b border-gray-200 last:border-b-0"
          >
            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
              <!-- Product -->
              <div class="md:col-span-4">
                <label class="block mb-2 text-sm font-medium text-gray-700">
                  Product <span class="text-red-500">*</span>
                </label>
                <select
                  class="w-full px-3 py-2 text-sm text-gray-800 bg-white border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :class="
                    form.errors[`products.${index}.product_id`]
                      ? 'border-red-500'
                      : 'border-gray-300'
                  "
                  v-model="product.product_id"
                  @change="onProductSelect(index)"
                >
                  <option value="">Select Product</option>
                  <option v-for="prod in products" :key="prod.id" :value="prod.id">
                    {{ prod.name }} (Shop: {{ prod.shop_quantity_in_sales_unit }} {{ prod.sales_unit?.symbol || 'btl' }})
                  </option>
                </select>
                <div
                  v-if="form.errors[`products.${index}.product_id`]"
                  class="mt-1 text-sm text-red-500"
                >
                  {{ form.errors[`products.${index}.product_id`] }}
                </div>
              </div>

              <!-- Unit -->
              <div class="md:col-span-3">
                <label class="block mb-2 text-sm font-medium text-gray-700">
                  Unit <span class="text-red-500">*</span>
                </label>
                <select
                  class="w-full px-3 py-2 text-sm text-gray-800 bg-white border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :class="
                    form.errors[`products.${index}.measurement_unit_id`]
                      ? 'border-red-500'
                      : 'border-gray-300'
                  "
                  v-model="product.measurement_unit_id"
                  @change="onUnitChange(index)"
                >
                  <option value="">Select Unit</option>
                  <option
                    v-if="selectedProducts[index]?.sales_unit_id" 
                    :value="selectedProducts[index].sales_unit_id"
                  >
                    {{ selectedProducts[index]?.sales_unit?.name || 'Bottle' }}
                    {{ selectedProducts[index]?.sales_unit?.symbol ? '(' + selectedProducts[index].sales_unit.symbol + ')' : '' }}
                    
                  </option>
                  <option
                    v-if="selectedProducts[index]?.transfer_unit_id" 
                    :value="selectedProducts[index].transfer_unit_id"
                  >
                    {{ selectedProducts[index]?.transfer_unit?.name || 'Bundle' }}
                    {{ selectedProducts[index]?.transfer_unit?.symbol ? '(' + selectedProducts[index].transfer_unit.symbol + ')' : '' }}
                   
                  </option>
                  <option
                    v-if="selectedProducts[index]?.purchase_unit_id" 
                    :value="selectedProducts[index].purchase_unit_id"
                  >
                    {{ selectedProducts[index]?.purchase_unit?.name || 'Box' }}
                    {{ selectedProducts[index]?.purchase_unit?.symbol ? '(' + selectedProducts[index].purchase_unit.symbol + ')' : '' }}
                   
                  </option>
                </select>
                <div
                  v-if="form.errors[`products.${index}.measurement_unit_id`]"
                  class="mt-1 text-sm text-red-500"
                >
                  {{ form.errors[`products.${index}.measurement_unit_id`] }}
                </div>
              </div>

              <!-- Quantity -->
              </div>
              <div class="md:col-span-3">
                <label class="block mb-2 text-sm font-medium text-gray-700">
                  Quantity <span class="text-red-500">*</span>
                </label>
                <input
                  type="number"
                  min="1"
                  :max="calculateAvailableInSelectedUnit(index)"
                  step="1"
                  class="w-full px-3 py-2 text-sm text-gray-800 bg-white border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :class="
                    form.errors[`products.${index}.stock_transfer_quantity`]
                      ? 'border-red-500'
                      : 'border-gray-300'
                  "
                  v-model.number="product.stock_transfer_quantity"
                />
                <div
                  v-if="form.errors[`products.${index}.stock_transfer_quantity`]"
                  class="mt-1 text-sm text-red-500"
                >
                  {{ form.errors[`products.${index}.stock_transfer_quantity`] }}
                </div>
                <!-- Available Quantity Display and Breakdown After Return -->
                <div class="mt-1 flex flex-col gap-1">
                  <!-- Show available quantity in selected unit -->
                  <span v-if="form.products[index].measurement_unit_id" class="text-xs font-medium text-blue-700">
                    Available: {{ calculateAvailableInSelectedUnit(index) }} {{ getUnitSymbol(index) }}
                  </span>
                  
                  <!-- Show breakdown after return -->
                  <!--
                  <span class="text-xs font-medium text-green-700">
                    {{ (() => {
                      const product = selectedProducts[index];
                      const unitId = form.products[index].measurement_unit_id;
                      const returnQty = Number(form.products[index].stock_transfer_quantity) || 0;
                      if (!product || !unitId) return '';
                      
                      // Get conversion rates
                  
                      const salesPerBundle = Number(product.transfer_to_sales_rate) || 1; // bottles per bundle
                      const bundlesPerBox = Number(product.purchase_to_transfer_rate) || 1; // bundles per box
                      const salesPerBox = salesPerBundle * bundlesPerBox; // bottles per box
                      
                      // Current shop quantities
                      let shopQtySales = Number(product.shop_quantity_in_sales_unit) || 0;
                      
                      // Convert return quantity to bottles based on selected unit
                      let returnInBottles = 0;
                      if (unitId == product.sales_unit_id) {
                        returnInBottles = returnQty;
                      } else if (unitId == product.transfer_unit_id) {
                        returnInBottles = returnQty * salesPerBundle;
                      } else if (unitId == product.purchase_unit_id) {
                        returnInBottles = returnQty * salesPerBox;
                      }
                      
                      // New shop quantity after return
                      let newShopQty = shopQtySales - returnInBottles;
                      if (newShopQty < 0) newShopQty = 0;
                      
                      // Calculate breakdown for display
                      const boxes = Math.floor(newShopQty / salesPerBox);
                      const remAfterBox = newShopQty % salesPerBox;
                      const bundles = Math.floor(remAfterBox / salesPerBundle);
                      const loose = remAfterBox % salesPerBundle;
                      
                      // Return formatted breakdown
                      let result = `Shop: ${newShopQty} ${product.sales_unit?.symbol || 'btl'}`;
                      if (boxes > 0) result += `, Store (${product.purchase_unit?.symbol || 'Box'}): ${boxes}`;
                      if (bundles > 0) result += `, Loose (${product.transfer_unit?.symbol || 'Bnl'}): ${bundles}`;
                      if (loose > 0) result += `, + Loose (${product.sales_unit?.symbol || 'Btl'}): ${loose}`;
                      
                      <span class="text-xs font-medium text-green-700">
                        {{ (() => {
                          const product = selectedProducts[index];
                          const unitId = form.products[index].measurement_unit_id;
                          const returnQty = Number(form.products[index].stock_transfer_quantity) || 0;
                          if (!product || !unitId) return '';
                          // ...conversion logic...
                        })() }}
                      </span>
                      -->
               </div>
            </div>
          </div>            
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end gap-3 pt-4 mt-4 border-t border-gray-300">
          <button
            type="button"
            @click="closeModal"
            class="px-8 py-2.5 rounded-full font-semibold text-sm bg-gray-500 text-white hover:bg-gray-600 transition-all duration-200"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="px-8 py-2.5 rounded-full font-semibold text-sm bg-blue-600 text-white hover:bg-blue-700 transition-all duration-200 disabled:opacity-50"
            :disabled="form.processing"
          >
            <span v-if="form.processing"> ⏳ Processing... </span>
            <span v-else> ✨ Create Return </span>
          </button>
        </div>
      </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, watch, onUnmounted } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import axios from 'axios';
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
} from '@headlessui/vue';
import { logActivity } from "@/composables/useActivityLog";

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  products: {
    type: Array,
    required: true,
  },
  measurementUnits: {
    type: Array,
    default: () => [],
  },
  users: {
    type: Array,
    required: true,
  },
  returnNo: {
    type: String,
    required: true,
  },
  inline: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["update:open", "close"]);

// Prevent background scrolling when modal is open
watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "";
    }
  }
);

// Cleanup on unmount
onUnmounted(() => {
  document.body.style.overflow = "";
});

const form = useForm({
  return_no: "",
  return_date: new Date().toISOString().split("T")[0],
  reason: "",
  products: [
    {
      product_id: "",
      measurement_unit_id: "",
      stock_transfer_quantity: 1,
    },
  ],
});

const selectedProducts = ref({});
const productUnits = ref({});
const availableQuantities = ref({}); // Track available quantities by index

watch(
  () => props.open,
  (newVal) => {
    if (newVal) {
      form.return_no = props.returnNo;
      form.return_date = new Date().toISOString().split("T")[0];
      form.reason = "";
      form.products = [
        {
          product_id: "",
          measurement_unit_id: "",
          stock_transfer_quantity: 1,
        },
      ];
      selectedProducts.value = {};
      productUnits.value = {};
      availableQuantities.value = {};
      form.clearErrors();
    }
  }
);

// Helper function to calculate available quantity in selected unit
const calculateAvailableInSelectedUnit = (index) => {
  const product = selectedProducts.value[index];
  const unitId = form.products[index].measurement_unit_id;
  
  if (!product || !unitId) return 0;
  
  // Get conversion rates
  const salesPerBundle = Number(product.transfer_to_sales_rate) || 1; // bottles per bundle
  const bundlesPerBox = Number(product.purchase_to_transfer_rate) || 1; // bundles per box
  const salesPerBox = salesPerBundle * bundlesPerBox; // bottles per box
  
  // Current shop quantity in bottles
  const shopQtyBottles = Number(product.shop_quantity_in_sales_unit) || 0;
  
  // Convert to selected unit
  if (unitId == product.sales_unit_id) {
    // Bottle unit - direct conversion
    return shopQtyBottles;
  } else if (unitId == product.transfer_unit_id) {
    // Bundle unit - bottles ÷ bottles per bundle
    return salesPerBundle > 0 ? Math.floor(shopQtyBottles / salesPerBundle) : 0;
  } else if (unitId == product.purchase_unit_id) {
    // Box unit - bottles ÷ bottles per box
    return salesPerBox > 0 ? Math.floor(shopQtyBottles / salesPerBox) : 0;
  }
  
  return 0;
};

// Get unit symbol
const getUnitSymbol = (index) => {
  const product = selectedProducts.value[index];
  const unitId = form.products[index].measurement_unit_id;
  
  if (!product || !unitId) return '';
  
  if (unitId == product.sales_unit_id) {
    return product.sales_unit?.symbol || 'btl';
  } else if (unitId == product.transfer_unit_id) {
    return product.transfer_unit?.symbol || 'bnl';
  } else if (unitId == product.purchase_unit_id) {
    return product.purchase_unit?.symbol || 'box';
  }
  
  return '';
};

// Get unit name
const getUnitName = (index) => {
  const product = selectedProducts.value[index];
  const unitId = form.products[index].measurement_unit_id;
  
  if (!product || !unitId) return '';
  
  if (unitId == product.sales_unit_id) {
    return product.sales_unit?.name || 'Bottle';
  } else if (unitId == product.transfer_unit_id) {
    return product.transfer_unit?.name || 'Bundle';
  } else if (unitId == product.purchase_unit_id) {
    return product.purchase_unit?.name || 'Box';
  }
  
  return '';
};

// Helper function for dropdown display
const getAvailableInUnit = (unitId, product) => {
  if (!product || !unitId) return 0;
  
  const salesPerBundle = Number(product.transfer_to_sales_rate) || 1;
  const bundlesPerBox = Number(product.purchase_to_transfer_rate) || 1;
  const salesPerBox = salesPerBundle * bundlesPerBox;
  const shopQtyBottles = Number(product.shop_quantity_in_sales_unit) || 0;
  
  if (unitId == product.sales_unit_id) {
    return shopQtyBottles;
  } else if (unitId == product.transfer_unit_id) {
    return salesPerBundle > 0 ? Math.floor(shopQtyBottles / salesPerBundle) : 0;
  } else if (unitId == product.purchase_unit_id) {
    return salesPerBox > 0 ? Math.floor(shopQtyBottles / salesPerBox) : 0;
  }
  
  return 0;
};

const fetchAvailableQuantity = async (index) => {
  const productId = form.products[index].product_id;
  const unitId = form.products[index].measurement_unit_id;

  if (!productId || !unitId) {
    availableQuantities.value[index] = null;
    return;
  }

  try {
    const response = await axios.post(route('stock-transfer-returns.available-quantity'), {
      product_id: productId,
      measurement_unit_id: unitId
    });
    
    availableQuantities.value[index] = response.data.available_quantity;
  } catch (error) {
    console.error('Error fetching available quantity:', error);
    availableQuantities.value[index] = null;
  }
};

const onProductSelect = (index) => {
  const productId = form.products[index].product_id;
  const product = props.products.find((p) => p.id == productId);
  selectedProducts.value[index] = product;

  if (product) {
    // Auto-select sales unit by default
    form.products[index].measurement_unit_id = product.sales_unit_id;
    
    // Also set quantity to 1 by default
    form.products[index].stock_transfer_quantity = 1;
  }
};

const onUnitChange = (index) => {
  const selectedUnitId = form.products[index].measurement_unit_id;
  const product = selectedProducts.value[index];
  
  if (product) {
    // When unit changes, reset quantity to 1 or max available
    const available = calculateAvailableInSelectedUnit(index);
    form.products[index].stock_transfer_quantity = available > 0 ? 1 : 0;
  }
};

const addProduct = () => {
  form.products.push({
    product_id: "",
    measurement_unit_id: "",
    stock_transfer_quantity: 1,
  });
};

const removeProduct = (index) => {
  form.products.splice(index, 1);
  delete selectedProducts.value[index];
  delete productUnits.value[index];
  delete availableQuantities.value[index];
};

const closeModal = () => {
  if (props.inline) {
    emit("close");
  } else {
    emit("update:open", false);
  }
};

const submitForm = () => {
  form.return_no = props.returnNo;

  // Validate quantities before submission
  for (let i = 0; i < form.products.length; i++) {
    const product = selectedProducts.value[i];
    const unitId = form.products[i].measurement_unit_id;
    const returnQty = Number(form.products[i].stock_transfer_quantity) || 0;
    const available = calculateAvailableInSelectedUnit(i);
    
    if (returnQty > available) {
      alert(`Cannot return ${returnQty} units. Only ${available} available in selected unit.`);
      return;
    }
  }

  form.post(route("stock-transfer-returns.store"), {
    onSuccess: async () => {
      // Log create activity
      await logActivity("create", "stock_transfer_returns", {
        return_number: form.return_no,
        return_date: form.return_date,
        user_id: form.user_id,
        products_count: form.products.length,
      });

      if (props.inline) {
        router.reload();
      } else {
        closeModal();
        router.reload();
      }
    },
    onError: (errors) => {
      console.error("Form submission errors:", errors);
    },
  });
};
</script>