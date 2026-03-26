<template>
  <TransitionRoot as="template" :show="open">
    <Dialog class="relative z-10" @close="$emit('update:open', false)">
      <!-- Modal Overlay -->
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div
          class="fixed inset-0 transition-opacity bg-black bg-opacity-60 backdrop-blur-sm"
        />
      </TransitionChild>

      <!-- Modal Content -->
      <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="opacity-0 scale-95"
          enter-to="opacity-100 scale-100"
          leave="ease-in duration-200"
          leave-from="opacity-100 scale-100"
          leave-to="opacity-0 scale-95"
        >
          <DialogPanel
            class="bg-gradient-to-br from-red-50 to-orange-50 rounded-2xl shadow-2xl max-w-md w-full p-6"
          >
            <!-- Modal Header -->
            <div class="flex items-center justify-between mb-6">
              <DialogTitle
                class="text-2xl font-bold bg-gradient-to-r from-red-600 to-orange-600 bg-clip-text text-transparent"
              >
                ⚠️ Delete Brand
              </DialogTitle>
              <button
                type="button"
                @click="closeModal"
                class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-200 rounded-full transition-all duration-300"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="w-6 h-6"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>

            <!-- Warning Icon -->
            <div class="flex justify-center mb-6">
              <div
                class="flex items-center justify-center w-20 h-20 bg-gradient-to-br from-red-500 to-orange-500 rounded-full shadow-lg"
              >
                <svg
                  class="w-10 h-10 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                  />
                </svg>
              </div>
            </div>

            <!-- Confirmation Message -->
            <div
              class="bg-white/40 backdrop-blur-xl rounded-xl p-4 shadow-lg border border-white/60 mb-6"
            >
              <p class="text-lg font-semibold text-gray-800 text-center mb-3">
                Are you sure you want to delete this brand?
              </p>
              <div class="bg-red-50 border-l-4 border-red-500 p-3 rounded">
                <p class="text-sm font-semibold text-gray-700">
                  Brand: <span class="text-red-600">{{ brand.name }}</span>
                </p>
              </div>
              <p class="mt-3 text-sm text-gray-600 text-center">
                This action cannot be undone.
              </p>
            </div>

            <!-- Modal Buttons -->
            <div class="flex gap-3 justify-end">
              <button
                @click="closeModal"
                type="button"
                class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white/60 border border-gray-300 rounded-full hover:bg-gray-100 hover:shadow-md transition-all duration-300"
              >
                Cancel
              </button>
              <button
                @click="confirmDelete"
                type="button"
                class="px-6 py-2.5 text-sm font-medium text-white bg-red-600 rounded-full hover:bg-red-700 hover:shadow-lg hover:scale-105 transition-all duration-300"
              >
                🗑️ Delete Brand
              </button>
            </div>
          </DialogPanel>
        </TransitionChild>
      </div>
    </Dialog>
  </TransitionRoot>
</template>
<script setup>
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";
import { router } from "@inertiajs/vue3";

const emit = defineEmits(["update:open"]);

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  brand: {
    type: Object,
    required: true,
  },
});

const confirmDelete = () => {
  router.delete(`/brands/${props.brand.id}`, {
    onSuccess: () => {
      emit("update:open", false);
    },
  });
};

const closeModal = () => {
  emit("update:open", false);
};
</script>
