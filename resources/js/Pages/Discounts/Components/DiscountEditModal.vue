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
                Edit Discount
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
                    Discount Name *
                  </label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    required
                  />
                  <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                    {{ form.errors.name }}
                  </p>
                </div>

                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Type 
                  </label>
                  <select
                    v-model="form.type"
                    class="w-full px-4 py-2.5 bg-gray-100 text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all cursor-not-allowed"
                    disabled
                  >
                    <option value="0">Percentage (%)</option>
                    <option value="1">Fixed Amount ({{ currencySymbol || 'Rs' }})</option>
                  </select>
                  <p v-if="form.errors.type" class="mt-1 text-sm text-red-500">
                    {{ form.errors.type }}
                  </p>
                </div>

                <div>
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Value *
                  </label>
                  <input
                    v-model="form.value"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full px-4 py-2.5 bg-gray-100 text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all cursor-not-allowed"
                    disabled
                  />
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
                    Status *
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
                    {{ form.processing ? 'Updating...' : 'Update Discount' }}
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
import { watch, computed } from 'vue';
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
  discount: Object,
});

const page = usePage();
const currencySymbol = computed(() => page.props.currencySymbol?.symbol || 'Rs');

const emit = defineEmits(['update:open']);

const form = useForm({
  name: '',
  type: '0',
  value: 0,
  start_date: '',
  end_date: '',
  status: '1',
});

watch(() => props.discount, (newDiscount) => {
  if (newDiscount) {
    form.name = newDiscount.name;
    form.type = String(newDiscount.type);
    form.value = newDiscount.value;
    form.start_date = newDiscount.start_date || '';
    form.end_date = newDiscount.end_date || '';
    form.status = String(newDiscount.status);
  }
}, { immediate: true });

const submit = () => {
  form.put(route('discounts.update', props.discount.id), {
    onSuccess: async () => {
      await logActivity('update', 'discounts', {
        discount_id: props.discount.id,
        discount_name: form.name
      });
      closeModal();
    },
  });
};

const closeModal = () => {
  emit('update:open', false);
  form.clearErrors();
};
</script>
