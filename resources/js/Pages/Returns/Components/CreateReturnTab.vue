<template>
  <Modal :show="show" @close="$emit('cancel')" max-width="7xl">
    <div class="bg-gray-800 rounded-lg shadow-lg p-6">
      <!-- Sale Information -->
      <div class="mb-6">
      <h3 class="text-xl font-semibold text-white mb-4">📋 Sale Information</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-white">
        <div>
          <div class="text-gray-400 text-sm">Sale Number</div>
          <div class="font-semibold">{{ selectedSale.invoice_no || 'N/A' }}</div>
        </div>
        <div>
          <div class="text-gray-400 text-sm">Customer</div>
          <div class="font-semibold">{{ selectedSale.customer_name || 'Walk-in Customer' }}</div>
        </div>
        <div>
          <div class="text-gray-400 text-sm">Sale Date</div>
          <div class="font-semibold">{{ selectedSale.sale_date_formatted }}</div>
        </div>
      </div>
    </div>

    <!-- Selected Products Table -->
    <div class="mb-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-semibold text-white">🛒 Selected Products for Return</h3>
        <div class="text-white font-semibold">
          Total Refund: <span class="text-green-400">${{ totalRefund.toFixed(2) }}</span>
        </div>
      </div>

      <div class="overflow-x-auto bg-gray-900 rounded-lg">
        <table class="w-full text-left text-white">
          <thead class="bg-amber-700">
            <tr>
              <th class="px-4 py-3 font-semibold">Product</th>
              <th class="px-4 py-3 font-semibold">Unit Price</th>
              <th class="px-4 py-3 font-semibold">Available Qty</th>
              <th class="px-4 py-3 font-semibold">Return Qty</th>
              <th class="px-4 py-3 font-semibold">Refund Amount2</th>
              <th class="px-4 py-3 font-semibold">Reason</th>
              <th class="px-4 py-3 font-semibold">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(product, index) in selectedProducts"
              :key="index"
              class="border-b border-gray-700"
            >
              <td class="px-4 py-3">
                <div class="font-medium">{{ product.product_name }}</div>
                <div class="text-sm text-gray-400">Code: {{ product.product_code }}</div>
              </td>
              <td class="px-4 py-3">${{ product.price }}</td>
              <td class="px-4 py-3">{{ product.quantity }}</td>
              <td class="px-4 py-3">
                <input
                  v-model.number="product.return_quantity"
                  type="number"
                  min="1"
                  :max="product.quantity"
                  class="w-20 px-2 py-1 bg-gray-700 text-white border border-gray-600 rounded"
                  @change="calculateRefund(product)"
                />
              </td>
              <td class="px-4 py-3 font-bold text-green-400">
                ${{ product.refund_amount.toFixed(2) }}
              </td>
              <td class="px-4 py-3">
                <input
                  v-model="product.return_reason"
                  type="text"
                  class="w-full px-2 py-1 bg-gray-700 text-white border border-gray-600 rounded"
                  placeholder="Reason..."
                />
              </td>
              <td class="px-4 py-3">
                <button
                  @click="$emit('remove-product', index)"
                  class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition"
                >
                  Remove
                </button>
              </td>
            </tr>
            <tr v-if="selectedProducts.length === 0">
              <td colspan="7" class="px-4 py-6 text-center text-gray-400">
                No products selected for return
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Search Available Products -->
    <div class="mb-6">
      <h3 class="text-xl font-semibold text-white mb-4">🔍 Search Products to Return</h3>
      <div class="flex gap-4 mb-4">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search by product name or code..."
          class="flex-1 px-4 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500"
        />
        <button
          @click="$emit('search-products', searchQuery)"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
        >
          🔍 Search
        </button>
      </div>

      <SalesProductsTable
        :products="availableProducts"
        :selected-products="selectedProducts"
        @add-product="$emit('add-product', $event)"
      />
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-end gap-4">
      <button
        @click="$emit('cancel')"
        class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition"
      >
        Cancel
      </button>
      <button
        @click="submitReturn"
        :disabled="selectedProducts.length === 0"
        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:bg-gray-500 disabled:cursor-not-allowed"
      >
        ✅ Submit Return
      </button>
    </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed } from 'vue'
import Modal from '@/Components/Modal.vue'
import SalesProductsTable from './SalesProductsTable.vue'

const props = defineProps({
  show: Boolean,
  selectedSale: Object,
  availableProducts: Array,
  selectedProducts: Array,
})

const emit = defineEmits(['add-product', 'remove-product', 'search-products', 'submit', 'cancel'])

const searchQuery = ref('')

const totalRefund = computed(() => {
  return props.selectedProducts.reduce((sum, product) => sum + product.refund_amount, 0)
})

const calculateRefund = (product) => {
  const qty = parseFloat(product.return_quantity) || 0
  const maxQty = parseFloat(product.quantity)
  
  if (qty > maxQty) {
    product.return_quantity = maxQty
  }
  
  product.refund_amount = product.return_quantity * parseFloat(product.price)
}

const submitReturn = () => {
  if (props.selectedProducts.length === 0) return
  
  emit('submit', {
    sale_id: props.selectedSale.id,
    products: props.selectedProducts,
    total_refund: totalRefund.value
  })
}
</script>