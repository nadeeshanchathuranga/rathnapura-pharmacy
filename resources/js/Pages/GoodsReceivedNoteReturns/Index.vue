<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50 p-6">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <button
            @click="goToStoresTab"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-4xl font-bold text-gray-800">Goods Return Notes</h1>
        </div>
        <button
          @click="openCreateModal"
          class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-[5px] hover:bg-blue-700 transition-all duration-200"
        >
          + Add New GRN Return
        </button>
      </div>

      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b-2 border-blue-600">
                <th class="px-6 py-4 text-blue-600 font-semibold text-sm">Return #</th>
                <th class="px-6 py-4 text-blue-600 font-semibold text-sm">GRN</th>
                <th class="px-6 py-4 text-blue-600 font-semibold text-sm">Date</th>
                <th class="px-6 py-4 text-blue-600 font-semibold text-sm">User</th>
                <th class="px-6 py-4 text-blue-600 font-semibold text-sm">Products</th>
                <th class="px-6 py-4 text-blue-600 font-semibold text-sm">Return Qty</th>
                <th class="px-6 py-4 text-blue-600 font-semibold text-sm">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="r in returns.data"
                :key="r.id"
                class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200"
              >
                <td class="px-4 py-4">
                  <span
                    class="inline-flex items-center justify-center w-8 h-8 rounded-[10px] bg-blue-100 text-blue-700 font-bold text-sm"
                  >
                    {{ r.id }}
                  </span>
                </td>
                <td class="px-6 py-4 text-gray-900">
                  {{ r.goods_received_note?.goods_received_note_no || "N/A" }}
                </td>
                <td class="px-6 py-4 text-gray-900">{{ formatDate(r.date) }}</td>
                <td class="px-6 py-4 text-gray-900">{{ r.user?.name || "N/A" }}</td>
                <td class="px-6 py-4 text-gray-900">
                  {{
                    r.goods_received_note_return_products?.length ||
                    r.products?.length ||
                    0
                  }}
                  items
                </td>
                <td class="px-6 py-4 text-gray-900">{{ sumReturnQty(r) }}</td>
                <td class="px-6 py-4">
                  <div class="flex gap-2">
                    <button
                      @click="openViewModal(r)"
                      class="px-4 py-2 text-xs font-medium text-white bg-green-600 rounded-[5px] hover:bg-green-700 transition-all duration-200"
                    >
                      View
                    </button>
                    <!-- <button
                      @click="openDeleteModal(r)"
                      class="px-4 py-2 text-xs font-medium text-white bg-red-600 rounded-[5px] hover:bg-red-700 transition-all duration-200"
                    >
                      Delete
                    </button> -->
                  </div>
                </td>
              </tr>
              <tr v-if="!returns.data || returns.data.length === 0">
                <td colspan="7" class="px-6 py-8 text-center text-gray-500 font-medium">
                  No returns found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div
          class="flex items-center justify-between px-6 py-4 border-t border-gray-200"
          v-if="returns.links && returns.links.length > 3"
        >
          <div class="text-sm text-gray-600">
            Showing {{ returns.from }} to {{ returns.to }} of {{ returns.total }} results
          </div>
          <div class="flex gap-2">
            <button
              v-for="link in returns.links"
              :key="link.label"
              @click="link.url ? router.visit(link.url) : null"
              :disabled="!link.url"
              :class="[
                'px-4 py-2 text-xs font-medium rounded-[5px] transition-all duration-200',
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
    <GoodsReceivedNoteReturnCreateModal
      v-model:open="isCreateModalOpen"
      :suppliers="suppliers"
      :purchase-orders="purchaseOrders"
      :products="products"
      :available-products="availableProducts"
      :measurement-units="measurementUnits"
      :grnNumber="grnNumber"
      :grns="grns"
      :user="user"
    />

    <!-- View Modal -->
    <GoodsReceivedNoteReturnViewModal
      v-model:open="isViewModalOpen"
      :ret="selectedReturn"
      :measurement-units="measurementUnits"
    />

    <!-- Delete Modal -->
    <GoodsReceivedNoteReturnDeleteModal
      v-model:open="isDeleteModalOpen"
      :grn="selectedReturn"
      @deleted="handleReturnDeleted"
    />
  </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import GoodsReceivedNoteReturnCreateModal from "./components/GoodsReceivedNoteReturnCreateModal.vue";
import GoodsReceivedNoteReturnViewModal from "./components/GoodsReceivedNoteReturnViewModal.vue";
import GoodsReceivedNoteReturnDeleteModal from "./components/GoodsReceivedNoteReturnDeleteModal.vue";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const props = defineProps({
  returns: Object,
  suppliers: { type: Array, default: () => [] },
  purchaseOrders: { type: Array, default: () => [] },
  products: { type: Array, default: () => [] },
  availableProducts: { type: Array, default: () => [] },
  grnNumber: { type: String, default: "" },
  grns: { type: Array, default: () => [] },
  measurementUnits: { type: Array, default: () => [] },
  user: { type: Object, default: null },
});

// Debug: show grns arriving from server
try {
  if (!props.grns || props.grns.length === 0) console.warn("Index.vue: `grns` is empty");
} catch (e) {
  console.error("Failed to read props.grns in Index.vue", e);
}

const formatDate = (date) => {
  if (!date) return "N/A";
  return new Date(date).toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
};

const sumReturnQty = (r) => {
  const rows = r.goods_received_note_return_products || r.products || [];
  if (!Array.isArray(rows) || rows.length === 0) return 0;
  return rows.reduce((sum, item) => {
    const qty = Number(item.quantity ?? item.qty ?? item.returnQty ?? 0) || 0;
    return sum + qty;
  }, 0);
};

const isCreateModalOpen = ref(false);
const isViewModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedReturn = ref(null);

const { goToStoresTab } = useDashboardNavigation();

const openCreateModal = () => {
  isCreateModalOpen.value = true;
};

const openViewModal = (r) => {
  selectedReturn.value = r;
  isViewModalOpen.value = true;
};

const openDeleteModal = (r) => {
  selectedReturn.value = r;
  isDeleteModalOpen.value = true;
};

const handleReturnSaved = (payload) => {
  // optimistic update: update selectedReturn products so UI reflects change immediately
  try {
    if (!selectedReturn.value) return;
    const id = selectedReturn.value.id;
    const mappedProducts = (payload.products || []).map((p) => ({
      products_id: p.product_id,
      qty: p.qty,
      remarks: p.remarks ?? null,
      product:
        (props.products || []).find((prod) => Number(prod.id) === Number(p.product_id)) ||
        null,
    }));

    // update selectedReturn
    selectedReturn.value.goods_received_note_return_products = mappedProducts;

    // try to update the table entry if present
    const idx = returns.data.findIndex((x) => x.id === id);
    if (idx !== -1) {
      returns.data[idx].goods_received_note_return_products = mappedProducts;
    }
  } catch (e) {
    console.error("Failed optimistic update for GRN return:", e);
  }
};

const handleReturnDeleted = (id) => {
  try {
    if (!id) return;
    // remove from paginated data if present
    if (returns && Array.isArray(returns.data)) {
      const idx = returns.data.findIndex((x) => x.id === id);
      if (idx !== -1) returns.data.splice(idx, 1);
    }
    if (selectedReturn.value?.id === id) {
      selectedReturn.value = null;
      isDeleteModalOpen.value = false;
    }
  } catch (e) {
    console.error("Failed optimistic delete update:", e);
  }
};
</script>

<style scoped></style>
