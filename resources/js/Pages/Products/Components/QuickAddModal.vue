<!-- resources/js/Components/QuickAddModal.vue -->
<template>
  <Modal :show="show" @close="close" max-width="md">
    <div class="p-8 bg-gray-50">
      <!-- Modal Header with Close Button -->
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
          Add New
          {{
            type === "unit"
              ? "Measurement Unit"
              : type.charAt(0).toUpperCase() + type.slice(1)
          }}
        </h2>
        <button
          type="button"
          @click="close"
          class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-200 rounded-full transition-all duration-200"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-5 h-5"
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
        <!-- Form Container -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 mb-6">
          <!-- Name Field (Required for all types) -->
          <div class="mb-4">
            <label class="block mb-2 text-sm font-medium text-gray-700">
              Name <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              autofocus
              class="w-full px-4 py-2.5 text-gray-800 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :placeholder="namePlaceholder"
            />
          </div>

          <!-- Status Field (for brand and type) -->
          <div v-if="type === 'brand' || type === 'type'" class="mb-4">
            <label class="block mb-2 text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
            <select
              v-model="form.status"
              required
              class="w-full px-4 py-2.5 text-gray-800 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>

          <!-- Parent Category Field (Only for category type) -->
          <div v-if="type === 'category' && parentCategories && parentCategories.length > 0" class="mb-4">
            <label class="block mb-2 text-sm font-medium text-gray-700">Parent Category</label>
            <select
              v-model="form.parent_id"
              class="w-full px-4 py-2.5 text-gray-800 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">None (Main Category)</option>
              <option v-for="cat in parentCategories" :key="cat.id" :value="cat.id">
                {{ cat.hierarchy_string ? cat.hierarchy_string + ' → ' + cat.name : cat.name }}
              </option>
            </select>
          </div>

          <!-- Status Field (for category type) -->
          <div v-if="type === 'category'" class="mb-4">
            <label class="block mb-2 text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
            <select
              v-model="form.status"
              required
              class="w-full px-4 py-2.5 text-gray-800 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>

          <!-- Symbol Field (Only for measurement units) -->
          <div v-if="type === 'unit'" class="mb-4">
            <label class="block mb-2 text-sm font-medium text-gray-700">
              Symbol <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.symbol"
              type="text"
              required
              class="w-full px-4 py-2.5 text-gray-800 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :placeholder="symbolPlaceholder"
            />
          </div>

          <!-- Status Field (Only for measurement units) -->
          <div v-if="type === 'unit'" class="mb-4">
            <label class="block mb-2 text-sm font-medium text-gray-700">Status</label>
            <div class="flex gap-6">
              <label class="flex items-center cursor-pointer">
                <input
                  type="radio"
                  v-model="form.status"
                  value="1"
                  class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 focus:ring-2 mr-2"
                />
                <span class="text-sm font-medium text-gray-800">Active</span>
              </label>
              <label class="flex items-center cursor-pointer">
                <input
                  type="radio"
                  v-model="form.status"
                  value="0"
                  class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 focus:ring-2 mr-2"
                />
                <span class="text-sm font-medium text-gray-800">Inactive</span>
              </label>
            </div>
          </div>

          <!-- Tax Fields (Only for tax type) -->
          <div v-if="type === 'tax'" class="space-y-4">
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
                required
                class="w-full px-4 py-2.5 text-gray-800 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="e.g. 15.5"
              />
            </div>

            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Type <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.tax_type"
                required
                class="w-full px-4 py-2.5 text-gray-800 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Select Type</option>
                <option value="0">Inclusive</option>
                <option value="1">Exclusive</option>
              </select>
            </div>

            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Status <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.status"
                required
                class="w-full px-4 py-2.5 text-gray-800 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Select Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>

          <!-- Discount Fields (Only for discount type) -->
          <div v-if="type === 'discount'" class="space-y-4">
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Type <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.discount_type"
                required
                class="w-full px-4 py-2.5 text-gray-800 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Select Type</option>
                <option value="0">Percentage (%)</option>
                <option value="1">Fixed Amount</option>
              </select>
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
                required
                class="w-full px-4 py-2.5 text-gray-800 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="e.g. 10"
              />
            </div>

            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Start Date
              </label>
              <input
                v-model="form.start_date"
                type="date"
                class="w-full px-4 py-2.5 text-gray-800 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                End Date
              </label>
              <input
                v-model="form.end_date"
                type="date"
                class="w-full px-4 py-2.5 text-gray-800 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Status <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.status"
                required
                class="w-full px-4 py-2.5 text-gray-800 bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Select Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Form Action Buttons -->
        <div class="flex justify-end space-x-3">
          <button
            type="button"
            @click="close"
            class="px-6 py-2.5 rounded-full font-medium text-sm bg-gray-500 text-white hover:bg-gray-600 hover:shadow-sm transition-all duration-200"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2.5 rounded-full font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 hover:shadow-sm transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ form.processing ? "Adding..." : "Add" }}
          </button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
/**
 * Quick Add Modal Component Script
 *
 * Handles rapid creation of supporting data without page navigation
 * Dynamically adapts form fields based on data type
 */

import { useForm } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import { computed } from 'vue';

/**
 * Component Props
 * @property {Boolean} show - Modal visibility state
 * @property {String} type - Data type: 'brand', 'category', 'type', or 'unit'
 * @property {String} routeName - Laravel route name for POST submission (e.g., 'brands.store')
 */
const props = defineProps({
  show: Boolean,
  type: String,
  routeName: String,
  parentCategories: {
    type: Array,
    default: () => [],
  },
});

/**
 * Component Emits
 * @event close - Emitted when modal is closed
 * @event created - Emitted with newly created item data
 */
const emit = defineEmits(["close", "created"]);

/**
 * Inertia Form Instance
 * Handles form data and submission state
 */
const form = useForm({
  name: "",
  symbol: "",
  status: "1",
  parent_id: "",
  percentage: "",
  tax_type: "",
  discount_type: "",
  value: "",
  start_date: "",
  end_date: "",
});

// Dynamic placeholders based on current `type`
const namePlaceholder = computed(() => {
  if (props.type === 'brand') return 'Enter brand';
  if (props.type === 'category') return 'Enter category';
  if (props.type === 'type') return 'Enter type';
  if (props.type === 'discount') return 'Enter discount name';
  if (props.type === 'unit') return 'e.g. Carton, Liter';
  return 'Enter name';
});

const symbolPlaceholder = computed(() => {
  if (props.type === 'unit') return 'Enter symbol (e.g. ctn, L, kg)';
  return '';
});

/**
 * Submit Form Handler
 * Posts data to appropriate route and returns new item to parent
 * Removes unnecessary fields for non-unit types to avoid validation errors
 */
const submit = () => {
  // Build payload based on type
  const payload = { name: form.name, status: form.status };

  if (props.type === "unit") {
    payload.symbol = form.symbol;
  } else if (props.type === "tax") {
    payload.percentage = form.percentage;
    payload.type = form.tax_type;  // Map tax_type to type for backend
  } else if (props.type === "discount") {
    payload.type = form.discount_type;  // Map discount_type to type for backend
    payload.value = form.value;
    payload.start_date = form.start_date || null;
    payload.end_date = form.end_date || null;
    payload.status = form.status;
  } else if (props.type === "category") {
    payload.parent_id = form.parent_id || null;
  } else if (props.type === "brand" || props.type === "type") {
    payload.status = form.status;
  }

  form.transform(() => payload).post(route(props.routeName), {
    preserveScroll: true,
    onSuccess: (page) => {
      // Extract newly created item from response props
      const newItem =
        page.props.newUnit ||
        page.props.newBrand ||
        page.props.newCategory ||
        page.props.newType ||
        page.props.newTax ||
        page.props.newDiscount;

      // Pass new item back to parent component
      emit("created", newItem);
      close();
    },
    onError: (errors) => {
      console.error("Error:", errors);
    },
  });
};

/**
 * Close Modal Handler
 * Resets form and emits close event
 */
const close = () => {
  form.reset();
  form.clearErrors();
  emit("close");
};
</script>