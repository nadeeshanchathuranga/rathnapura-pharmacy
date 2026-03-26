<template>
  <TransitionRoot appear :show="open" as="template">
    <Dialog as="div" @close="closeModal" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/25" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-full p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel
              class="w-full max-w-4xl p-6 overflow-hidden text-left align-middle transition-all transform bg-gray-50 shadow-2xl rounded-2xl max-h-[85vh] overflow-y-auto [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]"
            >
              <!-- Header -->
              <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-blue-600">
                  Stock Transfer Return Details
                </h2>
                <button
                  @click="closeModal"
                  class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-200 rounded-full transition-all duration-200"
                >
                  <svg
                    class="w-6 h-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"
                    ></path>
                  </svg>
                </button>
              </div>

              <div v-if="stockTransferReturn">
                <!-- Return Information -->
                <div
                  class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200"
                >
                  <h3
                    class="mb-3 text-lg font-semibold text-blue-600 flex items-center gap-2"
                  >
                    📋 Return Information
                  </h3>
                  <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div class="p-3 bg-white rounded-lg border border-gray-200">
                      <p class="text-xs text-gray-600">Return Number</p>
                      <p class="text-sm font-medium text-gray-800">
                        {{ stockTransferReturn.return_no }}
                      </p>
                    </div>
                    <div class="p-3 bg-white rounded-lg border border-gray-200">
                      <p class="text-xs text-gray-600">Return Date</p>
                      <p class="text-sm font-medium text-gray-800">
                        {{ formatDate(stockTransferReturn.return_date) }}
                      </p>
                    </div>
                    <div class="p-3 bg-white rounded-lg border border-gray-200">
                      <p class="text-xs text-gray-600">User</p>
                      <p class="text-sm font-medium text-gray-800">
                        {{ stockTransferReturn.user?.name || "N/A" }}
                      </p>
                    </div>
                    <div class="p-3 bg-white rounded-lg border border-gray-200">
                      <p class="text-xs text-gray-600">Status</p>
                      <span :class="getStatusClass(stockTransferReturn.status)">
                        {{ stockTransferReturn.status.toUpperCase() }}
                      </span>
                    </div>
                    <div
                      class="md:col-span-2 p-3 bg-white rounded-lg border border-gray-200"
                    >
                      <p class="text-xs text-gray-600">Reason</p>
                      <p class="text-sm font-medium text-gray-800">
                        {{ stockTransferReturn.reason || "N/A" }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Products -->
                <div
                  class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200"
                >
                  <h3
                    class="mb-3 text-lg font-semibold text-green-600 flex items-center gap-2"
                  >
                    📦 Products
                  </h3>
                  <div class="overflow-x-auto">
                    <table class="w-full text-left">
                      <thead class="border-b-2 border-blue-600">
                        <tr>
                          <th class="px-4 py-3 text-sm font-semibold text-blue-700">
                            Product
                          </th>
                          <th class="px-4 py-3 text-sm font-semibold text-blue-700">
                            Quantity
                          </th>
                          <th class="px-4 py-3 text-sm font-semibold text-blue-700">
                            Unit
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr
                          v-for="item in stockTransferReturn.products"
                          :key="item.id"
                          class="border-b border-gray-200 hover:bg-gray-50 transition"
                        >
                          <td class="px-4 py-3 text-gray-700 font-medium">
                            {{ item.product?.name || "N/A" }}
                          </td>
                          <td class="px-4 py-3 text-gray-700">
                            {{ item.stock_transfer_quantity }}
                          </td>
                          <td class="px-4 py-3 text-gray-700">
                            {{ item.measurement_unit?.name || "N/A" }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { watch, onUnmounted } from "vue";
import { TransitionRoot, TransitionChild, Dialog, DialogPanel } from "@headlessui/vue";

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  stockTransferReturn: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["update:open"]);

// Prevent background scrolling when modal is open
watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "";
    }
  }
);

// Cleanup on unmount
onUnmounted(() => {
  document.body.style.overflow = "";
});

const closeModal = () => {
  emit("update:open", false);
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
    pending:
      "bg-yellow-500 text-white px-3 py-1.5 rounded-[5px] font-medium text-sm inline-block",
    approved:
      "bg-green-600 text-white px-3 py-1.5 rounded-[5px] font-medium text-sm inline-block",
    completed:
      "bg-blue-600 text-white px-3 py-1.5 rounded-[5px] font-medium text-sm inline-block",
  };
  return (
    classes[status] ||
    "bg-gray-600 text-white px-3 py-1.5 rounded-[5px] font-medium text-sm inline-block"
  );
};
</script>
