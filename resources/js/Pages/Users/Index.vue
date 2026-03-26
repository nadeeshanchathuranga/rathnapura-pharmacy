<template>
  <AppLayout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 p-6">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
           <!-- Back to Dashboard Button -->
          <button
            @click="goToSettingsTab"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-3xl font-bold text-black">Users Management</h1>
        </div>
        <button
          @click="openCreateModal"
          class="px-6 py-2.5 text-white bg-blue-600 rounded-[5px] hover:bg-blue-700 hover:scale-105 transition-all duration-200 font-semibold shadow-md hover:shadow-lg"
        >
          + Add User
        </button>
      </div>

      <div class="bg-white rounded-2xl border border-gray-200 shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-white border-b-2 border-blue-600 px-6 py-4">
          <h2 class="text-xl font-semibold text-blue-600">All Users</h2>
          <p class="text-sm text-gray-600 mt-1">Manage system users and their roles</p>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-gray-50 border-b-2 border-gray-200">
              <tr>
                <th class="px-6 py-4 text-sm font-semibold text-gray-700">#</th>
                <th class="px-6 py-4 text-sm font-semibold text-gray-700">Name</th>
                <th class="px-6 py-4 text-sm font-semibold text-gray-700">Email</th>
                <th class="px-6 py-4 text-sm font-semibold text-gray-700">User Type</th>
                <th class="px-6 py-4 text-sm font-semibold text-gray-700">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr
                v-for="(user, index) in users.data"
                :key="user.id"
                class="hover:bg-blue-50/50 transition-colors duration-200"
              >
                <td class="px-6 py-4 text-sm text-gray-900">
                  {{ (users.current_page - 1) * users.per_page + index + 1 }}
                </td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                  {{ user.name }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ user.email }}</td>
                <td class="px-6 py-4">
                  <span
                    :class="{
                      'bg-purple-100 text-purple-700 px-3 py-1 rounded-[5px] text-xs font-semibold':
                        user.role == 0,
                      'bg-blue-100 text-blue-700 px-3 py-1 rounded-[5px] text-xs font-semibold':
                        user.role == 1,
                      'bg-green-100 text-green-700 px-3 py-1 rounded-[5px] text-xs font-semibold':
                        user.role == 2,
                      'bg-cyan-100 text-cyan-700 px-3 py-1 rounded-[5px] text-xs font-semibold':
                        user.role == 3,
                    }"
                  >
                    {{ getUserType(user.role) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex gap-2">
                    <button
                      @click="openEditModal(user)"
                      class="px-4 py-2 text-white bg-blue-600 rounded-[5px] hover:bg-blue-700 hover:scale-105 transition-all duration-200 text-sm font-medium shadow-sm"
                    >
                      Edit
                    </button>
                    <button
                      @click="openDeleteModal(user)"
                      class="px-4 py-2 text-white bg-red-600 rounded-[5px] hover:bg-red-700 hover:scale-105 transition-all duration-200 text-sm font-medium shadow-sm"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!users.data || users.data.length === 0">
                <td colspan="5" class="px-6 py-12 text-center">
                  <div class="text-gray-400">
                    <svg
                      class="mx-auto h-12 w-12 text-gray-300 mb-3"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                      />
                    </svg>
                    <p class="text-lg font-medium">No users found</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          class="flex items-center justify-between px-6 py-4 bg-blue-50 border-t border-gray-200"
          v-if="users.links"
        >
          <div class="text-sm text-gray-700 font-medium">
            Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              v-for="link in users.links"
              :key="link.label"
              @click="link.url ? router.visit(link.url) : null"
              :disabled="!link.url"
              :class="[
                'px-4 py-2 rounded-[5px] text-sm font-medium transition-all duration-200',
                link.active
                  ? 'bg-blue-600 text-white shadow-md'
                  : link.url
                  ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300 hover:scale-105'
                  : 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200',
              ]"
              v-html="link.label"
            ></button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <UserCreateModal v-model:open="isCreateModalOpen" />

    <!-- Edit Modal -->
    <UserEditModal
      v-model:open="isEditModalOpen"
      :user="selectedUser"
      v-if="selectedUser"
    />

    <!-- Delete Modal -->
    <UserDeleteModal
      v-model:open="isDeleteModalOpen"
      :user="selectedUserForDelete"
      v-if="selectedUserForDelete"
    />
  </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import UserCreateModal from "./Components/UserCreateModal.vue";
import UserEditModal from "./Components/UserEditModal.vue";
import UserDeleteModal from "./Components/UserDeleteModal.vue";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const { goToSettingsTab } = useDashboardNavigation();

defineProps({
  users: {
    type: Object,
    required: true,
  },
});

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedUser = ref(null);
const selectedUserForDelete = ref(null);

const getUserType = (type) => {
  const types = {
    0: "Admin",
    1: "Manager",
    2: "Cashier",
    3: "Stock Keeper",
  };
  return types[type] || "Unknown";
};

const openCreateModal = () => {
  isCreateModalOpen.value = true;
};

const openEditModal = (user) => {
  selectedUser.value = user;
  isEditModalOpen.value = true;
};

const openDeleteModal = (user) => {
  selectedUserForDelete.value = user;
  isDeleteModalOpen.value = true;
};
</script>
