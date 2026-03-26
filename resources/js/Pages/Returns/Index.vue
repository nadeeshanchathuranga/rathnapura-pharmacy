<template>
  <Head title="Product Returns" />

  <AppLayout>
    <div class="min-h-screen bg-gray-50 p-6">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <button
            @click="goToShopsTab"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-3xl font-bold text-black">Sales Returns</h1>
        </div>
        <button
          @click="openCreateModal"
          class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200 shadow-sm"
        >
          + Add New Sales Return
        </button>
      </div>

      <div class="overflow-hidden bg-white rounded-2xl shadow-md border border-gray-200">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="border-b-2 border-blue-600">
              <tr>
                <th class="px-6 py-4 text-sm font-semibold text-blue-700">Return No</th>
                <th class="px-6 py-4 text-sm font-semibold text-blue-700">Date</th>
                <th class="px-6 py-4 text-sm font-semibold text-blue-700">Customer</th>
                <th class="px-6 py-4 text-sm font-semibold text-blue-700 text-center">
                  Return Type
                </th>
                <th class="px-6 py-4 text-sm font-semibold text-blue-700 text-center">
                  Products
                </th>
                <th class="px-6 py-4 text-sm font-semibold text-blue-700 text-center">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="returnItem in returns.data"
                :key="returnItem.id"
                class="border-b border-gray-200 hover:bg-gray-50 transition"
              >
                <td class="px-6 py-4">
                  <span class="font-semibold text-gray-800">{{
                    returnItem.return_no || `RET-${returnItem.id}`
                  }}</span>
                </td>
                <td class="px-6 py-4 text-gray-700">
                  {{
                    returnItem.return_date_formatted ||
                    formatDate(returnItem.return_date) ||
                    "N/A"
                  }}
                </td>
                <td class="px-6 py-4">
                  <div class="font-medium text-gray-800">
                    {{
                      returnItem.customer_name ||
                      returnItem.customer?.name ||
                      "Walk-in Customer"
                    }}
                  </div>
                  <div class="text-sm text-gray-500">
                    {{ returnItem.customer_phone || returnItem.customer?.contact || "" }}
                  </div>
                </td>
                <td class="px-6 py-4 text-center">
                  <span
                    :class="[
                      'px-3 py-1.5 rounded-[5px] text-xs font-semibold inline-block',
                      returnItem.return_type === 1 || !returnItem.return_type
                        ? 'bg-blue-600 text-white'
                        : 'bg-green-600 text-white',
                    ]"
                  >
                    {{
                      returnItem.return_type === 2
                        ? "💰 Cash Refund"
                        : "🔄 Product Return"
                    }}
                  </span>
                </td>
                <td class="px-6 py-4 text-center">
                  <span
                    class="px-3 py-1.5 bg-blue-600 text-white rounded-[5px] text-sm font-medium inline-block"
                  >
                    {{
                      returnItem.products_count || returnItem.return_products?.length || 0
                    }}
                  </span>
                </td>
                <td class="px-6 py-4 text-center space-x-2">
                  <button
                    @click="openViewModal(returnItem)"
                    class="px-4 py-1.5 text-white bg-green-600 rounded-[5px] hover:bg-green-700 transition font-medium text-sm"
                  >
                    View
                  </button>
                  <a
                    :href="route('return.export.bill.pdf', returnItem.id)"
                    class="inline-block px-4 py-1.5 text-white bg-blue-600 rounded-[5px] hover:bg-blue-700 transition font-medium text-sm"
                    target="_blank"
                  >
                    Download PDF
                  </a>
                </td>
              </tr>
              <tr v-if="!returns.data || returns.data.length === 0">
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                  No Product Returns found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          class="flex items-center justify-between px-6 py-4 bg-blue-50 border-t border-gray-200"
          v-if="returns.links && returns.links.length > 3"
        >
          <div class="text-sm text-gray-700 font-medium">
            Showing {{ returns.from }} to {{ returns.to }} of {{ returns.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              v-for="link in returns.links"
              :key="link.label"
              @click="link.url ? router.visit(link.url) : null"
              :disabled="!link.url"
              :class="[
                'px-3 py-1.5 rounded-[5px] font-medium text-sm transition',
                link.active
                  ? 'bg-blue-600 text-white'
                  : link.url
                  ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                  : 'bg-gray-200 text-gray-400 cursor-not-allowed',
              ]"
              v-html="link.label"
            ></button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <ReturnCreateModal
      v-model:open="isCreateModalOpen"
      :sales-products="salesProducts"
      :shop-products="shopProducts"
      @success="handleSuccess"
    />

    <!-- View Modal -->
    <ReturnDetailsModal
      :open="showDetailsModal"
      :return-data="selectedReturn"
      @close="closeDetailsModal"
      @update-status="updateStatus"
    />
  </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import ReturnDetailsModal from "./Components/ReturnDetailsModal.vue";
import ReturnCreateModal from "./Components/ReturnCreateModal.vue";
import { logActivity } from "@/composables/useActivityLog";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const { goToShopsTab } = useDashboardNavigation();

const props = defineProps({
  returns: Object,
  salesProducts: Object,
  shopProducts: Array,
  filters: Object,
});

const isCreateModalOpen = ref(false);
const showDetailsModal = ref(false);
const selectedReturn = ref(null);

const openCreateModal = () => {
  isCreateModalOpen.value = true;
};

const openViewModal = async (returnItem) => {
  selectedReturn.value = returnItem;
  showDetailsModal.value = true;
  await logActivity("view", "product_returns", {
    return_id: returnItem.id,
    invoice_number: returnItem.invoice_number,
  });
};

const closeDetailsModal = () => {
  showDetailsModal.value = false;
  selectedReturn.value = null;
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
};

const getStatusClass = (status) => {
  const classes = {
    0: "bg-yellow-500 text-white px-3 py-1 rounded",
    1: "bg-green-500 text-white px-3 py-1 rounded",
    2: "bg-red-500 text-white px-3 py-1 rounded",
  };
  return classes[status] || "bg-gray-500 text-white px-3 py-1 rounded";
};

const updateStatus = (returnItem, newStatus) => {
  router.patch(
    route("return.update-status", returnItem.id),
    {
      status: parseInt(newStatus),
    },
    {
      onSuccess: () => {
        if (showDetailsModal.value && selectedReturn.value?.id === returnItem.id) {
          selectedReturn.value.status = parseInt(newStatus);
        }
      },
      onError: () => {
        // Error occurred
      },
    }
  );
};

const handleSuccess = () => {
  isCreateModalOpen.value = false;
  router.reload();
};
</script>
