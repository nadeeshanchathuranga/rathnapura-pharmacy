<template>
  <AppLayout title="Pro Notes">
    <!-- Main Container -->
    <div class="min-h-screen bg-gray-50 p-6">
      <!-- Header Section with Navigation and Actions -->
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
          <!-- Back to Dashboard Button -->
          <button
            @click="goToStoresTab"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-4xl font-bold text-gray-800">Goods Transfer Release Notes</h1>
        </div>
        <!-- Add New Product Release Note Button -->
        <button
          @click="openCreateModal"
          class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 transition-all duration-200"
        >
          + Add Product Release Note
        </button>
      </div>

      <!-- Product Release Notes Table Container -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <table class="w-full text-left border-collapse">
          <!-- Table Header -->
          <thead>
            <tr class="border-b-2 border-blue-600">
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Date</th>

              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Products</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-right">
                Total Amount ({{ page.props.currency || "" }})
              </th>
             
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Actions
              </th>
            </tr>
          </thead>

          <!-- Table Body - Product Release Note Rows -->
          <tbody>
            <tr
              v-for="productReleaseNote in productReleaseNotes.data"
              :key="productReleaseNote.id"
              class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200"
            >
              <td class="px-4 py-4">
                <div class="text-sm text-gray-800">
                  {{ formatDate(productReleaseNote.release_date) }}
                </div>
              </td>
              <td class="px-4 py-4">
                <span
                  class="inline-flex items-center justify-center px-3 py-1 bg-orange-100 text-orange-700 rounded-lg font-bold text-sm"
                  >{{
                    productReleaseNote.product_release_note_products?.length || 0
                  }}
                  items</span
                >
              </td>
              <td class="px-4 py-4 text-right">
                <div class="text-sm font-semibold text-blue-700">
                  {{ calculateTotal(productReleaseNote) }}
                </div>
              </td>
              <!-- <td class="px-4 py-4 text-center">
                <span
                  :class="{
                    'px-4 py-1.5 rounded-[5px] font-medium text-xs': true,
                    'bg-yellow-500 text-white': productReleaseNote.status === 0,
                    'bg-green-500 text-white': productReleaseNote.status === 1,
                  }"
                >
                  {{ productReleaseNote.status === 1 ? "Released" : "Pending" }}
                </span>
              </td> -->

              <td class="px-4 py-4">
                <div class="flex gap-2 justify-center">
                  <button
                    @click="openViewModal(productReleaseNote)"
                    class="px-4 py-2 text-xs font-medium text-white bg-green-600 rounded-[5px] hover:bg-green-700 transition-all duration-200"
                  >
                    View
                  </button>
                </div>
              </td>
            </tr>
            <!-- Empty State Message -->
            <tr v-if="!productReleaseNotes.data || productReleaseNotes.data.length === 0">
              <td colspan="5" class="px-6 py-8 text-center text-gray-500 font-medium">
                No Product Release Notes found
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div
          class="flex items-center justify-between px-6 py-4 bg-white border-t border-gray-200 mt-4"
          v-if="productReleaseNotes.links && productReleaseNotes.links.length > 3"
        >
          <div class="text-sm text-gray-600">
            Showing {{ productReleaseNotes.from }} to {{ productReleaseNotes.to }} of
            {{ productReleaseNotes.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              v-for="link in productReleaseNotes.links"
              :key="link.label"
              @click="link.url ? router.visit(link.url) : null"
              :disabled="!link.url"
              :class="[
                'px-3 py-1 rounded-[5px]',
                link.active
                  ? 'bg-blue-600 text-white'
                  : link.url
                  ? 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                  : 'bg-gray-100 text-gray-400 cursor-not-allowed',
              ]"
              v-html="link.label"
            ></button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <ProductReleaseNoteCreateModal
      v-model:open="isCreateModalOpen"
      :availableProducts="availableProducts"
      :productTransferRequests="productTransferRequests"
      :users="users"
    />

    <!-- View Modal -->
    <ProductReleaseNoteViewModel
      v-model:open="isViewModalOpen"
      :product-release-note="selectedProductReleaseNote"
    />
  </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
const page = usePage();
import { logActivity } from "@/composables/useActivityLog";
import ProductReleaseNoteCreateModal from "./Components/ProductReleaseNoteCreateModal.vue";
import ProductReleaseNoteViewModel from "./Components/ProductReleaseNoteViewModel.vue";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

defineProps({
  productReleaseNotes: Object,
  availableProducts: Array,
  productTransferRequests: Array,
  users: Array,
});

const isCreateModalOpen = ref(false);
const isViewModalOpen = ref(false);
const selectedProductReleaseNote = ref(null);

const { goToStoresTab } = useDashboardNavigation();

const openCreateModal = () => {
  isCreateModalOpen.value = true;
};

const openViewModal = async (productReleaseNote) => {
  selectedProductReleaseNote.value = productReleaseNote;
  isViewModalOpen.value = true;

  // Log view activity
  await logActivity("view", "product_release_notes", {
    release_note_id: productReleaseNote.id,
    release_note_number: productReleaseNote.product_release_note_no,
    release_date: productReleaseNote.product_release_date,
    user: productReleaseNote.user?.name || "N/A",
    status: productReleaseNote.status,
  });
};

const formatDate = (date) => {
  if (!date) return "N/A";
  return new Date(date).toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
};

const formatNumber = (number) => {
  return parseFloat(number || 0).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const calculateTotal = (productReleaseNote) => {
  const items = productReleaseNote?.product_release_note_products;
  if (!Array.isArray(items) || items.length === 0) {
    return formatNumber(0);
  }

  const productsTotal = items.reduce((sum, item) => {
    const t =
      typeof item.total === "string" ? parseFloat(item.total) : Number(item.total || 0);
    return sum + (isNaN(t) ? 0 : t);
  }, 0);

  return formatNumber(productsTotal);
};
</script>

<style scoped>
/* Tailwind CSS handles all styling */
</style>
