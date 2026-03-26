
<template>
  <Modal :show="open" @close="closeModal" max-width="md">
    <div class="p-6 bg-gray-900">
      <!-- Modal Title -->
      <h2 class="mb-4 text-2xl font-bold text-white">Delete Product</h2>
      
      <!-- Confirmation Message with Product Details -->
      <p class="mb-6 text-gray-300" v-if="product">
        Are you sure you want to delete the product
        <span class="font-bold text-white">"{{ product.name }}"</span>
        <span v-if="product.barcode">(Barcode: {{ product.barcode }})</span>?
        This action cannot be undone.
      </p>

      <!-- Action Buttons -->
      <div class="flex justify-end space-x-3">
        <!-- Cancel Button -->
        <button
          type="button"
          @click="closeModal"
          class="px-4 py-2 text-white bg-gray-600 rounded hover:bg-gray-700"
        >
          Cancel
        </button>
        <!-- Delete Confirmation Button -->
        <button
          @click="deleteProduct"
          :disabled="form.processing || !product"
          class="px-4 py-2 text-white bg-red-600 rounded hover:bg-red-700 disabled:opacity-50"
        >
          {{ form.processing ? 'Deleting...' : 'Delete' }}
        </button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
/**
 * Product Delete Modal Component Script
 * 
 * Handles product deletion with confirmation and validation
 * Includes error handling and console logging for debugging
 */

import { useForm } from "@inertiajs/vue3";
import { watch } from "vue";
import Modal from "@/Components/Modal.vue";

/**
 * Component Props
 * @property {Boolean} open - Modal visibility state
 * @property {Object} product - Product object to delete
 */
const props = defineProps({
  open: Boolean,
  product: Object,
});

/**
 * Component Emits
 * @event update:open - Emits boolean to update modal visibility
 */
const emit = defineEmits(["update:open"]);

/**
 * Inertia Form Instance
 * Empty form used for DELETE request
 */
const form = useForm({});

/**
 * Watch Modal Open State
 * Logs modal state and product data for debugging
 */
watch(() => props.open, (newVal) => {
});

/**
 * Delete Product Handler
 * Validates product data and sends DELETE request
 * Closes modal and resets form on success
 */
const deleteProduct = () => {
  // Validate product data exists
  if (!props.product || !props.product.id) {
    console.error('No product selected for deletion');
    return;
  }


  // Send DELETE request to products.destroy route
  form.delete(route("products.destroy", props.product.id), {
    preserveScroll: true,
    onSuccess: () => {
      closeModal();
      form.reset();
    },
    onError: (errors) => {
      console.error("Delete failed:", errors);
    },
  });
};

/**
 * Close Modal Handler
 * Resets form and emits close event
 */
const closeModal = () => {
  form.reset();
  emit("update:open", false);
};
</script>
