<template>
  <div class="overflow-x-auto bg-gray-900 rounded-lg">
    <table class="w-full text-left text-white">
      <thead class="bg-gray-700">
        <tr>
          <th class="px-4 py-3 font-semibold">Select</th>
          <th class="px-4 py-3 font-semibold">Product</th>
          <th class="px-4 py-3 font-semibold">Sale No</th>
          <th class="px-4 py-3 font-semibold">Sale Date</th>
          <th class="px-4 py-3 font-semibold">Customer</th>
          <th class="px-4 py-3 font-semibold">Unit Price</th>
          <th class="px-4 py-3 font-semibold">Quantity</th>
          <th class="px-4 py-3 font-semibold">Total</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="product in products"
          :key="product.id"
          class="border-b border-gray-700 hover:bg-gray-800/50 transition"
          :class="{ 'opacity-50': isProductSelected(product.id) }"
        >
          <td class="px-4 py-3">
            <input
              type="checkbox"
              :checked="isProductSelected(product.id)"
              @change="toggleProduct(product)"
              :disabled="isProductSelected(product.id)"
              class="w-5 h-5 text-amber-600 bg-gray-700 border-gray-600 rounded focus:ring-amber-500"
            />
          </td>
          <td class="px-4 py-3">
            <div class="font-medium">{{ product.product_name }}</div>
            <div class="text-sm text-gray-400">Code: {{ product.product_code }}</div>
          </td>
          <td class="px-4 py-3">
            <div class="font-medium text-amber-400">{{ product.sale_no }}</div>
          </td>
          <td class="px-4 py-3">{{ product.sale_date_formatted }}</td>
          <td class="px-4 py-3">
            <div class="font-medium">{{ product.customer_name || 'Walk-in Customer' }}</div>
            <div class="text-sm text-gray-400">{{ product.customer_phone || '' }}</div>
          </td>
          <td class="px-4 py-3">${{ product.price }}</td>
          <td class="px-4 py-3">
            <span class="px-2 py-1 bg-blue-600 text-white text-xs rounded">
              {{ product.quantity }}
            </span>
          </td>
          <td class="px-4 py-3 font-semibold text-green-400">${{ product.total }}</td>
        </tr>
        <tr v-if="!products || products.length === 0">
          <td colspan="8" class="px-6 py-8 text-center text-gray-400">
            <div class="text-6xl mb-4">🔍</div>
            <div class="text-xl font-semibold mb-2">No returnable products found</div>
            <div class="text-sm">Search for products from recent sales that allow returns</div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
const props = defineProps({
  products: Array,
  selectedProducts: Array,
})

const emit = defineEmits(['add-product'])

const isProductSelected = (productId) => {
  return props.selectedProducts.some(p => p.id === productId)
}

const toggleProduct = (product) => {
  if (!isProductSelected(product.id)) {
    emit('add-product', {
      ...product,
      return_quantity: 1,
      refund_amount: parseFloat(product.price),
      return_reason: ''
    })
  }
}
</script>