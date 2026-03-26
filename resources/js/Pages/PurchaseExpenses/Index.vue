<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50 p-6">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <button
            @click="goToStoresTab"
            class="px-6 py-2.5 rounded-[5px]  font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-4xl font-bold text-gray-800">Supplier Payment</h1>
        </div>
        <button
          @click="openCreateModal"
          class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-[5px] hover:bg-blue-700 transition-all duration-200"
        >
          + Add Supplier Payment
        </button>
      </div>

      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b-2 border-blue-600">
                <th class="px-6 py-4 text-blue-600 font-semibold text-sm">#</th>
                <th class="px-6 py-4 text-blue-600 font-semibold text-sm">Date</th>
                <th class="px-6 py-4 text-blue-600 font-semibold text-sm">Supplier</th>
                <th class="px-6 py-4 text-blue-600 font-semibold text-sm">Due Date</th>
                <th class="px-6 py-4 text-blue-600 font-semibold text-sm">
                  Amount ({{ page.props.currency }})
                </th>
                <th class="px-6 py-4 text-blue-600 font-semibold text-sm">
                  Payment Type
                </th>
                <th class="px-6 py-4 text-blue-600 font-semibold text-sm">Reference</th>
                <th class="px-6 py-4 text-blue-600 font-semibold text-sm">Added By</th>
                <th class="px-6 py-4 text-blue-600 font-semibold text-sm">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(expense, index) in expenses.data"
                :key="expense.id"
                class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200"
              >
                <td class="px-4 py-4">
                <span
                  class="inline-flex items-center justify-center w-8 h-8 rounded-[10px]  bg-blue-100 text-blue-700 font-bold text-sm"
                >
                  {{ (expenses.current_page - 1) * expenses.per_page + index + 1 }}
                </span>
              </td>
                <td class="px-6 py-4 text-gray-900">
                  {{ formatDate(expense.expense_date) }}
                </td>
                <td class="px-6 py-4 text-gray-900">
                  {{
                    expense.supplier
                      ? `${expense.supplier.id} - ${expense.supplier.name}`
                      : "-"
                  }}
                </td>
                <td class="px-6 py-4 text-gray-900">
                  {{ formatDate(expense?.supplier?.due_date) }}
                </td>
                <td class="px-6 py-4 text-gray-900">
                  {{ page.props.currency }} {{ formatAmount(expense.amount) }}
                </td>
                <td class="px-6 py-4">
                  <span
                    :class="{
                      'bg-green-500 text-white px-4 py-1.5 rounded-[5px] text-xs font-semibold':
                        expense.payment_type == 0,
                      'bg-blue-500 text-white px-4 py-1.5 rounded-[5px] text-xs font-semibold':
                        expense.payment_type == 1,
                      'bg-yellow-500 text-white px-4 py-1.5 rounded-[5px] text-xs font-semibold':
                        expense.payment_type == 2,
                      'bg-purple-500 text-white px-4 py-1.5 rounded-[5px] text-xs font-semibold':
                        expense.payment_type == 3,
                    }"
                  >
                    {{ getPaymentTypeName(expense) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-gray-900">{{ expense.reference || "-" }}</td>
                <td class="px-6 py-4 text-gray-900">{{ expense.user?.name || "-" }}</td>
                <td class="px-6 py-4">
                  <button
                    @click="openViewModal(expense)"
                    class="px-4 py-2 text-xs font-medium text-white bg-green-600 rounded-[5px] hover:bg-green-700 transition-all duration-200"
                  >
                    View
                  </button>
                </td>
              </tr>
              <tr v-if="!expenses.data || expenses.data.length === 0">
                <td colspan="9" class="px-6 py-8 text-center text-gray-500 font-medium">
                  No expenses found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          class="flex items-center justify-between px-6 py-4 border-t border-gray-200"
          v-if="expenses.links"
        >
          <div class="text-sm text-gray-600">
            Showing {{ expenses.from }} to {{ expenses.to }} of
            {{ expenses.total }} results
          </div>
          <div class="flex gap-2">
            <button
              v-for="link in expenses.links"
              :key="link.label"
              @click="link.url ? $inertia.visit(link.url) : null"
              :disabled="!link.url"
              :class="[
                'px-4 py-2 text-xs font-medium rounded-[5px] transition-all duration-200',
                link.active
                  ? 'bg-blue-600 text-white'
                  : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50',
                !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
              ]"
              v-html="link.label"
            ></button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <PurchaseExpenseCreateModal
      :show="showCreateModal"
      :suppliers="suppliers"
      :supplierData="supplierData"
      @close="closeCreateModal"
      @supplier-change="handleSupplierChange"
    />

    <!-- View Modal -->
    <PurchaseExpenseViewModal
      :show="showViewModal"
      :expense="selectedExpense"
      @close="closeViewModal"
      :isViewOnly="true"
    />
  </AppLayout>
</template>

<script setup>
/**
 * Expenses Index Component Script
 *
 * Manages expense records with CRUD operations and supplier financial tracking
 * Includes modal-based create/edit/delete operations
 */

import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";
import PurchaseExpenseCreateModal from "./Components/PurchaseExpenseCreateModal.vue";
import PurchaseExpenseViewModal from "./Components/PurchaseExpenseViewModal.vue";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

/**
 * Component Props
 * @property {Object} expenses - Paginated expense records from backend
 * @property {Array} suppliers - List of suppliers for dropdown selection
 */
const props = defineProps({
  expenses: {
    type: Object,
    required: true,
  },
  suppliers: {
    type: Array,
    default: () => [],
  },
});

const page = usePage();

const { goToStoresTab } = useDashboardNavigation();

/**
 * Reactive State Variables
 *
 * Modal visibility states for Create/Edit/Delete operations
 * selectedExpense: Currently selected expense for edit/delete
 * supplierData: Financial summary for selected supplier (total, paid, balance)
 */
const showCreateModal = ref(false);
const showViewModal = ref(false);
const selectedExpense = ref(null);
const supplierData = ref({
  supplier_id: null,
  supplier_name: '',
  supplier_due_date: null,
  transaction_due_date: null,
  grn_id: null,
  grn_no: null,
  grn_date: null,
  total_amount: 0,
  paid: 0,
  balance: 0,
});

/**
 * Open Create Expense Modal
 */
const openCreateModal = () => {
  showCreateModal.value = true;
};

/**
 * Close Create Modal and Reset Supplier Data
 * Ensures supplier financial data is cleared when modal closes
 */
const closeCreateModal = () => {
  showCreateModal.value = false;
  // Reset supplier data when closing
  supplierData.value = {
    supplier_id: null,
    supplier_name: '',
    supplier_due_date: null,
    transaction_due_date: null,
    grn_id: null,
    grn_no: null,
    grn_date: null,
    total_amount: 0,
    paid: 0,
    balance: 0,
  };
};

/**
 * Handle Supplier Selection Change
 * Fetches supplier financial summary (total, paid, balance) via AJAX
 *
 * @param {number} supplierId - Selected supplier ID
 */
const handleSupplierChange = async (supplierId) => {
  try {
    const response = await axios.get(route("purchase-expenses.supplier-data"), {
      params: { supplier_id: supplierId },
    });

    supplierData.value = response.data;
  } catch (error) {
    console.error("Error fetching supplier data:", error);
  }
};

/**
 * Open View Modal with Selected Expense
 *
 * @param {Object} expense - Expense record to view
 */
const openViewModal = (expense) => {
  selectedExpense.value = expense;
  showViewModal.value = true;
};

/**
 * Close View Modal and Clear Selection
 */
const closeViewModal = () => {
  showViewModal.value = false;
  selectedExpense.value = null;
};

/**
 * Format Date for Display
 * Converts date string to readable format (e.g., "Jan 15, 2025")
 *
 * @param {string} date - Date string from database
 * @returns {string} Formatted date or '-' if no date
 */
const formatDate = (date) => {
  if (!date) return "-";
  return new Date(date).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

/**
 * Format Currency Amount
 * Adds thousand separators and 2 decimal places
 *
 * @param {number} amount - Raw amount from database
 * @returns {string} Formatted amount (e.g., "1,234.56")
 */
const formatAmount = (amount) => {
  if (!amount) return "0.00";
  return parseFloat(amount).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

/**
 * Get Payment Type Display Name
 * Maps numeric payment type to readable name
 *
 * @param {number} type - Payment type ID (0=Cash, 1=Card, 2=Cheque)
 * @returns {string} Payment type name
 */
const getPaymentTypeName = (expense) => {
  const type = Number(expense?.payment_type);
  const cardType = (expense?.card_type || '').toLowerCase();

  if (type === 1) {
    if (cardType === 'visa') {
      return 'Card (Visa)';
    }

    if (cardType === 'mastercard') {
      return 'Card (MasterCard)';
    }

    return 'Card';
  }

  const types = {
    0: "Cash",
    2: "Cheque",
  };
  return types[type] || "Unknown";
};
</script>
