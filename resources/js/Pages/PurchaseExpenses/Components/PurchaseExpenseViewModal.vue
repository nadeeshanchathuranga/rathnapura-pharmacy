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
              class="w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-gray-50 shadow-xl rounded-2xl border border-gray-200"
            >
              <!-- Header -->
              <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                <DialogTitle
                  as="h3"
                  class="text-2xl font-bold text-blue-600"
                >
                View Supplier Payment
                </DialogTitle>
                <button type="button" @click="closeModal" class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-200 rounded-full transition-all duration-200">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <form @submit.prevent="submit" class="mt-4">
                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Amount ({{ page.props.currency || '' }})
                  </label>
                  <input
                    v-model="form.amount"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-lg cursor-not-allowed"
                    disabled
                    readonly
                  />
                </div>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Expense Date
                  </label>
                  <input
                    v-model="expenseDateDisplay"
                    type="text"
                    class="w-full px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-lg cursor-not-allowed"
                    disabled
                    readonly
                  />
                </div>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Supplier Due Date
                  </label>
                  <input
                    :value="supplierDueDateDisplay"
                    type="text"
                    class="w-full px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-lg cursor-not-allowed"
                    disabled
                    readonly
                  />
                </div>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Payment Type
                  </label>
                  <input
                    v-model="paymentTypeDisplay"
                    type="text"
                    class="w-full px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-lg cursor-not-allowed"
                    disabled
                    readonly
                  />
                </div>

                <!-- Reference field for Card and Cheque -->
                <div v-if="form.payment_type === '1' || form.payment_type === '2'" class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Reference
                  </label>
                  <input
                    v-model="form.reference"
                    type="text"
                    class="w-full px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-lg cursor-not-allowed"
                    disabled
                    readonly
                  />
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                  <button
                    type="button"
                    @click="closeModal"
                    class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-[5px] hover:bg-blue-700 transition-all duration-200"
                  >
                    Close
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
import { ref, watch, onUnmounted, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';

const props = defineProps({
  show: Boolean,
  expense: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
  amount: '',
  expense_date: '',
  payment_type: '',
  card_type: '',
  reference: '',
});

const page = usePage();

// Computed property to display payment type name
const paymentTypeDisplay = computed(() => {
  if (form.payment_type === '1') {
    const cardType = (form.card_type || '').toLowerCase();

    if (cardType === 'visa') {
      return 'Card (Visa)';
    }

    if (cardType === 'mastercard') {
      return 'Card (MasterCard)';
    }

    return 'Card';
  }

  const types = {
    '0': 'Cash',
    '2': 'Cheque',
  };
  return types[form.payment_type] || 'N/A';
});

// Computed property to display expense date in readable format
const expenseDateDisplay = computed(() => {
  if (!form.expense_date) return 'N/A';
  return new Date(form.expense_date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
});

const supplierDueDateDisplay = computed(() => {
  const dueDate = props.expense?.supplier?.due_date;
  if (!dueDate) return 'N/A';
  return new Date(dueDate).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
});

const closeModal = () => {
  emit('close');
  form.reset();
  form.clearErrors();
};

watch(() => props.expense, (expense) => {
  if (expense) {
    form.amount = expense.amount;
    form.expense_date = expense.expense_date;
    form.payment_type = expense.payment_type.toString();
    form.card_type = expense.card_type || '';
    form.reference = expense.reference || '';
  }
}, { immediate: true });

watch(() => props.show, (value) => {
  if (value) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
});

// Cleanup on unmount
onUnmounted(() => {
    document.body.style.overflow = '';
});
</script>
