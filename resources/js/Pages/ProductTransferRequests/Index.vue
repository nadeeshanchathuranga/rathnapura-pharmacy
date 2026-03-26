<template>
  <AppLayout title="Product Transfer Requests">
    <div class="min-h-screen bg-gray-50 p-6">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <button
            @click="goToShopsTab"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-3xl font-bold text-black">Products Transfer Requests


          </h1>
        </div>
        <button
          @click="openCreateModal"
          class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200 shadow-sm"
        >
          + Add New Transfer Request
        </button>
      </div>

      <div class="overflow-hidden bg-white rounded-2xl shadow-md border border-gray-200">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="border-b-2 border-blue-600">
              <tr>
                <th class="px-6 py-4 text-sm font-semibold text-blue-700">Transfer No</th>
                <th class="px-6 py-4 text-sm font-semibold text-blue-700">Date</th>
                <th class="px-6 py-4 text-sm font-semibold text-blue-700">User</th>
                <th class="px-6 py-4 text-sm font-semibold text-blue-700">Products</th>

                <th class="px-6 py-4 text-sm font-semibold text-blue-700 text-center">
                  Status
                </th>
                <th class="px-6 py-4 text-sm font-semibold text-blue-700 text-center">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="productTransferRequest in productTransferRequests.data"
                :key="productTransferRequest.id"
                class="border-b border-gray-200 hover:bg-blue-50 transition"
              >
                <td class="px-6 py-4">
                  <span class="font-semibold text-gray-800">{{
                    productTransferRequest.product_transfer_request_no
                  }}</span>
                </td>
                <td class="px-6 py-4 text-gray-700">
                  {{ formatDate(productTransferRequest.request_date) }}
                </td>
                <td class="px-6 py-4 text-gray-700">
                  {{ productTransferRequest.user?.name || "N/A" }}
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-700">
                    <div
                      v-if="
                        productTransferRequest.product_transfer_request_products &&
                        productTransferRequest.product_transfer_request_products.length >
                          0
                      "
                    >
                      <span
                        v-for="(
                          product, idx
                        ) in productTransferRequest.product_transfer_request_products.slice(
                          0,
                          2
                        )"
                        :key="idx"
                        class="block"
                      >
                        {{ product.product?.name }} ({{ product.requested_quantity }}
                        {{ product.measurement_unit?.code }})
                      </span>
                      <span
                        v-if="
                          productTransferRequest.product_transfer_request_products
                            .length > 2
                        "
                        class="text-gray-500"
                      >
                        +{{
                          productTransferRequest.product_transfer_request_products
                            .length - 2
                        }}
                        more
                      </span>
                    </div>
                    <span v-else class="text-gray-500">No products</span>
                  </div>
                </td>

                <td class="px-6 py-4 text-center">
                <span :class="getStatusClass(productTransferRequest.status)">
                  {{
                    productTransferRequest.status === 'pending'
                      ? 'Pending'
                      : productTransferRequest.status === 'approved'
                      ? 'Approved'
                      : productTransferRequest.status === 'rejected'
                      ? 'Rejected'
                      : productTransferRequest.status === 'completed'
                      ? 'Completed'
                      : productTransferRequest.status
                  }}
                </span>
                </td>
                <td class="px-6 py-4 text-center space-x-2">
                  <button
                    v-if="productTransferRequest.status === 'pending' && isAdmin"
                    @click="approveProductTransferRequest(productTransferRequest)"
                    class="px-4 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-[5px] hover:bg-blue-700 transition-all duration-200"
                  >
                    Approve
                  </button>
                  <button
                    @click="openViewModal(productTransferRequest)"
                    class="px-4 py-1.5 text-white bg-green-600 rounded-[5px] hover:bg-green-700 transition font-medium text-sm"
                  >
                    View
                  </button>
                </td>
              </tr>
              <tr
                v-if="
                  !productTransferRequests.data ||
                  productTransferRequests.data.length === 0
                "
              >
                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                  No Product Transfer Requests found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          class="flex items-center justify-between px-6 py-4 bg-blue-50 border-t border-gray-200"
          v-if="productTransferRequests.links && productTransferRequests.links.length > 3"
        >
          <div class="text-sm text-gray-700 font-medium">
            Showing {{ productTransferRequests.from }} to
            {{ productTransferRequests.to }} of
            {{ productTransferRequests.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              v-for="link in productTransferRequests.links"
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
    <ProductTransferRequestCreateModal
      v-model:open="isCreateModalOpen"
      :products="products"
      :measurementUnits="measurementUnits"
      :users="users"
      :transferNo="product_transfer_request_no"
    />

    <!-- View Modal -->
    <ProductTransferRequestViewModel
      v-model:open="isViewModalOpen"
      :product-transfer-request="selectedProductTransferRequest"
      v-if="selectedProductTransferRequest"
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { logActivity } from "@/composables/useActivityLog";
import ProductTransferRequestCreateModal from "./Components/ProductTransferRequestCreateModal.vue";
import ProductTransferRequestViewModel from "./Components/ProductTransferRequestViewModel.vue";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const { goToShopsTab } = useDashboardNavigation();
const page = usePage();

const isAdmin = computed(() => page.props.auth.user.role === 0);

defineProps({
  productTransferRequests: Object,
  products: Array,
  measurementUnits: Array,
  users: Array,
  product_transfer_request_no: String,
});

const isCreateModalOpen = ref(false);
const isViewModalOpen = ref(false);
const selectedProductTransferRequest = ref(null);

const openCreateModal = () => {
  isCreateModalOpen.value = true;
};

const openViewModal = async (productTransferRequest) => {
  selectedProductTransferRequest.value = productTransferRequest;
  isViewModalOpen.value = true;

  // Log view activity
  await logActivity("view", "product_transfer_requests", {
    request_id: productTransferRequest.id,
    request_number: productTransferRequest.product_transfer_request_no,
    request_date: productTransferRequest.product_transfer_request_date,
    user: productTransferRequest.user?.name || "N/A",
    status: productTransferRequest.status,
  });
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
};

const formatNumber = (number) => {
  return parseFloat(number).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};


const getStatusClass = (status) => {
  const classes = {
    approved: "bg-green-500 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs",
    pending: "bg-yellow-500 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs",
    completed: "bg-blue-500 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs",
    rejected: "bg-red-500 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs",
    inactive: "bg-gray-600 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs",
  };
  return (
    classes[status] ||
    "bg-gray-500 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs"
  );
};

// const updateStatus = (productTransferRequest, newStatus) => {
//   router.patch(
//     `/product-transfer-requests/${productTransferRequest.id}/status`,
//     { status: newStatus },
//     {
//       onSuccess: () => {
//         // Status updated successfully
//       },
//       onError: () => {
//         // Error occurred, revert to previous status
//       },
//     }
//   );
// };

// const calculateTotal = (productTransferRequest) => {
//   if (
//     !productTransferRequest.product_transfer_request_products ||
//     productTransferRequest.product_transfer_request_products.length === 0
//   )
//     return 0;
//   return productTransferRequest.product_transfer_request_products.reduce(
//     (total, item) => {
//       return total + item.requested_quantity * (item.unit_price || 0);
//     },
//     0
//   );
// };

const approveProductTransferRequest = (productTransferRequest) => {
  if (productTransferRequest.status !== 'pending') {
    alert('Only pending transfer requests can be approved');
    return;
  }

  if (!confirm('Are you sure you want to approve this transfer request? This will transfer the stock from store to shop.')) {
    return;
  }

  router.patch(
    `/product-transfer-requests/${productTransferRequest.id}/status`,
    { status: 'approved' },
    {
      onSuccess: async () => {
        // Log approval activity
        await logActivity('update', 'product_transfer_requests', {
          transfer_id: productTransferRequest.id,
          transfer_number: productTransferRequest.product_transfer_request_no,
          action: 'approved',
          status: 'approved',
        });
      },
      onError: (error) => {
        console.error('Failed to approve transfer request:', error);
        alert('Failed to approve transfer request');
      },
    }
  );
};
</script>

<style scoped>
/* Custom styling for status dropdown */
.status-dropdown {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
  background-position: right 0.5rem center;
  background-repeat: no-repeat;
  background-size: 1.5em 1.5em;
  padding-right: 2.5rem;
}

.status-dropdown option {
  background-color: #f3f4f6;
  color: #1f2937;
  padding: 0.5rem;
}
</style>
