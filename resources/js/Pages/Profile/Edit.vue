<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import DeleteUserForm from "./Partials/DeleteUserForm.vue";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm.vue";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm.vue";
import { Head } from "@inertiajs/vue3";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const { goToSettingsTab } = useDashboardNavigation();

defineProps({
  mustVerifyEmail: {
    type: Boolean,
  },
  status: {
    type: String,
  },
});
</script>

<template>
  <Head title="Profile" />

  <AppLayout>
    <!-- Main Container with Light Gradient -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 p-6">
      <!-- Header Section with Back Button and Title -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <!-- Back to Dashboard Button -->
          <button
            @click="goToSettingsTab"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-3xl font-bold text-black flex items-center gap-2">
            <span>👤</span> Profile Settings
          </h1>
        </div>
      </div>

      <!-- Profile Information Card -->
      <div
        class="bg-white rounded-2xl border border-gray-200 shadow-lg overflow-hidden mb-6"
      >
        <!-- Header -->
        <div class="bg-white border-b-2 border-blue-600 p-6">
          <h2 class="text-xl font-semibold text-blue-600">Profile Information</h2>
          <p class="text-sm text-gray-600 mt-1">
            Update your account's profile information and email address
          </p>
        </div>

        <!-- Form Content -->
        <div class="p-6 bg-gray-50">
          <UpdateProfileInformationForm
            :must-verify-email="mustVerifyEmail"
            :status="status"
          />
        </div>
      </div>

      <!-- Update Password Card -->
      <div class="bg-white rounded-2xl border border-gray-200 shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-white border-b-2 border-blue-600 p-6">
          <h2 class="text-xl font-semibold text-blue-600">Update Password</h2>
          <p class="text-sm text-gray-600 mt-1">
            Ensure your account is using a long, random password to stay secure
          </p>
        </div>

        <!-- Form Content -->
        <div class="p-6 bg-gray-50">
          <UpdatePasswordForm />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
