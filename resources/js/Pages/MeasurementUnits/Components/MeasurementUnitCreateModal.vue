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
              class="w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-gradient-to-br from-gray-50 to-blue-50 shadow-2xl rounded-2xl"
            >
              <!-- Modal Header -->
              <div class="flex items-center justify-between mb-6">
                <DialogTitle
                  as="h3"
                  class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent"
                >
                  ✨ Add Measurement Unit
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
                <div class="bg-white/40 backdrop-blur-xl rounded-xl p-4 shadow-lg border border-white/60 mb-6">
                  <div class="space-y-4">
                    <!-- Unit Name -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Unit Name <span class="text-red-500">*</span>
                      </label>
                      <input
                        v-model="form.name"
                        type="text"
                        class="w-full px-3 py-2 text-sm text-gray-800 bg-white/60 backdrop-blur-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Enter unit name"
                        required
                        pattern="^[A-Za-z\s]+$"
                        @input="onUnitNameInput"
                        title="Only alphabetic characters and spaces are allowed."
                      />
                      <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                        {{ form.errors.name }}
                      </p>
                    </div>

                    <!-- Symbol -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Symbol <span class="text-red-500">*</span>
                      </label>
                      <input
                        v-model="form.symbol"
                        type="text"
                        class="w-full px-3 py-2 text-sm text-gray-800 bg-white/60 backdrop-blur-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Enter symbol"
                        required
                      />
                      <p v-if="form.errors.symbol" class="mt-1 text-sm text-red-500">
                        {{ form.errors.symbol }}
                      </p>
                    </div>

                    <!-- Status -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Status <span class="text-red-500">*</span>
                      </label>
                      <select
                        v-model="form.status"
                        class="w-full px-3 py-2 text-sm text-gray-800 bg-white/60 backdrop-blur-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required
                      >
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                      </select>
                      <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">
                        {{ form.errors.status }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Modal Buttons -->
                <div class="flex gap-3 justify-end">
                  <button
                    type="button"
                    @click="closeModal"
                    class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white/60 border border-gray-300 rounded-[5px] hover:bg-gray-100 hover:shadow-md transition-all duration-300"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-[5px] hover:bg-blue-700 hover:shadow-lg hover:scale-105 transition-all duration-300 disabled:opacity-50"
                  >
                    {{ form.processing ? '💾 Creating...' : '💾 Save Unit' }}
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
// Only allow alphabetic characters and spaces in unit name
const onUnitNameInput = (e) => {
  e.target.value = e.target.value.replace(/[^A-Za-z\s]/g, "");
  form.name = e.target.value;
};
import { useForm } from '@inertiajs/vue3';
import { logActivity } from '@/composables/useActivityLog';
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
  DialogTitle,
} from '@headlessui/vue';

const props = defineProps({
  open: Boolean,
});

const emit = defineEmits(['update:open']);

const form = useForm({
  name: '',
  symbol: '',
  status: '1',
});

const submit = () => {
  form.post(route('measurement-units.store'), {
    onSuccess: async () => {
      // Log create activity
      await logActivity('create', 'measurement_units', {
        unit_name: form.name,
        symbol: form.symbol,
        status: form.status,
      });
      
      closeModal();
      form.reset();
    },
  });
};

const closeModal = () => {
  emit('update:open', false);
  form.reset();
  form.clearErrors();
};
</script>
