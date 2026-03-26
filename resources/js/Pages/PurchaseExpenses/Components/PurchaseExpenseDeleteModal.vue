<template>
  <TransitionRoot appear :show="show" as="template">
    <Dialog as="div" @close="closeModal" class="relative z-50">
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
              class="w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl border border-gray-200"
            >
              <DialogTitle
                as="h3"
                class="text-2xl font-bold text-red-600"
              >
                ⚠️ Delete Supplier Payment

              </DialogTitle>

              <div class="mt-4">
                <p class="text-sm text-gray-700">
                  Are you sure you want to delete the Supplier Payment

                  <span class="font-semibold text-gray-900">"{{ expense?.title }}"</span>
                  with amount
                  <span class="font-semibold text-gray-900">Rs. {{ formatAmount(expense?.amount) }}</span>?
                  This action cannot be undone.
                </p>
              </div>

              <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 mt-6">
                <button
                  type="button"
                  @click="closeModal"
                  class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-[5px] hover:bg-gray-50 transition-all duration-200"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  @click="deleteExpense"
                  :disabled="form.processing"
                  class="px-6 py-2.5 text-sm font-medium text-white bg-red-600 rounded-[5px] hover:bg-red-700 disabled:opacity-50 transition-all duration-200"
                >
                  {{ form.processing ? 'Deleting...' : 'Delete' }}
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
import { watch, onUnmounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';

const props = defineProps({
  show: Boolean,
  expense: Object,
});

const emit = defineEmits(['close']);

const form = useForm({});

const closeModal = () => {
  emit('close');
};

const deleteExpense = () => {
  form.delete(route('purchase-expenses.destroy', props.expense.id), {
    onSuccess: () => {
      closeModal();
    },
  });
};

const formatAmount = (amount) => {
  if (!amount) return '0.00';
  return parseFloat(amount).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

// Body scroll lock
watch(
    () => props.show,
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
