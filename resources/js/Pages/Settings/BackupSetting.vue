<template>
  <div>
    <Head title="Database Backup" />

    <AppLayout>
      <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 p-6">
        <div>
          <!-- Header with Back Button -->
          <div class="mb-6 flex items-center gap-4">
            <!-- Back to Dashboard Button -->
            <button
              @click="goToSettingsTab"
              class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
            >
              ← Back
            </button>
            <h1 class="text-3xl font-bold text-black flex items-center">
              Database Backup
            </h1>
            <div></div>
          </div>

          <!-- Main Card -->
          <div
            class="bg-white rounded-2xl border border-gray-200 shadow-lg overflow-hidden"
          >
            <!-- Create Backup Section -->
            <div class="bg-white border-b-2 border-blue-600 p-6">
              <h2 class="text-xl font-semibold text-blue-600 mb-2">Create New Backup</h2>
              <p class="text-sm text-gray-600">
                Create a backup of your database. The backup will be automatically
                downloaded to your computer.
              </p>
            </div>

            <div class="p-6 bg-gray-50">
              <button
                @click="createBackup"
                :disabled="isCreating || isRestoring"
                class="inline-flex items-center gap-2 px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-[5px] font-semibold shadow-md hover:shadow-lg hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
              >
                <svg
                  v-if="isCreating"
                  class="animate-spin h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  ></circle>
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  ></path>
                </svg>
                <svg
                  v-else
                  class="h-5 w-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                  ></path>
                </svg>
                {{ isCreating ? "Creating Backup..." : "Create & Download Backup" }}
              </button>
            </div>

            <!-- Restore Backup Section -->
            <div class="border-t-2 border-blue-600">
              <div class="bg-white p-6">
                <h2 class="text-xl font-semibold text-blue-600 mb-2">Restore Database</h2>
                <p class="text-sm text-gray-600">
                  Upload a SQL backup file to restore your database. Only .sql files are
                  accepted.
                </p>
              </div>

              <div class="p-6 bg-gray-50 space-y-6">
                <!-- Warning Box -->
                <div class="bg-yellow-50 border-2 border-yellow-300 rounded-[5px] p-4">
                  <div class="flex gap-3">
                    <div class="flex-shrink-0">
                      <svg
                        class="h-6 w-6 text-yellow-600"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                      >
                        <path
                          fill-rule="evenodd"
                          d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                          clip-rule="evenodd"
                        ></path>
                      </svg>
                    </div>
                    <div>
                      <h3 class="text-sm font-semibold text-yellow-800 mb-1">
                        ⚠️ Warning
                      </h3>
                      <p class="text-sm text-yellow-700">
                        Restoring a backup will <strong>completely replace</strong> your
                        current database. This action cannot be undone. Make sure to
                        create a backup first if you want to preserve your current data.
                      </p>
                    </div>
                  </div>
                </div>

                <!-- File Upload -->
                <div class="space-y-4">
                  <input
                    ref="fileInput"
                    type="file"
                    accept=".sql"
                    @change="handleFileSelect"
                    class="hidden"
                  />

                  <div class="flex items-center gap-4">
                    <button
                      @click="$refs.fileInput.click()"
                      :disabled="isCreating || isRestoring"
                      class="inline-flex items-center gap-2 px-6 py-2.5 border-2 border-gray-300 rounded-[5px] font-semibold text-gray-700 bg-white hover:bg-gray-50 hover:border-blue-500 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 transition-all duration-200"
                    >
                      <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                        ></path>
                      </svg>
                      Choose SQL File
                    </button>

                    <span v-if="selectedFile" class="text-sm text-gray-700 font-medium">
                      📄 {{ selectedFile.name }}
                      <span class="text-gray-500"
                        >({{ formatFileSize(selectedFile.size) }})</span
                      >
                    </span>
                  </div>

                  <div v-if="selectedFile" class="flex items-center gap-4">
                    <button
                      @click="restoreBackup"
                      :disabled="isCreating || isRestoring || !selectedFile"
                      class="inline-flex items-center gap-2 px-8 py-3 bg-red-600 hover:bg-red-700 text-white rounded-[5px] font-semibold shadow-md hover:shadow-lg hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 transition-all duration-200"
                    >
                      <svg
                        v-if="isRestoring"
                        class="animate-spin h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                      >
                        <circle
                          class="opacity-25"
                          cx="12"
                          cy="12"
                          r="10"
                          stroke="currentColor"
                          stroke-width="4"
                        ></circle>
                        <path
                          class="opacity-75"
                          fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                      </svg>
                      <svg
                        v-else
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        ></path>
                      </svg>
                      {{ isRestoring ? "Restoring Database..." : "Restore Database" }}
                    </button>

                    <button
                      @click="clearSelectedFile"
                      :disabled="isCreating || isRestoring"
                      class="px-4 py-2.5 text-gray-600 hover:text-red-600 font-medium transition-colors"
                    >
                      Cancel
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Success/Error Messages -->
            <div v-if="message.text" class="p-6 border-t border-gray-200">
              <div :class="messageClasses" class="p-4 rounded-[5px] border-2">
                <div class="flex gap-3">
                  <div class="flex-shrink-0">
                    <svg
                      v-if="message.type === 'success'"
                      class="h-6 w-6"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                    >
                      <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd"
                      ></path>
                    </svg>
                    <svg v-else class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20">
                      <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd"
                      ></path>
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm font-semibold">{{ message.text }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import axios from "axios";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const { goToSettingsTab } = useDashboardNavigation();

// Reactive data
const isCreating = ref(false);
const isRestoring = ref(false);
const selectedFile = ref(null);
const message = ref({ text: "", type: "" });

// Computed properties
const messageClasses = computed(() => ({
  "bg-green-50 text-green-700": message.value.type === "success",
  "bg-red-50 text-red-700": message.value.type === "error",
}));

/**
 * Create a new database backup
 */
const createBackup = async () => {
  isCreating.value = true;
  message.value = { text: "", type: "" };

  try {
    const response = await axios.post(
      "/backup/create",
      {},
      {
        responseType: "blob",
      }
    );

    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;

    // Get filename from response headers or create default
    const contentDisposition = response.headers["content-disposition"];
    let filename = "database_backup.sql";

    if (contentDisposition) {
      const match = contentDisposition.match(/filename="?(.+)"?/);
      if (match) {
        filename = match[1];
      }
    }

    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    message.value = {
      text: "Backup created and downloaded successfully!",
      type: "success",
    };
  } catch (error) {
    console.error("Backup failed:", error);
    message.value = {
      text: error.response?.data?.error || "Failed to create backup. Please try again.",
      type: "error",
    };
  } finally {
    isCreating.value = false;
  }
};

/**
 * Handle file selection for restore
 */
const handleFileSelect = (event) => {
  const file = event.target.files[0];
  if (file && file.name.endsWith(".sql")) {
    selectedFile.value = file;
    message.value = { text: "", type: "" };
  } else if (file) {
    message.value = { text: "Please select a valid .sql file.", type: "error" };
    event.target.value = "";
  }
};

/**
 * Clear selected file
 */
const clearSelectedFile = () => {
  selectedFile.value = null;
  const fileInput = document.querySelector('input[type="file"]');
  if (fileInput) {
    fileInput.value = "";
  }
};

/**
 * Restore database from selected backup file
 */
const restoreBackup = async () => {
  if (!selectedFile.value) {
    message.value = { text: "Please select a backup file first.", type: "error" };
    return;
  }

  // Show confirmation dialog
  if (
    !confirm(
      "Are you sure you want to restore the database? This will replace all current data and cannot be undone."
    )
  ) {
    return;
  }

  isRestoring.value = true;
  message.value = { text: "", type: "" };

  try {
    const formData = new FormData();
    formData.append("backup_file", selectedFile.value);

    const response = await axios.post("/backup/restore", formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });

    message.value = { text: "Database restored successfully!", type: "success" };
    clearSelectedFile();
  } catch (error) {
    console.error("Restore failed:", error);
    message.value = {
      text:
        error.response?.data?.error || "Failed to restore database. Please try again.",
      type: "error",
    };
  } finally {
    isRestoring.value = false;
  }
};

/**
 * Format file size for display
 */
const formatFileSize = (bytes) => {
  if (bytes === 0) return "0 Bytes";
  const k = 1024;
  const sizes = ["Bytes", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
};
</script>
