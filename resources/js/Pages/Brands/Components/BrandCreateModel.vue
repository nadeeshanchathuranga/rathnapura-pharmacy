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
            class="bg-gradient-to-br from-gray-50 to-blue-50 rounded-2xl shadow-2xl max-w-md w-full p-6"
          >
            <!-- Modal Header -->
            <div class="flex items-center justify-between mb-6">
              <DialogTitle
                class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent"
              >
                ✨ Add Brand
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

            <form @submit.prevent="submit">
              <!-- Form Content -->
              <div
                class="bg-white/40 backdrop-blur-xl rounded-xl p-4 shadow-lg border border-white/60 mb-6"
              >
                <div class="space-y-4">
                  <!-- Brand Name -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Brand Name <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="form.name"
                      type="text"
                      id="name"
                      required
                      class="w-full px-3 py-2 text-sm text-gray-800 bg-white/60 backdrop-blur-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Enter brand name"
                      pattern="^[A-Za-z\s]+$"
                      @input="onBrandNameInput"
                      title="Only alphabetic characters and spaces are allowed."
                    />
                    <span
                      v-if="form.errors.name"
                      class="text-sm text-red-500 mt-1 block"
                      >{{ form.errors.name }}</span
                    >
                  </div>

                  <!-- Status -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Status <span class="text-red-500">*</span>
                    </label>
                    <select
                      v-model="form.status"
                      id="status"
                      required
                      class="w-full px-3 py-2 text-sm text-gray-800 bg-white/60 backdrop-blur-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                      <option value="1">Active</option>
                      <option value="0">Inactive</option>
                    </select>
                    <span
                      v-if="form.errors.status"
                      class="text-sm text-red-500 mt-1 block"
                      >{{ form.errors.status }}</span
                    >
                  </div>
                </div>
              </div>

              <!-- Modal Buttons -->
              <div class="flex gap-3 justify-end">
                <button
                  @click="closeModal"
                  type="button"
                  class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white/60 border border-gray-300 rounded-[5px] hover:bg-gray-100 hover:shadow-md transition-all duration-300"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-[5px] hover:bg-blue-700 hover:shadow-lg hover:scale-105 transition-all duration-300"
                >
                  💾 Save Brand
                </button>
              </div>
            </form>
          </DialogPanel>
        </TransitionChild>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
// Only allow alphabetic characters and spaces in brand name
const onBrandNameInput = (e) => {
  e.target.value = e.target.value.replace(/[^A-Za-z\s]/g, "");
  form.name = e.target.value;
};
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { logActivity } from "@/composables/useActivityLog";

const emit = defineEmits(["update:open"]);

// The `open` prop controls the visibility of the modal
defineProps({
  open: {
    type: Boolean,
    required: true,
  },
});

const form = useForm({
  name: "",
  status: "1",
});

const submit = () => {
  form.post("/brands", {
    onSuccess: async () => {
      // Log create activity
      await logActivity("create", "brands", {
        brand_name: form.name,
        status: form.status,
      });

      form.reset();
      emit("update:open", false);
    },
  });
};

const closeModal = () => {
  form.reset();
  emit("update:open", false);
};
</script>
