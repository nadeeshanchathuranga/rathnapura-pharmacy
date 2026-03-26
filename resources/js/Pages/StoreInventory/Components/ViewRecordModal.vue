<template>
  <div
    v-if="show && record"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    @click.self="close"
  >
    <div
      class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto"
    >
      <!-- Modal Header -->
      <div
        class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 rounded-t-2xl flex justify-between items-center"
      >
        <h2 class="text-2xl font-bold">Inventory Record Details</h2>
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
      <div class="p-6 space-y-6">
        <!-- Reference Number & Status -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-500 mb-1">Reference Number</label>
            <div class="text-lg font-bold text-gray-900">{{ record.reference_no }}</div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
            <span
              class="inline-block px-3 py-1 rounded-full text-sm font-medium"
              :class="getStatusBadgeClass(record.status)"
            >
              {{ record.status }}
            </span>
          </div>
        </div>

        <!-- Product Information -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h3 class="text-sm font-semibold text-gray-700 mb-3">Product Information</h3>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs text-gray-500 mb-1">Product Name</label>
              <div class="font-medium text-gray-900">{{ record.product?.name }}</div>
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">Barcode</label>
              <div class="font-medium text-gray-900">{{ record.product?.barcode || 'N/A' }}</div>
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">Brand</label>
              <div class="font-medium text-gray-900">{{ record.product?.brand?.name || "N/A" }}</div>
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">Category</label>
              <div class="font-medium text-gray-900">{{ record.product?.category?.name || "N/A" }}</div>
            </div>
          </div>
        </div>

        <!-- Transaction Details -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <h3 class="text-sm font-semibold text-gray-700 mb-3">Transaction Details</h3>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs text-gray-500 mb-1">Transaction Type</label>
              <span
                class="inline-block px-3 py-1 rounded-full text-xs font-medium"
                :class="getTransactionTypeBadgeClass(record.transaction_type)"
              >
                {{ formatTransactionType(record.transaction_type) }}
              </span>
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">Transaction Date</label>
              <div class="font-medium text-gray-900">{{ formatDate(record.transaction_date) }}</div>
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">Created By</label>
              <div class="font-medium text-gray-900">{{ record.user?.name || "N/A" }}</div>
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">Created At</label>
              <div class="font-medium text-gray-900">{{ formatDateTime(record.created_at) }}</div>
            </div>
          </div>
        </div>

        <!-- Quantity Changes -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
          <h3 class="text-sm font-semibold text-gray-700 mb-3">Quantity Changes</h3>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-xs text-gray-500 mb-1">Quantity Before</label>
              <div class="text-xl font-bold text-gray-700">
                {{ Number(record.quantity_before).toFixed(2) }}
                <span class="text-sm text-gray-500">{{ record.product?.purchase_unit?.symbol }}</span>
              </div>
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">Change</label>
              <div
                class="text-xl font-bold"
                :class="Number(record.quantity_change) >= 0 ? 'text-green-600' : 'text-red-600'"
              >
                {{ Number(record.quantity_change) >= 0 ? '+' : '' }}{{ Number(record.quantity_change).toFixed(2) }}
                <span class="text-sm">{{ record.product?.purchase_unit?.symbol }}</span>
              </div>
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">Quantity After</label>
              <div class="text-xl font-bold text-gray-900">
                {{ Number(record.quantity_after).toFixed(2) }}
                <span class="text-sm text-gray-500">{{ record.product?.purchase_unit?.symbol }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Remarks -->
        <div v-if="record.remarks">
          <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="text-gray-900">{{ record.remarks }}</p>
          </div>
        </div>

        <!-- Visual Timeline -->
        <div class="relative pt-4">
          <div class="flex items-center justify-between">
            <!-- Before -->
            <div class="flex-1 text-center">
              <div class="w-16 h-16 mx-auto bg-gray-200 rounded-full flex items-center justify-center mb-2">
                <span class="text-lg font-bold text-gray-700">{{ Number(record.quantity_before).toFixed(0) }}</span>
              </div>
              <div class="text-xs text-gray-500">Before</div>
            </div>

            <!-- Arrow -->
            <div class="flex-1 flex items-center justify-center">
              <svg class="w-12 h-12" :class="Number(record.quantity_change) >= 0 ? 'text-green-500' : 'text-red-500'" fill="currentColor" viewBox="0 0 20 20">
                <path v-if="Number(record.quantity_change) >= 0" fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                <path v-else fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
              </svg>
            </div>

            <!-- After -->
            <div class="flex-1 text-center">
              <div class="w-16 h-16 mx-auto bg-blue-500 rounded-full flex items-center justify-center mb-2">
                <span class="text-lg font-bold text-white">{{ Number(record.quantity_after).toFixed(0) }}</span>
              </div>
              <div class="text-xs text-gray-500">After</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-4 bg-gray-50 rounded-b-2xl flex justify-end">
        <button
          @click="close"
          class="px-6 py-2.5 rounded-lg font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    show: Boolean,
    record: Object,
  },
  methods: {
    close() {
      this.$emit("close");
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
      });
    },
    formatDateTime(datetime) {
      return new Date(datetime).toLocaleString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    },
    formatTransactionType(type) {
      const types = {
        adjustment: "Manual Adjustment",
        physical_count: "Physical Count",
        damage: "Damaged Goods",
        expired: "Expired Items",
        return: "Supplier Return",
        transfer_in: "Transfer In",
        transfer_out: "Transfer Out",
      };
      return types[type] || type;
    },
    getTransactionTypeBadgeClass(type) {
      const classes = {
        adjustment: "bg-blue-100 text-blue-800",
        physical_count: "bg-purple-100 text-purple-800",
        damage: "bg-red-100 text-red-800",
        expired: "bg-orange-100 text-orange-800",
        return: "bg-green-100 text-green-800",
        transfer_in: "bg-emerald-100 text-emerald-800",
        transfer_out: "bg-yellow-100 text-yellow-800",
      };
      return classes[type] || "bg-gray-100 text-gray-800";
    },
    getStatusBadgeClass(status) {
      const classes = {
        pending: "bg-yellow-100 text-yellow-800",
        completed: "bg-green-100 text-green-800",
        cancelled: "bg-red-100 text-red-800",
      };
      return classes[status] || "bg-gray-100 text-gray-800";
    },
  },
};
</script>
