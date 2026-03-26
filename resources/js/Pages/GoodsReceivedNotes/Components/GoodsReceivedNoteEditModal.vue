<template>
  <div v-if="open" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-900 rounded-lg p-6 w-full max-w-4xl max-h-screen overflow-y-auto">
      
      <h2 class="text-2xl font-bold text-white mb-4">Edit GRN</h2>

      <form @submit.prevent="submitForm">

        <!-- GRN DETAILS -->
        <div class="mb-6">
          <h3 class="text-lg font-semibold text-white mb-4">GRN Details</h3>

          <div class="grid grid-cols-2 gap-4 mb-4">

            <div>
              <label class="block text-white mb-2">GRN Number *</label>
              <input v-model="form.goods_received_note_no" type="text" class="w-full px-3 py-2 bg-gray-800 text-white rounded" required />
            </div>

            <div>
              <label class="block text-white mb-2">Supplier *</label>
              <select v-model="form.supplier_id" class="w-full px-3 py-2 bg-gray-800 text-white rounded" required>
                <option value="">Select Supplier</option>
                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                  {{ supplier.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-white mb-2">GRN Date *</label>
              <input v-model="form.goods_received_note_date" type="date" class="w-full px-3 py-2 bg-gray-800 text-white rounded" required />
            </div>

            <div>
              <label class="block text-white mb-2">Purchase Order</label>
              <select v-model="form.purchase_order_request_id" class="w-full px-3 py-2 bg-gray-800 text-white rounded">
                <option value="">Select PO (Optional)</option>
                <option v-for="po in purchaseOrders" :key="po.id" :value="po.id">
                  {{ po.order_number }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-white mb-2">Discount</label>
              <input v-model.number="form.discount" type="number" step="0.01" class="w-full px-3 py-2 bg-gray-800 text-white rounded" />

            </div>
            <div>
              <label class="block text-white mb-2">Tax Total</label>
              <input v-model.number="form.tax_total" type="number" step="0.01" class="w-full px-3 py-2 bg-gray-800 text-white rounded" />
            </div>

            <div>
              <label class="block text-white mb-2">Status</label>
              <select v-model.number="form.status" class="w-full px-3 py-2 bg-gray-800 text-white rounded" required>
                <option :value="0">INACTIVE</option>
                <option :value="1">ACTIVE</option>
                <option :value="2">DEFAULT</option>
              </select>
            </div>

          </div>

          <div>
            <label class="block text-white mb-2">Remarks</label>
            <textarea v-model="form.remarks" rows="3" class="w-full px-3 py-2 bg-gray-800 text-white rounded"></textarea>
          </div>
        </div>

        <!-- PRODUCTS SECTION -->
        <div class="mb-6">
          <div class="overflow-x-auto">
            <table class="w-full text-white text-sm">
              <thead class="bg-blue-600">
                <tr>
                  <th class="px-4 py-2">Product</th>
                  <th class="px-4 py-2">Qty</th>
                  <th class="px-4 py-2">Unit</th>
                  <th class="px-4 py-2">Purchase Price</th>
                  <th class="px-4 py-2">Discount</th>
                  <th class="px-4 py-2">Total</th>
                  <th class="px-4 py-2">Action</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(product, index) in products" :key="index" class="border-b border-gray-700">
                  <td class="px-4 py-2">
                    <span class="text-gray-300">
                      {{ product.product_name }}
                    </span>
                  </td>

                 <td class="px-4 py-2">
                    <input v-model.number="product.quantity" type="number" step="0.01" min="0.01"
                           @input="calculateTotal(index)"
                           class="w-full px-2 py-1 bg-gray-800 text-white rounded" />
                  </td>

                   <td class="px-4 py-2">
                    <span class="text-gray-300">
                      {{ product.unit || 'N/A' }}
                    </span>
                  </td>

                  <td class="px-4 py-2">
                    <input v-model.number="product.purchase_price" type="number" step="0.01" min="0"
                           @input="calculateTotal(index)"
                           class="w-full px-2 py-1 bg-gray-800 text-white rounded" />
                  </td>

                  <td class="px-4 py-2">
                    <input v-model.number="product.discount" type="number" step="0.01" min="0"
                           @input="calculateTotal(index)"
                           class="w-full px-2 py-1 bg-gray-800 text-white rounded" />
                  </td>

                  <td class="px-4 py-2">
                    <span class="font-semibold">
                      {{ formatNumber(product.total) }}
                    </span>
                  </td>

                  <td class="px-4 py-2">
                    <button type="button" @click="removeProduct(index)"
                            class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700">
                      Remove
                    </button>
                  </td>

                </tr>

                <tr v-if="products.length === 0">
                  <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                    No products added yet. Click "Add Product" to start.
                  </td>
                </tr>

              </tbody>

              <tfoot v-if="products.length > 0" class="bg-gray-800">
                <tr>
                  <td colspan="5" class="px-4 py-3 text-right font-semibold">Grand Total:</td>
                  <td class="px-4 py-3 font-bold text-lg">
                    {{ formatNumber(grandTotal) }}
                  </td>
                  <td></td>
                </tr>
              </tfoot>

            </table>
          </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="flex justify-end gap-2">
          <button type="button" @click="close"
                  class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-600">
            Cancel
          </button>

          <button type="submit"
                  class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Update GRN
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  open: Boolean,
  grn: Object,
  suppliers: Array,
  purchaseOrders: Array,
    products: Array,
  availableProducts: Array,
})

// safe computed fallback for available products (prevents undefined.find errors)
const availableProductsList = computed(() => props.availableProducts || [])

const emit = defineEmits(['update:open'])

const form = ref({
  goods_received_note_no: '',
  supplier_id: '',
  goods_received_note_date: '',
  purchase_order_request_id: '',
  discount: 0,
  tax_total: 0,
  remarks: '',
  status: 1,
})

const products = ref([])

// Watch for grn prop changes to load data
watch(() => props.grn, (newGrn) => {
  if (newGrn && props.open) {
    form.value = {
      goods_received_note_no: newGrn.goods_received_note_no || '',
      supplier_id: newGrn.supplier_id || '',
      goods_received_note_date: newGrn.goods_received_note_date || '',
      purchase_order_request_id: newGrn.purchase_order_request_id || '',
      discount: newGrn.discount || 0,
      tax_total: newGrn.tax_total || 0,
      remarks: newGrn.remarks || '',
      status: newGrn.status || 1,
    }
    
    products.value = newGrn.goods_received_note_products?.map(p => {
      const selectedProduct = availableProductsList.value.find(a => a.id === p.product_id)
      return {
        product_id: p.product_id,
        product_name: selectedProduct?.name || p.product?.name || 'N/A',
        quantity: p.quantity,
        purchase_price: p.purchase_price,
        unit: p.product?.measurement_unit?.name || '',
        discount: p.discount,
        total: p.total,
      }
    }) || []
  }
}, { immediate: true })


const grandTotal = computed(() => {
  const total = products.value.reduce((sum, product) => sum + (parseFloat(product.total) || 0), 0)
  return Math.round(total)
})

const close = () => {
  emit('update:open', false)
}

const removeProduct = (index) => {
  products.value.splice(index, 1)
}

const onProductSelect = (index) => {
  const product = products.value[index]
  const selectedProduct = availableProductsList.value.find(p => p.id === product.product_id)

  if (selectedProduct) {
    product.purchase_price = selectedProduct.price || selectedProduct.wholesale_price || 0
    product.unit = selectedProduct.measurement_unit?.name || selectedProduct.purchaseUnit?.name || selectedProduct.unit || ''
    calculateTotal(index)
  }
}

const calculateTotal = (index) => {
  const p = products.value[index]
  const qty = parseFloat(p.quantity) || 0
  const price = parseFloat(p.purchase_price) || 0
  const discount = parseFloat(p.discount) || 0

  p.total = Math.round((qty * price) - discount)
}

const formatNumber = (number) => {
  return Math.round(parseFloat(number || 0)).toLocaleString('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  })
}

const submitForm = () => {
  const payload = {
    ...form.value,
    products: products.value,
  }

  router.patch(route('good-receive-notes.update', props.grn.id), payload, {
    onSuccess: () => close(),
    onError: (e) => console.error('GRN update error:', e),
  })
}
</script>
