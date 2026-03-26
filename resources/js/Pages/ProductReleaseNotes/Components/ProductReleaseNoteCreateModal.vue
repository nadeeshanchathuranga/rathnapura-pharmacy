<template>
  <Modal :show="open" @close="close" max-width="5xl">
    <div class="p-6 bg-white">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-blue-700">✨ Create New PRN</h2>
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

      <form @submit.prevent="submitForm">
        <!-- PRN DETAILS -->
        <div class="bg-gray-50 rounded-xl p-5 mb-6 border border-gray-200">
          <h3 class="text-xl font-semibold text-gray-800 mb-4">PRN Details</h3>

          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-gray-700 font-medium mb-2"
                >Product Transfer Request <span class="text-red-500">*</span></label
              >
              <select
                v-model.number="form.ptr_id"
                @change="onPtrSelect"
                class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                required
              >
                <option :value="null">Select PTR</option>
                <option
                  v-for="ptr in filteredProductTransferRequests"
                  :key="ptr.id"
                  :value="ptr.id"
                >
                  {{ ptr.product_transfer_request_no }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-gray-700 font-medium mb-2">Release Date <span class="text-red-500">*</span></label>
              <input
                v-model="form.release_date"
                type="date"
                class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-not-allowed"
  disabled
                required
              />
            </div>

            <div>
              <label class="block text-gray-700 font-medium mb-2">Status</label>
              <select
                v-model.number="form.status"
                class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all cursor-not-allowed"
  disabled
              >
                <option :value="0">Pending</option>
                <option :value="1">Released</option>
              </select>
            </div>

            <div class="hidden">
              <label class="block text-gray-700 font-medium mb-2">User</label>
              <select
                v-model.number="form.user_id"
                class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
              >
                <option :value="null">Select User</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-gray-700 font-medium mb-2">Remark</label>
            <textarea
              v-model="form.remark"
              rows="3"
              class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
            ></textarea>
          </div>
        </div>

        <!-- PRODUCTS -->
        <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
          <div class="flex justify-between items-center mb-4 hidden">
            <h3 class="text-xl font-semibold text-gray-800">Products *</h3>
            <button
              type="button"
              @click="addProduct"
              class="px-4 py-2 text-xs font-medium text-white bg-green-600 rounded-[5px] hover:bg-green-700 transition-all duration-200"
            >
              + Add Product
            </button>
          </div>

          <div class="overflow-x-auto bg-white rounded-lg border border-gray-200">
            <table class="w-full text-sm border-collapse">
              <thead>
                <tr class="border-b-2 border-blue-600">
                  <th class="px-4 py-3 text-left text-blue-600 font-semibold text-sm">
                    Product
                  </th>
                  <th class="px-4 py-3 text-left text-blue-600 font-semibold text-sm">
                    Unit
                  </th>
                  <th class="px-4 py-3 text-right text-blue-600 font-semibold text-sm">
                    Qty
                  </th>
                  <th class="px-4 py-3 text-center text-blue-600 font-semibold text-sm">
                    Action
                  </th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="(product, index) in products"
                  :key="index"
                  class="border-b border-gray-200 hover:bg-gray-50 transition-colors"
                >
                  <td class="px-4 py-3">
                    <div
                      v-if="product.product_name && !product.isManual"
                      class="text-gray-800 font-medium"
                    >
                      {{ product.product_name }}
                    </div>
                    <select
                      v-else
                      v-model.number="product.product_id"
                      @change="onProductSelect(index)"
                      class="w-full px-3 py-2 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    >
                      <option :value="null">Select Product</option>
                      <option
                        v-for="prod in availableProducts"
                        :key="prod.id"
                        :value="prod.id"
                      >
                        {{ prod.name }}
                      </option>
                    </select>
                  </td>

                  <td class="px-4 py-3">
  <span v-if="product.unit && !product.isManual" class="text-gray-800 font-medium">
    {{ product.unit }}
  </span>
  <select
    v-else
    v-model.number="product.unit_id"
    @change="onUnitSelect(index)"
    class="w-full px-3 py-2 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
    :disabled="!product.product_id"
  >
    <option :value="null">Select Unit</option>
    <option
      v-for="unit in getProductUnits(product.product_id)"
      :key="unit.id"
      :value="unit.id"
    >
      {{ unit.name }}
    </option>
  </select>
</td>

                  <td class="px-4 py-3">
                    <input
                      v-model.number="product.qty"
                      type="number"
                      @input="calculateTotal(index)"
                      class="w-full px-3 py-2 bg-white text-gray-800 text-right border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    />
                  </td>

                  <td class="px-4 py-3 text-center">
                    <button
                      type="button"
                      @click="removeProduct(index)"
                      class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-[5px] hover:bg-red-700 transition-all duration-200"
                    >
                      Remove
                    </button>
                  </td>
                </tr>
                <tr v-if="products.length === 0">
                  <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                    No products added yet. Click "Add Product" to get started.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
          <button
            type="button"
            @click="close"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 hover:border-gray-400 transition-all duration-200"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 transition-all duration-200"
          >
          ✨ Create PRN
          </button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import { logActivity } from "@/composables/useActivityLog";
import axios from "axios";

const props = defineProps({
  open: Boolean,
  availableProducts: Array,
  productTransferRequests: Array,
  users: Array,
});

const emit = defineEmits(["update:open"]);

const form = ref({
  ptr_id: null,
  user_id: null,
  release_date: new Date().toISOString().split("T")[0],
  status: 0,
  remark: "",
});

const products = ref([]);
const page = usePage();

const close = () => {
  emit("update:open", false);
  resetForm();
};

const resetForm = () => {
  form.value = {
    ptr_id: null,
    user_id: null,
    release_date: new Date().toISOString().split("T")[0],
    status: 0,
    remark: "",
  };
  products.value = [];
};

// 🔥 Load PTR products when selected
const onPtrSelect = async () => {
  if (!form.value.ptr_id) {
    products.value = [];
    return;
  }

  try {
    const response = await axios.get(
      `/product-transfer-requests/${form.value.ptr_id}/details`
    );

    const ptrData = response.data;
    const sourceProducts =
      ptrData.productTransferRequestProducts || ptrData.ptrProducts || [];

    if (!Array.isArray(sourceProducts) || sourceProducts.length === 0) {
      products.value = [];
      return;
    }

    products.value = sourceProducts.map((item) => {
      const quantity = Number(item.qty ?? item.requested_quantity) || 0;
      const purchasePrice = Number(item.purchase_price ?? 0) || 0;
      const productData = getProductData(item.product_id);
      
      // Use the unit from the PTR, not default to purchase unit
      const ptrUnitId = item.unit_id || item.measurement_unit?.id;
      const unitId = ptrUnitId || productData?.purchase_unit_id || null;
      
      // Get conversion rates from item or product data
      const purchaseToTransferRate = item.purchase_to_transfer_rate || productData?.purchase_to_transfer_rate || 1;
      const transferToSalesRate = item.transfer_to_sales_rate || productData?.transfer_to_sales_rate || 1;
      
      // Calculate unit price based on selected unit
      let unitPrice = purchasePrice;
      const purchaseUnitId = item.purchase_unit?.id || productData?.purchase_unit_id;
      const transferUnitId = item.transfer_unit?.id || productData?.transfer_unit_id;
      const salesUnitId = item.sales_unit?.id || productData?.sales_unit_id;

      
      if (unitId === transferUnitId) {
        unitPrice = purchaseToTransferRate > 0 ? purchasePrice / purchaseToTransferRate : 0;
      } else if (unitId === salesUnitId) {
        unitPrice = (purchaseToTransferRate * transferToSalesRate) > 0 ? purchasePrice / (purchaseToTransferRate * transferToSalesRate) : 0;
      }
      
      return {
        product_id: item.product_id,
        product_name: item.name,
        qty: quantity,
        unit_price: unitPrice,
        unit: item.unit || "N/A",
        unit_id: unitId,
        purchase_price: purchasePrice,
        purchase_to_transfer_rate: purchaseToTransferRate,
        transfer_to_sales_rate: transferToSalesRate,
        // Add new attributes
        purchase_unit_id: purchaseUnitId,
        transfer_unit_id: transferUnitId,
        sales_unit_id: salesUnitId,
        purchase_unit: item.purchase_unit || productData?.purchase_unit || null,
        transfer_unit: item.transfer_unit || productData?.transfer_unit || null,
        sales_unit: item.sales_unit || productData?.sales_unit || null,
        total: quantity * unitPrice,
        isManual: false,
      };
    });
  } catch (error) {
    console.error("Failed to load PTR details:", error);
    products.value = [];
  }
};

// Add manual product
const addProduct = () => {
  products.value.push({
    product_id: null,
    product_name: "",
    qty: 1,
    unit_price: 0,
    unit: "",
    unit_id: null,
    purchase_price: 0,
    purchase_to_transfer_rate: 1,
    transfer_to_sales_rate: 1,
    // Add new attributes
    purchase_unit_id: null,
    transfer_unit_id: null,
    sales_unit_id: null,
    purchase_unit: null,
    transfer_unit: null,
    sales_unit: null,
    total: 0,
    isManual: true, // Flag to indicate this is manually added
  });
};

const removeProduct = (index) => products.value.splice(index, 1);

const onProductSelect = (index) => {
  const p = products.value[index];
  const prod = props.availableProducts.find((a) => a.id === p.product_id);

  if (prod) {
    p.product_name = prod.name;
    p.purchase_price = prod.purchase_price; // Store purchase price
    p.purchase_to_transfer_rate = prod.purchase_to_transfer_rate || 1;
    p.transfer_to_sales_rate = prod.transfer_to_sales_rate || 1;
    // Store unit IDs
    p.purchase_unit_id = prod.purchase_unit_id;
    p.transfer_unit_id = prod.transfer_unit_id;
    p.sales_unit_id = prod.sales_unit_id;
    p.purchase_unit = prod.purchase_unit;
    p.transfer_unit = prod.transfer_unit;
    p.sales_unit = prod.sales_unit;
    // Set default unit to purchase unit
    p.unit_id = prod.purchase_unit_id;
    p.unit = prod.purchase_unit?.name || "N/A";
    // Purchase unit price is same as purchase price
    p.unit_price = prod.purchase_price || 0;
    calculateTotal(index);
  }
};

const calculateUnitPrice = (index) => {
  const p = products.value[index];
  const basePurchasePrice = Number(p.purchase_price) || 0;
  const purchaseToTransferRate = Number(p.purchase_to_transfer_rate) || 1;
  const transferToSalesRate = Number(p.transfer_to_sales_rate) || 1;
  
  if (p.unit_id === p.purchase_unit_id) {
    // Purchase Unit: Use purchase price directly
    p.unit_price = basePurchasePrice;
  } else if (p.unit_id === p.transfer_unit_id) {
    // Transfer Unit: purchase_price / purchase_to_transfer_rate
    p.unit_price = purchaseToTransferRate > 0 ? basePurchasePrice / purchaseToTransferRate : 0;
  } else if (p.unit_id === p.sales_unit_id) {
    // Sales Unit: purchase_price / (purchase_to_transfer_rate * transfer_to_sales_rate)
    const totalRate = purchaseToTransferRate * transferToSalesRate;
    p.unit_price = totalRate > 0 ? basePurchasePrice / totalRate : 0;
  }
};

const onUnitSelect = (index) => {
  const p = products.value[index];
  
  if (p.unit_id) {
    // Find the selected unit and update unit name
    const units = getProductUnits(p.product_id);
    const selectedUnit = units.find((u) => u.id === p.unit_id);
    
    if (selectedUnit) {
      p.unit = selectedUnit.name;
    }
    
    // Calculate unit price based on selected unit
    const basePurchasePrice = Number(p.purchase_price) || 0;
    const purchaseToTransferRate = Number(p.purchase_to_transfer_rate) || 1;
    const transferToSalesRate = Number(p.transfer_to_sales_rate) || 1;
    
    if (p.unit_id === p.purchase_unit_id) {
      // Purchase Unit: Use purchase price directly
      p.unit_price = basePurchasePrice;
    } else if (p.unit_id === p.transfer_unit_id) {
      // Transfer Unit: purchase_price / purchase_to_transfer_rate
      p.unit_price = purchaseToTransferRate > 0 ? basePurchasePrice / purchaseToTransferRate : 0;
    } else if (p.unit_id === p.sales_unit_id) {
      // Sales Unit: purchase_price / (purchase_to_transfer_rate * transfer_to_sales_rate)
      const totalRate = purchaseToTransferRate * transferToSalesRate;
      p.unit_price = totalRate > 0 ? basePurchasePrice / totalRate : 0;
    }
    
    // Recalculate total with new unit price
    calculateTotal(index);
  }
};

const calculateTotal = (index) => {
  const p = products.value[index];
  p.total = p.qty * p.unit_price;
};

const getProductData = (productId) => {
  if (!productId || !props.availableProducts) return null;
  const prod = props.availableProducts.find((p) => p.id === productId);
  return prod || null;
};

const getProductUnits = (productId) => {
  const prod = getProductData(productId);
  if (!prod) return [];
  
  const units = [];
  
  // Add purchase unit
  if (prod.purchase_unit) {
    units.push({
      id: prod.purchase_unit.id,
      name: prod.purchase_unit.name,
    });
  }
  
  // Add transfer unit (if different from purchase unit)
  if (prod.transfer_unit && prod.transfer_unit.id !== prod.purchase_unit?.id) {
    units.push({
      id: prod.transfer_unit.id,
      name: prod.transfer_unit.name,
    });
  }
  
  // Add sales unit (if different from purchase and transfer units)
  if (prod.sales_unit && prod.sales_unit.id !== prod.purchase_unit?.id && prod.sales_unit.id !== prod.transfer_unit?.id) {
    units.push({
      id: prod.sales_unit.id,
      name: prod.sales_unit.name,
    });
  }
  
  return units;
};

// Filter to show only approved Product Transfer Requests
const filteredProductTransferRequests = computed(() => {
  const list = props.productTransferRequests || [];
  return list.filter((ptr) => {
    const status = (ptr.status || "").toString().toLowerCase();
    return status === "approved";
  });
});


const getAvailableUnits = (product) => {
  if (!product.product_id) return [];
  
  // First try to get units from the product object itself (loaded from PTR)
  const units = [];
  if (product.purchase_unit) units.push(product.purchase_unit);
  if (product.transfer_unit && product.transfer_unit.id !== product.purchase_unit?.id) {
    units.push(product.transfer_unit);
  }
  if (product.sales_unit && 
      product.sales_unit.id !== product.purchase_unit?.id && 
      product.sales_unit.id !== product.transfer_unit?.id) {
    units.push(product.sales_unit);
  }
  
  // If units found, return them
  if (units.length > 0) return units;
  
  // Otherwise, fallback to availableProducts
  if (!props.availableProducts) return [];
  const prod = props.availableProducts.find(p => p.id === product.product_id);
  if (!prod) return [];
  
  if (prod.purchase_unit) units.push(prod.purchase_unit);
  if (prod.transfer_unit && prod.transfer_unit.id !== prod.purchase_unit?.id) {
    units.push(prod.transfer_unit);
  }
  if (prod.sales_unit && 
      prod.sales_unit.id !== prod.purchase_unit?.id && 
      prod.sales_unit.id !== prod.transfer_unit?.id) {
    units.push(prod.sales_unit);
  }
  
  return units;
};

const submitForm = () => {
  // Validation checks
  if (!form.value.ptr_id) {
    alert("Please select a Product Transfer Request");
    return;
  }

  if (products.value.length === 0) {
    alert("Please add at least one product");
    return;
  }

  const mappedProducts = products.value.map((p) => ({
    product_id: p.product_id,
    quantity: p.qty,
    unit_price: p.unit_price,
    total: p.total,
    unit_id: p.unit_id,
  }));

  router.post(
    route("product-release-notes.store"),
    {
      product_transfer_request_id: form.value.ptr_id,
      user_id: form.value.user_id,
      release_date: form.value.release_date,
      status: form.value.status,
      remark: form.value.remark,
      products: mappedProducts,
    },
    {
      onSuccess: async () => {
        // Log create activity
        await logActivity("create", "product_release_notes", {
          ptr_id: form.value.ptr_id,
          user_id: form.value.user_id,
          release_date: form.value.release_date,
          products_count: mappedProducts.length,
        });

        close();
        // Return to the PRN list
        router.visit(route("product-release-notes.index"));
      },
      onError: (errors) => {
        console.error("PRN creation failed:", errors);
        alert("Failed to create PRN. Check console for details.");
      },
    }
  );
};
</script>
