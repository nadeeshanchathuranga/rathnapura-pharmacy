<template>
  <Head title="Till Management" />

  <AppLayout>
    <div class="min-h-screen bg-gray-50 p-6">
      <div class="mx-auto w-full max-w-7xl">
      <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <button
            @click="goToShopsTab"
            class="inline-flex h-10 items-center justify-center rounded-[5px] border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 transition-all duration-200 hover:border-gray-300 hover:bg-gray-50"
          >
            ← Back
          </button>
          <h1 class="text-3xl font-bold text-black">Till Management</h1>
        </div>
        <Link
          :href="route('shift-management.index')"
          class="inline-flex h-10 items-center justify-center rounded-[5px] bg-emerald-600 px-5 text-sm font-medium text-white transition-all duration-200 hover:bg-emerald-700"
        >
          Shift Management
        </Link>
      </div>

      <div>
        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <p class="text-sm text-gray-500">Opening Cash</p>
            <p class="text-lg font-semibold text-gray-800">{{ currency }} {{ formatMoney(totals.opening_cash) }}</p>
          </div>
          <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <p class="text-sm text-gray-500">Cash In Total</p>
            <p class="text-lg font-semibold text-green-700">{{ currency }} {{ formatMoney(totals.cash_in_total) }}</p>
          </div>
          <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <p class="text-sm text-gray-500">Cash Out Total</p>
            <p class="text-lg font-semibold text-red-700">{{ currency }} {{ formatMoney(totals.cash_out_total) }}</p>
          </div>
          <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <p class="text-sm text-gray-500">Sales Total (Cash)</p>
            <p class="text-lg font-semibold text-blue-700">{{ currency }} {{ formatMoney(totals.cash_sales_total) }}</p>
          </div>
          <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <p class="text-sm text-gray-500">Running Balance</p>
            <p class="text-lg font-semibold text-gray-800">{{ currency }} {{ formatMoney(totals.balance) }}</p>
          </div>
          <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm">
            <p class="text-sm text-gray-500">Available Cash</p>
            <p class="text-lg font-semibold text-indigo-700">{{ currency }} {{ formatMoney(totals.available_cash) }}</p>
            <p class="text-xs text-gray-500 mt-1">Rule: {{ totals.available_cash_rule }}</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
          <form @submit.prevent="submitTransaction" class="space-y-6">
            <div class="rounded-[5px] border border-blue-100 bg-blue-50 px-4 py-3">
              <p class="text-sm text-blue-800">
                Transaction time will be captured automatically when you click Save.
              </p>
            </div>

            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">Transaction Type</label>
              <select
                v-model="form.type"
                class="h-10 w-full rounded-[5px] border border-gray-300 px-4 text-sm focus:ring-2 focus:ring-blue-500"
              >
                <option value="cash_in">Cash In</option>
                <option value="cash_out">Cash Out</option>
              </select>
              <p v-if="form.errors.type" class="mt-2 text-sm text-red-600">{{ form.errors.type }}</p>
            </div>

            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">Amount</label>
              <input
                v-model="form.amount"
                type="number"
                min="0.01"
                step="0.01"
                class="h-10 w-full rounded-[5px] border border-gray-300 px-4 text-sm focus:ring-2 focus:ring-blue-500"
                placeholder="0.00"
              />
              <p v-if="form.errors.amount" class="mt-2 text-sm text-red-600">{{ form.errors.amount }}</p>
            </div>

            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">Notes (Optional)</label>
              <textarea
                v-model="form.note"
                rows="4"
                class="w-full rounded-[5px] border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500"
              ></textarea>
              <p v-if="form.errors.note" class="mt-2 text-sm text-red-600">{{ form.errors.note }}</p>
            </div>

            <div v-if="!activeShift" class="rounded-[5px] border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">
              No active shift found. Please start a shift before recording till transactions.
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 pt-5">
              <button
                type="submit"
                :disabled="form.processing || !activeShift"
                class="inline-flex h-10 items-center justify-center rounded-[5px] bg-blue-600 px-5 text-sm font-medium text-white transition-all duration-200 hover:bg-blue-700 disabled:opacity-50"
              >
                Save Till Entry
              </button>
              <button
                type="button"
                @click="cancelForm"
                class="inline-flex h-10 items-center justify-center rounded-[5px] bg-gray-100 px-5 text-sm font-medium text-gray-700 transition-all duration-200 hover:bg-gray-200"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>

        <div class="mt-6 bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Till History</h2>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-gray-600">
                  <th class="px-3 py-2 text-left">Time</th>
                  <th class="px-3 py-2 text-left">Type</th>
                  <th class="px-3 py-2 text-right">Amount</th>
                  <th class="px-3 py-2 text-left">Note</th>
                  <th class="px-3 py-2 text-left">User</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="transaction in transactions.data || []" :key="transaction.id" class="border-b border-gray-100">
                  <td class="px-3 py-2">{{ transaction.transaction_time }}</td>
                  <td class="px-3 py-2">
                    <span
                      class="inline-flex px-2 py-1 rounded text-xs font-semibold"
                      :class="transaction.type === 'cash_in' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                    >
                      {{ transaction.type }}
                    </span>
                  </td>
                  <td class="px-3 py-2 text-right">{{ currency }} {{ formatMoney(transaction.amount) }}</td>
                  <td class="px-3 py-2">{{ transaction.note || '-' }}</td>
                  <td class="px-3 py-2">{{ transaction.user_name || '-' }}</td>
                </tr>
                <tr v-if="!(transactions.data && transactions.data.length)">
                  <td colspan="5" class="px-3 py-4 text-center text-gray-500">No till transactions found for this shift.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="transactions.links && transactions.links.length" class="mt-4 flex flex-wrap gap-2">
            <Link
              v-for="(link, index) in transactions.links"
              :key="`${index}-${link.label}`"
              :href="link.url || '#'"
              class="px-3 py-1 rounded border text-sm"
              :class="[
                link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300',
                !link.url ? 'opacity-50 pointer-events-none' : 'hover:bg-gray-50'
              ]"
              v-html="link.label"
            ></Link>
          </div>
        </div>
      </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

defineProps({
  activeShift: {
    type: Object,
    default: null,
  },
  totals: {
    type: Object,
    required: true,
  },
  transactions: {
    type: Object,
    default: () => ({
      data: [],
      links: [],
    }),
  },
});

const { goToShopsTab } = useDashboardNavigation();
const page = usePage();
const currency = page.props.currency || "Rs.";

const form = useForm({
  type: "cash_in",
  amount: "0.00",
  note: "",
});

const cancelForm = () => {
  form.reset();
  form.type = "cash_in";
  form.amount = "0.00";
};

const submitTransaction = () => {
  form.post(route("till-management.store"), {
    onSuccess: () => {
      cancelForm();
    },
  });
};

const formatMoney = (value) => Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>
