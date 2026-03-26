<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75">
    <div class="w-full max-w-md p-6 bg-black border-4 border-red-600 rounded-lg">
      <h2 class="mb-4 text-2xl font-bold text-white">Delete Product Return</h2>

      <div class="mb-6 text-gray-300">
        <p class="mb-2">Are you sure you want to delete this return?</p>
        <div class="p-4 mt-4 bg-gray-900 rounded">
          <p><strong>Return No:</strong> {{ returnData.return_no || `RET-${returnData.id}` }}</p>
          <p><strong>Customer:</strong> {{ returnData.customer?.name || returnData.customer_name || 'N/A' }}</p>
          <p><strong>Products:</strong> {{ returnData.products_count || returnData.return_products?.length || 0 }}</p>
        </div>
        <p class="mt-4 text-sm text-yellow-500">
          ⚠️ This action will reverse stock movements and cannot be undone.
        </p>
      </div>

      <div class="flex justify-end gap-4">
        <button
          @click="closeModal"
          class="px-6 py-2 text-white bg-gray-600 rounded hover:bg-gray-700"
          :disabled="form.processing"
        >
          Cancel
        </button>
        <button
          @click="deleteReturn"
          class="px-6 py-2 text-white bg-red-600 rounded hover:bg-red-700 disabled:opacity-50"
          :disabled="form.processing"
        >
          <span v-if="form.processing">
            <i class="fas fa-spinner fa-spin me-2"></i>Deleting...
          </span>
          <span v-else>
            <i class="fas fa-trash me-2"></i>Delete
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  returnData: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update:open'])

const form = useForm({})

const closeModal = () => {
  emit('update:open', false)
}

const deleteReturn = () => {
  form.delete(route('return.destroy', props.returnData.id), {
    onSuccess: () => {
      closeModal()
      router.reload()
    },
  })
}
</script>
