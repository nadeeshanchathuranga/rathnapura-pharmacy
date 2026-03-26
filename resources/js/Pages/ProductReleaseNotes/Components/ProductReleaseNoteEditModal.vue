<template>
  <div v-if="open" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-5xl max-h-[90vh] overflow-y-auto">

      <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-gray-800">Edit PRN</h2>
        <button @click="close" class="text-gray-400 hover:text-gray-600 transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <form @submit.prevent="submitForm">

        <!-- PRN DETAILS -->
        <div class="bg-gray-50 rounded-xl p-5 mb-6 border border-gray-200">
          <h3 class="text-xl font-semibold text-gray-800 mb-4">PRN Details</h3>

          <div class="grid grid-cols-2 gap-4 mb-4">

            <div>
              <label class="block text-gray-700 font-medium mb-2">Product Transfer Request (Read Only)</label>
              <input type="text" :value="prn.ptr?.transfer_no || 'N/A'" disabled
                     class="w-full px-4 py-2.5 bg-gray-100 text-gray-500 border border-gray-300 rounded-[5px] cursor-not-allowed" />
            </div>

            <div>
              <label class="block text-gray-700 font-medium mb-2">Release Date *</label>
              <input v-model="form.release_date" type="date"
                     class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all cursor-not-allowed"
  disabled required />
            </div>

            <div>
              <label class="block text-gray-700 font-medium mb-2">Status</label>
              <select v-model.number="form.status" class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                <option :value="0">Pending</option>
                <option :value="1">Released</option>
              </select>
            </div>

            <div>
              <label class="block text-gray-700 font-medium mb-2">User</label>
              <select v-model.number="form.user_id" class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                <option :value="null">Select User</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }}
                </option>
              </select>
            </div>

          </div>

          <div>
            <label class="block text-gray-700 font-medium mb-2">Remark</label>
            <textarea v-model="form.remark" rows="3"
                      class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"></textarea>
          </div>
        </div>

        <!-- PRODUCTS -->
        <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-800">Products *</h3>
            <button type="button" @click="addProduct"
                    class="px-4 py-2 text-xs font-medium text-white bg-green-600 rounded-[5px] hover:bg-green-700 transition-all duration-200">+ Add Product</button>
          </div>

          <div class="overflow-x-auto bg-white rounded-lg border border-gray-200">
            <table class="w-full text-sm border-collapse">
              <thead>
                <tr class="border-b-2 border-blue-600">
                  <th class="px-4 py-3 text-left text-blue-600 font-semibold text-sm">Product</th>
                  <th class="px-4 py-3 text-left text-blue-600 font-semibold text-sm">Unit</th>
                  <th class="px-4 py-3 text-right text-blue-600 font-semibold text-sm">Qty</th>
                  <th class="px-4 py-3 text-right text-blue-600 font-semibold text-sm">Unit Price</th>
                  <th class="px-4 py-3 text-right text-blue-600 font-semibold text-sm">Total</th>
                  <th class="px-4 py-3 text-center text-blue-600 font-semibold text-sm">Action</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(product, index) in products" :key="index" class="border-b border-gray-200 hover:bg-gray-50 transition-colors">

                  <td class="px-4 py-3">
                    <select v-model.number="product.product_id" @change="onProductSelect(index)"
                            class="w-full px-3 py-2 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                      <option :value="null">Select Product</option>
                      <option v-for="prod in availableProducts" :key="prod.id" :value="prod.id">
                        {{ prod.name }}
                      </option>
                    </select>
                  </td>

                  <td class="px-4 py-3 text-gray-700">{{ product.unit }}</td>

                  <td class="px-4 py-3">
                    <input v-model.number="product.qty" type="number"
                           @input="calculateTotal(index)"
                           class="w-full px-3 py-2 bg-white text-gray-800 text-right border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                  </td>

                  <td class="px-4 py-3">
                    <input v-model.number="product.unit_price" type="number" step="0.01"
                           @input="calculateTotal(index)"
                           class="w-full px-3 py-2 bg-white text-gray-800 text-right border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                  </td>

                  <td class="px-4 py-3 text-right">
                    <span class="text-gray-800 font-semibold">{{ formatNumber(product.total) }}</span>
                  </td>

                  <td class="px-4 py-3 text-center">
                    <button type="button" @click="removeProduct(index)"
                            class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-[5px] hover:bg-red-700 transition-all duration-200">Remove</button>
                  </td>

                </tr>
                <tr v-if="products.length === 0">
                  <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                    No products added yet. Click "Add Product" to get started.
                  </td>
                </tr>
              </tbody>

              <tfoot v-if="products.length > 0">
                <tr class="bg-blue-50 border-t-2 border-blue-600">
                  <td colspan="4" class="px-4 py-4 text-right font-semibold text-gray-800 text-base">Grand Total:</td>
                  <td class="px-4 py-4 text-right">
                    <span class="font-bold text-blue-700 text-lg">{{ formatNumber(grandTotal) }}</span>
                  </td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
          <button type="button" @click="close" class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 hover:border-gray-400 transition-all duration-200">Cancel</button>
          <button type="submit" class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 transition-all duration-200">Update PRN</button>
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
  prn: Object,
  availableProducts: Array,
  users: Array,
})

const emit = defineEmits(['update:open'])

const form = ref({
  ptr_id: null,
  user_id: null,
  release_date: '',
  status: 0,
  remark: '',
})

const products = ref([])

// Watch for modal open and load PRN data
watch(() => props.open, (isOpen) => {
  if (isOpen && props.prn) {
    loadPrnData()
  }
})

const loadPrnData = () => {
  form.value = {
    ptr_id: props.prn.ptr_id,
    user_id: props.prn.user_id,
    release_date: props.prn.release_date,
    status: Number(props.prn.status),
    remark: props.prn.remark || '',
  }

  products.value = props.prn.prn_products?.map(item => ({
    id: item.id,
    product_id: Number(item.product_id),
    qty: Number(item.quantity),
    unit_price: Number(item.unit_price),
    product_name: item.product?.name || '',
    unit: item.product?.measurementUnit?.name || item.product?.measurement_unit?.name || 'N/A',
    total: Number(item.total),
  })) || []
}

const close = () => {
  emit('update:open', false)
  resetForm()
}

const resetForm = () => {
  form.value = {
    ptr_id: null,
    user_id: null,
    release_date: '',
    status: 0,
    remark: '',
  }
  products.value = []
}

const addProduct = () => {
  products.value.push({
    product_id: null,
    qty: 1,
    unit_price: 0,
    unit: '',
    total: 0,
  })
}

const removeProduct = (index) => products.value.splice(index, 1)

const onProductSelect = (index) => {
  const p = products.value[index]
  const prod = props.availableProducts.find(a => a.id === p.product_id)

  if (prod) {
    p.unit_price = prod.price
    p.unit = prod.measurementUnit?.name || 'N/A'
    p.product_name = prod.name
    calculateTotal(index)
  }
}

const calculateTotal = (index) => {
  const p = products.value[index]
  p.total = p.qty * p.unit_price
}

const grandTotal = computed(() =>
  products.value.reduce((sum, p) => sum + p.total, 0)
)

const formatNumber = (n) => Number(n).toFixed(2)

const submitForm = () => {
  const mappedProducts = products.value.map(p => ({
    product_id: p.product_id,
    quantity: p.qty,
    unit_price: p.unit_price,
    total: p.total
  }))

  router.put(route('product-release-notes.update', props.productReleaseNote.id), {
    ...form.value,
    products: mappedProducts
  }, {
    onSuccess: () => close(),
  })
}
</script>
