<template>
  <TransitionRoot appear :show="open" as="template">
    <Dialog as="div" @close="closeModal" class="relative z-10">
      <!-- Backdrop -->
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
        <div class="flex min-h-full items-center justify-center p-4 text-center">
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
              class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all"
            >
              <DialogTitle
                as="h3"
                class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent"
              >
              Edit Supplier
              </DialogTitle>

              <!-- FORM -->
              <form @submit.prevent="submit" class="mt-4 space-y-5">
                <!-- Small description -->
                <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                  <p class="text-sm text-gray-600">
                    Update the supplier details. Fields marked with
                    <span class="text-red-500">*</span> are required.
                  </p>
                </div>

                <!-- Supplier Name -->
                <div class="space-y-1.5">
                  <label class="block text-sm font-medium text-gray-800">
                    Supplier Name <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm
                           placeholder:text-gray-400 focus:outline-none focus:ring-2
                           focus:ring-blue-500 focus:border-blue-500"
                    placeholder="e.g. Global Trading Pvt Ltd"
                    required
                  />
                  <p v-if="form.errors.name" class="text-xs text-red-600">
                    {{ form.errors.name }}
                  </p>
                </div>

                <!-- Email & phone_number -->
                <div class="grid gap-4 md:grid-cols-2">
                  <!-- Email -->
                  <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-800">
                      Email
                    </label>
                    <input
                      v-model="form.email"
                      type="email"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm
                             placeholder:text-gray-400 focus:outline-none focus:ring-2
                             focus:ring-blue-500 focus:border-blue-500"
                      placeholder="name@example.com"
                    />
                    <p v-if="form.errors.email" class="text-xs text-red-600">
                      {{ form.errors.email }}
                    </p>
                  </div>

                  <!-- phone_number -->
                  <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-800">
                      Phone Number
                    </label>
                    <input
                      v-model="form.phone_number"
                      type="text"
                      maxlength="10"
                      @input="form.phone_number = form.phone_number.replace(/[^0-9]/g, '')"
                      class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm
                             placeholder:text-gray-400 focus:outline-none focus:ring-2
                             focus:ring-blue-500 focus:border-blue-500"
                      placeholder="07XXXXXXXX"
                    />
                    <p v-if="form.errors.phone_number" class="text-xs text-red-600">
                      {{ form.errors.phone_number }}
                    </p>
                  </div>
                </div>

                <!-- Address -->
                <div class="space-y-1.5">
                  <label class="block text-sm font-medium text-gray-800">
                    Address
                  </label>
                  <textarea
                    v-model="form.address"
                    rows="3"
                    class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm
                           placeholder:text-gray-400 focus:outline-none focus:ring-2
                           focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Street, City, Country"
                  ></textarea>
                  <p v-if="form.errors.address" class="text-xs text-red-600">
                    {{ form.errors.address }}
                  </p>
                </div>

                <!-- Status -->
                <div class="space-y-1.5">
                  <label class="block text-sm font-medium text-gray-800">
                    Status <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="form.status"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    required
                  >
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                  </select>
                  <p v-if="form.errors.status" class="text-xs text-red-600">
                    {{ form.errors.status }}
                  </p>
                </div>

                <!-- Actions -->
                <div class="mt-2 flex items-center justify-end space-x-3 border-t border-gray-100 pt-4">
                  <button
                    type="button"
                    @click="closeModal"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700
                           hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-gray-300"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm
                           hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-500
                           disabled:cursor-not-allowed disabled:opacity-60"
                  >
                    {{ form.processing ? 'Updating...' : 'Update Supplier' }}
                  </button>
                </div>
              </form>
              <!-- /FORM -->
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { watch } from 'vue';
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
  open: {
    type: Boolean,
    default: false,
  },
  supplier: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['update:open']);

const form = useForm({
  name: '',
  email: '',
  phone_number: '',
  address: '',
  status: '1',
});

// When supplier changes (or first loads), fill the form
watch(
  () => props.supplier,
  (supplier) => {
    if (supplier) {
      form.name = supplier.name || '';
      form.email = supplier.email || '';
      form.phone_number = (supplier.phone_number || '').replace(/[^0-9]/g, '');
      form.address = supplier.address || '';
      form.status = String(supplier.status ?? '1');
    }
  },
  { immediate: true }
);

const validateForm = () => {
  form.clearErrors();

  if (!form.name) {
    form.errors.name = 'Supplier name is required.';
  }

  if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    form.errors.email = 'Please enter a valid email address.';
  }

  if (form.phone_number && !/^[0-9]{10}$/.test(form.phone_number)) {
    form.errors.phone_number = 'Phone number must be 10 digits.';
  }

  if (form.status !== '0' && form.status !== '1') {
    form.errors.status = 'Status must be Active or Inactive.';
  }

  return Object.keys(form.errors).length === 0;
};

const submit = () => {
  if (!validateForm()) return;

  form.put(route('suppliers.update', props.supplier.id), {
    onSuccess: async () => {
      await logActivity('update', 'suppliers', {
        supplier_id: props.supplier.id,
        supplier_name: form.name,
        old_name: props.supplier.name,
        email: form.email,
        phone_number: form.phone_number,
        address: form.address,
        status: form.status,
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
