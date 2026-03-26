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
        <div class="fixed inset-0 bg-black/25" />
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
              class="w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl"
            >
              <div class="flex justify-between items-center mb-4">
                <DialogTitle
                  as="h3"
                  class="text-2xl font-bold text-blue-700"
                >
                ✨ Add New Tax
                </DialogTitle>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </div>

              <form @submit.prevent="submit" class="space-y-4">
                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Tax Name <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    required
                    pattern="^[A-Za-z\s]+$"
                    @input="onTaxNameInput"
                    title="Only alphabetic characters and spaces are allowed."
                  />
                  <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                    {{ form.errors.name }}
                  </p>
                </div>

                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Percentage <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.percentage"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    required
                  />
                  <p v-if="form.errors.percentage" class="mt-1 text-sm text-red-500">
                    {{ form.errors.percentage }}
                  </p>
                </div>

                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Type <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="form.type"
                    class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    required
                  >
                    <option value="0">Inclusive</option>
                    <option value="1">Exclusive</option>
                  </select>
                  <p v-if="form.errors.type" class="mt-1 text-sm text-red-500">
                    {{ form.errors.type }}
                  </p>
                </div>

                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Status <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="form.status"
                    class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    required
                  >
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                  </select>
                  <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">
                    {{ form.errors.status }}
                  </p>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                  <button
                    type="button"
                    @click="closeModal"
                    class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 hover:border-gray-400 transition-all duration-200"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 transition-all duration-200 disabled:opacity-50"
                  >
                    {{ form.processing ? '✨ Creating...' : '✨ Create Tax' }}
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
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
  DialogTitle,
} from '@headlessui/vue';
import { logActivity } from '@/composables/useActivityLog';

// Only allow alphabetic characters and spaces in tax name
const onTaxNameInput = (e) => {
  e.target.value = e.target.value.replace(/[^A-Za-z\s]/g, "");
  form.name = e.target.value;
};

const props = defineProps({
  open: Boolean,
});

const emit = defineEmits(['update:open']);

const form = useForm({
  name: '',
  percentage: 0,
  type: '0',
  status: '1',
});

const submit = () => {
  form.post(route('taxes.store'), {
    onSuccess: async () => {
      await logActivity('create', 'taxes', {
        tax_name: form.name,
        tax_rate: form.rate
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
