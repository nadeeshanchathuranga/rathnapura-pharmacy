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
          <h1 class="text-4xl font-bold text-gray-800">Categories</h1>
        </div>
        <!-- Add New Category Button -->
        <button
          @click="openCreateModal"
          class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-blue-600 text-white shadow-lg hover:bg-blue-700 hover:shadow-xl hover:scale-105 transition-all duration-300"
        >
          + Add Category
        </button>
      </div>

      <!-- Categories Table Container -->
      <div
        class="bg-white rounded-2xl border border-gray-200 p-6"
      >
        <table class="w-full text-left border-collapse">
          <!-- Table Header -->
          <thead>
            <tr class="border-b-2 border-blue-600">
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">#</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Category Name</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Parent Category</th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Status
              </th>
              <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-center">
                Actions
              </th>
            </tr>
          </thead>
          <!-- Table Body - Category Rows -->
          <tbody>
            <tr
              v-for="(category, index) in categories.data"
              :key="category.id"
              class="border-b border-gray-200 hover:bg-blue-50/50 transition-colors duration-200"
            >
              <!-- Sequential ID -->
              <td class="px-4 py-4">
                <span
                  class="inline-flex items-center justify-center w-8 h-8 rounded-[10px] bg-blue-100 text-blue-700 font-bold text-sm"
                >
                  {{ (categories.current_page - 1) * categories.per_page + index + 1 }}
                </span>
              </td>
              <!-- Category Name -->
              <td class="px-4 py-4">
                <div class="font-semibold text-gray-900">{{ category.name }}</div>
              </td>
              <!-- Parent Category -->
              <td class="px-4 py-4">
                <div class="text-sm text-gray-700">{{ category.hierarchy_string || '-' }}</div>
              </td>
              <!-- Category Status Badge -->
              <td class="px-4 py-4 text-center">
                <span
                  :class="{
                    'bg-red-500/90 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs shadow-md':
                      category.status == 0,
                    'bg-green-500/90 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs shadow-md':
                      category.status == 1,
                    'bg-blue-500/90 text-white px-4 py-1.5 rounded-[5px] font-medium text-xs shadow-md':
                      category.status == 2,
                  }"
                >
                  {{
                    category.status == 1
                      ? "Active"
                      : category.status == 0
                      ? "Inactive"
                      : "Default"
                  }}
                </span>
              </td>
              <!-- Action Buttons -->
              <td class="px-4 py-4">
                <div class="flex gap-2 justify-center">
                  <button
                    @click="openEditModal(category)"
                    :disabled="category.status == 2"
                    :class="[
                      'px-4 py-2 text-xs font-medium rounded-[5px] transition-all duration-300',
                      category.status == 2
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
            <tr v-if="!categories.data || categories.data.length === 0">
              <td colspan="5" class="px-6 py-8 text-center text-gray-500 font-medium">
                No categories found
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="flex items-center justify-between px-6 py-4 mt-4" v-if="categories.links">
          <div class="text-sm text-gray-600 font-medium">
            Showing {{ categories.from }} to {{ categories.to }} of {{ categories.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              v-for="link in categories.links"
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
    <CategoryCreateModal v-model:open="isCreateModalOpen" :categories="categories.data" />

    <!-- Edit Modal -->
    <CategoryEditModal
      v-model:open="isEditModalOpen"
      :category="selectedCategory"
      :categories="categories.data"
      v-if="selectedCategory"
    />
  </AppLayout>
</template>

<script setup>
/**
 * Categories Index Component Script
 *
 * Manages the categories listing page with modal-based CRUD operations
 * Handles category viewing, editing, and creation
 */

import { ref } from "vue";
import { router } from "@inertiajs/vue3";

import CategoryCreateModal from "./Components/CategoryCreateModal.vue";
import CategoryEditModal from "./Components/CategoryEditModal.vue";

/**
 * Component Props
 * All data passed from CategoryController
 */
defineProps({
  categories: {
    type: Object,
    required: true,
  },
});

/**
 * Reactive State Variables
 *
 * Modal visibility states for each operation
 * Selected category references for edit operations
 */
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedCategory = ref(null);

/**
 * Open Create Category Modal
 * Opens empty form for new category creation
 */
const openCreateModal = () => {
  isCreateModalOpen.value = true;
};

/**
 * Open Edit Category Modal
 * Loads category data into edit form
 * Also logs the edit activity to activity_logs table
 *
 * @param {Object} category - Category object to edit
 */
const openEditModal = (category) => {
  selectedCategory.value = category;
  isEditModalOpen.value = true;
};
</script>
