<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50 p-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
          <button
            @click="goToStoresTab"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-4xl font-bold text-gray-800">Purchase Orders</h1>
        </div>
        <button
          @click="openCreateModal"
          class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 transition-all duration-200"
        >
          + Add Purchase Order
        </button>
      </div>

      <!-- Flash messages -->
      <div
        v-if="$page.props.flash?.success"
        class="mb-4 px-4 py-3 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm"
      >
        {{ $page.props.flash.success }}
      </div>
      <div
        v-if="$page.props.flash?.error || $page.props.errors?.error"
        class="mb-4 px-4 py-3 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"
      >
        {{ $page.props.flash?.error || $page.props.errors?.error }}
      </div>

      <!-- Table -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b-2 border-blue-600">
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">PO Number</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Supplier</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Date</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Products</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">
                Total ({{ currencySymbol?.currency_symbol || '' }})
              </th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">Status</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="po in purchaseOrders.data"
              :key="po.id"
              class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200"
            >
              <td class="px-4 py-4 font-semibold text-gray-900">{{ po.order_number }}</td>
              <td class="px-4 py-4 text-sm text-gray-800">{{ po.supplier?.name || 'N/A' }}</td>
              <td class="px-4 py-4 text-sm text-gray-800">{{ formatDate(po.order_date) }}</td>
              <td class="px-4 py-4 text-sm text-gray-700">{{ po.products?.length || 0 }} items</td>
              <td class="px-4 py-4 text-sm text-gray-900 font-semibold">
                {{ formatNumber(po.total_amount) }}
              </td>
              <td class="px-4 py-4 text-center">
                <span :class="getStatusClass(po.status)">
                  {{ po.status ? po.status.charAt(0).toUpperCase() + po.status.slice(1) : 'Pending' }}
                </span>
              </td>
              <td class="px-4 py-4">
                <div class="flex gap-2 justify-center flex-wrap">
                  <button
                    @click="openViewModal(po)"
                    class="px-4 py-2 text-xs font-medium text-white bg-green-600 rounded-[5px] hover:bg-green-700 transition-all duration-200"
                  >
                    View
                  </button>
                  <!-- PDF Download -->
                  <a
                    :href="route('purchase-orders.pdf', po.id)"
                    target="_blank"
                    class="px-4 py-2 text-xs font-medium text-white bg-orange-500 rounded-[5px] hover:bg-orange-600 transition-all duration-200 inline-block"
                  >
                    PDF
                  </a>
                  <!-- Approve (admin only, pending POs) -->
                  <button
                    v-if="isAdmin && po.status === 'pending'"
                    @click="updateStatus(po, 'approved')"
                    class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-[5px] hover:bg-blue-700 transition-all duration-200"
                  >
                    Approve
                  </button>
                  <!-- Mark Completed (admin only, approved POs) -->
                  <button
                    v-if="isAdmin && po.status === 'approved'"
                    @click="updateStatus(po, 'completed')"
                    class="px-4 py-2 text-xs font-medium text-white bg-purple-600 rounded-[5px] hover:bg-purple-700 transition-all duration-200"
                  >
                    Complete
                  </button>
                  <!-- Delete (pending only) -->
                  <button
                    v-if="po.status === 'pending'"
                    @click="confirmDelete(po)"
                    class="px-4 py-2 text-xs font-medium text-white bg-red-600 rounded-[5px] hover:bg-red-700 transition-all duration-200"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="!purchaseOrders.data || purchaseOrders.data.length === 0">
              <td colspan="7" class="px-6 py-8 text-center text-gray-500 font-medium">
                No Purchase Orders found. Click "+ Add Purchase Order" to create one.
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div
          class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200"
          v-if="purchaseOrders.links && purchaseOrders.links.length > 3"
        >
          <div class="text-sm text-gray-600">
            Showing {{ purchaseOrders.from }} to {{ purchaseOrders.to }} of
            {{ purchaseOrders.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              v-for="link in purchaseOrders.links"
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
    <PurchaseOrderCreateModal
      v-model:open="isCreateModalOpen"
      :suppliers="suppliers"
      :available-products="availableProducts"
      :measurement-units="measurementUnits"
      :po-number="poNumber"
    />

    <!-- View Modal -->
    <PurchaseOrderViewModel
      v-model:open="isViewModalOpen"
      :purchase-order="selectedPo"
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useDashboardNavigation } from '@/composables/useDashboardNavigation';
import PurchaseOrderCreateModal from './Components/PurchaseOrderCreateModal.vue';
import PurchaseOrderViewModel from './Components/PurchaseOrderViewModel.vue';

const props = defineProps({
  purchaseOrders: Object,
  suppliers: Array,
  availableProducts: Array,
  measurementUnits: Array,
  poNumber: String,
  currencySymbol: Object,
});

const page = usePage();
const { goToStoresTab } = useDashboardNavigation();

const isAdmin = computed(() => page.props.auth?.user?.role === 0);
const isCreateModalOpen = ref(false);
const isViewModalOpen = ref(false);
const selectedPo = ref(null);

const openCreateModal = () => { isCreateModalOpen.value = true; };

const openViewModal = (po) => {
  selectedPo.value = po;
  isViewModalOpen.value = true;
};

const confirmDelete = (po) => {
  if (confirm(`Delete Purchase Order "${po.order_number}"? This cannot be undone.`)) {
    router.delete(route('purchase-orders.destroy', po.id));
  }
};

const updateStatus = (po, status) => {
  const labels = { approved: 'approve', completed: 'mark as completed', cancelled: 'cancel' };
  if (confirm(`Are you sure you want to ${labels[status] || status} this Purchase Order?`)) {
    router.patch(route('purchase-orders.update-status', po.id), { status });
  }
};

const getStatusClass = (status) => {
  const map = {
    pending:   'bg-yellow-500 text-white px-3 py-1 rounded-full text-xs font-semibold',
    approved:  'bg-green-500 text-white px-3 py-1 rounded-full text-xs font-semibold',
    completed: 'bg-blue-500 text-white px-3 py-1 rounded-full text-xs font-semibold',
    cancelled: 'bg-red-500 text-white px-3 py-1 rounded-full text-xs font-semibold',
  };
  return map[status] || 'bg-gray-500 text-white px-3 py-1 rounded-full text-xs font-semibold';
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatNumber = (number) => {
  return Math.round(parseFloat(number || 0)).toLocaleString('en-US');
};
</script>
