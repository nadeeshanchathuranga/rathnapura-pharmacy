<template>
  <AppLayout>
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
          <h1 class="text-4xl font-bold text-gray-800">Goods Received Notes</h1>
        </div>
        <!-- Add New Button -->
        <button
          @click="openCreateModal"
          class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 transition-all duration-200"
        >
          + Add Goods Received Note
        </button>
      </div>

      <!-- Goods Received Notes Table Container -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <table class="w-full text-left border-collapse">
          <!-- Table Header -->
          <thead>
            <tr class="border-b-2 border-blue-600">
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Note Number</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Supplier</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Date</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Products</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Discount</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Tax</th>

              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Actions
              </th>
            </tr>
          </thead>
          <!-- Table Body -->
          <tbody>
            <tr
              v-for="goodsReceivedNote in goodsReceivedNotes.data"
              :key="goodsReceivedNote.id"
              class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200"
            >
              <td class="px-4 py-4">
                <span class="font-semibold text-gray-900">{{
                  goodsReceivedNote.goods_received_note_no
                }}</span>
              </td>
              <td class="px-4 py-4">
                <span class="text-sm text-gray-800">{{
                  goodsReceivedNote.supplier?.name || "N/A"
                }}</span>
              </td>
              <td class="px-4 py-4">
                <span class="text-sm text-gray-800">{{
                  formatDate(goodsReceivedNote.goods_received_note_date)
                }}</span>
              </td>
              <td class="px-4 py-4">
                <span class="text-sm text-gray-700"
                  >{{
                    goodsReceivedNote.goods_received_note_products?.length || 0
                  }}
                  items</span
                >
              </td>
              <td class="px-4 py-4">
                <span class="text-sm text-gray-800">{{
                  formatItemDiscountRates(goodsReceivedNote)
                }}</span>
              </td>
              <td class="px-4 py-4">
                <span class="text-sm text-gray-800">{{
                  formatNumber(goodsReceivedNote.tax_total)
                }}</span>
              </td>

              <td class="px-4 py-4">
                <div class="flex gap-2 justify-center">
                  <button
                    @click="openViewModal(goodsReceivedNote)"
                    class="px-4 py-2 text-xs font-medium text-white bg-green-600 rounded-[5px] hover:bg-green-700 transition-all duration-200"
                  >
                    View
                  </button>
                </div>
              </td>
            </tr>
            <!-- Empty State Message -->
            <tr v-if="!goodsReceivedNotes.data || goodsReceivedNotes.data.length === 0">
              <td colspan="8" class="px-6 py-8 text-center text-gray-500 font-medium">
                No Goods Received Notes found
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div
          class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200"
          v-if="goodsReceivedNotes.links && goodsReceivedNotes.links.length > 3"
        >
          <div class="text-sm text-gray-600">
            Showing {{ goodsReceivedNotes.from }} to {{ goodsReceivedNotes.to }} of
            {{ goodsReceivedNotes.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              v-for="link in goodsReceivedNotes.links"
              :key="link.label"
              @click="link.url ? router.visit(link.url) : null"
              :disabled="!link.url"
              :class="[
                'px-4 py-2 rounded-[5px] text-sm font-medium transition-all duration-200',
                link.active
                  ? 'bg-blue-600 text-white'
                  : link.url
                  ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-200'
                  : 'bg-gray-100 text-gray-400 cursor-not-allowed',
              ]"
              v-html="link.label"
            ></button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <GoodsReceivedNoteCreateModal
      v-model:open="isCreateModalOpen"
      :suppliers="suppliers"
      :products="products"
      :available-products="availableProducts"
      :grnNumber="grnNumber"
      :measurementUnits="measurementUnits"
    />

    <!-- View Modal -->
    <GoodsReceivedNoteViewModel
      v-model:open="isViewModalOpen"
      :grn="selectedGoodsReceivedNote"
    />

    <!-- Delete Modal -->
    <GoodsReceivedNoteDeleteModal
      v-model:open="isDeleteModalOpen"
      :grn="selectedGoodsReceivedNote"
    />
  </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { logActivity } from "@/composables/useActivityLog";
import AppLayout from "@/Layouts/AppLayout.vue";
import GoodsReceivedNoteCreateModal from "./Components/GoodsReceivedNoteCreateModal.vue";
import GoodsReceivedNoteViewModel from "./Components/GoodsReceivedNoteViewModel.vue";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

defineProps({
  products: Array,
  goodsReceivedNotes: Object,
  suppliers: Array,
  availableProducts: Array,
  grnNumber: String,
  measurementUnits: Array,
});

const isCreateModalOpen = ref(false);
const isViewModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedGoodsReceivedNote = ref(null);

const { goToStoresTab } = useDashboardNavigation();

const openCreateModal = () => {
  isCreateModalOpen.value = true;
};

const openViewModal = async (goodsReceivedNote) => {
  selectedGoodsReceivedNote.value = goodsReceivedNote;
  isViewModalOpen.value = true;

  // Log view activity
  await logActivity("view", "goods_received_notes", {
    grn_id: goodsReceivedNote.id,
    grn_number: goodsReceivedNote.goods_received_note_no,
    grn_date: goodsReceivedNote.goods_received_note_date,
    supplier: goodsReceivedNote.supplier?.name || "N/A",
  });
};

const openDeleteModal = (goodsReceivedNote) => {
  selectedGoodsReceivedNote.value = goodsReceivedNote;
  isDeleteModalOpen.value = true;
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
  return Math.round(parseFloat(number || 0)).toLocaleString("en-US", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  });
};

const formatItemDiscountRates = (goodsReceivedNote) => {
  const products = goodsReceivedNote?.goods_received_note_products || [];

  const uniqueRates = [...new Set(
    products
      .map((product) => {
        const rate = Number(product?.discount_percentage ?? 0);
        return Number.isFinite(rate) ? rate : 0;
      })
      .filter((rate) => rate > 0)
      .map((rate) => Number(rate.toFixed(2)))
  )];

  if (uniqueRates.length > 0) {
    return uniqueRates.map((rate) => `${formatNumber(rate)}%`).join(", ");
  }

  const headerRate = Number(goodsReceivedNote?.discount_percentage ?? 0);
  if (Number.isFinite(headerRate) && headerRate > 0) {
    return `${formatNumber(headerRate)}%`;
  }

  return "0%";
};
</script>

<style scoped>
/* Tailwind CSS handles all styling */
</style>
