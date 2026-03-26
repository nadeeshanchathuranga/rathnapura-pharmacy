<template>
  <AppLayout>
    <!-- Main Container -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 p-6">
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
          <h1 class="text-4xl font-bold text-gray-800">Units</h1>
        </div>
        <!-- Add New Unit Button -->
        <button
          @click="openCreateModal"
          class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-blue-600 text-white shadow-lg hover:bg-blue-700 hover:shadow-xl hover:scale-105 transition-all duration-300"
        >
          + Add Unit
        </button>
      </div>

      <!-- Units Table Container -->
      <div
        class="bg-white rounded-2xl border border-gray-200 p-6"
      >
        <table class="w-full text-left border-collapse">
          <!-- Table Header -->
          <thead>
            <tr class="border-b-2 border-blue-600">
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">#</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Unit Name</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Symbol</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Status
              </th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Actions
              </th>
            </tr>
          </thead>
          <!-- Table Body - Unit Rows -->
          <tbody>
            <tr
              v-for="(unit, index) in normalizedUnits"
              :key="unit.id"
              class="border-b border-gray-200 hover:bg-blue-50/50 transition-colors duration-200"
            >
              <!-- Sequential ID -->
              <td class="px-4 py-4">
                <span
                  class="inline-flex items-center justify-center w-8 h-8 rounded-[10px] bg-blue-100 text-blue-700 font-bold text-sm"
                >
                  {{ (measurementUnits.current_page - 1) * measurementUnits.per_page + index + 1 }}
                </span>
              </td>
              <!-- Unit Name -->
              <td class="px-4 py-4">
                <div class="font-semibold text-gray-900">{{ unit.name }}</div>
              </td>
              <!-- Symbol -->
              <td class="px-4 py-4">
                <div class="text-sm text-gray-700">{{ unit.symbol }}</div>
              </td>
              <!-- Unit Status Badge -->
              <td class="px-4 py-4 text-center">
                <span
                  :class="[
                    'px-4 py-1.5 rounded-[5px] font-medium text-xs shadow-md text-white',
                    unit.status === 0 ? 'bg-red-500/90' : '',
                    unit.status === 1 ? 'bg-green-500/90' : '',
                    unit.status === 2 ? 'bg-blue-500/90' : ''
                  ]"
                >
                  {{ statusMap[unit.status].text }}
                </span>
              </td>
              <!-- Action Buttons -->
              <td class="px-4 py-4">
                <div class="flex gap-2 justify-center">
                  <button
                    @click="openEditModal(unit)"
                    :disabled="unit.status === 2"
                    :class="[
                      'px-4 py-2 text-xs font-medium rounded-[5px] transition-all duration-300',
                      unit.status === 2
                        ? 'bg-gray-400 text-gray-200 cursor-not-allowed opacity-50'
                        : 'text-white bg-blue-600 hover:bg-blue-700 hover:shadow-lg hover:scale-105',
                    ]"
                  >
                    Edit
                  </button>
                </div>
              </td>
            </tr>
            <!-- Empty State Message -->
            <tr v-if="!normalizedUnits.length">
              <td colspan="5" class="px-6 py-8 text-center text-gray-500 font-medium">
                No measurement units found
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="flex items-center justify-between px-6 py-4 mt-4" v-if="measurementUnits.links">
          <div class="text-sm text-gray-600 font-medium">
            Showing {{ measurementUnits.from }} to {{ measurementUnits.to }} of {{ measurementUnits.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              v-for="link in measurementUnits.links"
              :key="link.label"
              @click="link.url && router.visit(link.url)"
              :disabled="!link.url"
              v-html="link.label"
              :class="[
                'px-3 py-1 rounded-[5px] text-xs font-medium transition-all duration-300',
                link.active
                  ? 'bg-blue-600 text-white shadow-md'
                  : link.url
                  ? 'bg-blue-100 text-blue-700 hover:bg-blue-200 hover:shadow-md'
                  : 'bg-gray-200 text-gray-400 cursor-not-allowed'
              ]"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <MeasurementUnitCreateModal v-model:open="isCreateModalOpen" />
    <MeasurementUnitEditModal
      v-if="selectedUnit"
      v-model:open="isEditModalOpen"
      :unit="selectedUnit"
    />
  </AppLayout>
</template>

<script setup>
/**
 * Measurement Units Index Component Script
 *
 * Manages the measurement units listing page with modal-based CRUD operations
 * Handles unit viewing, editing, and creation
 */

import { ref, computed, toRefs } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import MeasurementUnitCreateModal from "./Components/MeasurementUnitCreateModal.vue";
import MeasurementUnitEditModal from "./Components/MeasurementUnitEditModal.vue";

/**
 * Component Props
 * All data passed from MeasurementUnitController
 */
const props = defineProps({
  measurementUnits: Object,
});

const { measurementUnits } = toRefs(props);

/**
 * Reactive State Variables
 *
 * Modal visibility states for each operation
 * Selected unit references for edit operations
 */
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedUnit = ref(null);

/**
 * Status Map
 * Maps status codes to display text and styling
 */
const statusMap = {
  0: { text: "Inactive", class: "bg-red-500" },
  1: { text: "Active", class: "bg-green-500" },
  2: { text: "Default", class: "bg-blue-500" },
};

/**
 * Normalize status values
 * Ensures status is always a number
 */
const normalizedUnits = computed(() => {
  const data = measurementUnits.value?.data || [];
  return data.map(u => ({
    ...u,
    status: Number(u.status) || 0,
  }));
});

/**
 * Open Create Unit Modal
 * Opens empty form for new unit creation
 */
const openCreateModal = () => {
  isCreateModalOpen.value = true;
};

/**
 * Open Edit Unit Modal
 * Loads unit data into edit form
 *
 * @param {Object} unit - Unit object to edit
 */
const openEditModal = (unit) => {
  selectedUnit.value = unit;
  isEditModalOpen.value = true;
};
</script>
