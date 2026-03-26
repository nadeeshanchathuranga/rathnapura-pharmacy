<template>
  <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75">
    <div class="w-full max-w-md p-6 bg-gray-900 border-2 rounded-lg" :class="por?.deleted_at ? 'border-yellow-600' : 'border-red-600'">
      <h2 class="text-xl font-bold text-white mb-4">{{ por?.deleted_at ? 'Restore Purchase Order Request' : 'Delete Purchase Order Request' }}</h2>
      
      <div v-if="por?.deleted_at" class="mb-4 p-3 bg-yellow-900 border border-yellow-600 rounded text-yellow-200 text-sm">
        <p><strong>Info:</strong> This POR was marked as <span class="font-semibold">INACTIVE</span> on {{ formatDate(por.deleted_at) }}.</p>
        <p class="mt-2">You can restore it to active status.</p>
      </div>

      <div v-else class="mb-4 p-3 bg-yellow-900 border border-yellow-600 rounded text-yellow-200 text-sm">
        <p><strong>Note:</strong> This will <strong>mark</strong> the POR as <span class="font-semibold">INACTIVE</span> (soft-delete). Related products will be preserved.</p>
      </div>

      <div class="flex justify-end space-x-4">
        <button
          @click="$emit('update:open', false)"
          class="px-4 py-2 text-white bg-gray-600 rounded hover:bg-gray-700"
        >
          Cancel
        </button>
        <button
          v-if="por?.deleted_at"
          @click="confirmRestore"
          :disabled="isProcessing"
          class="px-4 py-2 text-white bg-green-600 rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ isProcessing ? 'Processing...' : 'Restore' }}
        </button>
        <button
          v-else
          @click="confirmDelete"
          :disabled="isDeleting || por?.status === 'inactive'"
          class="px-4 py-2 text-white bg-red-600 rounded hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ isProcessing ? 'Processing...' : 'Mark Inactive' }}
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
  por: Object
});

const emit = defineEmits(['update:open']);

const isProcessing = ref(false);

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const confirmDelete = () => {
  if (!props.por) return;

  isProcessing.value = true;

  router.delete(`/purchase-order-requests/${props.por.id}`, {
    onSuccess: () => {
      isProcessing.value = false;
      emit('update:open', false);
      router.reload();
    },
    onError: () => {
      isProcessing.value = false;
    }
  });
};

const confirmRestore = () => {
  if (!props.por || !props.por.deleted_at) return;

  isProcessing.value = true;

  router.post(`/purchase-order-requests/${props.por.id}/restore`, {}, {
    onSuccess: () => {
      isProcessing.value = false;
      emit('update:open', false);
      router.reload();
    },
    onError: () => {
      isProcessing.value = false;
    }
  });
};

</script>

<style scoped>
/* Tailwind CSS handles all styling */
</style>
