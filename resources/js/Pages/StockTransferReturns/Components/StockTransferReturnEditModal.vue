<template>
  <div v-if="open" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-900 rounded-lg p-6 w-full max-w-4xl max-h-screen overflow-y-auto">
      
      <h2 class="text-2xl font-bold text-white mb-4">Edit Stock Transfer Return</h2>

      <form @submit.prevent="submitForm">

        <!-- Return Information -->
        <div class="mb-6">
          <h3 class="text-lg font-semibold text-white mb-4">Return Information</h3>

          <div class="grid grid-cols-2 gap-4 mb-4">

            <div>
              <label class="block text-white mb-2">Return Number *</label>
              <input v-model="form.return_no" type="text" class="w-full px-3 py-2 bg-gray-800 text-white rounded" readonly />
            </div>

            <div>
              <label class="block text-white mb-2">Return Date *</label>
              <input v-model="form.return_date" type="date" class="w-full px-3 py-2 bg-gray-800 text-white rounded" required />
            </div>

          </div>

          <div>
            <label class="block text-white mb-2">Reason</label>
            <textarea v-model="form.reason" rows="3" class="w-full px-3 py-2 bg-gray-800 text-white rounded"></textarea>
          </div>
        </div>

        <!-- Products Section -->
        <div class="mb-6">

          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-white">Products *</h3>
            <button type="button" @click="addProduct"
                    class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700">
              + Add Product
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-white text-sm">
              <thead class="bg-blue-600">
                <tr>
                  <th class="px-4 py-2">Product</th>
                  <th class="px-4 py-2">Unit</th>
                  <th class="px-4 py-2">Quantity</th>
                  <th class="px-4 py-2">Action</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(product, index) in products" :key="index" class="border-b border-gray-700">

                  <td class="px-4 py-2">
                    <select v-model.number="product.product_id" @change="onProductSelect(index)"
                            class="w-full px-2 py-1 bg-gray-800 text-white rounded">
                      <option :value="null">Select Product</option>
                      <option v-for="prod in availableProducts" :key="prod.id" :value="prod.id">
                        {{ prod.name }}
                      </option>
                    </select>
                  </td>

                  <td class="px-4 py-2">
                    <select v-model="product.measurement_unit_id"
                            class="w-full px-2 py-1 bg-gray-800 text-white rounded">
                      <option value="">Select Unit</option>
                      <option v-for="unit in product.available_units || measurementUnits" :key="unit.id" :value="unit.id">
                        {{ unit.name }}
                      </option>
                    </select>
                  </td>

                  <td class="px-4 py-2">
                    <input v-model.number="product.stock_transfer_quantity" type="number" min="1"
                           class="w-full px-2 py-1 bg-gray-800 text-white rounded" />
                  </td>

                  <td class="px-4 py-2">
                    <button type="button" @click="removeProduct(index)"
                            class="px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700"
                            :disabled="products.length === 1">
                      {{ products.length === 1 ? '-' : 'Remove' }}
                    </button>
                  </td>

                </tr>

                <tr v-if="products.length === 0">
                  <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                    No products added yet. Click "Add Product" to start.
                  </td>
                </tr>

              </tbody>

            </table>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end gap-2">
          <button type="button" @click="close"
                  class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-600">
            Cancel
          </button>

          <button type="submit" :disabled="products.length === 0"
                  class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
            Update Return
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  open: Boolean,
  stockTransferReturn: Object,
  users: Array,
  products: Array,
  measurementUnits: Array,
})

const emit = defineEmits(['update:open'])

const form = ref({
  return_no: '',
  return_date: '',
  reason: '',
})

const products = ref([])
const availableProducts = ref(props.products || [])

watch(() => props.open, (newVal) => {
  if (newVal && props.stockTransferReturn) {
    form.value = {
      return_no: props.stockTransferReturn.return_no,
      return_date: props.stockTransferReturn.return_date,
      reason: props.stockTransferReturn.reason || '',
    }
    
    // Load existing products
    products.value = (props.stockTransferReturn.products || []).map(item => ({
      id: item.id,
      product_id: item.product_id,
      measurement_unit_id: item.measurement_unit_id,
      stock_transfer_quantity: item.stock_transfer_quantity,
      available_units: getProductUnits(item.product_id),
    }))
  }
})

const getProductUnits = (productId) => {
  const product = availableProducts.value.find(p => p.id === productId)
  if (product && product.measurement_units) {
    return product.measurement_units
  }
  return props.measurementUnits || []
}

const close = () => {
  emit('update:open', false)
}

const addProduct = () => {
  products.value.push({
    product_id: null,
    measurement_unit_id: null,
    stock_transfer_quantity: 1,
    available_units: [],
  })
}

const removeProduct = (index) => {
  if (products.value.length > 1) {
    products.value.splice(index, 1)
  }
}

const onProductSelect = (index) => {
  const product = products.value[index]
  const selectedProduct = availableProducts.value.find(p => p.id === product.product_id)

  if (selectedProduct && selectedProduct.measurement_units) {
    product.available_units = selectedProduct.measurement_units
    if (selectedProduct.measurement_units.length > 0) {
      product.measurement_unit_id = selectedProduct.measurement_units[0].id
    }
  }
}

const submitForm = () => {
  const payload = {
    ...form.value,
    products: products.value.map(p => ({
      product_id: p.product_id,
      measurement_unit_id: p.measurement_unit_id,
      stock_transfer_quantity: p.stock_transfer_quantity,
    })),
  }

  router.put(route('stock-transfer-returns.update', props.stockTransferReturn.id), payload, {
    onSuccess: () => close(),
    onError: (e) => console.error('Stock Transfer Return update error:', e),
  })
}
</script>
