<template>
  <AppLayout>
    <!-- Main Container -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 p-6">
      <!-- Header Section with Navigation and Actions -->
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
          <!-- Back to Dashboard Button -->
          <button
            @click="$inertia.visit(route('dashboard'))"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-4xl font-bold text-gray-800">Products</h1>
        </div>
        <!-- Add New Product Button -->
        <button
          @click="openCreateModal"
          class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 transition-all duration-300"
        >
          + Add Product
        </button>
      </div>
      <!-- Division Filter (Admin/Backoffice only) -->
      <div v-if="isAdmin" class="mb-4 flex items-center gap-2">
        <span class="text-sm font-medium text-gray-600">Filter by Division:</span>
        <button
          @click="applyDivisionFilter(null)"
          :class="[
            'px-4 py-1.5 rounded-[5px] text-sm font-medium transition-all',
            !divisionFilter ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
          ]"
        >All</button>
        <button
          v-for="div in divisions"
          :key="div.id"
          @click="applyDivisionFilter(div.id)"
          :class="[
            'px-4 py-1.5 rounded-[5px] text-sm font-medium transition-all',
            divisionFilter == div.id ? 'bg-blue-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
          ]"
        >{{ div.name }}</button>
      </div>

      <!-- Products Table Container -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <table class="w-full text-left border-collapse">
          <!-- Table Header -->
          <thead>
            <tr class="border-b-2 border-blue-600">
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">#</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Product Info</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">
                Brand/Category
              </th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-right">
                Price ({{ currencySymbol.currency }})
              </th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Qty
              </th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Status
              </th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Actions
              </th>
            </tr>
          </thead>
          <!-- Table Body - Product Rows -->
          <tbody>
            <tr
              v-for="(product, index) in products.data"
              :key="product.id"
              class="border-b border-gray-200 hover:bg-blue-50/50 transition-colors duration-200"
            >
              <!-- Sequential ID -->
              <td class="px-4 py-4">
                <span
                  class="inline-flex items-center justify-center w-8 h-8 rounded-[10px] bg-blue-100 text-blue-700 font-bold text-sm"
                >
                  {{ (products.current_page - 1) * products.per_page + index + 1 }}
                </span>
              </td>
              <!-- Product Info (Name, Barcode, Code) -->
              <td class="px-4 py-4">
                <div class="space-y-1">
                  <div class="font-semibold text-gray-900">{{ product.name }}</div>
                  <!-- <div class="text-xs text-gray-600">Barcode: {{ product.barcode }}</div> -->
                  <div class="text-xs text-gray-500" v-if="product.code">
                    Code: {{ product.code }}
                  </div>
                </div>
              </td>
              <!-- Brand & Category -->
              <td class="px-4 py-4">
                <div class="space-y-1">
                  <div class="text-sm text-gray-800">
                    {{ product.brand?.name || "N/A" }}
                  </div>
                  <div class="text-xs text-gray-600">
                    {{
                      product?.category?.hierarchy_string
                        ? product.category.hierarchy_string +
                          " → " +
                          product.category.name
                        : product?.category?.name || "N/A"
                    }}
                  </div>
                </div>
              </td>
              <!-- Prices -->
              <td class="px-4 py-4 text-right">
                <div class="space-y-1">
                  <div class="text-sm font-semibold text-blue-700">
                    Retail: {{ Number(product.retail_price ?? 0).toFixed(2) }} <br />
                    Wholesale: {{ Number(product.wholesale_price ?? 0).toFixed(2) }}
                  </div>
                  <!-- <div class="text-xs text-gray-600">
                    Cost: {{ product.purchase_price || "0.00" }}
                  </div> -->
                </div>
              </td>
              <!-- Quantity -->
              <td class="px-4 py-4 text-center">
              <div class="space-y-1">
                <!-- Shop Quantities in Sales Unit (Primary Display) -->
                <div class="flex flex-col gap-1">
                  <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-100 text-green-700 rounded-lg font-bold text-xs">
                    <span class="font-semibold">Shop ({{ product.sales_unit?.symbol || 'Unit' }}):</span>
                    <span>{{ Number(product.shop_quantity_in_sales_unit || 0).toFixed(0) }}</span>
                  </span>
                </div>
                
                <!-- Store Quantities -->
                <div class="flex flex-col gap-1">
                  <!-- Full Boxes (Purchase Unit) -->
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs">
                    <span class="text-[10px] font-semibold">Store ({{ product.purchase_unit?.symbol || 'Box' }}):</span>
                    <span>{{ product.store_quantity_in_purchase_unit }}</span>
                  </span>
                  
                  <!-- Loose Bundles (from opened boxes) -->
                 <span v-if="Number(product.loose_bundles) > 0" 
                    class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs">
                    <span class="text-[10px] font-semibold">
                      + Loose ({{ product.transfer_unit?.symbol || 'Bundle' }}):
                    </span>
                    <span>{{ product.loose_bundles }}</span>
                  </span>

                  <!-- Total Store Quantity in Sales Unit -->
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded text-xs">
                    <span class="text-[10px] font-semibold">Loose  ({{ product.sales_unit?.symbol || 'Unit' }}):</span>
                    <span>{{ product.store_quantity_in_sale_unit }}</span>
                  </span>

                </div>
              </div>
              </td>
              <!-- Product Status Badge -->
              <td class="px-4 py-4 text-center">
                <span
                  :class="{
                    'bg-red-500/90 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs':
                      product.status == 0,
                    'bg-green-500/90 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs':
                      product.status == 1,
                    'bg-blue-500/90 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs':
                      product.status == 2,
                  }"
                >
                  {{
                    product.status == 1
                      ? "Active"
                      : product.status == 0
                      ? "Inactive"
                      : "Default"
                  }}
                </span>
              </td>
              <!-- Action Buttons -->
              <td class="px-4 py-4">
                <div class="flex gap-2 justify-center">
                  <button
                    @click="openViewModal(product)"
                    class="px-4 py-2 text-xs font-medium text-white bg-green-600 rounded-[5px] hover:bg-green-700 hover:scale-105 transition-all duration-300"
                  >
                    View
                  </button>
                  <button
                    @click="openEditModal(product)"
                    :disabled="product.status == 2"
                    :class="[
                      'px-4 py-2 text-xs font-medium rounded-[5px] transition-all duration-300',
                      product.status == 2
                        ? 'bg-gray-400 text-gray-200 cursor-not-allowed opacity-50'
                        : 'text-white bg-blue-600 hover:bg-blue-700 hover:scale-105',
                    ]"
                  >
                    Edit
                  </button>
                  <button
                    @click="openDuplicateModal(product)"
                    class="px-4 py-2 text-xs font-medium text-white bg-purple-600 rounded-[5px] hover:bg-purple-700 hover:scale-105 transition-all duration-300"
                  >
                    Duplicate
                  </button>
                </div>
              </td>
            </tr>
            <!-- Empty State Message -->
            <tr v-if="!products.data || products.data.length === 0">
              <td colspan="7" class="px-6 py-8 text-center text-gray-500 font-medium">
                No products found
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div
          class="flex items-center justify-between px-6 py-4 mt-4"
          v-if="products.links"
        >
          <div class="text-sm text-gray-600 font-medium">
            Showing {{ products.from }} to {{ products.to }} of
            {{ products.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              v-for="link in products.links"
              :key="link.label"
              @click="link.url ? router.visit(link.url) : null"
              :disabled="!link.url"
              :class="[
                'px-3 py-1 rounded-[5px] text-xs font-medium transition-all duration-300',
                link.active
                  ? 'bg-blue-600 text-white'
                  : link.url
                  ? 'bg-blue-100 text-blue-700 hover:bg-blue-200'
                  : 'bg-gray-200 text-gray-400 cursor-not-allowed',
              ]"
              v-html="link.label"
            ></button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Components for CRUD Operations -->

    <!-- Create Product Modal - Full product creation form with image upload, units, pricing -->
    <ProductCreateModal
      v-model:open="isCreateModalOpen"
      :brands="brands"
      :categories="categories"
      :types="types"
      :measurementUnits="measurementUnits"
      :suppliers="suppliers"
      :customers="customers"
      :discounts="discounts"
      :taxes="taxes"
      :currencySymbol="currencySymbol"
      :divisions="divisions"
    />

    <!-- View Product Modal - Read-only display with barcode printing capability -->
    <ProductViewModal
      v-model:open="isViewModalOpen"
      :product="selectedProductForView"
      :currencySymbol="currencySymbol"
    />

    <!-- Edit Product Modal - Full editing interface for existing products -->
    <ProductEditModal
      v-model:open="isEditModalOpen"
      :product="selectedProduct"
      :brands="brands"
      :categories="categories"
      :types="types"
      :measurementUnits="measurementUnits"
      :suppliers="suppliers"
      :customers="customers"
      :discounts="discounts"
      :taxes="taxes"
      :currencySymbol="currencySymbol"
      :divisions="divisions"
    />
    <!-- Duplicate Product Modal - Clone product with new barcode for variants -->
    <ProductDuplicateModal
      v-model:open="isDuplicateModalOpen"
      :product="selectedProductForDuplicate"
      :brands="brands"
      :categories="categories"
      :types="types"
      :currencySymbol="currencySymbol"
      :measurementUnits="measurementUnits"
      :discounts="discounts"
      :taxes="taxes"
    />
  </AppLayout>
</template>

<script setup>
/**
 * Products Index Component Script
 *
 * Manages the products listing page with modal-based CRUD operations
 * Handles product viewing, editing, duplication, and deletion
 */

import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { logActivity } from "@/composables/useActivityLog";
import ProductCreateModal from "./Components/ProductCreateModal.vue";
import ProductViewModal from "./Components/ProductViewModal.vue";
import ProductEditModal from "./Components/ProductEditModal.vue";
import ProductDuplicateModal from "./Components/ProductDuplicateModal.vue";

/**
 * Component Props
 * All data passed from ProductController
 */
defineProps({
  products: {
    type: Object,
    required: true,
  },
  brands: {
    type: Array,
    required: true,
  },
  currencySymbol: {
    type: Array,
    required: true,
  },
  categories: {
    type: Array,
    required: true,
  },
  types: {
    type: Array,
    required: true,
  },
  measurementUnits: {
    type: Array,
    required: true,
  },
  discounts: {
    type: Array,
    required: true,
  },
  taxes: {
    type: Array,
    required: true,
  },
  divisions: {
    type: Array,
    default: () => [],
  },
  divisionFilter: {
    type: String,
    default: null,
  },
});

const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === 0 || page.props.auth?.user?.role === 1);
const selectedDivisionFilter = ref(null);

const applyDivisionFilter = (divId) => {
  selectedDivisionFilter.value = divId;
  router.get(route('products.index'), divId ? { division_filter: divId } : {}, { preserveScroll: true });
};

/**
 * Reactive State Variables
 *
 * Modal visibility states for each operation
 * Selected product references for edit/view/delete/duplicate operations
 */
const isCreateModalOpen = ref(false);
const isViewModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDuplicateModalOpen = ref(false);
const selectedProduct = ref(null);
const selectedProductForView = ref(null);
const selectedProductForDuplicate = ref(null);

/**
 * Open Create Product Modal
 * Opens empty form for new product creation
 */
const openCreateModal = () => {
  isCreateModalOpen.value = true;
};

/**
 * Open View Product Modal
 * Displays product details in read-only mode with barcode printing
 * Also logs the view activity to activity_logs table
 *
 * @param {Object} product - Product object to view
 */
const openViewModal = async (product) => {
  selectedProductForView.value = product;
  isViewModalOpen.value = true;

  // Log the view activity
  await logActivity("view", "products", {
    product_id: product.id,
    product_name: product.name,
    barcode: product.barcode,
    brand: product.brand?.name || "N/A",
    category: product.category?.name || "N/A",
    purchase_price: product.purchase_price,
    selling_price: product.selling_price,
    qty: product.qty,
    status: product.status,
  });
};

/**
 * Open Edit Product Modal
 * Loads product data into edit form
 * Also logs the edit activity to activity_logs table
 *
 * @param {Object} product - Product object to edit
 */
const openEditModal = (product) => {
  selectedProduct.value = product;
  isEditModalOpen.value = true;
};

/**
 * Open Delete Confirmation Modal
 * Shows confirmation dialog before deletion
 *
 * @param {Object} product - Product object to delete
 */
const openDeleteModal = (product) => {
  if (!product || !product.id) {
    console.error("Invalid product data");
    return;
  }
  selectedProductForDelete.value = { ...product };
  isDeleteModalOpen.value = true;
};

/**
 * Open Duplicate Product Modal
 * Clones product data for creating variants
 * Useful for creating similar products with different attributes
 *
 * @param {Object} product - Product object to duplicate
 */
const openDuplicateModal = (product) => {
  if (!product || !product.id) {
    console.error("Invalid product data");
    return;
  }
  selectedProductForDuplicate.value = { ...product };
  isDuplicateModalOpen.value = true;
};
</script>
