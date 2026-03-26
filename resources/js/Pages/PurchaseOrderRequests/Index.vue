<template>
  <AppLayout title="Purchase Order Requests">
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
          <h1 class="text-4xl font-bold text-gray-800">Purchase Order Requests</h1>
        </div>
        <!-- Add New Purchase Order Request Button -->
        <button
          @click="openCreateModal"
          class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 transition-all duration-200"
        >
          + Add Purchase Order Request
        </button>
      </div>

      <!-- Purchase Order Requests Table Container -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <table class="w-full text-left border-collapse">
          <!-- Table Header -->
          <thead>
            <tr class="border-b-2 border-blue-600">
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Order Number</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Date</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">User</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Status
              </th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Actions
              </th>
            </tr>
          </thead>
          <!-- Table Body - Purchase Order Request Rows -->
          <tbody>
            <tr
              v-for="purchaseOrderRequest in purchaseOrderRequests.data"
              :key="purchaseOrderRequest.id"
              class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200"
            >
              <td class="px-4 py-4">
                <span class="font-semibold text-gray-900">{{
                  purchaseOrderRequest.order_number
                }}</span>
              </td>
              <td class="px-4 py-4">
                <span class="text-sm text-gray-800">{{
                  formatDate(purchaseOrderRequest.order_date)
                }}</span>
              </td>
              <td class="px-4 py-4">
                <span class="text-sm text-gray-800">{{
                  purchaseOrderRequest.user?.name || "N/A"
                }}</span>
              </td>
              <td class="px-4 py-4 text-center">
                <span :class="getStatusClass(purchaseOrderRequest.status)">
                  {{
                    purchaseOrderRequest.status === "approved"
                      ? "Approved"
                      : purchaseOrderRequest.status === "pending"
                      ? "Pending"
                      : purchaseOrderRequest.status === "rejected"
                      ? "Rejected"
                      : purchaseOrderRequest.status === "completed"
                      ? "Completed"
                      : purchaseOrderRequest.status === "inactive"
                      ? "Inactive"
                      : purchaseOrderRequest.status
                  }}
                </span>
              </td>
              <td class="px-4 py-4">
                <div class="flex gap-2 justify-center">
                  <button
                    @click="openViewModal(purchaseOrderRequest)"
                    class="px-4 py-2 text-xs font-medium text-white bg-green-600 rounded-[5px] hover:bg-green-700 transition-all duration-200"
                  >
                    View
                  </button>
                  <button
                    v-if="purchaseOrderRequest.status === 'pending' && isAdmin"
                    @click="approvePurchaseOrder(purchaseOrderRequest)"
                    class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-[5px] hover:bg-blue-700 transition-all duration-200"
                  >
                    Approve
                  </button>
                </div>
              </td>
            </tr>
            <!-- Empty State Message -->
            <tr
              v-if="
                !purchaseOrderRequests.data || purchaseOrderRequests.data.length === 0
              "
            >
              <td colspan="5" class="px-6 py-8 text-center text-gray-500 font-medium">
                No Purchase Order Requests found
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div
          class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200"
          v-if="purchaseOrderRequests.links && purchaseOrderRequests.links.length > 3"
        >
          <div class="text-sm text-gray-600">
            Showing {{ purchaseOrderRequests.from }} to {{ purchaseOrderRequests.to }} of
            {{ purchaseOrderRequests.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              v-for="link in purchaseOrderRequests.links"
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
    <PurchaseOrderRequestCreateModal
      v-model:open="isCreateModalOpen"
      :products="products"
      :all-products="allProducts"
      :measurementUnits="measurementUnits"
      :users="users"
       
      :orderNumber="orderNumber"
    />

    <!-- View Modal -->
    <PurchaseOrderRequestViewModel
      v-model:open="isViewModalOpen"
      :por="selectedPurchaseOrderRequest"
    />
  </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { logActivity } from "@/composables/useActivityLog";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";
import PurchaseOrderRequestCreateModal from "./Components/PurchaseOrderRequestCreateModal.vue";
import PurchaseOrderRequestViewModel from "./Components/PurchaseOrderRequestViewModel.vue";
import PurchaseOrderRequestEditModal from "./Components/PurchaseOrderRequestEditModal.vue";

defineProps({
  purchaseOrderRequests: Object,
  products: Array,
  allProducts: Array,
  measurementUnits: Array,
  users: Array,

  orderNumber: String,
});

const page = usePage();
const { goToStoresTab } = useDashboardNavigation();

const isCreateModalOpen = ref(false);
const isViewModalOpen = ref(false);
const selectedPurchaseOrderRequest = ref(null);

// Check if current user is admin
const isAdmin = page.props.auth.user.role === 0;

const openCreateModal = () => {
  isCreateModalOpen.value = true;
};

const openViewModal = async (purchaseOrderRequest) => {
  selectedPurchaseOrderRequest.value = purchaseOrderRequest;
  isViewModalOpen.value = true;

  // Log view activity
  await logActivity("view", "purchase_orders", {
    order_id: purchaseOrderRequest.id,
    order_number: purchaseOrderRequest.order_number,
    order_date: purchaseOrderRequest.order_date,
    user: purchaseOrderRequest.user?.name || "N/A",
    status: purchaseOrderRequest.status,
    supplier: purchaseOrderRequest.supplier?.name || "N/A",
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
    rejected: "bg-red-600 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs",
    inactive: "bg-gray-500 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs",
  };
  return (
    classes[status] ||
    "bg-gray-500 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs"
  );
};

const updateStatus = (purchaseOrderRequest, newStatus) => {
  router.patch(
    `/purchase-order-requests/${purchaseOrderRequest.id}/status`,
    { status: newStatus },
    {
      onSuccess: () => {
        // Status updated successfully
      },
      onError: () => {
        // Error occurred, revert to previous status
      },
    }
  );
};

const approvePurchaseOrder = (purchaseOrderRequest) => {
  if (purchaseOrderRequest.status !== "pending") {
    alert("Only pending purchase orders can be approved");
    return;
  }

  if (confirm("Are you sure you want to approve this purchase order?")) {
    router.patch(
      `/purchase-order-requests/${purchaseOrderRequest.id}/status`,
      { status: "approved" },
      {
        onSuccess: () => {
          // Log approval activity
          logActivity("approve", "purchase_orders", {
            order_id: purchaseOrderRequest.id,
            order_number: purchaseOrderRequest.order_number,
            previous_status: "pending",
            new_status: "approved",
          });
        },
        onError: (error) => {
          alert("Failed to approve purchase order: " + (error.message || "Unknown error"));
        },
      }
    );
  }
};

const cancelPurchaseOrder = (purchaseOrderRequest) => {
  if (purchaseOrderRequest.status !== "active") {
    alert("Only active purchase orders can be cancelled");
    return;
  }

  if (confirm("Are you sure you want to cancel this purchase order?")) {
    router.patch(
      `/purchase-order-requests/${purchaseOrderRequest.id}/status`,
      { status: "inactive" },
      {
        onSuccess: () => {
          // Log cancellation activity
          logActivity("cancel", "purchase_orders", {
            order_id: purchaseOrderRequest.id,
            order_number: purchaseOrderRequest.order_number,
            previous_status: "active",
            new_status: "inactive",
          });
        },
        onError: (error) => {
          alert("Failed to cancel purchase order: " + (error.message || "Unknown error"));
        },
      }
    );
  }
};
</script>

<style scoped>
/* Tailwind CSS handles all styling */
</style>
