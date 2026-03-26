<template>
  <div v-if="open" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-900 rounded-lg p-6 w-full max-w-4xl max-h-screen overflow-y-auto">
      <h2 class="text-2xl font-bold text-white mb-4">GRN Return Details</h2>
      <div class="bg-gray-800 p-4 rounded mb-4">
        <div class="grid grid-cols-2 gap-4">
          <div><span class="text-gray-400">GRN Number:</span><span class="text-white ml-2">{{ ret?.grn?.grn_no || 'N/A' }}</span></div>
          <div><span class="text-gray-400">Date:</span><span class="text-white ml-2">{{ formatDate(ret?.date) }}</span></div>
        </div>
      </div>
      <h3 class="text-xl font-bold text-white mb-2">Returned Products</h3>
      <div class="overflow-x-auto mb-4">
        <table class="w-full text-white text-sm">
          <thead class="bg-blue-600">
            <tr>
              <th class="px-4 py-2">Product</th>
              <th class="px-4 py-2">Unit</th>
              <th class="px-4 py-2">Qty</th>
              <th class="px-4 py-2">Return Qty</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, idx) in editableItems" :key="item.id ?? idx" class="border-b border-gray-700">
              <td class="px-4 py-2">{{ item.product?.name || item.products?.name || 'N/A' }}</td>
              <td class="px-4 py-2">{{ getUnitName(item) }}</td>
              <td class="px-4 py-2">{{ getOriginalQty(item) ?? 'N/A' }}</td>
              <td class="px-4 py-2">
                <input
                  type="number"
                  v-model.number="editableItems[idx].qty"
                  class="w-full bg-gray-800 text-white px-2 py-1 rounded"
                />
              </td>
            </tr>
            <tr v-if="editableItems.length === 0">
              <td colspan="4" class="px-4 py-4 text-center text-gray-400">No returned products</td>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- ACTION BUTTONS -->
        <div class="flex justify-end gap-2">
          <button type="button" @click="close"
                  class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-600">
            Cancel
          </button>

          <button type="button"
                  @click="save"
                  :disabled="saving"
                  class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
            <span v-if="!saving">Update GRN Return</span>
            <span v-else>Updating...</span>
          </button>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  open: Boolean,
  ret: Object,
  measurementUnits: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:open', 'save']);

const saving = ref(false);

const close = () => {
    emit('update:open', false);
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const formatNumber = (number) => {
    return parseFloat(number || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

const getOriginalQty = (item) => {
  try {
    const grn = props.ret?.grn || {};
    const grnProducts = grn.grnProducts || grn.grn_products || [];
    const pid = item.products_id ?? item.product_id ?? null;
    if (!pid) return null;
    const match = (Array.isArray(grnProducts) ? grnProducts : (grnProducts.data || [])).find(gp => Number(gp.product_id) === Number(pid));
    return match ? (match.qty ?? null) : null;
  } catch (e) {
    return null;
  }
}

const getUnitName = (item) => {
  // 1. Try direct unit on item (if exists)
  if (item.unit?.name) return item.unit.name
  if (item.measurement_units?.name) return item.measurement_units.name

  // 2. Try product-level unit ID lookup
  const unitId = item.product?.purchase_unit_id ?? item.product?.measurement_unit_id
  if (unitId && Array.isArray(props.measurementUnits)) {
    const found = props.measurementUnits.find(u => Number(u.id) === Number(unitId))
    if (found) return found.name
  }

  // fallback
  return 'N/A'
}

// Local editable copy of returned products so we don't mutate props directly
const editableItems = ref([]);

const syncEditable = () => {
  try {
    editableItems.value = JSON.parse(JSON.stringify(props.ret?.grn_return_products || []));
  } catch (e) {
    editableItems.value = [];
  }
}

watch(() => props.ret, () => {
  syncEditable();
}, { immediate: true });

watch(() => props.open, (v) => {
  if (v) syncEditable();
});

const save = () => {
  if (!props.ret) return;

  // prepare payload matching controller expectations
  const payload = {
    grn_id: props.ret.grn_id ?? props.ret.grn?.id ?? null,
    date: props.ret.date ?? null,
    user_id: props.ret.user_id ?? props.ret.user?.id ?? null,
    products: (editableItems.value || []).map(i => ({
      product_id: i.products_id ?? i.product_id ?? i.product?.id ?? i.products?.id ?? null,
      qty: Number(i.qty) ?? 0,
      remarks: i.remarks ?? null,
    })).filter(p => p.product_id !== null),
  };

  saving.value = true;

  // send patch to backend route
  router.patch(route('grn-returns.update', props.ret.id), payload, {
    onSuccess: (page) => {
      saving.value = false;
      // emit for parent listeners and close modal
      emit('save', payload);
      close();
    },
    onError: (errors) => {
      saving.value = false;
      // keep modal open for correction; Inertia will populate validation errors
    }
  });
}

const getStatusText = (status) => {
    const statuses = { '0': 'INACTIVE', '1': 'ACTIVE', '2': 'DEFAULT' };
    return statuses[status] || 'UNKNOWN';
};
</script>
