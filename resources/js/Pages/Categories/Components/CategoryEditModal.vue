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
                Edit Category
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
                    <!-- Category Name -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Category Name <span class="text-red-500">*</span>
                      </label>
                      <input
                        v-model="form.name"
                        type="text"
                        class="w-full px-3 py-2 text-sm text-gray-800 bg-white/60 backdrop-blur-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Enter category name"
                        required
                      />
                      <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                        {{ form.errors.name }}
                      </p>
                    </div>

                    <!-- Parent Category -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Parent Category
                      </label>
                      <select
                        v-model="form.parent_id"
                        class="w-full px-3 py-2 text-sm text-gray-800 bg-white/60 backdrop-blur-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      >
                        <option :value="null">None (Main Category)</option>
                        <option
                          v-for="cat in availableCategories"
                          :key="cat.id"
                          :value="cat.id"
                        >
                          {{ cat.hierarchy_string ? cat.hierarchy_string + ' → ' + cat.name : cat.name }}
                        </option>
                      </select>
                      <p v-if="form.errors.parent_id" class="mt-1 text-sm text-red-500">
                        {{ form.errors.parent_id }}
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
                    {{ form.processing ? ' Updating...' : ' Update Category' }}
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
import { computed, watch } from 'vue';
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
  category: Object,
  categories: Array,
});

const emit = defineEmits(['update:open']);

const availableCategories = computed(() => {
  return props.categories.filter(cat => cat.status != 2 && cat.id !== props.category?.id);
});

const form = useForm({
  name: '',
  parent_id: null,
  status: '1',
});

watch(() => props.category, (newCategory) => {
  if (newCategory) {
    form.name = newCategory.name;
    form.parent_id = newCategory.parent_id;
    form.status = String(newCategory.status);
  }
}, { immediate: true });

const submit = () => {
  form.put(route('categories.update', props.category.id), {
    onSuccess: async () => {
      // Log update activity
      await logActivity('update', 'categories', {
        category_id: props.category.id,
        category_name: form.name,
        old_name: props.category.name,
        parent_id: form.parent_id,
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
