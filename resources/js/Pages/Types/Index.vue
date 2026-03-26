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
          <h1 class="text-4xl font-bold text-gray-800">Types</h1>
        </div>
        <!-- Add New Type Button -->
        <button
          @click="openCreateModal"
          class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-blue-600 text-white shadow-lg hover:bg-blue-700 hover:shadow-xl hover:scale-105 transition-all duration-300"
        >
          + Add Type
        </button>
      </div>

      <!-- Types Table Container -->
      <div
        class="bg-white rounded-2xl border border-gray-200 p-6"
      >
        <table class="w-full text-left border-collapse">
          <!-- Table Header -->
          <thead>
            <tr class="border-b-2 border-blue-600">
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">#</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Type Name</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Status
              </th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Actions
              </th>
            </tr>
          </thead>
          <!-- Table Body - Type Rows -->
          <tbody>
            <tr
              v-for="(type, index) in types.data"
              :key="type.id"
              class="border-b border-gray-200 hover:bg-blue-50/50 transition-colors duration-200"
            >
              <!-- Sequential ID -->
              <td class="px-4 py-4">
                <span
                  class="inline-flex items-center justify-center w-8 h-8 rounded-[10px] bg-blue-100 text-blue-700 font-bold text-sm"
                >
                  {{ (types.current_page - 1) * types.per_page + index + 1 }}
                </span>
              </td>
              <!-- Type Name -->
              <td class="px-4 py-4">
                <div class="font-semibold text-gray-900">{{ type.name }}</div>
              </td>
              <!-- Type Status Badge -->
              <td class="px-4 py-4 text-center">
                <span
                  :class="{
                    'bg-red-500/90 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs shadow-md':
                      type.status == 0,
                    'bg-green-500/90 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs shadow-md':
                      type.status == 1,
                    'bg-blue-500/90 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs shadow-md':
                      type.status == 2,
                  }"
                >
                  {{
                    type.status == 1
                      ? "Active"
                      : type.status == 0
                      ? "Inactive"
                      : "Default"
                  }}
                </span>
              </td>
              <!-- Action Buttons -->
              <td class="px-4 py-4">
                <div class="flex gap-2 justify-center">
                  <button
                    @click="openEditModal(type)"
                    :disabled="type.status == 2"
                    :class="[
                      'px-4 py-2 text-xs font-medium rounded-[5px] transition-all duration-300',
                      type.status == 2
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
            <tr v-if="!types.data || types.data.length === 0">
              <td colspan="4" class="px-6 py-8 text-center text-gray-500 font-medium">
                No types found
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="flex items-center justify-between px-6 py-4 mt-4" v-if="types.links">
          <div class="text-sm text-gray-600 font-medium">
            Showing {{ types.from }} to {{ types.to }} of {{ types.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              v-for="link in types.links"
              :key="link.label"
              @click="link.url ? router.visit(link.url) : null"
              :disabled="!link.url"
              :class="[
                'px-3 py-1 rounded-[5px] text-xs font-medium transition-all duration-300',
                link.active
                  ? 'bg-blue-600 text-white shadow-md'
                  : link.url
                  ? 'bg-blue-100 text-blue-700 hover:bg-blue-200 hover:shadow-md'
                  : 'bg-gray-200 text-gray-400 cursor-not-allowed'
              ]"
              v-html="link.label"
            ></button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <TypeCreateModal v-model:open="isCreateModalOpen" />

    <!-- Edit Modal -->
    <TypeEditModal
      v-model:open="isEditModalOpen"
      :type="selectedType"
      v-if="selectedType"
    />
  </AppLayout>
</template>

<script setup>
/**
 * Types Index Component Script
 *
 * Manages the types listing page with modal-based CRUD operations
 * Handles type viewing, editing, and creation
 */

import { ref } from "vue";
import { router } from "@inertiajs/vue3";

import TypeCreateModal from "./Components/TypeCreateModal.vue";
import TypeEditModal from "./Components/TypeEditModal.vue";

/**
 * Component Props
 * All data passed from TypeController
 */
defineProps({
  types: {
    type: Object,
    required: true,
  },
});

/**
 * Reactive State Variables
 *
 * Modal visibility states for each operation
 * Selected type references for edit operations
 */
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedType = ref(null);

/**
 * Open Create Type Modal
 * Opens empty form for new type creation
 */
const openCreateModal = () => {
  isCreateModalOpen.value = true;
};

/**
 * Open Edit Type Modal
 * Loads type data into edit form
 * Also logs the edit activity to activity_logs table
 *
 * @param {Object} type - Type object to edit
 */
const openEditModal = (type) => {
  selectedType.value = type;
  isEditModalOpen.value = true;
};
</script>
