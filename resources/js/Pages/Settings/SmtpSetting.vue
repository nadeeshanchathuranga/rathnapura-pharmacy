
<template>
  <AppLayout>
    <!-- Main Container -->
    <div class="p-6">
      <!-- Header Section with Back Button and Title -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <!-- Back to Dashboard Button -->
          <button
            @click="$inertia.visit(route('dashboard'))"
            class="px-4 py-2 text-white bg-accent rounded hover:bg-accent"
          >
            Back
          </button>
          <h1 class="text-3xl font-bold text-white">SMTP Settings</h1>
        </div>
      </div>

      <!-- Settings Form Container -->
      <div class="bg-dark border-4 border-accent rounded-lg p-6">
        <form @submit.prevent="submit">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Mail Mailer Field -->
            <div>
              <label class="block mb-2 text-sm font-medium text-white">
                Mail Mailer <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.mail_mailer"
                class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              >
                <option value="smtp">SMTP</option>
                <option value="sendmail">Sendmail</option>
                <option value="mail">Mail</option>
              </select>
              <p v-if="form.errors.mail_mailer" class="mt-1 text-sm text-red-500">
                {{ form.errors.mail_mailer }}
              </p>
            </div>

            <!-- Mail Host Field (Required) -->
            <div>
              <label class="block mb-2 text-sm font-medium text-white">
                Mail Host <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.mail_host"
                type="text"
                class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
                placeholder="e.g., smtp.gmail.com"
              />
              <p v-if="form.errors.mail_host" class="mt-1 text-sm text-red-500">
                {{ form.errors.mail_host }}
              </p>
            </div>

            <!-- Mail Port Field (Required) -->
            <div>
              <label class="block mb-2 text-sm font-medium text-white">
                Mail Port <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.mail_port"
                type="number"
                min="1"
                max="65535"
                class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
                placeholder="e.g., 587"
              />
              <p v-if="form.errors.mail_port" class="mt-1 text-sm text-red-500">
                {{ form.errors.mail_port }}
              </p>
            </div>

            <!-- Mail Username Field (Required) -->
            <div>
              <label class="block mb-2 text-sm font-medium text-white">
                Mail Username <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.mail_username"
                type="text"
                class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
                placeholder="Your email address"
              />
              <p v-if="form.errors.mail_username" class="mt-1 text-sm text-red-500">
                {{ form.errors.mail_username }}
              </p>
            </div>

            <!-- Mail Password Field (Required) -->
            <div>
              <label class="block mb-2 text-sm font-medium text-white">
                Mail Password <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <input
                  v-model="form.mail_password"
                  :type="showPassword ? 'text' : 'password'"
                  class="w-full px-3 py-2 pr-10 bg-gray-800 border border-gray-700 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                  placeholder="Your email password or app password"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-white"
                >
                  <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                  </svg>
                </button>
              </div>
              <p v-if="form.errors.mail_password" class="mt-1 text-sm text-red-500">
                {{ form.errors.mail_password }}
              </p>
            </div>

            <!-- Mail Encryption Field -->
            <div>
              <label class="block mb-2 text-sm font-medium text-white">
                Mail Encryption
              </label>
              <select
                v-model="form.mail_encryption"
                class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">None</option>
                <option value="ssl">SSL</option>
                <option value="tls">TLS</option>
              </select>
              <p v-if="form.errors.mail_encryption" class="mt-1 text-sm text-red-500">
                {{ form.errors.mail_encryption }}
              </p>
            </div>

            <!-- Mail From Address Field -->
            <div>
              <label class="block mb-2 text-sm font-medium text-white">
                Mail From Address
              </label>
              <input
                v-model="form.mail_from_address"
                type="email"
                class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="noreply@example.com"
              />
              <p v-if="form.errors.mail_from_address" class="mt-1 text-sm text-red-500">
                {{ form.errors.mail_from_address }}
              </p>
            </div>

            <!-- Mail From Name Field -->
            <div>
              <label class="block mb-2 text-sm font-medium text-white">
                Mail From Name
              </label>
              <input
                v-model="form.mail_from_name"
                type="text"
                class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Your Application Name"
              />
              <p v-if="form.errors.mail_from_name" class="mt-1 text-sm text-red-500">
                {{ form.errors.mail_from_name }}
              </p>
            </div>
          </div>

          <!-- Form Submit Button -->
          <div class="flex justify-end mt-6">
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50"
            >
              {{ form.processing ? 'Saving...' : 'Save Settings' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
/**
 * SMTP Settings Component Script
 * 
 * Manages SMTP configuration for email sending
 */

import { ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { logActivity } from '@/composables/useActivityLog';

/**
 * Component Props
 */
const props = defineProps({
  smtpSetting: {
    type: Object,
    default: null,
  },
});

/**
 * Show/hide password toggle
 */
const showPassword = ref(false);

/**
 * Inertia Form Instance
 */
const form = useForm({
  mail_mailer: 'smtp',
  mail_host: '',
  mail_port: 587,
  mail_username: '',
  mail_password: '',
  mail_encryption: 'tls',
  mail_from_address: '',
  mail_from_name: '',
});

/**
 * Submit Form Handler
 */
const submit = () => {
  form.post(route('settings.smtp.store'), {
    preserveScroll: true,
    onSuccess: async () => {
      await logActivity('update', 'smtp_settings', {
        mail_host: form.mail_host,
        mail_mailer: form.mail_mailer,
        mail_encryption: form.mail_encryption
      });
    },
  });
};

/**
 * Component Mounted Hook
 * Populates form with existing settings
 */
onMounted(() => {
  if (props.smtpSetting) {
    form.mail_mailer = props.smtpSetting.mail_mailer || 'smtp';
    form.mail_host = props.smtpSetting.mail_host || '';
    form.mail_port = props.smtpSetting.mail_port || 587;
    form.mail_username = props.smtpSetting.mail_username || '';
    form.mail_password = props.smtpSetting.mail_password || '';
    form.mail_encryption = props.smtpSetting.mail_encryption || 'tls';
    form.mail_from_address = props.smtpSetting.mail_from_address || '';
    form.mail_from_name = props.smtpSetting.mail_from_name || '';
  }
});
</script>
