<template>
  <div v-if="open" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-900 rounded-lg p-6 w-full max-w-md">
      
      <h2 class="text-2xl font-bold text-white mb-4">Delete PRN</h2>

      <div class="mb-6">
        <p class="text-white mb-2">Are you sure you want to delete this PRN?</p>
        <p class="text-gray-400 text-sm">This action cannot be undone.</p>
        
        <div class="mt-4 p-4 bg-gray-800 rounded">
          <p class="text-white text-sm"><strong>Release Date:</strong> {{ formatDate(prn.release_date) }}</p>
          <p class="text-white text-sm mt-2"><strong>Products:</strong> {{ prn.prn_products?.length || 0 }} items</p>
          <p class="text-white text-sm mt-2"><strong>Status:</strong> {{ getStatusLabel(prn.status) }}</p>
        </div>
      </div>

      <div class="flex justify-end gap-2">
        <button 
          @click="close" 
          class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-600"
        >
          Cancel
        </button>
        <button 
          @click="deletePrn" 
          class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
        >
          Delete
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'

const props = defineProps({
  open: Boolean,
  prn: Object,
})

const emit = defineEmits(['update:open'])

const close = () => {
  emit('update:open', false)
}

const deletePrn = () => {
  router.delete(route('product-release-notes.destroy', props.productReleaseNote.id), {
    onSuccess: () => {
      close()
    },
    onError: () => {
      alert('Failed to delete PRN')
    }
  })
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

const getStatusLabel = (status) => {
  const labels = {
    0: 'Pending',
    1: 'Released'
  }
  return labels[status] || 'Unknown'
}
</script>
