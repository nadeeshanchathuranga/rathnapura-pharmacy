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
              class="w-full max-w-md p-6 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl"
            >
              <DialogTitle
                as="h3"
                class="text-lg font-medium leading-6 text-blue-700"
              >
              Edit User
              </DialogTitle>

              <form @submit.prevent="submit" class="mt-4">
                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Name *
                  </label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                  <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                    {{ form.errors.name }}
                  </p>
                </div>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Email *
                  </label>
                  <input
                    v-model="form.email"
                    type="email"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                  <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                    {{ form.errors.email }}
                  </p>
                </div>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    Password (leave blank to keep current)
                  </label>
                  <input
                    v-model="form.password"
                    type="password"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                  <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                    {{ form.errors.password }}
                  </p>
                </div>

                <div class="mb-4">
                  <label class="block mb-2 text-sm font-medium text-gray-700">
                    User Type 
                  </label>
                  <select
                    v-model="form.role"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="0">Admin</option>
                    <option value="1">Manager</option>
                    <option value="2">Cashier</option>
                    <option value="3">Stock Keeper</option>
                  </select>
                  <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">
                    {{ form.errors.role }}
                  </p>
                </div>

                <div class="flex justify-end mt-6 space-x-3">
                  <button
                    type="button"
                    @click="closeModal"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50"
                  >
                    {{ form.processing ? 'Updating...' : 'Update User' }}
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
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
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
  user: Object,
});

const emit = defineEmits(['update:open']);

const form = useForm({
  name: '',
  email: '',
  password: '',
  role: '0',
});

watch(() => props.user, (newUser) => {
  if (newUser) {
    form.name = newUser.name;
    form.email = newUser.email;
    form.password = '';
    form.role = String(newUser.role);
  }
}, { immediate: true });

const submit = () => {
  form.put(route('users.update', props.user.id), {
    onSuccess: async () => {
      await logActivity('update', 'users', {
        user_id: props.user.id,
        user_name: form.name,
        user_email: form.email
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
