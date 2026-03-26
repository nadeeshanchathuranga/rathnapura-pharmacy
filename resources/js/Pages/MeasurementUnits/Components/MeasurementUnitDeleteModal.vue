<template>
  <TransitionRoot appear :show="open" as="template">
    <Dialog as="div" @close="closeModal" class="relative z-10">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-full p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel
              class="w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-gradient-to-br from-red-50 to-orange-50 shadow-2xl rounded-2xl"
            >
              <!-- Modal Header -->
              <div class="flex items-center justify-between mb-6">
                <DialogTitle
                  as="h3"
                  class="text-2xl font-bold bg-gradient-to-r from-red-600 to-orange-600 bg-clip-text text-transparent"
                >
                  ⚠️ Delete Measurement Unit
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

              <!-- Warning Message -->
              <div
                class="bg-white/40 backdrop-blur-xl rounded-xl p-4 shadow-lg border border-white/60 mb-6"
              >
                <p class="text-sm text-gray-700 leading-relaxed">
                  Are you sure you want to delete the measurement unit
                  <span class="font-semibold text-red-600"
                    >"{{ unit.name }} ({{ unit.symbol }})"</span
                  >? <br /><br />
                  <span class="text-red-600 font-medium"
                    >⚠️ This action cannot be undone.</span
                  >
                </p>
              </div>

              <!-- Modal Buttons -->
              <div class="flex gap-3 justify-end">
                <button
                  type="button"
                  @click="closeModal"
                  class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white/60 border border-gray-300 rounded-full hover:bg-gray-100 hover:shadow-md transition-all duration-300"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  @click="deleteUnit"
                  :disabled="form.processing"
                  class="px-6 py-2.5 text-sm font-medium text-white bg-red-600 rounded-full hover:bg-red-700 hover:shadow-lg hover:scale-105 transition-all duration-300 disabled:opacity-50"
                >
                  {{ form.processing ? "🗑️ Deleting..." : "🗑️ Delete Unit" }}
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
  DialogTitle,
} from "@headlessui/vue";

const props = defineProps({
  open: Boolean,
  unit: Object,
});

const emit = defineEmits(["update:open"]);

const form = useForm({});

const deleteUnit = () => {
  form.delete(route("measurement-units.destroy", props.unit.id), {
    onSuccess: () => {
      closeModal();
    },
  });
};

const closeModal = () => {
  emit("update:open", false);
};
</script>
