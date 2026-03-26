<template>
  <AppLayout>
    <!-- Main Container -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 p-6">
      <!-- Header Section with Back Button and Title -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <!-- Back to Dashboard Button -->
          <button
            @click=" goToSettingsTab"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-3xl font-bold text-black">App Settings</h1>
        </div>
      </div>

      <!-- Settings Form Container -->
      <div class="bg-white rounded-2xl border border-gray-200 shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-white border-b-2 border-blue-600 p-6">
          <h2 class="text-xl font-semibold text-blue-600">Application Configuration</h2>
          <p class="text-sm text-gray-600 mt-1">
            Customize your application branding and settings
          </p>
        </div>

        <!-- Form Content -->
        <form @submit.prevent="submit" class="p-6 bg-gray-50">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- App Name Field (Required) -->
            <div class="md:col-span-2">
              <label class="block mb-2 text-sm font-semibold text-gray-700">
                App Name <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.app_name"
                type="text"
                class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-[5px] text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                required
                placeholder="Enter application name"
              />
              <!-- Validation Error Display -->
              <p v-if="form.errors.app_name" class="mt-1 text-sm text-red-500">
                {{ form.errors.app_name }}
              </p>
            </div>

               <!-- Company Address Field (Multi-line) -->
            <div class="md:col-span-2">
              <label class="block mb-2 text-sm font-semibold text-gray-700">
                Address
              </label>
              <textarea
                v-model="form.address"
                rows="3"
                class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-[5px] text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"
                placeholder="Enter company address"
              ></textarea>
              <p v-if="form.errors.address" class="mt-1 text-sm text-red-500">
                {{ form.errors.address }}
              </p>
            </div>

            <!-- Phone Number Field -->
            <div>
                <label class="block mb-2 text-sm font-semibold text-gray-700">
                  Phone
                </label>
                <input
                  v-model="form.phone"
                  type="text"
                  inputmode="numeric"
                  pattern="\d*"
                  maxlength="10"
                  @input="handleMobileInput('phone', $event)"
                  class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-[5px] text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                  placeholder="Enter phone number"
                />
              <p v-if="form.errors.phone" class="mt-1 text-sm text-red-500">
                {{ form.errors.phone }}
              </p>
            </div>

            <!-- Email Address Field -->
            <div>
              <label class="block mb-2 text-sm font-semibold text-gray-700">
                Email
              </label>
              <input
                v-model="form.email"
                type="email"
                @blur="validateEmail"
                @input="clearEmailError"
                :class="[
                  'w-full px-4 py-2.5 bg-white border rounded-[5px] text-gray-900 focus:outline-none focus:ring-2 transition-all',
                  emailError ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-transparent'
                ]"
                placeholder="Enter email address"
              />
              <p
                v-if="emailError"
                class="mt-2 text-sm text-red-600 font-medium"
              >
                ⚠️ {{ emailError }}
              </p>
              <p
                v-else-if="form.errors.email"
                class="mt-1 text-sm text-red-500"
              >
                {{ form.errors.email }}
              </p>
            </div>

            <!-- Website URL Field -->
            <div>
              <label class="block mb-2 text-sm font-semibold text-gray-700">
                Website
              </label>
              <input
                v-model="form.website"
                type="url"
                @blur="validateWebsiteUrl"
                @input="clearWebsiteError"
                :class="[
                  'w-full px-4 py-2.5 bg-white border rounded-[5px] text-gray-900 focus:outline-none focus:ring-2 transition-all',
                  websiteError ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-transparent'
                ]"
                placeholder="https://example.com"
              />
              <p
                v-if="websiteError"
                class="mt-2 text-sm text-red-600 font-medium"
              >
                ⚠️ {{ websiteError }}
              </p>
              <p
                v-else-if="form.errors.website"
                class="mt-1 text-sm text-red-500"
              >
                {{ form.errors.website }}
              </p>
            </div>

            <!-- Currency Selection Dropdown (Required) -->
            <div>
              <label class="block mb-2 text-sm font-semibold text-gray-700">
                Currency 
              </label>

              <select
                v-model="form.currency"
                class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-[5px] text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                
              >
                <option v-for="c in currencies" :key="c.id" :value="c.code">
                  {{ c.code }} - {{ c.name }}
                </option>
              </select>
              <p v-if="form.errors.currency" class="mt-1 text-sm text-red-500">
                {{ form.errors.currency }}
              </p>
            </div>


            <!-- App Logo Upload Field with Preview -->
            <div class="md:col-span-2">
              <label class="block mb-2 text-sm font-semibold text-gray-700">
                App Logo
              </label>
              <div class="flex items-center gap-4">
                <!-- File Input for Logo Upload -->
                <input
                  type="file"
                  @change="handleLogoUpload"
                  accept="image/*"
                  class="block w-full text-sm text-gray-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-[5px] file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 file:transition-all file:cursor-pointer"
                />
                <!-- Logo Preview: Shows either new preview or existing logo -->
                <div v-if="currentLogo || logoPreview" class="flex-shrink-0">
                  <img
                    :src="logoPreview || `/storage/${currentLogo}`"
                    alt="App logo preview"
                    class="h-20 w-20 object-contain border-2 border-gray-300 rounded-lg bg-white p-2"
                  />
                </div>
              </div>
              <p class="mt-1 text-xs text-gray-500">
                Current logo is shown on the right. File input resets after saving.
              </p>
              <!-- File Format Guidelines -->
              <p class="mt-2 text-xs text-gray-500">
                Accepted formats: JPG, PNG, GIF (Max size: 2MB)
              </p>
              <!-- Validation Error Display -->
              <p v-if="form.errors.app_logo" class="mt-1 text-sm text-red-500">
                {{ form.errors.app_logo }}
              </p>
            </div>

            <!-- App Icon Upload Field with Preview -->
            <div class="md:col-span-2">
              <label class="block mb-2 text-sm font-semibold text-gray-700">
                App Icon (Favicon)
              </label>
              <div class="flex items-center gap-4">
                <!-- File Input for Icon Upload -->
                <input
                  type="file"
                  @change="handleIconUpload"
                  accept="image/*"
                  class="block w-full text-sm text-gray-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-[5px] file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 file:transition-all file:cursor-pointer"
                />
                <!-- Icon Preview: Shows either new preview or existing icon -->
                <div v-if="currentIcon || iconPreview" class="flex-shrink-0">
                  <img
                    :src="iconPreview || `/storage/${currentIcon}`"
                    alt="App icon preview"
                    class="h-20 w-20 object-contain border-2 border-gray-300 rounded-lg bg-white p-2"
                  />
                </div>
              </div>
              <p class="mt-1 text-xs text-gray-500">
                Current icon is shown on the right. File input resets after saving.
              </p>
              <!-- File Format Guidelines -->
              <p class="mt-2 text-xs text-gray-500">
                Accepted formats: JPG, PNG, GIF, ICO (Max size: 2MB)
              </p>
              <!-- Validation Error Display -->
              <p v-if="form.errors.app_icon" class="mt-1 text-sm text-red-500">
                {{ form.errors.app_icon }}
              </p>
            </div>

            <!-- App Footer Text Field -->
            <div class="md:col-span-2">
              <label class="block mb-2 text-sm font-semibold text-gray-700">
                App Footer
              </label>
              <textarea
                v-model="form.app_footer"
                rows="3"
                class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-[5px] text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"
                placeholder="Enter footer text (e.g., Copyright © 2025 Your Company)"
              ></textarea>
              <!-- Validation Error Display -->
              <p v-if="form.errors.app_footer" class="mt-1 text-sm text-red-500">
                {{ form.errors.app_footer }}
              </p>
            </div>
          </div>

          <!-- Form Submit Button -->
          <div class="flex justify-end mt-8 pt-6 border-t border-gray-200">
            <button
              type="submit"
              :disabled="form.processing"
              class="px-8 py-3 text-white bg-blue-600 rounded-[5px] hover:bg-blue-700 hover:scale-105 disabled:opacity-50 disabled:hover:scale-100 transition-all duration-200 font-semibold shadow-md hover:shadow-lg"
            >
              <!-- Dynamic button text based on processing state -->
              {{ form.processing ? "Saving..." : "Save Settings" }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
/**
 * App Settings Component Script
 *
 * Manages the application settings form including file uploads for logo and icon
 * Uses Inertia.js form helper for seamless form submission with file handling
 */

import { ref, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import { logActivity } from "@/composables/useActivityLog";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const { goToSettingsTab } = useDashboardNavigation();

/**
 * Component Props
 * @property {Object} appSetting - Existing app settings from database (nullable)
 */
const props = defineProps({
  appSetting: {
    type: Object,
    default: null,
  },
  currencies: {
    type: Array,
    default: () => [],
  },
});

/**
 * Reactive State Variables
 *
 * logoPreview: Stores base64 preview of newly selected logo
 * iconPreview: Stores base64 preview of newly selected icon
 * currentLogo: Stores path of existing logo from database
 * currentIcon: Stores path of existing icon from database
 */
const logoPreview = ref(null);
const iconPreview = ref(null);
const currentLogo = ref(null);
const currentIcon = ref(null);

/**
 * Inertia Form Instance
 * Handles form data, validation errors, and submission state
 */
const form = useForm({
  app_name: "",
  app_logo: null,
  app_icon: null,
  app_footer: "",
  address: "",
  phone: "",
  email: "",
  website: "",
  currency: "",
});

/**
 * Handle Logo File Upload
 * Reads selected file and creates a preview using FileReader API
 *
 * @param {Event} event - File input change event
 */
const handleLogoUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.app_logo = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      logoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

/**
 * Handle Icon File Upload
 * Reads selected file and creates a preview using FileReader API
 *
 * @param {Event} event - File input change event
 */
const handleIconUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.app_icon = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      iconPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

/**
 * Submit Form Handler
 * Posts form data to backend including file uploads
 * On success: clears previews, updates current images, resets file inputs
 */
const submit = () => {
  form.post(route("settings.app.store"), {
    preserveScroll: true,
    onSuccess: async (page) => {
      await logActivity("update", "app_settings", {
        app_name: form.app_name,
        has_logo: form.app_logo !== null,
        has_icon: form.app_icon !== null,
      });
      // Always clear preview images after save
      logoPreview.value = null;
      iconPreview.value = null;

      // Always update current images with newly saved ones from response
      if (page.props.appSetting) {
        currentLogo.value = page.props.appSetting.app_logo || null;
        currentIcon.value = page.props.appSetting.app_icon || null;
      }

      // Reset file input elements
      const fileInputs = document.querySelectorAll('input[type="file"]');
      fileInputs.forEach((input) => (input.value = ""));

      // Clear form file references
      form.app_logo = null;
      form.app_icon = null;
    },
  });
};

/**
 * Component Mounted Hook
 * Populates form with existing settings data when component loads
 */
onMounted(() => {
  if (props.appSetting) {
    form.app_name = props.appSetting.app_name || "";
    form.app_footer = props.appSetting.app_footer || "";
    currentLogo.value = props.appSetting.app_logo || null;
    currentIcon.value = props.appSetting.app_icon || null;
    form.address = props.appSetting.address || "";
    form.phone = props.appSetting.phone || "";
    form.email = props.appSetting.email || "";
    form.website = props.appSetting.website || "";
    form.currency = props.appSetting.currency || "";
  }
});
</script>
