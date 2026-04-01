<template>
  <Modal :show="open" @close="close" max-width="6xl">
    <div class="p-6 bg-gray-50">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
        <h2 class="text-2xl font-bold text-blue-600">✨ Create Purchase Order</h2>
        <button type="button" @click="close"
          class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-200 rounded-full transition-all duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form @submit.prevent="submitForm">
        <!-- PO Details -->
        <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
          <h3 class="mb-3 text-lg font-semibold text-blue-600 flex items-center gap-2">
            📋 Order Details
          </h3>
          <div class="grid grid-cols-2 gap-3 mb-4">
            <!-- PO Number -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                PO Number <span class="text-red-500">*</span>
              </label>
              <input v-model="form.order_number" type="text" readonly
                class="w-full px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-lg focus:outline-none cursor-not-allowed font-medium" />
            </div>

            <!-- Supplier -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Supplier <span class="text-red-500">*</span>
              </label>
              <select v-model="form.supplier_id" required
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Select Supplier</option>
                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
            </div>

            <!-- Order Date -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Order Date <span class="text-red-500">*</span>
              </label>
              <input :value="form.order_date" type="date" readonly disabled
                class="w-full px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-lg focus:outline-none cursor-not-allowed font-medium" />
            </div>

            <!-- Discount -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Discount</label>
              <div class="flex gap-2">
                <select v-model="form.discount_type"
                  class="w-44 px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <option value="percentage">Percentage (%)</option>
                  <option value="amount">Fixed Amount ({{ page.props.currency || '' }})</option>
                </select>
                <input v-model.number="form.discount" type="number" step="0.01" min="0"
                  class="flex-1 px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
              </div>
            </div>

            <!-- Tax -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Tax Total ({{ page.props.currency || '' }})
              </label>
              <input v-model.number="form.tax_total" type="number" step="0.01" min="0"
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>

          <!-- Remarks -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
            <textarea v-model="form.remarks" rows="2"
              class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
          </div>
        </div>

        <!-- Products -->
        <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold text-blue-600 flex items-center gap-2">
              📦 Products
            </h3>
            <button type="button" @click="addProduct"
              class="px-4 py-2 text-xs font-medium text-white bg-green-600 rounded-[5px] hover:bg-green-700 transition-all duration-200">
              + Add Product
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b-2 border-blue-600">
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Product</th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Unit</th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">
                    Qty <span class="text-red-500">*</span>
                  </th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">
                    Price ({{ page.props.currency || '' }}) <span class="text-red-500">*</span>
                  </th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Discount (%)</th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">
                    Total ({{ page.props.currency || '' }})
                  </th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(product, index) in products" :key="index"
                  class="border-b border-gray-200 hover:bg-gray-50">
                  <!-- Product select -->
                  <td class="px-4 py-4">
                    <select v-model.number="product.product_id" @change="onProductSelect(index)"
                      class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                      <option :value="null">Select Product</option>
                      <option v-for="prod in availableProducts" :key="prod.id" :value="prod.id"
                        :disabled="isProductSelectedElsewhere(prod.id, index)">
                        {{ prod.name }}
                      </option>
                    </select>
                  </td>

                  <!-- Unit display -->
                  <td class="px-4 py-4 text-sm text-gray-700">{{ product.unit }}</td>

                  <!-- Quantity -->
                  <td class="px-4 py-4">
                    <input v-model.number="product.quantity" type="number" step="0.01" min="0.01" required
                      @input="calculateTotal(index)"
                      class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                  </td>

                  <!-- Purchase price -->
                  <td class="px-4 py-4">
                    <input v-model.number="product.purchase_price" type="number" step="0.01" min="0" required
                      @input="calculateTotal(index)"
                      class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                  </td>

                  <!-- Discount % -->
                  <td class="px-4 py-4">
                    <div class="space-y-1">
                      <div class="relative">
                        <input v-model.number="product.discount_percentage" type="number" step="0.01" min="0"
                          max="100" @input="calculateTotal(index)"
                          class="w-full pr-8 px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-500">%</span>
                      </div>
                      <p class="text-xs text-gray-500">
                        Amount: {{ formatNumber(product.discount) }} {{ page.props.currency || '' }}
                      </p>
                    </div>
                  </td>

                  <!-- Line total -->
                  <td class="px-4 py-4 font-semibold text-gray-900">
                    {{ formatNumber(product.total) }}
                  </td>

                  <!-- Remove -->
                  <td class="px-4 py-4 text-center">
                    <button type="button" @click="removeProduct(index)" :disabled="products.length <= 1"
                      class="px-4 py-2 text-xs font-medium text-white bg-red-600 rounded-[5px] hover:bg-red-700 disabled:opacity-50 transition-all duration-200">
                      Remove
                    </button>
                  </td>
                </tr>

                <tr v-if="products.length === 0">
                  <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                    No products added yet. Click "+ Add Product" to start.
                  </td>
                </tr>
              </tbody>

              <!-- Totals footer -->
              <tfoot v-if="products.length > 0" class="bg-gray-100 border-t-2 border-gray-300">
                <tr class="border-b border-gray-300">
                  <td colspan="5" class="px-4 py-3 text-right font-semibold text-gray-900">Subtotal:</td>
                  <td class="px-4 py-3 font-bold text-gray-900">
                    {{ formatNumber(productsGrossTotal) }} ({{ page.props.currency || '' }})
                  </td>
                  <td></td>
                </tr>
                <tr class="border-b border-gray-300">
                  <td colspan="5" class="px-4 py-3 text-right font-semibold text-gray-900">
                    Discount ({{ form.discount_type === 'percentage' ? '%' : (page.props.currency || '') }}):
                  </td>
                  <td class="px-4 py-3 font-semibold text-red-600">
                    -{{ formatNumber(productsDiscountTotal + headerDiscountAmount) }} ({{ page.props.currency || '' }})
                  </td>
                  <td></td>
                </tr>
                <tr class="border-b border-gray-300">
                  <td colspan="5" class="px-4 py-3 text-right font-semibold text-gray-900">Tax:</td>
                  <td class="px-4 py-3 font-semibold text-green-600">
                    +{{ formatNumber(form.tax_total) }} ({{ page.props.currency || '' }})
                  </td>
                  <td></td>
                </tr>
                <tr class="bg-blue-50">
                  <td colspan="5" class="px-4 py-3 text-right font-bold text-lg text-gray-900">Grand Total:</td>
                  <td class="px-4 py-3 font-bold text-lg text-blue-600">
                    {{ formatNumber(grandTotal) }} ({{ page.props.currency || '' }})
                  </td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
          <button type="button" @click="close"
            class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-[5px] hover:bg-gray-50 transition-all duration-200">
            Cancel
          </button>
          <button type="submit" :disabled="products.length === 0 || isSubmitting"
            class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-[5px] hover:bg-blue-700 disabled:opacity-50 transition-all duration-200">
            {{ isSubmitting ? 'Creating...' : 'Create Purchase Order' }}
          </button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const page = usePage();

const props = defineProps({
  open: Boolean,
  suppliers: Array,
  availableProducts: Array,
  measurementUnits: Array,
  poNumber: { type: String, default: '' },
});

const emit = defineEmits(['update:open']);

const isSubmitting = ref(false);

const createEmptyRow = () => ({
  product_id: null,
  measurement_unit_id: null,
  quantity: 1,
  purchase_price: 0,
  discount_percentage: 0,
  discount: 0,
  unit: 'N/A',
  total: 0,
});

const form = ref({
  order_number: props.poNumber,
  order_date: new Date().toISOString().split('T')[0],
  supplier_id: '',
  discount: 0,
  discount_type: 'amount',
  tax_total: 0,
  remarks: '',
});

const products = ref([createEmptyRow()]);

// ── Computed totals ──────────────────────────────────────────────

const roundToWhole = (v) => Math.round(Number(v) || 0);

const productsGrossTotal = computed(() =>
  products.value.reduce((sum, p) => sum + (parseFloat(p.quantity) || 0) * (parseFloat(p.purchase_price) || 0), 0)
);

const productsDiscountTotal = computed(() =>
  products.value.reduce((sum, p) => sum + (parseFloat(p.discount) || 0), 0)
);

const productsNetTotal = computed(() =>
  roundToWhole(productsGrossTotal.value - productsDiscountTotal.value)
);

const headerDiscountAmount = computed(() => {
  const d = parseFloat(form.value.discount) || 0;
  return form.value.discount_type === 'percentage'
    ? roundToWhole((productsNetTotal.value * d) / 100)
    : roundToWhole(d);
});

const grandTotal = computed(() =>
  roundToWhole(productsNetTotal.value - headerDiscountAmount.value + (parseFloat(form.value.tax_total) || 0))
);

// ── Product row helpers ──────────────────────────────────────────

const onProductSelect = (index) => {
  const row = products.value[index];
  if (isProductSelectedElsewhere(row.product_id, index)) {
    alert('This product is already added. Each product can be added only once per PO.');
    products.value[index] = createEmptyRow();
    return;
  }
  const found = props.availableProducts.find((p) => p.id === row.product_id);
  if (found) {
    row.purchase_price = parseFloat(found.purchase_price ?? found.price) || 0;
    row.measurement_unit_id = found.measurement_unit_id ?? null;
    row.unit =
      found.purchaseUnit?.name ||
      found.measurement_unit?.name ||
      (props.measurementUnits?.find((u) => u.id === found.measurement_unit_id)?.name || 'N/A');
    calculateTotal(index);
  } else {
    products.value[index] = createEmptyRow();
  }
};

const addProduct = () => products.value.push(createEmptyRow());

const removeProduct = (index) => {
  if (products.value.length <= 1) return;
  products.value.splice(index, 1);
};

const isProductSelectedElsewhere = (productId, currentIndex) => {
  if (!productId) return false;
  return products.value.some((r, i) => i !== currentIndex && Number(r.product_id) === Number(productId));
};

const calculateTotal = (index) => {
  const p = products.value[index];
  const qty = parseFloat(p.quantity) || 0;
  const price = parseFloat(p.purchase_price) || 0;
  const discPct = Math.min(100, Math.max(0, parseFloat(p.discount_percentage) || 0));
  p.discount = roundToWhole((qty * price * discPct) / 100);
  p.total = roundToWhole(qty * price - p.discount);
};

// ── Form helpers ─────────────────────────────────────────────────

const close = () => {
  emit('update:open', false);
  resetForm();
};

const resetForm = () => {
  form.value = {
    order_number: props.poNumber,
    order_date: new Date().toISOString().split('T')[0],
    supplier_id: '',
    discount: 0,
    discount_type: 'amount',
    tax_total: 0,
    remarks: '',
  };
  products.value = [createEmptyRow()];
};

watch(
  () => props.open,
  (val) => {
    if (val) {
      resetForm();
      nextTick(() => {
        const first = document.querySelector('dialog[open] input:not([readonly]):not([disabled])');
        if (first) first.focus();
      });
    }
  }
);

const submitForm = () => {
  if (!form.value.supplier_id) {
    alert('Please select a supplier.');
    return;
  }

  const validProducts = products.value.filter((p) => p.product_id && Number(p.quantity) > 0);
  if (validProducts.length === 0) {
    alert('Please add at least one product with a quantity.');
    return;
  }

  const ids = validProducts.map((p) => Number(p.product_id));
  if (new Set(ids).size !== ids.length) {
    alert('Duplicate products found. Please keep each product only once per PO.');
    return;
  }

  isSubmitting.value = true;

  router.post(
    route('purchase-orders.store'),
    { ...form.value, products: validProducts },
    {
      onSuccess: () => close(),
      onError: (errors) => {
        console.error('PO create error:', errors);
        isSubmitting.value = false;
      },
      onFinish: () => { isSubmitting.value = false; },
    }
  );
};

const formatNumber = (n) => roundToWhole(n).toLocaleString('en-US');
</script>
