<template>
  <Head title="Unpaid Sales Report" />

  <AppLayout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-orange-50 p-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
          <button
            @click="goToReportsTab"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-4xl font-bold text-gray-800">Unpaid Sales Report</h1>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white rounded-2xl border border-gray-200 p-5 mb-6 flex flex-wrap gap-4 items-end">
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">From Date</label>
          <input
            type="date"
            v-model="startDate"
            class="px-3 py-2 border border-gray-300 rounded-[5px] text-sm focus:ring-2 focus:ring-orange-400"
          />
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 mb-1">To Date</label>
          <input
            type="date"
            v-model="endDate"
            class="px-3 py-2 border border-gray-300 rounded-[5px] text-sm focus:ring-2 focus:ring-orange-400"
          />
        </div>
        <div class="flex gap-2">
          <button
            @click="applyFilter"
            class="px-5 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-[5px] transition"
          >
            Apply
          </button>
          <button
            @click="resetFilter"
            class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-semibold rounded-[5px] transition"
          >
            Reset
          </button>
        </div>
        <!-- Total Unpaid Summary -->
        <div class="ml-auto bg-orange-100 rounded-xl px-6 py-3 text-orange-700">
          <div class="text-xs font-medium">Total Unpaid Amount</div>
          <div class="text-2xl font-bold">{{ currency }} {{ props.totalUnpaid }}</div>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-800">
            Unpaid Sales ({{ sales.total }} records)
          </h3>
          <button
            @click="exportCsv"
            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-[5px] transition flex items-center gap-2"
          >
            ⬇ Export CSV
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b-2 border-orange-500">
                <th class="px-4 py-3 text-left text-orange-600 font-semibold">Invoice No</th>
                <th class="px-4 py-3 text-left text-orange-600 font-semibold">Customer</th>
                <th class="px-4 py-3 text-left text-orange-600 font-semibold">Cashier</th>
                <th class="px-4 py-3 text-center text-orange-600 font-semibold">Date</th>
                <th class="px-4 py-3 text-right text-orange-600 font-semibold">Total</th>
                <th class="px-4 py-3 text-right text-orange-600 font-semibold">Discount</th>
                <th class="px-4 py-3 text-right text-orange-600 font-semibold">Net Amount</th>
                <th class="px-4 py-3 text-center text-orange-600 font-semibold">Status</th>
                <th class="px-4 py-3 text-center text-orange-600 font-semibold">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr
                v-for="sale in sales.data"
                :key="sale.id"
                class="hover:bg-orange-50 transition-colors"
              >
                <td class="px-4 py-3 font-medium text-gray-800">{{ sale.invoice_no }}</td>
                <td class="px-4 py-3 text-gray-700">{{ sale.customer_name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ sale.cashier }}</td>
                <td class="px-4 py-3 text-center text-gray-600">{{ sale.sale_date }}</td>
                <td class="px-4 py-3 text-right text-gray-700">{{ currency }} {{ sale.total_amount }}</td>
                <td class="px-4 py-3 text-right text-red-500">- {{ currency }} {{ sale.discount }}</td>
                <td class="px-4 py-3 text-right font-bold text-orange-600">{{ currency }} {{ sale.net_amount }}</td>
                <td class="px-4 py-3 text-center">
                  <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-xs font-semibold">
                    Pending
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <button
                    @click="markAsPaid(sale)"
                    :disabled="sale.marking"
                    class="px-3 py-1.5 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white text-xs font-semibold rounded-[5px] transition"
                  >
                    {{ sale.marking ? 'Saving...' : '✅ Mark Paid' }}
                  </button>
                </td>
              </tr>
              <tr v-if="!sales.data || sales.data.length === 0">
                <td colspan="9" class="px-6 py-10 text-center text-gray-500">
                  No unpaid sales found for the selected period.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          v-if="sales.data?.length > 0"
          class="flex items-center justify-between px-2 py-4 mt-4"
        >
          <div class="text-sm text-gray-600">
            Showing {{ sales.from }} to {{ sales.to }} of {{ sales.total }} records
          </div>
          <div class="flex gap-2">
            <template v-for="(link, index) in sales.links" :key="index">
              <a
                v-if="link.url"
                :href="link.url"
                @click.prevent="router.visit(link.url, { preserveState: true, preserveScroll: true })"
                :class="[
                  'px-4 py-2 text-sm rounded-[5px] font-medium transition-all',
                  link.active
                    ? 'bg-orange-500 text-white'
                    : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50',
                ]"
                v-html="link.label"
              />
              <span
                v-else
                class="px-4 py-2 text-sm rounded-[5px] bg-gray-100 text-gray-400 cursor-not-allowed"
                v-html="link.label"
              />
            </template>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import axios from "axios";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const { goToReportsTab } = useDashboardNavigation();

const props = defineProps({
  sales: Object,
  totalUnpaid: String,
  startDate: String,
  endDate: String,
  currencySymbol: Object,
});

const currency = computed(() => props.currencySymbol?.currency ?? 'Rs.');

const startDate = ref(props.startDate || "");
const endDate = ref(props.endDate || "");

const applyFilter = () => {
  router.get(
    route("reports.unpaid-sales"),
    { start_date: startDate.value, end_date: endDate.value },
    { preserveState: true, preserveScroll: true }
  );
};

const resetFilter = () => {
  startDate.value = "";
  endDate.value = "";
  router.get(route("reports.unpaid-sales"), {}, { preserveState: false });
};

const markAsPaid = async (sale) => {
  sale.marking = true;
  try {
    await axios.patch(route("sales.mark-paid", sale.id));
    // Reload page to refresh list and totals
    router.reload({ preserveScroll: true });
  } catch (e) {
    alert("Failed to mark as paid.");
    sale.marking = false;
  }
};

const exportCsv = () => {
  const params = new URLSearchParams();
  if (startDate.value) params.set("start_date", startDate.value);
  if (endDate.value) params.set("end_date", endDate.value);
  params.set("export", "csv");
  window.location.href = route("reports.unpaid-sales") + "?" + params.toString() + "&export=csv";
};
</script>
