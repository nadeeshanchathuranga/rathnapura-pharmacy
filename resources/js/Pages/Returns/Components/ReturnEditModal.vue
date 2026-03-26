<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black bg-opacity-75">
    <div class="relative w-full max-w-4xl p-6 mx-4 my-8 bg-black border-4 border-blue-600 rounded-lg max-h-[90vh] overflow-y-auto">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-white">Edit Product Return</h2>
        <button @click="closeModal" class="text-white hover:text-gray-300">
          <i class="text-2xl fas fa-times"></i>
        </button>
      </div>

      <form @submit.prevent="submitForm">
        <!-- Return Information -->
        <div class="mb-6 bg-gray-900 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-white mb-4">Return Information</h3>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-2">Return No</label>
              <input
                type="text"
                :value="returnData.return_no || `RET-${returnData.id}`"
                readonly
                class="w-full px-3 py-2 bg-gray-800 text-white border border-gray-600 rounded"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
              <select
                v-model="form.status"
                class="w-full px-3 py-2 bg-gray-800 text-white border border-gray-600 rounded"
              >
                <option value="0">Pending</option>
                <option value="1">Approved</option>
                <option value="2">Rejected</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Products -->
        <div class="mb-6 bg-gray-900 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-white mb-4">Products</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-white text-sm">
              <thead class="bg-gray-800">
                <tr>
                  <th class="px-3 py-2">Product</th>
                  <th class="px-3 py-2">Sale Info</th>
                  <th class="px-3 py-2 text-center">Return Qty</th>
                  <th class="px-3 py-2 text-center">Unit Price</th>
                  <th class="px-3 py-2 text-center">Refund</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="product in form.products" :key="product.id" class="border-b border-gray-700">
                  <td class="px-3 py-2">
                    <div class="font-medium">{{ product.product_name || product.product?.name }}</div>
                  </td>
                  <td class="px-3 py-2">
                    <div class="text-xs">{{ product.sale_no || product.sales_product?.sale?.sale_no }}</div>
                  </td>
                  <td class="px-3 py-2 text-center">
                    <input
                      v-model.number="product.return_quantity"
                      type="number"
                      min="1"
                      class="w-20 px-2 py-1 bg-gray-800 text-white border border-gray-600 rounded text-center"
                    />
                  </td>
                  <td class="px-3 py-2 text-center">Rs. {{ parseFloat(product.price || 0).toFixed(2) }}</td>
                  <td class="px-3 py-2 text-center font-semibold">
                    Rs. {{ ((product.return_quantity || 0) * parseFloat(product.price || 0)).toFixed(2) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end gap-4">
          <button
            type="button"
            @click="closeModal"
            class="px-6 py-2 text-white bg-gray-600 rounded hover:bg-gray-700"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="processing"
            class="px-6 py-2 text-white bg-blue-600 rounded hover:bg-blue-700 disabled:opacity-50"
          >
            {{ processing ? 'Updating...' : 'Update Return' }}
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
  returnData: Object,
  salesProducts: Object,
})

const emit = defineEmits(['update:open'])

const processing = ref(false)
const form = ref({
  status: 0,
  products: []
})

watch(() => props.open, (newVal) => {
  if (newVal && props.returnData) {
    form.value = {
      status: props.returnData.status || 0,
      products: props.returnData.return_products?.map(p => ({
        id: p.id,
        product_name: p.product?.name || p.product_name,
        sale_no: p.sales_product?.sale?.sale_no || p.sale_no,
        return_quantity: p.quantity || p.return_quantity,
        price: p.price || p.sales_product?.price || 0
      })) || []
    }
  }
})

const closeModal = () => {
  emit('update:open', false)
}

const submitForm = () => {
  processing.value = true

  router.put(route('return.update', props.returnData.id), {
    status: form.value.status,
    products: form.value.products
  }, {
    onSuccess: () => {
      closeModal()
      processing.value = false
    },
    onError: (errors) => {
      console.error('Update errors:', errors)
      processing.value = false
    }
  })
}
</script>
