<template>
  <div v-if="open" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="close">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl">
      <h2 class="text-2xl font-bold text-red-600 mb-4">⚠️ Delete GRN Return</h2>
      <p class="text-gray-700 mb-6">
        Are you sure you want to delete GRN Return <strong class="text-gray-900">{{ grn.grn?.grn_no || grn.id }}</strong>? This action cannot be undone.
      </p>
      <div class="flex justify-end gap-3">
        <button @click="close" class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-[5px] hover:bg-gray-50 transition-all duration-200">
          Cancel
        </button>
        <button @click="deleteGrn" :disabled="saving" class="px-6 py-2.5 text-sm font-medium text-white bg-red-600 rounded-[5px] hover:bg-red-700 disabled:opacity-50 transition-all duration-200">
          <span v-if="!saving">Delete</span>
          <span v-else>Deleting...</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    open: Boolean,
    grn: Object,
});

const emit = defineEmits(['update:open', 'deleted']);

const saving = ref(false);

const close = () => {
    emit('update:open', false);
};

const deleteGrn = () => {
    if (!props.grn) return;
    saving.value = true;
    router.delete(route('good-receive-note-returns.destroy', props.grn.id), {
        onSuccess: () => {
            saving.value = false;
            emit('deleted', props.grn.id);
            close();
        },
        onError: () => {
            saving.value = false;
        }
    });
};

// Body scroll lock
watch(
    () => props.open,
    (newVal) => {
        if (newVal) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
);

// Cleanup on unmount
onUnmounted(() => {
    document.body.style.overflow = '';
});
</script>
