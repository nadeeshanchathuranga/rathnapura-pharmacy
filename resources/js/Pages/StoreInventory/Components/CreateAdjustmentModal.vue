<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    @click.self="close"
  >
    <div
      class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"
    >
      <!-- Modal Header -->
      <div
        class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 rounded-t-2xl flex justify-between items-center"
      >
        <h2 class="text-2xl font-bold">Add Inventory Adjustment</h2>
        <button
          @click="close"
          class="text-white hover:bg-white/20 rounded-lg p-2 transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Modal Body -->
      <form @submit.prevent="submit" class="p-6 space-y-6">
        <!-- Product Selection -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">
            Product <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.product_id"
            @change="onProductChange"
            required
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Select Product</option>
            <option v-for="product in products" :key="product.id" :value="product.id">
              {{ product.name }} ({{ product.barcode || 'No Barcode' }})
            </option>
          </select>
          <p v-if="form.errors.product_id" class="mt-1 text-sm text-red-600">
            {{ form.errors.product_id }}
          </p>
        </div>

        <!-- Current Stock Display -->
        <div v-if="selectedProduct" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-gray-700">Current Store Stock:</span>
            <span class="text-lg font-bold text-blue-700">
              {{ Number(selectedProduct.store_quantity_in_purchase_unit || 0).toFixed(2) }}
              {{ selectedProduct.purchase_unit?.symbol }}
            </span>
          </div>
        </div>

        <!-- Transaction Type -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">
            Transaction Type <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.transaction_type"
            required
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Select Type</option>
            <option value="adjustment">Manual Adjustment</option>
            <option value="physical_count">Physical Count Adjustment</option>
            <option value="damage">Damaged Goods</option>
            <option value="expired">Expired Items</option>
            <option value="return">Supplier Return</option>
            <option value="transfer_in">Transfer In from Other Location</option>
            <option value="transfer_out">Transfer Out to Other Location</option>
          </select>
          <p v-if="form.errors.transaction_type" class="mt-1 text-sm text-red-600">
            {{ form.errors.transaction_type }}
          </p>
        </div>

        <!-- Quantity Change -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">
            Quantity Change <span class="text-red-500">*</span>
          </label>
          <input
            type="number"
            v-model.number="form.quantity_change"
            step="0.01"
            required
            placeholder="Use negative values for reductions (e.g., -10)"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
          <p class="mt-1 text-xs text-gray-500">
            Enter positive values to increase stock, negative values to decrease
          </p>
          <p v-if="form.errors.quantity_change" class="mt-1 text-sm text-red-600">
            {{ form.errors.quantity_change }}
          </p>
        </div>

        <!-- New Stock Preview -->
        <div v-if="selectedProduct && form.quantity_change" class="bg-green-50 border border-green-200 rounded-lg p-4">
          <div class="flex justify-between items-center">
            <span class="text-sm font-medium text-gray-700">New Stock After Adjustment:</span>
            <span class="text-lg font-bold" :class="newQuantity >= 0 ? 'text-green-700' : 'text-red-700'">
              {{ newQuantity.toFixed(2) }} {{ selectedProduct.purchase_unit?.symbol }}
            </span>
          </div>
          <p v-if="newQuantity < 0" class="mt-2 text-sm text-red-600">
            ⚠️ Warning: Adjustment will result in negative inventory!
          </p>
        </div>

        <!-- Transaction Date -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">
            Transaction Date <span class="text-red-500">*</span>
          </label>
          <input
            type="date"
            v-model="form.transaction_date"
            required
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
          <p v-if="form.errors.transaction_date" class="mt-1 text-sm text-red-600">
            {{ form.errors.transaction_date }}
          </p>
        </div>

        <!-- Remarks -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">
            Remarks/Notes
          </label>
          <textarea
            v-model="form.remarks"
            rows="3"
            placeholder="Enter any additional notes or reasons for this adjustment..."
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
          ></textarea>
          <p v-if="form.errors.remarks" class="mt-1 text-sm text-red-600">
            {{ form.errors.remarks }}
          </p>
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-end gap-3 pt-4 border-t">
          <button
            type="button"
            @click="close"
            class="px-6 py-2.5 rounded-lg font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2.5 rounded-lg font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            {{ form.processing ? "Saving..." : "Save Adjustment" }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { useForm } from "@inertiajs/vue3";

export default {
  props: {
    show: Boolean,
    products: Array,
  },
  data() {
    const today = new Date().toISOString().split("T")[0];
    return {
      form: useForm({
        product_id: "",
        transaction_type: "",
        quantity_change: 0,
        transaction_date: today,
        remarks: "",
      }),
      selectedProduct: null,
    };
  },
  computed: {
    newQuantity() {
      if (!this.selectedProduct) return 0;
      const currentQty = Number(this.selectedProduct.store_quantity_in_purchase_unit || 0);
      const change = Number(this.form.quantity_change || 0);
      return currentQty + change;
    },
  },
  methods: {
    onProductChange() {
      this.selectedProduct = this.products.find(
        (p) => p.id === this.form.product_id
      );
    },
    submit() {
      this.form.post(route("store-inventory.store"), {
        onSuccess: () => {
          this.$emit("created");
          this.resetForm();
        },
      });
    },
    close() {
      this.$emit("close");
      this.resetForm();
    },
    resetForm() {
      const today = new Date().toISOString().split("T")[0];
      this.form.reset();
      this.form.transaction_date = today;
      this.selectedProduct = null;
    },
  },
};
</script>
