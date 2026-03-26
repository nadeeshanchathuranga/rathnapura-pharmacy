<script setup>
/**
 * ImportExport Component Script
 *
 * Page for managing import and export of data
 */

import AppLayout from "@/Layouts/AppLayout.vue";
import { Head } from "@inertiajs/vue3";
import { logActivity } from "@/composables/useActivityLog";

// Methods for handling structure download (template)
const handleStructureDownload = async (type) => {
  try {
    // Log activity before download, pass module name as details (string)
    await logActivity("download", "import & export", type);
    // Download template structure
    window.location.href = `/excel/export/${type}`;
  } catch (error) {
    console.error("Download error:", error);
    alert("Download failed. Please try again.");
  }
};

// Methods for handling header-only download (MySQL structure without data)
const handleHeaderDownload = async (type) => {
  try {
    // Log activity before download
    await logActivity("download-header", "import & export", type);
    // Download only table headers from MySQL
    window.location.href = `/excel/export-headers/${type}`;
  } catch (error) {
    console.error("Header download error:", error);
    alert("Header download failed. Please try again.");
  }
};

// Methods for handling data export (with actual MySQL data)
const handleDataExport = async (type) => {
  try {
    // Log activity before export
    await logActivity("export", "import & export", type);
    // Export actual data from database
    window.location.href = `/excel/export-data/${type}`;
  } catch (error) {
    console.error("Export error:", error);
    alert("Export failed. Please try again.");
  }
};

// Back button handler
const goBack = () => {
  window.history.back();
};

const handleUpload = (type) => {
  // Create a hidden file input
  const input = document.createElement("input");
  input.type = "file";
  input.accept = ".xlsx,.xls";
  input.onchange = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const formData = new FormData();
    formData.append("file", file);

    try {
      console.log(`Starting upload for module: ${type}, file: ${file.name}`);
      
      const response = await fetch(`/excel/upload/${encodeURIComponent(type)}`, {
        method: "POST",
        body: formData,
        headers: {
          "X-CSRF-TOKEN": document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content"),
        },
      });

      console.log(`Response status: ${response.status}`);
      const contentType = response.headers.get("content-type");
      console.log(`Content type: ${contentType}`);

      // Check if response is valid JSON
      let result;
      try {
        result = await response.json();
      } catch (parseError) {
        console.error("JSON parse error. Response text:", await response.text());
        alert(`Upload failed: Server returned invalid response. Check browser console.`);
        return;
      }

      if (!response.ok) {
        alert(`Upload failed: ${result.message || `HTTP ${response.status}`}`);
        return;
      }

      if (!result.success) {
        alert(`Upload failed: ${result.message || "Unknown error"}`);
        return;
      }

      // Log activity after successful upload, pass module name as details (string)
      await logActivity("upload", "import & export", type);
      alert(result.message || `${type} data uploaded successfully!`);

      // Optionally reload the page to reflect changes
      // window.location.reload();
    } catch (error) {
      console.error("Upload exception:", error);
      alert(`Upload failed: ${error.message || "Network error"}. Check browser console.`);
    }
  };
  input.click();
};
</script>

<template>
  <!-- Page Title for Browser Tab -->
  <Head title="Import & Export" />

  <AppLayout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 p-6">
      <div>
        <!-- Header -->
        <div class="mb-6 flex items-center gap-4">
          <!-- Back to Dashboard Button -->
          <button
            @click="goToSettingsTab"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-3xl font-bold text-black flex items-center gap-2">
            Import & Export
          </h1>
          <div></div>
        </div>

        <!-- Main Card -->
        <div
          class="bg-white rounded-2xl border border-gray-200 shadow-lg overflow-hidden"
        >
          <!-- Header Section -->
          <div class="bg-white border-b-2 border-blue-600 p-6">
            <h2 class="text-xl font-semibold text-blue-600">Data Management</h2>
            <p class="text-sm text-gray-600 mt-1">
              Import and export data for different modules
            </p>
          </div>

          <!-- Table Section -->
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="bg-gray-50 border-b-2 border-gray-200">
                  <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">
                    Module
                  </th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">
                    Export Data
                  </th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">
                    Import Data
                  </th>
                  <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">
                    Download Template
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr
                  v-for="section in sections"
                  :key="section.name"
                  class="hover:bg-blue-50 transition-colors"
                >
                  <td class="px-6 py-4">
                    <span class="text-sm font-medium text-gray-900">{{
                      section.title
                    }}</span>
                  </td>
                  <td class="px-6 py-4">
                    <button
                      @click="() => handleDataExport(section.name)"
                      class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm rounded-[5px] shadow-sm hover:shadow-md hover:scale-105 transition-all duration-200"
                    >
                      <svg
                        class="h-4 w-4"
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
                      Download
                    </button>
                  </td>
                  <td class="px-6 py-4">
                    <button
                      @click="() => handleUpload(section.name)"
                      class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-sm rounded-[5px] shadow-sm hover:shadow-md hover:scale-105 transition-all duration-200"
                    >
                      <svg
                        class="h-4 w-4"
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
                      Upload
                    </button>
                  </td>
                  <td class="px-6 py-4">
                    <button
                      @click="() => handleHeaderDownload(section.name)"
                      class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold text-sm rounded-[5px] shadow-sm hover:shadow-md hover:scale-105 transition-all duration-200"
                    >
                      <svg
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        ></path>
                      </svg>
                      Template
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Info Footer -->
          <div class="bg-blue-50 border-t border-gray-200 p-6">
            <div class="flex items-start gap-3">
              <svg
                class="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                  clip-rule="evenodd"
                ></path>
              </svg>
              <div>
                <h3 class="text-sm font-semibold text-gray-900 mb-1">
                  Import & Export Guide
                </h3>
                <ul class="text-sm text-gray-600 space-y-1">
                  <li>
                    <strong>Export Data:</strong> Download existing data from the database
                  </li>
                  <li>
                    <strong>Import Data:</strong> Upload and import data from Excel files
                    (.xlsx, .xls)
                  </li>
                  <li>
                    <strong>Download Template:</strong> Get the structure/template file
                    with column headers
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const { goToSettingsTab } = useDashboardNavigation();

const sections = [
  { name: "categories", title: "Categories" },
  { name: "types", title: "Types" },
  { name: "brands", title: "Brands" },
  { name: "suppliers", title: "Suppliers" },
  { name: "customers", title: "Customers" },
  { name: "discounts", title: "Discounts" },
  { name: "taxes", title: "Taxes" },
  { name: "products", title: "Products" },
];
</script>
