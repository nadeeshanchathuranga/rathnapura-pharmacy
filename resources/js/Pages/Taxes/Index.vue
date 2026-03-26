<template>
  <AppLayout>
    <!-- Main Container -->
    <div class="min-h-screen bg-gray-50 p-6">
      <!-- Header Section with Navigation and Actions -->
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
          <!-- Back to Dashboard Button -->
          <button
            @click="goToShopsTab"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-4xl font-bold text-gray-800">Taxes</h1>
        </div>
        <!-- Add Tax Button -->
        <button
          @click="openCreateModal"
          class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 transition-all duration-200"
        >
          + Add Tax
        </button>
      </div>

      <!-- Taxes Table Container -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <table class="w-full text-left border-collapse">
          <!-- Table Header -->
          <thead>
            <tr class="border-b-2 border-blue-600">
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">#</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Tax Name</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-right">
                Percentage
              </th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Type
              </th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Status
              </th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Actions
              </th>
            </tr>
          </thead>
          <!-- Table Body - Tax Rows -->
          <tbody>
            <tr
              v-for="(tax, index) in taxes.data"
              :key="tax.id"
              class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200"
            >
              <!-- Sequential ID -->
              <td class="px-4 py-4">
                <span
                  class="inline-flex items-center justify-center w-8 h-8 rounded-[10px] bg-blue-100 text-blue-700 font-bold text-sm"
                >
                  {{ (taxes.current_page - 1) * taxes.per_page + index + 1 }}
                </span>
              </td>
              <!-- Tax Name -->
              <td class="px-4 py-4">
                <div class="font-semibold text-gray-900">{{ tax.name }}</div>
              </td>
              <!-- Percentage -->
              <td class="px-4 py-4 text-right">
                <div class="text-sm font-semibold text-blue-700">
                  {{ tax.percentage }}%
                </div>
              </td>
              <!-- Type Badge -->
              <td class="px-4 py-4 text-center">
                <span
                  class="px-3 py-1.5 text-xs font-medium rounded-[5px]"
                  :class="
                    tax.type == 0
                      ? 'bg-purple-500 text-white'
                      : 'bg-orange-500 text-white'
                  "
                >
                  {{ tax.type == 0 ? "Inclusive" : "Exclusive" }}
                </span>
              </td>
              <!-- Status Badge -->
              <td class="px-4 py-4 text-center">
                <span
                  :class="{
                    'bg-red-500 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs':
                      tax.status == 0,
                    'bg-green-500 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs':
                      tax.status == 1,
                    'bg-blue-500 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs':
                      tax.status == 2,
                  }"
                >
                  {{
                    tax.status == 1 ? "Active" : tax.status == 0 ? "Inactive" : "Default"
                  }}
                </span>
              </td>
              <!-- Action Buttons -->
              <td class="px-4 py-4">
                <div class="flex gap-2 justify-center">
                  <button
                    @click="openEditModal(tax)"
                    :disabled="tax.status == 2"
                    :class="[
                      'px-4 py-2 text-xs font-medium rounded-[5px] transition-all duration-200',
                      tax.status == 2
                        ? 'bg-gray-400 text-gray-200 cursor-not-allowed opacity-50'
                        : 'text-white bg-blue-600 hover:bg-blue-700',
                    ]"
                  >
                    Edit
                  </button>
                </div>
              </td>
            </tr>
            <!-- Empty State Message -->
            <tr v-if="!taxes.data || taxes.data.length === 0">
              <td colspan="6" class="px-6 py-8 text-center text-gray-500 font-medium">
                No taxes found
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div
          class="flex items-center justify-between px-6 py-4 bg-white border-t border-gray-200 mt-4"
          v-if="taxes.links"
        >
          <div class="text-sm text-gray-600">
            Showing {{ taxes.from }} to {{ taxes.to }} of {{ taxes.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              v-for="link in taxes.links"
              :key="link.label"
              @click="link.url ? router.visit(link.url) : null"
              :disabled="!link.url"
              :class="[
                'px-3 py-1 rounded-[5px]',
                link.active
                  ? 'bg-blue-600 text-white'
                  : link.url
                  ? 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                  : 'bg-gray-100 text-gray-400 cursor-not-allowed',
              ]"
              v-html="link.label"
            ></button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <TaxCreateModal v-model:open="isCreateModalOpen" />

    <!-- Edit Modal -->
    <TaxEditModal v-model:open="isEditModalOpen" :tax="selectedTax" v-if="selectedTax" />
  </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import TaxCreateModal from "./Components/TaxCreateModal.vue";
import TaxEditModal from "./Components/TaxEditModal.vue";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

defineProps({
  taxes: {
    type: Object,
    required: true,
  },
});

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedTax = ref(null);

const { goToShopsTab } = useDashboardNavigation();

const openCreateModal = () => {
  isCreateModalOpen.value = true;
};

const openEditModal = (tax) => {
  selectedTax.value = tax;
  isEditModalOpen.value = true;
};
</script>
