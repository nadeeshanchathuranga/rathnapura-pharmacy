<template>
  <AppLayout>
    <!-- Main Container -->
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
          <h1 class="text-3xl font-bold text-black">Company Information</h1>
        </div>
      </div>

      <!-- Settings Form Container -->
      <div class="bg-white rounded-2xl border border-gray-200 shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-white border-b-2 border-blue-600 p-6">
          <h2 class="text-xl font-semibold text-blue-600">Company Details</h2>
          <p class="text-sm text-gray-600 mt-1">
            Update your company information and branding
          </p>
        </div>

        <!-- Form Content -->
        <form @submit.prevent="submit" class="p-6 bg-gray-50">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Company Name Field (Required) -->
            <div class="md:col-span-2">
              <label class="block mb-2 text-sm font-semibold text-gray-700">
                Company Name <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.company_name"
                type="text"
                class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-[5px] text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                required
                placeholder="Enter company name"
              />
              <!-- Validation Error Display -->
              <p v-if="form.errors.company_name" class="mt-1 text-sm text-red-500">
                {{ form.errors.company_name }}
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
                Currency <span class="text-red-500">*</span>
              </label>

              <select
                v-model="form.currency"
                class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-[5px] text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                required
              >
                <option v-for="c in currencies" :key="c.id" :value="c.code">
                  {{ c.code }} - {{ c.name }}
                </option>
              </select>
              <p v-if="form.errors.currency" class="mt-1 text-sm text-red-500">
                {{ form.errors.currency }}
              </p>
            </div>

            <!-- Company Logo Upload with Preview -->
            <div class="md:col-span-2">
              <label class="block mb-2 text-sm font-semibold text-gray-700">
                Company Logo
              </label>
              <div class="flex items-center gap-4">
                <!-- File Input for Logo Upload -->
                <input
                  type="file"
                  @change="handleFileUpload"
                  accept="image/*"
                  class="block w-full text-sm text-gray-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-[5px] file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 file:transition-all file:cursor-pointer"
                />
                <!-- Logo Preview: Shows either new preview or existing logo -->
                <div v-if="currentLogo || logoPreview" class="flex-shrink-0">
                  <img
                    :src="logoPreview || `/storage/${currentLogo}`"
                    alt="Logo preview"
                    class="h-20 w-20 object-contain border-2 border-gray-300 rounded-lg bg-white p-2"
                  />
                </div>
              </div>
              <!-- File Format Guidelines -->
              <p class="mt-2 text-xs text-gray-500">
                Accepted formats: JPG, PNG, GIF (Max size: 2MB)
              </p>
              <!-- Validation Error Display -->
              <p v-if="form.errors.logo" class="mt-1 text-sm text-red-500">
                {{ form.errors.logo }}
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
 * Company Information Component Script
 *
 * Manages company profile settings including logo upload
 * Company name and logo are displayed globally in the navigation bar
 * Uses Inertia.js form helper for seamless form submission with file handling
 */

import { ref, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import { logActivity } from "@/composables/useActivityLog";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const { goToSettingsTab } = useDashboardNavigation();

/**
 * Component Props
 * @property {Object} companyInfo - Existing company information from database (nullable)
 */
const props = defineProps({
  companyInfo: {
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
 * currentLogo: Stores path of existing logo from database
 */
const logoPreview = ref(null);
const currentLogo = ref(null);
const emailError = ref('');
const websiteError = ref('');

/**
 * Inertia Form Instance
 * Handles form data, validation errors, and submission state
 */
const form = useForm({
  company_name: "",
  address: "",
  phone: "",
  email: "",
  website: "",
  logo: null,
  currency: "LKR",
});

// Email validation function
const validateEmail = () => {
  if (!form.email) {
    emailError.value = '';
    return true;
  }

  const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

  if (!emailRegex.test(form.email)) {
    emailError.value = 'Please enter a valid email address';
    return false;
  }

  if (form.email.length > 255) {
    emailError.value = 'Email address must be less than 255 characters';
    return false;
  }

  emailError.value = '';
  return true;
};

// Website URL validation function
const validateWebsiteUrl = () => {
  if (!form.website) {
    websiteError.value = '';
    return true;
  }

  try {
    // Add protocol if missing
    let url = form.website;
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
      url = 'https://' + url;
      form.website = url;
    }

    const urlObject = new URL(url);

    // Check if it's a valid HTTP/HTTPS URL
    if (!['http:', 'https:'].includes(urlObject.protocol)) {
      websiteError.value = 'Website URL must be a valid HTTP or HTTPS URL';
      return false;
    }

    // Check URL length
    if (url.length > 2048) {
      websiteError.value = 'Website URL must be less than 2048 characters';
      return false;
    }

    // Check for valid domain
    if (!urlObject.hostname || urlObject.hostname.length < 3) {
      websiteError.value = 'Please enter a valid website URL';
      return false;
    }

    websiteError.value = '';
    return true;
  } catch (error) {
    websiteError.value = 'Please enter a valid website URL (e.g., https://example.com)';
    return false;
  }
};

// Clear validation errors
const clearEmailError = () => {
  if (emailError.value) {
    emailError.value = '';
  }
};

const clearWebsiteError = () => {
  if (websiteError.value) {
    websiteError.value = '';
  }
};

/**
 * Handle Logo File Upload
 * Reads selected file and creates a preview using FileReader API
 * This logo will be displayed in the navigation bar after saving
 *
 * @param {Event} event - File input change event
 */
const handleFileUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.logo = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      logoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

/**
 * Submit Form Handler
 * Posts form data to backend including file upload
 * On success: clears preview, updates current logo, resets file input
 * Logo will be automatically displayed in navigation via shared props
 */
const submit = () => {
  // Clear any previous errors
  emailError.value = '';
  websiteError.value = '';

  // Validate email and website URL
  const isEmailValid = validateEmail();
  const isWebsiteValid = validateWebsiteUrl();

  // Don't submit if validation fails
  if (!isEmailValid || !isWebsiteValid) {
    return;
  }

  form.post(route("settings.company.store"), {
    preserveScroll: true,
    onSuccess: async (page) => {
      await logActivity("update", "company_information", {
        company_name: form.company_name,
        company_email: form.email,
      });
      // Clear preview image
      logoPreview.value = null;

      // Update current logo with newly saved one from response
      if (page.props.companyInfo && page.props.companyInfo.logo) {
        currentLogo.value = page.props.companyInfo.logo;
      }

      // Reset the file input element
      const fileInput = document.querySelector('input[type="file"]');
      if (fileInput) {
        fileInput.value = "";
      }

      // Clear form file reference
      form.logo = null;

      // Clear validation errors on success
      emailError.value = '';
      websiteError.value = '';
    },
    onError: () => {
      // Re-validate fields if backend returns errors
      validateEmail();
      validateWebsiteUrl();
    }
  });
};

/**
 * Component Mounted Hook
 * Populates form with existing company information when component loads
 */
onMounted(() => {
  if (props.companyInfo) {
    form.company_name = props.companyInfo.company_name || "";
    form.address = props.companyInfo.address || "";
    form.phone = props.companyInfo.phone || "";
    form.email = props.companyInfo.email || "";
    form.website = props.companyInfo.website || "";
    form.currency = props.companyInfo.currency || "LKR";
    currentLogo.value = props.companyInfo.logo || null;
  }
});

// Sanitize phone input: allow only digits and limit to 10 characters
const handleMobileInput = (field, event) => {
  const raw = event.target.value || '';
  const digits = raw.replace(/\D/g, '').slice(0, 10);
  form[field] = digits;
};
</script>
