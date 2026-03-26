<template>
  <div class="bg-gray-800 rounded-lg p-6 shadow-lg mb-6">
    <h3 class="text-lg font-semibold text-white mb-4">🔍 Filters & Search</h3>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-300 mb-2">Search</label>
        <input
          :value="filters.search"
          @input="$emit('update:search', $event.target.value)"
          type="text"
          placeholder="Customer, Sale No, Product..."
          class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
        <select
          :value="filters.status"
          @change="$emit('update:status', $event.target.value)"
          class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500"
        >
          <option value="">All Status</option>
          <option v-for="status in statusOptions" :key="status.value" :value="status.value">
            {{ status.label }}
          </option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-300 mb-2">From Date</label>
        <input
          :value="filters.date_from"
          @input="$emit('update:date_from', $event.target.value)"
          type="date"
          class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-300 mb-2">To Date</label>
        <input
          :value="filters.date_to"
          @input="$emit('update:date_to', $event.target.value)"
          type="date"
          class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500"
        />
      </div>
    </div>
    <div class="flex gap-2 mt-4">
      <button
        @click="$emit('apply-filters')"
        class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition"
      >
        Apply Filters
      </button>
      <button
        @click="$emit('clear-filters')"
        class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition"
      >
        Clear
      </button>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  filters: Object,
  statusOptions: Array,
})

defineEmits(['update:search', 'update:status', 'update:date_from', 'update:date_to', 'apply-filters', 'clear-filters'])
</script>