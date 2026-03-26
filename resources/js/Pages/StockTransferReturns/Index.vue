<template>
  <Head title="Stock Transfer Returns" />

  <AppLayout title="Stock Transfer Returns">
    <div class="min-h-screen bg-gray-50 p-6">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <button
            @click="goToShopsTab"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-3xl font-bold text-black">Goods Transfer Returns</h1>
        </div>
        <button
          @click="openCreateModal"
          class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200 shadow-sm"
        >
          + Add New Stock Transfer Return
        </button>
      </div>

      <div class="overflow-hidden bg-white rounded-2xl shadow-md border border-gray-200">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="border-b-2 border-blue-600">
              <tr>
                <th class="px-6 py-4 text-sm font-semibold text-blue-700">Return Number</th>
                <th class="px-6 py-4 text-sm font-semibold text-blue-700">Date</th>
                <th class="px-6 py-4 text-sm font-semibold text-blue-700">User</th>
                <th class="px-6 py-4 text-sm font-semibold text-blue-700 text-center">Status</th>
                <th class="px-6 py-4 text-sm font-semibold text-blue-700 text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="stockReturn in stockTransferReturns.data"
                :key="stockReturn.id"
                class="border-b border-gray-200 hover:bg-gray-50 transition"
              >
                <td class="px-6 py-4">
                  <span class="font-semibold text-gray-800">{{ stockReturn.return_no }}</span>
                </td>
                <td class="px-6 py-4 text-gray-700">{{ formatDate(stockReturn.return_date) }}</td>
                <td class="px-6 py-4 text-gray-700">{{ stockReturn.user?.name || 'N/A' }}</td>
                <td class="px-6 py-4 text-center">
                  <select
                    :value="stockReturn.status"
                    @change="updateStatus(stockReturn, $event.target.value)"
                    :class="getStatusClass(stockReturn.status)"
                    class="status-dropdown px-8 py-1.5 rounded-[5px] text-white font-medium text-sm cursor-pointer border-0 focus:ring-2 focus:ring-offset-1"
                  >
                    <option value="pending" class="bg-gray-100 text-gray-800">PENDING</option>
                    <option value="approved" class="bg-gray-100 text-gray-800">APPROVED</option>
                    <option value="completed" class="bg-gray-100 text-gray-800">COMPLETED</option>
                  </select>
                </td>
                <td class="px-6 py-4 text-center">
                  <button
                    @click="openViewModal(stockReturn)"
                    class="px-4 py-1.5 text-white bg-green-600 rounded-[5px] hover:bg-green-700 transition font-medium text-sm"
                  >
                    View
                  </button>
                </td>
              </tr>
              <tr v-if="!stockTransferReturns.data || stockTransferReturns.data.length === 0">
                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                  No Stock Transfer Returns found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between px-6 py-4 bg-blue-50 border-t border-gray-200" v-if="stockTransferReturns.links && stockTransferReturns.links.length > 3">
          <div class="text-sm text-gray-700 font-medium">
            Showing {{ stockTransferReturns.from }} to {{ stockTransferReturns.to }} of {{ stockTransferReturns.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              v-for="link in stockTransferReturns.links"
              :key="link.label"
              @click="link.url ? router.visit(link.url) : null"
              :disabled="!link.url"
              :class="[
                'px-3 py-1.5 rounded-[5px] font-medium text-sm transition',
                link.active
                  ? 'bg-blue-600 text-white'
                  : link.url
                  ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                  : 'bg-gray-200 text-gray-400 cursor-not-allowed'
              ]"
              v-html="link.label"
            ></button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <StockTransferReturnCreateModal 
      v-model:open="isCreateModalOpen"
      :products="products"
      :measurementUnits="measurementUnits"
      :users="users"
      :returnNo="returnNo"
    />

    <!-- View Modal -->
    <StockTransferReturnViewModel
      v-model:open="isViewModalOpen"
      :stock-transfer-return="selectedStockReturn"
      v-if="selectedStockReturn"
    />


  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, Head, router } from '@inertiajs/vue3';
import { logActivity } from '@/composables/useActivityLog';
import AppLayout from '@/Layouts/AppLayout.vue';
import StockTransferReturnCreateModal from './Components/StockTransferReturnCreateModal.vue';
import StockTransferReturnViewModel from './Components/StockTransferReturnViewModel.vue';
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

defineProps({
  stockTransferReturns: Object,
  products: Array,
  measurementUnits: Array,
  users: Array,
  returnNo: String,
});

const { goToShopsTab } = useDashboardNavigation();

const isCreateModalOpen = ref(false);
const isViewModalOpen = ref(false);
const selectedStockReturn = ref(null);

const openCreateModal = () => {
  isCreateModalOpen.value = true;
};

const openViewModal = async (stockReturn) => {
  selectedStockReturn.value = stockReturn;
  isViewModalOpen.value = true;

  // Log view activity
  await logActivity('view', 'stock_transfer_returns', {
    return_id: stockReturn.id,
    return_number: stockReturn.return_no,
    return_date: stockReturn.return_date,
    user: stockReturn.user?.name || 'N/A',
    status: stockReturn.status,
  });
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
};

const getStatusClass = (status) => {
  const classes = {
    'pending': 'bg-yellow-500 hover:bg-yellow-600 focus:ring-yellow-400',
    'approved': 'bg-green-600 hover:bg-green-700 focus:ring-green-400',
    'completed': 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-400'
  };
  return classes[status] || 'bg-gray-600 hover:bg-gray-700 focus:ring-gray-400';
};

const updateStatus = (stockReturn, newStatus) => {
  router.patch(`/stock-transfer-returns/${stockReturn.id}/status`, { status: newStatus }, {
    onSuccess: () => {
      // Status updated successfully
    },
    onError: () => {
      // Error occurred
    }
  });
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
