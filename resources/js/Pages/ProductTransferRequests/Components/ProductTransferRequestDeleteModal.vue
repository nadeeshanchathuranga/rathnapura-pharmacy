<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75">
    <div class="w-full max-w-md p-6 bg-gray-900 border-2 border-red-600 rounded-lg">
      <h2 class="text-xl font-bold text-white mb-4">Delete Purchase Order Request</h2>
      
      <div v-if="productTransferRequest?.status !== 'pending'" class="mb-4 p-3 bg-red-900 border border-red-600 rounded text-red-200 text-sm">
        <p><strong>Error:</strong> Only pending PTRs can be deleted. Current status: <span class="font-semibold">{{ productTransferRequest?.status.toUpperCase() }}</span></p>
      </div>

      <div v-else class="mb-4 p-3 bg-yellow-900 border border-yellow-600 rounded text-yellow-200 text-sm">
        <p><strong>Note:</strong> This PTR and all related products will be deleted.</p>
      </div>

      <div class="flex justify-end space-x-4">
        <button
          @click="closeModal"
          class="px-4 py-2 text-white bg-gray-600 rounded hover:bg-gray-700"
        >
          Cancel
        </button>
        <button
          @click="confirmDelete"
          :disabled="isDeleting || productTransferRequest?.status !== 'pending'"
          class="px-4 py-2 text-white bg-red-600 rounded hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ isDeleting ? 'Deleting...' : 'Delete' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  open: Boolean,
  productTransferRequest: Object
});

const emit = defineEmits(['update:open']);

const isDeleting = ref(false);

const closeModal = () => {
  emit('update:open', false);
};

const confirmDelete = () => {
  if (!props.productTransferRequest || props.productTransferRequest.status !== 'pending') return;

  isDeleting.value = true;
  router.delete(route('product-transfer-requests.destroy', props.productTransferRequest.id), {
    onSuccess: () => {
      isDeleting.value = false;
      closeModal();
      router.reload();
    },
    onError: () => {
      isDeleting.value = false;
    }
  });
};
</script>

<style scoped>
/* Tailwind CSS handles all styling */
</style>
