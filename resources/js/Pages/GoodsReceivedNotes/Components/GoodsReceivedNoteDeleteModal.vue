<template>
  <div v-if="open" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-900 rounded-lg p-6 w-full max-w-md">
      <h2 class="text-2xl font-bold text-white mb-4">Delete GRN</h2>
      <p class="text-gray-300 mb-6">
        Are you sure you want to delete GRN <strong>{{ grn.goods_received_note_no }}</strong>? This action cannot be undone.
      </p>
      <div class="flex justify-end gap-2">
        <button @click="close" class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-600">
          Cancel
        </button>
        <button @click="deleteGrn" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
          Delete
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    open: Boolean,
    grn: Object,
});

const emit = defineEmits(['update:open']);

const close = () => {
    emit('update:open', false);
};

const deleteGrn = () => {
    router.delete(route('good-receive-notes.destroy', props.grn.id), {
        onSuccess: () => {
            close();
        },
    });
};
</script>
