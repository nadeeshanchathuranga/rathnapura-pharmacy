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
                ✨ Add New Discount
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
                    Discount Name <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    :class="{ 'border-red-500': validationErrors.name || form.errors.name }"
                    required
                    pattern="^[A-Za-z\s]+$"
                    @input="onDiscountNameInput"
                    title="Only alphabetic characters and spaces are allowed."
                  />
                  <p v-if="validationErrors.name" class="mt-1 text-sm text-red-500">
                    {{ validationErrors.name }}
                  </p>
                  <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                    {{ form.errors.name }}
                  </p>
                </div>

                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Type <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="form.type"
                    class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    :class="{ 'border-red-500': validationErrors.type || form.errors.type }"
                    required
                  >
                    <option value="">Select Type</option>
                    <option value="0">Percentage (%)</option>
                    <option value="1">Fixed Amount ({{ currencySymbol || 'Rs' }})</option>
                  </select>
                  <p v-if="validationErrors.type" class="mt-1 text-sm text-red-500">
                    {{ validationErrors.type }}
                  </p>
                  <p v-if="form.errors.type" class="mt-1 text-sm text-red-500">
                    {{ form.errors.type }}
                  </p>
                </div>

                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Value <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.value"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    :class="{ 'border-red-500': validationErrors.value || form.errors.value }"
                    required
                  />
                  <p v-if="validationErrors.value" class="mt-1 text-sm text-red-500">
                    {{ validationErrors.value }}
                  </p>
                  <p v-if="form.errors.value" class="mt-1 text-sm text-red-500">
                    {{ form.errors.value }}
                  </p>
                </div>

                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Start Date
                  </label>
                  <input
                    v-model="form.start_date"
                    type="date"
                    class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                  />
                  <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-500">
                    {{ form.errors.start_date }}
                  </p>
                </div>

                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    End Date
                  </label>
                  <input
                    v-model="form.end_date"
                    type="date"
                    class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                  />
                  <p v-if="form.errors.end_date" class="mt-1 text-sm text-red-500">
                    {{ form.errors.end_date }}
                  </p>
                </div>

                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Status <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="form.status"
                    class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    :class="{ 'border-red-500': validationErrors.status || form.errors.status }"
                    required
                  >
                    <option value="">Select Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                  </select>
                  <p v-if="validationErrors.status" class="mt-1 text-sm text-red-500">
                    {{ validationErrors.status }}
                  </p>
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
                    {{ form.processing ? '✨ Creating...' : '✨ Create Discount' }}
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
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
  DialogTitle,
} from '@headlessui/vue';
import { logActivity } from '@/composables/useActivityLog';

const props = defineProps({
  open: Boolean,
});

const page = usePage();
const currencySymbol = computed(() => page.props.currencySymbol?.symbol || 'Rs');

const emit = defineEmits(['update:open']);

const validationErrors = ref({});

const form = useForm({
  name: '',
  type: '0',
  value: 0,
  start_date: '',
  end_date: '',
  status: '1',
});

// Only allow alphabetic characters and spaces in discount name
const onDiscountNameInput = (e) => {
  e.target.value = e.target.value.replace(/[^A-Za-z\s]/g, "");
  form.name = e.target.value;
};

const validateForm = () => {
  validationErrors.value = {};

  if (!form.name || form.name.trim() === '') {
    validationErrors.value.name = 'Discount name is required';
  } else if (!/^[A-Za-z\s]+$/.test(form.name)) {
    validationErrors.value.name = 'Only alphabetic characters and spaces are allowed';
  }

  if (!form.type) {
    validationErrors.value.type = 'Type is required';
  }

  if (form.value === '' || form.value === null || form.value === undefined || form.value < 0) {
    validationErrors.value.value = 'Value is required and must be greater than or equal to 0';
  }

  if (!form.status) {
    validationErrors.value.status = 'Status is required';
  }

  return Object.keys(validationErrors.value).length === 0;
};

const submit = () => {
  if (!validateForm()) {
    return;
  }

  form.post(route('discounts.store'), {
    onSuccess: async () => {
      await logActivity('create', 'discounts', {
        discount_name: form.name,
        discount_type: form.type
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
  validationErrors.value = {};
};
</script>
