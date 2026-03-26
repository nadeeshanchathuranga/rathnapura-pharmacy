<template>
  <AppLayout>
    <!-- Main Container -->
    <div class="min-h-screen bg-gray-50 p-6">
      <!-- Header Section with Navigation and Actions -->
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
          <!-- Back to Dashboard Button -->
          <button
            @click="$inertia.visit(route('dashboard'))"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-4xl font-bold text-gray-800">Suppliers</h1>
        </div>
        <!-- Add New Supplier Button -->
        <button
          @click="openCreateModal"
          class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 transition-all duration-200"
        >
          + Add Supplier
        </button>
      </div>

      <!-- Suppliers Table Container -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <table class="w-full text-left border-collapse">
          <!-- Table Header -->
          <thead>
            <tr class="border-b-2 border-blue-600">
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">#</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Name</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Email</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Phone</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Due Date</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-right">Due Payment</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">Status</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">Actions</th>
            </tr>
          </thead>
          <!-- Table Body - Supplier Rows -->
          <tbody>
            <tr
              v-for="(supplier, index) in suppliers.data"
              :key="supplier.id"
              class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200"
            >
              <!-- Sequential ID -->
              <td class="px-4 py-4">
                <span
                  class="inline-flex items-center justify-center w-8 h-8 rounded-[10px] bg-blue-100 text-blue-700 font-bold text-sm"
                >
                  {{ (suppliers.current_page - 1) * suppliers.per_page + index + 1 }}
                </span>
              </td>
              <!-- Supplier Name -->
              <td class="px-4 py-4">
                <div class="font-semibold text-gray-900">{{ supplier.name }}</div>
              </td>
              <!-- Email -->
              <td class="px-4 py-4">
                <div class="text-sm text-gray-700">{{ supplier.email || '-' }}</div>
              </td>
              <!-- Phone -->
              <td class="px-4 py-4">
                <div class="text-sm text-gray-700">{{ supplier.phone_number || '-' }}</div>
              </td>
              <!-- Due Date -->
              <td class="px-4 py-4">
                <div class="text-sm text-gray-700">{{ formatDate(supplier.due_date) }}</div>
              </td>
              <!-- Due Payment -->
              <td class="px-4 py-4 text-right">
                <div :class=" [
                  'font-bold text-sm',
                  (supplier.total_amount - supplier.paid_amount) > 0 ? 'text-red-600' : 'text-green-600'
                ]">
                  {{ formatCurrency(supplier.total_amount - supplier.paid_amount) }}
                </div>
              </td>
              <!-- Status Badge -->
              <td class="px-4 py-4 text-center">
                  <span
                    :class="{
                      'bg-red-500 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs': supplier.status == 0,
                      'bg-green-500 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs': supplier.status == 1,
                      'bg-blue-500 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs': supplier.status == 2
                    }"
                  >
                    {{ supplier.status == 1 ? 'Active' : supplier.status == 0 ? 'Inactive' : 'Default' }}
                  </span>
                </td>
                <!-- Action Buttons -->
                <td class="px-4 py-4">
                  <div class="flex gap-2 justify-center">
                    <button
                      @click="openEditModal(supplier)"
                      :disabled="supplier.status == 2"
                      :class="[
                        'px-4 py-2 text-xs font-medium rounded-[5px] transition-all duration-200',
                        supplier.status == 2
                          ? 'bg-gray-400 text-gray-200 cursor-not-allowed opacity-50'
                          : 'text-white bg-blue-600 hover:bg-blue-700'
                      ]"
                    >
                      Edit
                    </button>
                  </div>
                </td>
              </tr>
            <!-- Empty State Message -->
            <tr v-if="!suppliers.data || suppliers.data.length === 0">
              <td colspan="8" class="px-6 py-8 text-center text-gray-500 font-medium">
                No suppliers found
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200" v-if="suppliers.links">
          <div class="text-sm text-gray-600">
            Showing {{ suppliers.from }} to {{ suppliers.to }} of {{ suppliers.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              v-for="link in suppliers.links"
              :key="link.label"
              @click="link.url ? router.visit(link.url) : null"
              :disabled="!link.url"
              :class="[
                'px-3 py-1 rounded-lg transition-colors duration-200',
                link.active
                  ? 'bg-blue-600 text-white'
                  : link.url
                  ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                  : 'bg-gray-50 text-gray-400 cursor-not-allowed'
              ]"
              v-html="link.label"
            ></button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <SupplierCreateModal v-model:open="isCreateModalOpen" />

    <!-- Edit Modal -->
    <SupplierEditModal
      v-model:open="isEditModalOpen"
      :supplier="selectedSupplier"
      v-if="selectedSupplier"
    />
  </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

import SupplierCreateModal from "./Components/SupplierCreateModal.vue";
import SupplierEditModal from "./Components/SupplierEditModal.vue";

const page = usePage();

const props = defineProps({
  suppliers: {
    type: Object,
    required: true,
  },
});

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedSupplier = ref(null);

const openCreateModal = () => {
  isCreateModalOpen.value = true;
};

const openEditModal = (supplier) => {
  selectedSupplier.value = supplier;
  isEditModalOpen.value = true;
};

const formatCurrency = (value) => {
  const currency = page.props.currency || "USD";
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value || 0);
};

const formatDate = (dateValue) => {
  if (!dateValue) return "-";

  const date = new Date(dateValue);
  if (Number.isNaN(date.getTime())) return "-";

  return date.toLocaleDateString("en-CA");
};

</script>
