<template>
  <Head :title="`End Shift #${shift.id}`" />

  <AppLayout>
    <div class="min-h-screen bg-gray-50 p-4">
      <div class="mx-auto w-full max-w-4xl">
        <div class="mb-4 flex flex-wrap items-center gap-2.5">
          <button
            @click="goBack"
            class="inline-flex h-9 items-center justify-center rounded-[5px] border border-gray-200 bg-white px-4 text-sm font-medium text-gray-700 transition-all duration-200 hover:border-gray-300 hover:bg-gray-50"
          >
            ← Back
          </button>
          <h1 class="text-2xl font-bold text-black">End Shift #{{ shift.id }}</h1>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
          <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
              <p class="text-xs text-gray-500">Opening Cash</p>
              <p class="mt-0.5 text-2xl font-semibold text-gray-900">{{ formatMoney(shift.opening_till_amount) }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
              <p class="text-xs text-gray-500">Sales Total</p>
              <p class="mt-0.5 text-2xl font-semibold text-gray-900">{{ formatMoney(shift.sales_total) }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
              <p class="text-xs text-gray-500">Cash In / Cash Out</p>
              <p class="mt-0.5 text-2xl font-semibold text-gray-900">
                {{ formatMoney(shift.cash_in_total) }} / {{ formatMoney(shift.cash_out_total) }}
              </p>
            </div>

            <div class="rounded-xl border border-blue-200 bg-blue-50 p-3">
              <p class="text-xs text-blue-600">Expected Closing Cash</p>
              <p class="mt-0.5 text-2xl font-semibold text-blue-700">{{ formatMoney(shift.expected_closing_amount) }}</p>
            </div>
          </div>

          <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-sm font-medium text-gray-700">Counted Closing Cash</label>
              <input
                v-model="form.closing_cash_amount"
                type="number"
                min="0"
                step="0.01"
                class="h-9 w-full rounded-[5px] border border-gray-300 px-3 text-sm focus:ring-2 focus:ring-blue-500"
              />
              <p v-if="form.errors.closing_cash_amount" class="mt-2 text-sm text-red-600">
                {{ form.errors.closing_cash_amount }}
              </p>
            </div>

            <div class="rounded-xl border p-3" :class="varianceCardClass">
              <p class="text-xs" :class="varianceLabelClass">Projected Variance</p>
              <p class="mt-1 text-2xl font-semibold" :class="varianceValueClass">
                {{ varianceLabel }}: {{ formatMoney(Math.abs(projectedVariance)) }}
              </p>
            </div>
          </div>

          <div class="mb-4">
            <label class="mb-1.5 block text-sm font-medium text-gray-700">Closing Notes (Optional)</label>
            <textarea
              v-model="form.end_note"
              rows="3"
              class="w-full rounded-[5px] border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500"
            ></textarea>
            <p v-if="form.errors.end_note" class="mt-2 text-sm text-red-600">{{ form.errors.end_note }}</p>
          </div>

          <div class="flex flex-wrap items-center justify-end gap-2.5 border-t border-gray-200 pt-3">
            <button
              type="button"
              @click="submitEndShift"
              :disabled="form.processing"
              class="inline-flex h-9 items-center justify-center rounded-[5px] bg-red-600 px-4 text-sm font-medium text-white transition-all duration-200 hover:bg-red-700 disabled:opacity-50"
            >
              {{ form.processing ? 'Ending...' : 'End Shift' }}
            </button>
            <button
              type="button"
              @click="goBack"
              class="inline-flex h-9 items-center justify-center rounded-[5px] bg-gray-100 px-4 text-sm font-medium text-gray-700 transition-all duration-200 hover:bg-gray-200"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
  shift: {
    type: Object,
    required: true,
  },
});

const form = useForm({
  closing_cash_amount: "",
  end_note: "",
  redirect_to: "shift-management.index",
});

const parsedClosingAmount = computed(() => Number(form.closing_cash_amount || 0));
const projectedVariance = computed(() => parsedClosingAmount.value - Number(props.shift.expected_closing_amount || 0));

const varianceLabel = computed(() => {
  if (projectedVariance.value > 0) {
    return "Over";
  }

  if (projectedVariance.value < 0) {
    return "Short";
  }

  return "Balanced";
});

const varianceCardClass = computed(() => {
  if (projectedVariance.value > 0) {
    return "border-green-200 bg-green-50";
  }

  if (projectedVariance.value < 0) {
    return "border-red-200 bg-red-50";
  }

  return "border-gray-200 bg-gray-50";
});

const varianceLabelClass = computed(() => {
  if (projectedVariance.value > 0) {
    return "text-green-600";
  }

  if (projectedVariance.value < 0) {
    return "text-red-600";
  }

  return "text-gray-600";
});

const varianceValueClass = computed(() => {
  if (projectedVariance.value > 0) {
    return "text-green-700";
  }

  if (projectedVariance.value < 0) {
    return "text-red-700";
  }

  return "text-gray-700";
});

const formatMoney = (value) => Number(value || 0).toLocaleString(undefined, {
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

const goBack = () => {
  router.visit(route("shift-management.index"));
};

const submitEndShift = () => {
  form.post(route("shift-management.end"));
};
</script>
