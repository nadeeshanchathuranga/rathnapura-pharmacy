<template>
  <Head title="Expenses Report" />

  <AppLayout>
    <!-- Main Container -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 p-6">
      <!-- Header Section with Navigation and Actions -->
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
          <!-- Back to Dashboard Button -->
          <button
            @click="goToReportsTab"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-4xl font-bold text-gray-800">Supplier Payment Report</h1>
        </div>

        <!-- Compact Date Filter with Supplier Filter -->
        <div
          class="flex items-center gap-2 bg-white rounded-lg p-3 shadow-sm border border-gray-200"
        >
          <!-- Supplier Search Input -->
          <div class="relative w-48">
            <input
              v-model="supplierSearch"
              type="text"
              placeholder="Search supplier..."
              class="w-full px-3 py-2 bg-white text-gray-800 text-sm border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
              @focus="showSupplierDropdown = true"
              @blur="setTimeout(() => showSupplierDropdown = false, 200)"
            />
            <!-- Supplier Dropdown Suggestions -->
            <div
              v-if="showSupplierDropdown"
              class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-[5px] shadow-lg z-10 max-h-48 overflow-y-auto"
            >
              <div
                v-if="filteredSuppliers.length > 0"
              >
                <div
                  v-for="supplier in filteredSuppliers"
                  :key="supplier.id"
                  @click="selectSupplier(supplier)"
                  class="px-3 py-2 hover:bg-blue-100 cursor-pointer text-sm text-gray-800 transition-colors duration-150"
                >
                  {{ supplier.name }}
                </div>
              </div>
              <div v-else class="px-3 py-2 text-sm text-gray-500 text-center">
                No suppliers found
              </div>
            </div>
            <!-- Selected Supplier Display -->
            <!-- <div v-if="selectedSupplierObj" class="absolute right-2 top-2 bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-medium">
              {{ selectedSupplierObj.name }}
              <button
                @click="clearSupplierSelection"
                class="ml-1 text-blue-700 hover:text-blue-900"
              >
                ✕
              </button>
            </div> -->
          </div>
          <input
            type="date"
            v-model="startDate"
            class="px-3 py-2 bg-white text-gray-800 text-sm border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
          />
          <span class="text-gray-400">to</span>
          <input
            type="date"
            v-model="endDate"
            class="px-3 py-2 bg-white text-gray-800 text-sm border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
          />
          <button
            @click="filterReports"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-[5px] transition-all duration-200"
          >
            Apply
          </button>
          <button
            @click="resetFilter"
            class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-[5px] transition-all duration-200"
          >
            Reset
          </button>
        </div>
      </div>

      <!-- Expenses Details -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-semibold text-gray-800">Expense Transactions</h3>
          <div class="flex gap-2">
            <button
              @click="exportExpensesPdf"
              class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-[5px] hover:scale-105 transition-all duration-300 flex items-center gap-2"
            >
              📄 Export PDF
            </button>
            <button
              @click="exportExpensesExcel"
              class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-[5px] hover:scale-105 transition-all duration-300 flex items-center gap-2"
            >
                Export Excel
            </button>
          </div>
        </div>
        <table class="w-full text-left border-collapse">
          <!-- Table Header -->
          <thead>
            <tr class="border-b-2 border-blue-600">
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Expense Date
              </th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Supplier Name</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-right">
                Amount
              </th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Payment Type
              </th>
            </tr>
          </thead>
          <!-- Table Body - Product Rows -->
          <tbody>
            <tr
              v-for="expense in expensesList.data"
              :key="expense.id"
              class="border-b border-gray-200 hover:bg-blue-50/50 transition-colors duration-200"
            >
              <td class="px-4 py-4 text-center">
                <span class="text-sm text-gray-700">{{
                  formatDate(expense.expense_date)
                }}</span>
              </td>
              <td class="px-4 py-4">
                <div class="font-semibold text-gray-900">{{ expense.supplier_name }}</div>
              </td>
              <td class="px-4 py-4 text-right">
                <span class="text-sm font-semibold text-red-600"
                  >{{ page.props.currency || "" }} {{ expense.amount }}</span
                >
              </td>
              <td class="px-4 py-4 text-center">
                <span
                  class="px-3 py-1 rounded-[5px] text-white text-xs font-medium"
                  :class="getPaymentTypeColor(expense.payment_type)"
                >
                  {{ expense.payment_type_name }}
                </span>
              </td>
            </tr>
            <!-- Empty State Message -->
            <tr v-if="!expensesList.data || expensesList.data.length === 0">
              <td colspan="4" class="px-6 py-8 text-center text-gray-500 font-medium">
                No expenses for selected date range
              </td>
            </tr>
            <!-- Total Payment Row -->
            <tr v-if="expensesList.data && expensesList.data.length > 0" class="border-t-2 border-blue-600 bg-blue-50">
              <td class="px-4 py-4"></td>
              <td class="px-4 py-4 text-right font-bold text-blue-700 text-lg">
                Total Payment:
              </td>
              <td class="px-4 py-4 text-right">
                <span class="text-lg font-bold text-blue-700">
                  {{ page.props.currency || "" }} {{ totalExpenses }}
                </span>
              </td>
              <td class="px-4 py-4"></td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div
          v-if="expensesList.data?.length > 0"
          class="flex items-center justify-between px-6 py-4 mt-4"
        >
          <div class="text-sm text-gray-600 font-medium">
            Showing {{ expensesList.from }} to {{ expensesList.to }} of
            {{ expensesList.total }} results
          </div>
          <div class="flex gap-2">
            <template v-for="(link, index) in expensesList.links" :key="index">
              <a
                v-if="link.url"
                :href="link.url"
                @click.prevent="
                  router.visit(link.url, { preserveState: true, preserveScroll: true })
                "
                :class="[
                  'px-4 py-2 text-sm rounded-[5px] font-medium transition-all duration-200',
                  link.active
                    ? 'bg-blue-600 text-white'
                    : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50',
                ]"
                v-html="link.label"
              ></a>
              <span
                v-else
                :class="[
                  'px-4 py-2 text-sm rounded-[5px]',
                  'bg-gray-100 text-gray-400 cursor-not-allowed',
                ]"
                v-html="link.label"
              ></span>
            </template>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { logActivity } from "@/composables/useActivityLog";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const { goToReportsTab } = useDashboardNavigation();

const props = defineProps({
  expensesSummary: Array,
  expensesList: Object,
  totalExpenses: String,
  startDate: String,
  endDate: String,
  suppliers: {
    type: Array,
    default: () => [],
  },
});

const startDate = ref(props.startDate);
const endDate = ref(props.endDate);
const selectedSupplier = ref("");
const supplierSearch = ref("");
const showSupplierDropdown = ref(false);

const selectedSupplierObj = computed(() => {
  return props.suppliers.find(s => s.id === selectedSupplier.value) || null;
});

const filteredSuppliers = computed(() => {
  if (!supplierSearch.value.trim()) {
    return props.suppliers || [];
  }
  const searchTerm = supplierSearch.value.toLowerCase();
  const filtered = (props.suppliers || []).filter(supplier => {
    const supplierName = (supplier.name || "").toLowerCase();
    return supplierName.includes(searchTerm);
  });
  return filtered;
});

const page = usePage();

const totalTransactions = computed(() => {
  return props.expensesList.total || 0;
});

const averageExpense = computed(() => {
  const totalCount = props.expensesList.total || 0;
  if (totalCount === 0) return "0.00";
  const total = parseFloat(props.totalExpenses.replace(/,/g, ""));
  const avg = total / totalCount;
  return avg.toFixed(2);
});

const exportExpensesPdfUrl = computed(() => {
  return route("reports.export.expenses.pdf", {
    start_date: startDate.value,
    end_date: endDate.value,
    supplier_id: selectedSupplier.value || "",
    currency: page.props.currency || "",
  });
});

const exportExpensesExcelUrl = computed(() => {
  return route("reports.export.expenses.excel", {
    start_date: startDate.value,
    end_date: endDate.value,
    supplier_id: selectedSupplier.value || "",
    currency: page.props.currency || "",
  });
});

const exportExpensesPdf = async () => {
  await logActivity("export", "supplier_payment_report", {
    action: "export_pdf",
    start_date: startDate.value,
    end_date: endDate.value,
    total_expenses: props.totalExpenses,
  });
  window.location.href = exportExpensesPdfUrl.value;
};

const exportExpensesExcel = async () => {
  await logActivity("export", "supplier_payment_report", {
    action: "export_excel",
    start_date: startDate.value,
    end_date: endDate.value,
    total_expenses: props.totalExpenses,
  });
  window.location.href = exportExpensesExcelUrl.value;
};

const filterReports = () => {
  router.get(
    route("reports.expenses"),
    {
      start_date: startDate.value,
      end_date: endDate.value,
      supplier_id: selectedSupplier.value || "",
    },
    {
      preserveState: true,
      preserveScroll: true,
    }
  );
};

const resetFilter = () => {
  selectedSupplier.value = "";
  supplierSearch.value = "";
  router.get(
    route("reports.expenses"),
    {},
    {
      preserveState: false,
      preserveScroll: false,
    }
  );
};

const selectSupplier = (supplier) => {
  selectedSupplier.value = supplier.id;
  supplierSearch.value = supplier.name;
  showSupplierDropdown.value = false;
};

const clearSupplierSelection = () => {
  selectedSupplier.value = "";
  supplierSearch.value = "";
};

const getPaymentTypeColor = (type) => {
  const colors = {
    0: "bg-green-600",
    1: "bg-blue-600",
    2: "bg-orange-600",
  };
  return colors[type] || "bg-gray-600";
};

const formatDate = (dateString) => {
  if (!dateString) return "N/A";
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};
</script>
