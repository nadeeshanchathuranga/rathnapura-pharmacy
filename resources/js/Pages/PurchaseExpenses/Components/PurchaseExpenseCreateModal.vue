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
              class="w-full max-w-4xl p-6 overflow-hidden text-left align-middle transition-all transform bg-gray-50 shadow-xl rounded-2xl border border-gray-200"
            >
              <!-- Header -->
              <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                <DialogTitle
                  as="h3"
                  class="text-2xl font-bold text-blue-600"
                >
                  ✨ Add New Supplier Payment
                </DialogTitle>
                <button type="button" @click="closeModal" class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-200 rounded-full transition-all duration-200">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <form @submit.prevent="submit" class="mt-4">
                <!-- Display Fields from Other Table (Read-only) -->
                <div class="mb-4 p-4 bg-white rounded-xl border border-gray-200 shadow-sm">
                  <h4 class="text-lg font-semibold text-blue-600 mb-3 flex items-center gap-2">
                    💼 Supplier Information
                  </h4>

                  <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                      <label class="block mb-2 text-sm font-medium text-gray-700">
                        Supplier <span class="text-red-500">*</span>
                      </label>
                      <select
                        v-model="selectedSupplierId"
                        @change="onSupplierChange"
                        class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required
                      >
                        <option value="">Select Supplier</option>
                      <option
  v-for="supplier in suppliers"
  :key="supplier.id"
  :value="supplier.id"
>
  {{ supplier.name }} — 📞 {{ supplier.phone_number }}
</option>

                      </select>
                    </div>

                    <div>
                      <label class="block mb-2 text-sm font-medium text-gray-700">
                        Transaction Due Date
                      </label>
                      <input
                        type="text"
                        :value="formatDate(supplierData.transaction_due_date || supplierData.supplier_due_date)"
                        class="w-full px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-lg"
                        readonly
                      />
                      <p class="mt-1 text-xs text-gray-500">
                        {{ supplierData.grn_no ? `Linked GRN: ${supplierData.grn_no}` : 'Linked GRN: N/A' }}
                      </p>
                    </div>

                    <div>
                      <label class="block mb-2 text-sm font-medium text-gray-700">
                        Total Amount ({{ page.props.currency || '' }})
                      </label>
                      <input
                        type="text"
                        :value=" formatAmount(supplierData.total_amount)"
                        class="w-full px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-lg"
                        readonly
                      />
                    </div>

                    <div>
                      <label class="block mb-2 text-sm font-medium text-gray-700">
                        Paid ({{ page.props.currency || '' }})
                      </label>
                      <input
                        type="text"
                        :value="formatAmount(supplierData.paid)"
                        class="w-full px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-lg"
                        readonly
                      />
                    </div>

                    <div class="col-span-2">
                      <label class="block mb-2 text-sm font-medium text-gray-700">
                        Balance ({{ page.props.currency || '' }})
                      </label>
                      <input
                        type="text"
                        :value="formatAmount(supplierData.balance)"
                        class="w-full px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-lg font-semibold"
                        readonly
                      />
                    </div>
                  </div>
                </div>

                <!-- Expense Entry Fields (To be submitted) -->
                <div class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm mb-4">
                  <h4 class="text-lg font-semibold text-blue-600 mb-3 flex items-center gap-2">
                    💵 Payment Details
                  </h4>
                  <div class="grid grid-cols-2 gap-4">
                    <div class="mb-4">
                      <label class="block mb-2 text-sm font-medium text-gray-700">
                        Amount ({{ page.props.currency || '' }})<span class="text-red-500">*</span>
                      </label>
                      <input
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="0"
                        :max="maxAmount"
                        @input="handleAmountInput"
                        class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required
                      />
                      <p v-if="form.errors.amount" class="mt-1 text-sm text-red-500">
                        {{ form.errors.amount }}
                      </p>
                    </div>

                    <div class="mb-4">
                      <label class="block mb-2 text-sm font-medium text-gray-700">
                        Date <span class="text-red-500">*</span>
                      </label>
                      <input
                        v-model="form.expense_date"
                        type="date"
                        class="w-full px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-lg"
                        required
                        readonly
                        tabindex="-1"
                        @keydown.prevent
                      />
                      <p v-if="form.errors.expense_date" class="mt-1 text-sm text-red-500">
                        {{ form.errors.expense_date }}
                      </p>
                    </div>

                    <div class="mb-4">
                      <label class="block mb-2 text-sm font-medium text-gray-700">
                        Payment Type <span class="text-red-500">*</span>
                      </label>
                      <select
                        v-model="form.payment_type"
                        class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required
                      >
                        <option value="">Select Payment Type</option>
                        <option value="0">Cash</option>
                        <option value="1">Card</option>
                        <option value="2">Cheque</option>

                      </select>
                      <p v-if="form.errors.payment_type" class="mt-1 text-sm text-red-500">
                        {{ form.errors.payment_type }}
                      </p>
                    </div>

                    <div v-if="form.payment_type === '1'" class="mb-4">
                      <label class="block mb-2 text-sm font-medium text-gray-700">
                        Card Type <span class="text-red-500">*</span>
                      </label>
                      <select
                        v-model="form.card_type"
                        class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required
                      >
                        <option value="">Select Card Type</option>
                        <option value="visa">Visa</option>
                        <option value="mastercard">MasterCard</option>
                      </select>
                      <p v-if="form.errors.card_type" class="mt-1 text-sm text-red-500">
                        {{ form.errors.card_type }}
                      </p>
                    </div>

                    <!-- Reference field for Card and Cheque -->
                    <div v-if="form.payment_type === '1' || form.payment_type === '2'" class="mb-4">
                      <label class="block mb-2 text-sm font-medium text-gray-700">
                        Reference <span class="text-red-500">*</span>
                      </label>
                      <input
                        v-model="form.reference"
                        type="text"
                        class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :required="form.payment_type === '1' || form.payment_type === '2'"
                        placeholder="Enter card or cheque reference number"
                      />
                      <p v-if="form.errors.reference" class="mt-1 text-sm text-red-500">
                        {{ form.errors.reference }}
                      </p>
                    </div>
                  </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                  <button
                    type="button"
                    @click="closeModal"
                    class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-[5px] hover:bg-gray-50 transition-all duration-200"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="form.processing || Number(form.amount || 0) > Number(maxAmount) || Number(form.amount || 0) <= 0"
                    class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-[5px] hover:bg-blue-700 disabled:opacity-50 transition-all duration-200"
                  >
                    {{ form.processing ? 'Creating...' : 'Create  Supplier Payment' }}
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
import { useForm } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { logActivity } from '@/composables/useActivityLog';
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue';

const props = defineProps({
  show: Boolean,
  suppliers: {
    type: Array,
    default: () => [],
  },
  supplierData: {
    type: Object,
    default: () => ({
      supplier: '',
      supplier_due_date: null,
      transaction_due_date: null,
      grn_no: null,
      grn_date: null,
      total_amount: 0,
      paid: 0,
      balance: 0,
    }),
  },
});

const emit = defineEmits(['close', 'supplier-change']);

const selectedSupplierId = ref('');

const form = useForm({
  amount: '',
  expense_date: new Date().toISOString().split('T')[0],
  payment_type: '',
  card_type: '',
  supplier_id: '',
  reference: '',
});

const page = usePage();

// Max amount user can pay is the current supplier balance
const maxAmount = computed(() => {
  const bal = Number(props.supplierData?.balance ?? 0);
  return isNaN(bal) ? 0 : bal;
});

const handleAmountInput = () => {
  const val = Number(form.amount || 0);
  const max = Number(maxAmount.value || 0);
  if (val > max) {
    form.amount = max.toFixed(2);
    form.setError('amount', `Amount cannot exceed ${formatAmount(max)}`);
  } else if (val < 0) {
    form.amount = '0';
    form.setError('amount', 'Amount cannot be negative');
  } else {
    // Clear only the amount error if it was previously set
    if (form.errors.amount) {
      form.clearErrors('amount');
    }
  }
};

const closeModal = () => {
  emit('close');
  form.reset();
  form.clearErrors();
  selectedSupplierId.value = '';
};

const submit = () => {
  // Guard: prevent submitting amount greater than balance
  const max = Number(maxAmount.value || 0);
  const amt = Number(form.amount || 0);
  if (amt > max) {
    form.setError('amount', `Amount cannot exceed ${formatAmount(max)}`);
    return;
  }

  form.post(route('purchase-expenses.store'), {
    onSuccess: async () => {
      // Log create activity
      await logActivity('create', 'expenses', {
        supplier_id: form.supplier_id,
        expense_date: form.expense_date,
        amount: form.amount,
        payment_type: form.payment_type,
        card_type: form.card_type || null,
      });
      // Close modal and redirect/refresh to Supplier Payment page
      emit('close');
      // Optionally, force reload to ensure data is up to date
      window.location.href = route('purchase-expenses.index');
    },
  });
};

const onSupplierChange = () => {
  if (selectedSupplierId.value) {
    form.supplier_id = selectedSupplierId.value;
    emit('supplier-change', selectedSupplierId.value);
  }
};

const formatAmount = (amount) => {
  if (!amount) return '0.00';
  return parseFloat(amount).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

watch(() => props.show, (value) => {
  if (value) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
    form.reset();
    form.clearErrors();
    selectedSupplierId.value = '';
  }
});

watch(
  () => form.payment_type,
  (value) => {
    if (value !== '1') {
      form.card_type = '';
      form.clearErrors('card_type');
    }
  }
);

// Cleanup on unmount
onUnmounted(() => {
    document.body.style.overflow = '';
});
</script>
