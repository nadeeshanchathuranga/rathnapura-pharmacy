<template>
  <Head title="Shift Management" />

  <AppLayout>
    <div class="min-h-screen bg-gray-50 p-6">
      <div class="mx-auto w-full max-w-7xl">
      <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div class="flex items-center gap-3">
          <button
            @click="goToShopsTab"
            class="inline-flex h-10 items-center justify-center rounded-[5px] border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition-all duration-200 hover:border-gray-300 hover:bg-gray-50"
          >
            ← Back
          </button>
          <h1 class="text-3xl font-bold text-black">Shift Management</h1>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <Link
            :href="route('till-management.index')"
            class="inline-flex h-10 items-center justify-center rounded-[5px] bg-emerald-600 px-5 text-sm font-medium text-white transition-all duration-200 hover:bg-emerald-700"
          >
            Till Management
          </Link>
          <button
            v-if="!activeShift"
            @click="isStartModalOpen = true"
            class="inline-flex h-10 items-center justify-center rounded-[5px] bg-blue-600 px-5 text-sm font-medium text-white transition-all duration-200 hover:bg-blue-700"
          >
            Start Shift
          </button>
          <button
            v-else
            @click="goToEndShiftPage"
            class="inline-flex h-10 items-center justify-center rounded-[5px] bg-red-600 px-5 text-sm font-medium text-white transition-all duration-200 hover:bg-red-700"
          >
            End Shift
          </button>
        </div>
      </div>

      <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-center">
          <div class="lg:col-span-4">
            <input
              v-model="filterForm.search"
              type="text"
              placeholder="Search by user or note"
              class="h-10 w-full rounded-[5px] border border-gray-300 px-4 text-sm focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div class="lg:col-span-4">
            <select
              v-model="filterForm.status"
              class="h-10 w-full rounded-[5px] border border-gray-300 px-4 text-sm focus:ring-2 focus:ring-blue-500"
            >
              <option value="">All Statuses</option>
              <option value="open">open</option>
              <option value="closed">closed</option>
            </select>
          </div>

          <div class="lg:col-span-4 inline-flex h-10 items-center gap-2 text-sm text-gray-700">
            <input
              v-model="filterForm.only_my_shifts"
              type="checkbox"
              class="w-4 h-4 rounded border-gray-300 text-blue-600"
            />
            <span>Show only my shifts</span>
          </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3">
          <button
            @click="applyFilters"
            class="inline-flex h-10 items-center justify-center rounded-[5px] bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700"
          >
            Filter
          </button>
          <button
            @click="resetFilters"
            class="inline-flex h-10 items-center justify-center rounded-[5px] bg-gray-100 px-5 text-sm font-medium text-gray-700 hover:bg-gray-200"
          >
            Reset
          </button>
        </div>
      </div>

      <div class="rounded-2xl border border-gray-200 bg-white overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-gray-200 bg-gray-50">
                <th class="px-4 py-3 text-sm font-semibold text-blue-600">#</th>
                <th class="px-4 py-3 text-sm font-semibold text-blue-600">User</th>
                <th class="px-4 py-3 text-sm font-semibold text-blue-600">Start</th>
                <th class="px-4 py-3 text-sm font-semibold text-blue-600">End</th>
                <th class="px-4 py-3 text-sm font-semibold text-blue-600 text-right">Start Amt</th>
                <th class="px-4 py-3 text-sm font-semibold text-blue-600 text-right">Expected</th>
                <th class="px-4 py-3 text-sm font-semibold text-blue-600 text-right">Sales</th>
                <th class="px-4 py-3 text-sm font-semibold text-blue-600 text-right">Till Txns</th>
                <th class="px-4 py-3 text-sm font-semibold text-blue-600">Status</th>
                <th class="px-4 py-3 text-sm font-semibold text-blue-600">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(shift, index) in shifts.data"
                :key="shift.id"
                class="border-b border-gray-200 hover:bg-gray-50"
              >
                <td class="px-4 py-4 text-sm text-gray-700">{{ rowNumber(index) }}</td>
                <td class="px-4 py-4 text-sm text-gray-700">{{ shift.user_name }}</td>
                <td class="px-4 py-4 text-sm text-gray-700">{{ shift.start_time || '-' }}</td>
                <td class="px-4 py-4 text-sm text-gray-700">{{ shift.end_time || '-' }}</td>
                <td class="px-4 py-4 text-sm text-gray-700 text-right">{{ formatMoney(shift.opening_till_amount) }}</td>
                <td class="px-4 py-4 text-sm text-gray-700 text-right">{{ formatMoney(shift.expected_closing_amount) }}</td>
                <td class="px-4 py-4 text-sm text-gray-700 text-right">{{ formatMoney(shift.total_sales) }}</td>
                <td class="px-4 py-4 text-sm text-gray-700 text-right">{{ shift.transactions_count }}</td>
                <td class="px-4 py-4 text-sm">
                  <span class="inline-flex items-center gap-1 rounded-[5px] bg-gray-600 px-3 py-1 text-xs font-medium text-white">
                    <span v-if="shift.variance_amount < 0" class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                    {{ shift.status }}
                  </span>
                </td>
                <td class="px-4 py-4">
                  <div class="flex flex-wrap items-center gap-2">
                    <button
                      @click="showShift(shift.id)"
                      class="inline-flex h-8 items-center justify-center rounded-[5px] bg-green-600 px-3 text-xs font-medium text-white hover:bg-green-700"
                    >
                      Show
                    </button>
                    <button
                      @click="deleteShift(shift.id)"
                      class="inline-flex h-8 items-center justify-center rounded-[5px] bg-red-600 px-3 text-xs font-medium text-white hover:bg-red-700"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!shifts.data || shifts.data.length === 0">
                <td colspan="10" class="px-6 py-8 text-center text-sm text-gray-500">No shifts found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      </div>
    </div>

    <Modal :show="isStartModalOpen" @close="isStartModalOpen = false">
      <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Start Shift</h3>
        <label class="block text-sm font-medium text-gray-700 mb-2">Opening Till Amount</label>
        <input
          v-model="startForm.opening_till_amount"
          type="number"
          step="0.01"
          min="0"
          class="h-10 w-full rounded-[5px] border border-gray-300 px-4 text-sm focus:ring-2 focus:ring-blue-500"
        />
        <p v-if="startForm.errors.opening_till_amount" class="mt-2 text-sm text-red-600">
          {{ startForm.errors.opening_till_amount }}
        </p>
        <label class="block text-sm font-medium text-gray-700 mb-2 mt-4">Opening Note (Optional)</label>
        <textarea
          v-model="startForm.start_note"
          rows="3"
          class="w-full rounded-[5px] border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500"
        ></textarea>
        <p v-if="startForm.errors.start_note" class="mt-2 text-sm text-red-600">
          {{ startForm.errors.start_note }}
        </p>
        <div class="mt-5 flex justify-end gap-3">
          <button
            class="inline-flex h-10 items-center justify-center rounded-[5px] bg-gray-100 px-5 text-sm font-medium text-gray-700"
            @click="isStartModalOpen = false"
          >
            Cancel
          </button>
          <button
            class="inline-flex h-10 items-center justify-center rounded-[5px] bg-blue-600 px-5 text-sm font-medium text-white"
            :disabled="startForm.processing"
            @click="startShift"
          >
            Start
          </button>
        </div>
      </div>
    </Modal>

    <Modal :show="isShowModalOpen" @close="isShowModalOpen = false">
      <div class="p-6" v-if="selectedShift">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Shift #{{ selectedShift.id }}</h3>
        <div class="grid grid-cols-2 gap-3 text-sm text-gray-700">
          <p><strong>User:</strong> {{ selectedShift.user_name }}</p>
          <p><strong>Status:</strong> {{ selectedShift.status }}</p>
          <p><strong>Start:</strong> {{ selectedShift.start_time || '-' }}</p>
          <p><strong>End:</strong> {{ selectedShift.end_time || '-' }}</p>
          <p><strong>Opening:</strong> {{ currency }} {{ formatMoney(selectedShift.opening_till_amount) }}</p>
          <p><strong>Opening Note:</strong> {{ selectedShift.start_note || '-' }}</p>
          <p><strong>Closing:</strong> {{ currency }} {{ formatMoney(selectedShift.closing_cash_amount) }}</p>
          <p><strong>Closing Note:</strong> {{ selectedShift.end_note || '-' }}</p>
          <p><strong>Cash Sales:</strong> {{ currency }} {{ formatMoney(selectedShift.cash_sales_total) }}</p>
          <p><strong>Total Sales:</strong> {{ currency }} {{ formatMoney(selectedShift.total_sales) }}</p>
          <p><strong>Cash In:</strong> {{ currency }} {{ formatMoney(selectedShift.cash_in_total) }}</p>
          <p><strong>Cash Out:</strong> {{ currency }} {{ formatMoney(selectedShift.cash_out_total) }}</p>
          <p><strong>Expected:</strong> {{ currency }} {{ formatMoney(selectedShift.expected_closing_amount) }}</p>
          <p class="col-span-2"><strong>Variance:</strong> {{ currency }} {{ formatMoney(selectedShift.variance_amount) }}</p>
          <p><strong>Sales Count:</strong> {{ selectedShift.sales_count }}</p>
          <p><strong>Till Transaction Count:</strong> {{ selectedShift.transaction_count }}</p>
        </div>

        <div class="mt-5">
          <h4 class="font-semibold text-gray-900 mb-2">Sales in Shift</h4>
          <div class="max-h-44 overflow-auto border border-gray-200 rounded-md">
            <table class="w-full text-xs">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-2 py-1 text-left">Invoice</th>
                  <th class="px-2 py-1 text-left">Date</th>
                  <th class="px-2 py-1 text-right">Net</th>
                  <th class="px-2 py-1 text-right">Paid</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="sale in selectedShift.sales || []" :key="`sale-${sale.id}`" class="border-t border-gray-100">
                  <td class="px-2 py-1">{{ sale.invoice_no }}</td>
                  <td class="px-2 py-1">{{ sale.sale_date }}</td>
                  <td class="px-2 py-1 text-right">{{ formatMoney(sale.net_amount) }}</td>
                  <td class="px-2 py-1 text-right">{{ formatMoney(sale.paid_amount) }}</td>
                </tr>
                <tr v-if="!(selectedShift.sales && selectedShift.sales.length)">
                  <td colspan="4" class="px-2 py-2 text-center text-gray-500">No sales found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="mt-4">
          <h4 class="font-semibold text-gray-900 mb-2">Till Transactions</h4>
          <div class="max-h-44 overflow-auto border border-gray-200 rounded-md">
            <table class="w-full text-xs">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-2 py-1 text-left">Time</th>
                  <th class="px-2 py-1 text-left">Type</th>
                  <th class="px-2 py-1 text-right">Amount</th>
                  <th class="px-2 py-1 text-left">Note</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="txn in selectedShift.transactions || []" :key="`txn-${txn.id}`" class="border-t border-gray-100">
                  <td class="px-2 py-1">{{ txn.transaction_time }}</td>
                  <td class="px-2 py-1">{{ txn.type }}</td>
                  <td class="px-2 py-1 text-right">{{ formatMoney(txn.amount) }}</td>
                  <td class="px-2 py-1">{{ txn.note || '-' }}</td>
                </tr>
                <tr v-if="!(selectedShift.transactions && selectedShift.transactions.length)">
                  <td colspan="4" class="px-2 py-2 text-center text-gray-500">No till transactions found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="mt-5 flex justify-end">
          <button class="inline-flex h-10 items-center justify-center rounded-[5px] bg-gray-100 px-5 text-sm font-medium text-gray-700" @click="isShowModalOpen = false">Close</button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import Modal from "@/Components/Modal.vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import { ref } from "vue";
import axios from "axios";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const props = defineProps({
  activeShift: {
    type: Object,
    default: null,
  },
  shifts: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({
      search: "",
      status: "",
      only_my_shifts: false,
    }),
  },
});

const page = usePage();
const currency = page.props.currency || "Rs.";
const { goToShopsTab } = useDashboardNavigation();

const filterForm = useForm({
  search: props.filters.search || "",
  status: props.filters.status || "",
  only_my_shifts: !!props.filters.only_my_shifts,
});

const startForm = useForm({
  opening_till_amount: 0,
  start_note: "",
});

const isStartModalOpen = ref(false);
const isShowModalOpen = ref(false);
const selectedShift = ref(null);

const formatMoney = (value) => Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const rowNumber = (index) => {
  const pageNumber = props.shifts.current_page || 1;
  const perPage = props.shifts.per_page || 15;
  return (pageNumber - 1) * perPage + index + 1;
};

const applyFilters = () => {
  router.get(route("shift-management.index"), filterForm.data(), {
    preserveState: true,
    replace: true,
  });
};

const resetFilters = () => {
  filterForm.search = "";
  filterForm.status = "";
  filterForm.only_my_shifts = false;
  applyFilters();
};

const startShift = () => {
  startForm.post(route("shift-management.start"), {
    onSuccess: () => {
      isStartModalOpen.value = false;
      startForm.reset();
      window.dispatchEvent(new CustomEvent("shift:started"));
      window.dispatchEvent(new CustomEvent("shift:status-changed"));
    },
  });
};

const goToEndShiftPage = () => {
  router.visit(route("shift-management.end-page"));
};

const showShift = async (shiftId) => {
  try {
    const { data } = await axios.get(route("shift-management.show", shiftId));
    selectedShift.value = data;
    isShowModalOpen.value = true;
  } catch (error) {
    console.error(error);
  }
};

const deleteShift = (shiftId) => {
  if (!confirm("Are you sure you want to delete this shift?")) {
    return;
  }

  router.delete(route("shift-management.destroy", shiftId), {
    preserveScroll: true,
  });
};
</script>
