<template>
  <AppLayout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 p-6">
      <div>
        <!-- Header Section with Back Button and Title -->
        <div class="mb-6 flex items-center gap-4">
          <!-- Back to Dashboard Button -->
          <button
            @click="goToSettingsTab"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-3xl font-bold text-black flex items-center gap-2">
          Bill Settings
          </h1>
          <div></div>
        </div>

        <!-- Main Card -->
        <div
          class="bg-white rounded-2xl border border-gray-200 shadow-lg overflow-hidden"
        >
          <!-- Header -->
          <div class="bg-white border-b-2 border-blue-600 p-6">
            <h2 class="text-xl font-semibold text-blue-600">Bill Configuration</h2>
            <p class="text-sm text-gray-600 mt-1">
              Configure your bill/receipt printing settings and company information
            </p>
          </div>

          <!-- Form Content -->
          <div class="p-6 bg-gray-50">
            <form @submit.prevent="submit" enctype="multipart/form-data">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Bill Logo Upload -->
                <div class="md:col-span-2">
                  <label class="block mb-2 text-sm font-semibold text-gray-700">
                    Bill Logo
                  </label>
                  <div class="flex items-center gap-4">
                    <input
                      type="file"
                      @change="handleLogoUpload"
                      accept="image/*"
                      class="block w-full text-sm text-gray-700 file:mr-4 file:py-2.5 file:px-4 file:rounded-[5px] file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 file:cursor-pointer cursor-pointer border border-gray-300 rounded-[5px] bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <div v-if="currentLogo || logoPreview" class="flex-shrink-0">
                      <img
                        :src="logoPreview || `/storage/${currentLogo}`"
                        alt="Logo preview"
                        class="h-20 w-20 object-contain border-2 border-gray-300 rounded-lg bg-white p-2"
                      />
                    </div>
                  </div>
                  <p class="mt-2 text-xs text-gray-500">
                    📎 Accepted formats: JPG, PNG, GIF (Max size: 2MB)
                  </p>
                  <p
                    v-if="form.errors.logo"
                    class="mt-2 text-sm text-red-600 font-medium"
                  >
                    ⚠️ {{ form.errors.logo }}
                  </p>
                </div>

                <!-- Company Name -->
                <div class="md:col-span-2">
                  <label class="block mb-2 text-sm font-semibold text-gray-700">
                    Company Name <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.company_name"
                    type="text"
                    class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-[5px] text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    placeholder="Enter company name"
                    required
                  />
                  <p
                    v-if="form.errors.company_name"
                    class="mt-2 text-sm text-red-600 font-medium"
                  >
                    ⚠️ {{ form.errors.company_name }}
                  </p>
                </div>

                <!-- Address -->
                <div class="md:col-span-2">
                  <label class="block mb-2 text-sm font-semibold text-gray-700">
                    Address
                  </label>
                  <textarea
                    v-model="form.address"
                    rows="3"
                    class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-[5px] text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    placeholder="Enter company address"
                  ></textarea>
                  <p
                    v-if="form.errors.address"
                    class="mt-2 text-sm text-red-600 font-medium"
                  >
                    ⚠️ {{ form.errors.address }}
                  </p>
                </div>

                <!-- Mobile 1 -->
                <div>
                  <label class="block mb-2 text-sm font-semibold text-gray-700">
                    Mobile Number 1 <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.mobile_1"
                    type="text"
                    inputmode="numeric"
                    pattern="\d*"
                    maxlength="10"
                    @input="handleMobileInput('mobile_1', $event)"
                    class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-[5px] text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    placeholder="Enter mobile number"
                    required
                  />
                  <p
                    v-if="form.errors.mobile_1"
                    class="mt-2 text-sm text-red-600 font-medium"
                  >
                    ⚠️ {{ form.errors.mobile_1 }}
                  </p>
                </div>

                <!-- Mobile 2 -->
                <div>
                  <label class="block mb-2 text-sm font-semibold text-gray-700">
                    Mobile Number 2
                  </label>
                  <input
                    v-model="form.mobile_2"
                    type="text"
                    inputmode="numeric"
                    pattern="\d*"
                    maxlength="10"
                    @input="handleMobileInput('mobile_2', $event)"
                    class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-[5px] text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    placeholder="Enter mobile number (optional)"
                  />
                  <p
                    v-if="form.errors.mobile_2"
                    class="mt-2 text-sm text-red-600 font-medium"
                  >
                    ⚠️ {{ form.errors.mobile_2 }}
                  </p>
                </div>

                <!-- Email -->
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
                    placeholder="company@example.com"
                  />
                  <p
                    v-if="emailError"
                    class="mt-2 text-sm text-red-600 font-medium"
                  >
                    ⚠️ {{ emailError }}
                  </p>
                  <p
                    v-else-if="form.errors.email"
                    class="mt-2 text-sm text-red-600 font-medium"
                  >
                    ⚠️ {{ form.errors.email }}
                  </p>
                </div>

                <!-- Website URL -->
                <div>
                  <label class="block mb-2 text-sm font-semibold text-gray-700">
                    Website URL
                  </label>
                  <input
                    v-model="form.website_url"
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
                    v-else-if="form.errors.website_url"
                    class="mt-2 text-sm text-red-600 font-medium"
                  >
                    ⚠️ {{ form.errors.website_url }}
                  </p>
                </div>

                <!-- Footer Description -->
                <div class="md:col-span-2">
                  <label class="block mb-2 text-sm font-semibold text-gray-700">
                    Footer Description
                  </label>
                  <textarea
                    v-model="form.footer_description"
                    rows="2"
                    class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-[5px] text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    placeholder="Thank you for your business!"
                  ></textarea>
                  <p
                    v-if="form.errors.footer_description"
                    class="mt-2 text-sm text-red-600 font-medium"
                  >
                    ⚠️ {{ form.errors.footer_description }}
                  </p>
                </div>

                <!-- Print Bill Size -->
                <div>
                  <label class="block mb-2 text-sm font-semibold text-gray-700">
                    Print Bill Size <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="form.print_size"
                    class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-[5px] text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all cursor-pointer"
                    required
                  >
                    <option value="58mm">58mm (Small Receipt)</option>
                    <option value="80mm">80mm (Standard Receipt)</option>
                    <option value="112mm">112mm (Large Receipt)</option>
                    <option value="210mm">210mm (A4 Size)</option>
                  </select>
                  <p
                    v-if="form.errors.print_size"
                    class="mt-2 text-sm text-red-600 font-medium"
                  >
                    ⚠️ {{ form.errors.print_size }}
                  </p>
                </div>
              </div>

              <!-- Submit Button -->
              <div class="flex justify-end mt-8 pt-6 border-t border-gray-200">
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="inline-flex items-center gap-2 px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-[5px] font-semibold shadow-md hover:shadow-lg hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 transition-all duration-200"
                >
                  <svg
                    v-if="form.processing"
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
                      d="M5 13l4 4L19 7"
                    ></path>
                  </svg>
                  {{ form.processing ? "Saving..." : "Save Settings" }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const { goToSettingsTab } = useDashboardNavigation();

const props = defineProps({
  setting: {
    type: Object,
    default: null,
  },
});

const logoPreview = ref(null);
const currentLogo = ref(null);
const emailError = ref('');
const websiteError = ref('');

const form = useForm({
  company_name: "",
  address: "",
  mobile_1: "",
  mobile_2: "",
  email: "",
  website_url: "",
  footer_description: "",
  print_size: "80mm",
  logo: null,
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
  if (!form.website_url) {
    websiteError.value = '';
    return true;
  }

  try {
    // Add protocol if missing
    let url = form.website_url;
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
      url = 'https://' + url;
      form.website_url = url;
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

const handleLogoUpload = (event) => {
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

  form.post(route("settings.bill.store"), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      logoPreview.value = null;
      const fileInput = document.querySelector('input[type="file"]');
      if (fileInput) fileInput.value = "";
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

import { watchEffect } from "vue";

watchEffect(() => {
  if (props.setting) {
    form.company_name = props.setting.company_name || "";
    form.address = props.setting.address || "";
    form.mobile_1 = props.setting.mobile_1 || "";
    form.mobile_2 = props.setting.mobile_2 || "";
    form.email = props.setting.email || "";
    form.website_url = props.setting.website_url || "";
    form.footer_description = props.setting.footer_description || "";
    form.print_size = props.setting.print_size || "80mm";
    currentLogo.value = props.setting.logo_path || null;
  }
});

// Sanitize mobile inputs: allow only digits and limit to 10 characters
const handleMobileInput = (field, event) => {
  const raw = event.target.value || '';
  const digits = raw.replace(/\D/g, '').slice(0, 10);
  form[field] = digits;
};
</script>
