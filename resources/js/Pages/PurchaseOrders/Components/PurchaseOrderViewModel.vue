<template>
  <Modal :show="open" @close="close" max-width="5xl">
    <div class="p-6 bg-gray-50">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
        <h2 class="text-2xl font-bold text-blue-600">Purchase Order Details</h2>
        <button type="button" @click="close"
          class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-200 rounded-full transition-all duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div v-if="purchaseOrder">
        <!-- Info block -->
        <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
          <h3 class="mb-3 text-lg font-semibold text-blue-600 flex items-center gap-2">
            📋 Information
          </h3>
          <div class="grid grid-cols-2 gap-4">
            <div class="flex flex-col gap-1">
              <span class="text-sm font-medium text-gray-600">PO Number:</span>
              <span class="text-gray-900 font-semibold">{{ purchaseOrder.order_number }}</span>
            </div>
            <div class="flex flex-col gap-1">
              <span class="text-sm font-medium text-gray-600">Supplier:</span>
              <span class="text-gray-900 font-semibold">{{ purchaseOrder.supplier?.name || 'N/A' }}</span>
            </div>
            <div class="flex flex-col gap-1">
              <span class="text-sm font-medium text-gray-600">Date:</span>
              <span class="text-gray-900 font-semibold">{{ formatDate(purchaseOrder.order_date) }}</span>
            </div>
            <div class="flex flex-col gap-1">
              <span class="text-sm font-medium text-gray-600">Created By:</span>
              <span class="text-gray-900 font-semibold">{{ purchaseOrder.user?.name || 'N/A' }}</span>
            </div>
            <div class="flex flex-col gap-1">
              <span class="text-sm font-medium text-gray-600">Status:</span>
              <span :class="getStatusClass(purchaseOrder.status)">
                {{ purchaseOrder.status ? (purchaseOrder.status.charAt(0).toUpperCase() + purchaseOrder.status.slice(1)) : 'Pending' }}
              </span>
            </div>
            <div class="flex flex-col gap-1" v-if="purchaseOrder.remarks">
              <span class="text-sm font-medium text-gray-600">Remarks:</span>
              <span class="text-gray-900">{{ purchaseOrder.remarks }}</span>
            </div>
          </div>
        </div>

        <!-- Products table -->
        <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
          <h3 class="mb-3 text-lg font-semibold text-blue-600 flex items-center gap-2">
            📦 Products
          </h3>
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b-2 border-blue-600">
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Product</th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Qty</th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Unit</th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Discount (%)</th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">
                    Purchase Price ({{ page.props.currency || '' }})
                  </th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">
                    Total ({{ page.props.currency || '' }})
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in purchaseOrder.products" :key="item.id"
                  class="border-b border-gray-200 hover:bg-gray-50">
                  <td class="px-4 py-4 text-gray-900">{{ item.product?.name || 'N/A' }}</td>
                  <td class="px-4 py-4 text-gray-900">{{ item.quantity }}</td>
                  <td class="px-4 py-4 text-gray-900">
                    {{ item.measurement_unit?.name || item.product?.measurement_unit?.name || 'N/A' }}
                  </td>
                  <td class="px-4 py-4 text-gray-900">{{ item.discount_percentage }}%</td>
                  <td class="px-4 py-4 text-gray-900">{{ formatNumber(item.purchase_price) }}</td>
                  <td class="px-4 py-4 text-gray-900 font-semibold">{{ formatNumber(item.total) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Grand Total -->
        <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
          <div class="space-y-2">
            <div class="flex justify-end gap-4 pb-2 border-b border-gray-300">
              <span class="font-semibold text-gray-900">Subtotal:</span>
              <span class="font-semibold text-gray-900">
                {{ formatNumber(purchaseOrder.subtotal) }} ({{ page.props.currency || '' }})
              </span>
            </div>
            <div class="flex justify-end gap-4 pb-2 border-b border-gray-300">
              <span class="font-semibold text-red-600">Discount:</span>
              <span class="font-semibold text-red-600">
                -{{ formatNumber(purchaseOrder.discount) }} ({{ page.props.currency || '' }})
              </span>
            </div>
            <div class="flex justify-end gap-4 pb-2 border-b border-gray-300">
              <span class="font-semibold text-green-600">Tax:</span>
              <span class="font-semibold text-green-600">
                +{{ formatNumber(purchaseOrder.tax_total) }} ({{ page.props.currency || '' }})
              </span>
            </div>
            <div class="flex justify-end gap-4 pt-2 bg-blue-50 -mx-4 -mb-4 px-4 py-3 rounded-b-xl">
              <span class="font-bold text-lg text-gray-900">Grand Total:</span>
              <span class="font-bold text-lg text-blue-600">
                {{ formatNumber(purchaseOrder.total_amount) }} ({{ page.props.currency || '' }})
              </span>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="py-10 text-center text-gray-400">No purchase order selected.</div>
    </div>
  </Modal>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const page = usePage();

defineProps({
  open: Boolean,
  purchaseOrder: { type: Object, default: null },
});

const emit = defineEmits(['update:open']);

const close = () => emit('update:open', false);

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatNumber = (n) => Math.round(parseFloat(n || 0)).toLocaleString('en-US');

const getStatusClass = (status) => {
  const map = {
    pending:   'px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500 text-white',
    approved:  'px-3 py-1 rounded-full text-xs font-semibold bg-green-500 text-white',
    completed: 'px-3 py-1 rounded-full text-xs font-semibold bg-blue-500 text-white',
    cancelled: 'px-3 py-1 rounded-full text-xs font-semibold bg-red-500 text-white',
  };
  return map[status] || 'px-3 py-1 rounded-full text-xs font-semibold bg-gray-500 text-white';
};
</script>
