<template>
  <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-white">
        <thead class="bg-amber-700">
          <tr>
            <th class="px-4 py-4 font-semibold">Return ID</th>
            <th class="px-4 py-4 font-semibold">Sale No</th>
            <th class="px-4 py-4 font-semibold">Customer</th>
            <th class="px-4 py-4 font-semibold">Return Date</th>
            <th class="px-4 py-4 font-semibold">Products</th>
            <th class="px-4 py-4 font-semibold">Refund Amount3</th>
            <th class="px-4 py-4 font-semibold">Status</th>
            <th class="px-4 py-4 font-semibold">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="returnItem in returns.data"
            :key="returnItem.id"
            class="border-b border-gray-700 hover:bg-gray-700/50 transition"
          >
            <td class="px-4 py-4">
              <div class="font-semibold text-amber-400">#{{ returnItem.id }}</div>
            </td>
            <td class="px-4 py-4">
              <div class="font-medium">{{ returnItem.sale_no || 'N/A' }}</div>
            </td>
            <td class="px-4 py-4">
              <div class="font-medium">{{ returnItem.customer_name || 'Walk-in Customer' }}</div>
              <div class="text-sm text-gray-400">{{ returnItem.customer_phone || '' }}</div>
            </td>
            <td class="px-4 py-4">
              <div class="font-medium">{{ returnItem.return_date_formatted }}</div>
            </td>
            <td class="px-4 py-4">
              <span class="px-2 py-1 bg-blue-600 text-white text-xs rounded">
                {{ returnItem.products_count }} items
              </span>
            </td>
            <td class="px-4 py-4">
              <div class="font-bold text-green-400">${{ returnItem.total_refund_formatted }}</div>
            </td>
            <td class="px-4 py-4">
              <span
                :class="{
                  'bg-yellow-500 text-yellow-100': returnItem.status_color === 'yellow',
                  'bg-green-500 text-green-100': returnItem.status_color === 'green',
                  'bg-red-500 text-red-100': returnItem.status_color === 'red',
                  'bg-gray-500 text-gray-100': returnItem.status_color === 'gray'
                }"
                class="px-3 py-1 rounded-full text-xs font-semibold"
              >
                {{ returnItem.status_text }}
              </span>
            </td>
            <td class="px-4 py-4">
              <div class="flex gap-2">
                <button
                  @click="$emit('view-return', returnItem)"
                  class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition"
                >
                  👁️ View
                </button>
                <button
                  v-if="returnItem.status === 0"
                  @click="$emit('update-status', returnItem, 1)"
                  class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition"
                >
                  ✅ Approve
                </button>
                <button
                  v-if="returnItem.status === 0"
                  @click="$emit('update-status', returnItem, 2)"
                  class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition"
                >
                  ❌ Reject
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!returns.data || returns.data.length === 0">
            <td colspan="8" class="px-6 py-8 text-center text-gray-400">
              <div class="text-6xl mb-4">📦</div>
              <div class="text-xl font-semibold mb-2">No returns found</div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="flex items-center justify-between px-6 py-4 bg-gray-900" v-if="returns.links && returns.data.length > 0">
      <div class="text-sm text-gray-400">
        Showing {{ returns.from }} to {{ returns.to }} of {{ returns.total }} results
      </div>
      <div class="flex space-x-2">
        <button
          v-for="link in returns.links"
          :key="link.label"
          @click="link.url ? $emit('navigate', link.url) : null"
          :disabled="!link.url"
          :class="[
            'px-3 py-1 text-sm rounded',
            link.active
              ? 'bg-amber-600 text-white'
              : link.url
              ? 'bg-gray-700 text-gray-300 hover:bg-gray-600'
              : 'bg-gray-800 text-gray-500 cursor-not-allowed'
          ]"
          v-html="link.label"
        ></button>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  returns: Object,
})

defineEmits(['view-return', 'update-status', 'navigate'])
</script>