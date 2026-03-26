<script setup>
import Checkbox from "@/Components/Checkbox.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

defineProps({
  canResetPassword: {
    type: Boolean,
  },
  status: {
    type: String,
  },
});

const form = useForm({
  email: "",
  password: "",
  remember: false,
});

const showPassword = ref(false);

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};

const submit = () => {
  form.post(route("login"), {
    onFinish: () => form.reset("password"),
  });
};
const currentYear = new Date().getFullYear();
</script>

<template>
  <GuestLayout>
    <Head title="Log in" />

    <div class="min-h-screen flex bg-gray-50">
      <!-- Left Column - Features Section -->
      <div
        class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-700 p-12 flex-col justify-center"
      >
        <div class="max-w-lg mx-auto">
          <!-- Logo/Brand -->
          <div class="mb-12">
            <div class="flex items-center space-x-3">
              <div
                class="h-12 w-12 bg-indigo-600 rounded-xl flex items-center justify-center"
              >
                <svg
                  class="h-7 w-7 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13 10V3L4 14h7v7l9-11h-7z"
                  />
                </svg>
              </div>
              <span class="text-2xl font-bold text-white">JPOS System</span>
            </div>
          </div>

          <!-- Main Heading -->
          <h1 class="text-4xl font-bold text-white mb-4">
            Powerful Point of Sale Solution
          </h1>
          <p class="text-lg text-blue-100 mb-12">
            Streamline your business operations with our comprehensive inventory and sales
            management system.
          </p>

          <!-- Features Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Feature 1 -->
            <div
              class="bg-white/10 backdrop-blur-md rounded-xl p-5 border border-white/20 hover:bg-white/20 hover:scale-105 hover:shadow-2xl transition-all duration-300 group cursor-pointer"
            >
              <div class="flex items-center mb-3">
                <div
                  class="h-10 w-10 bg-white/30 rounded-lg flex items-center justify-center group-hover:bg-white/40 transition-all duration-300"
                >
                  <svg
                    class="h-5 w-5 text-white"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                    />
                  </svg>
                </div>
              </div>
              <h3 class="text-base font-semibold text-white mb-2">Real-time Analytics</h3>
              <p class="text-sm text-blue-100">
                Track sales, inventory, and performance metrics in real-time.
              </p>
            </div>

            <!-- Feature 2 -->
            <div
              class="bg-white/10 backdrop-blur-md rounded-xl p-5 border border-white/20 hover:bg-white/20 hover:scale-105 hover:shadow-2xl transition-all duration-300 group cursor-pointer"
            >
              <div class="flex items-center mb-3">
                <div
                  class="h-10 w-10 bg-white/30 rounded-lg flex items-center justify-center group-hover:bg-white/40 transition-all duration-300"
                >
                  <svg
                    class="h-5 w-5 text-white"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                    />
                  </svg>
                </div>
              </div>
              <h3 class="text-base font-semibold text-white mb-2">
                Inventory Management
              </h3>
              <p class="text-sm text-blue-100">
                Efficiently manage stock levels and product transfers.
              </p>
            </div>

            <!-- Feature 3 -->
            <div
              class="bg-white/10 backdrop-blur-md rounded-xl p-5 border border-white/20 hover:bg-white/20 hover:scale-105 hover:shadow-2xl transition-all duration-300 group cursor-pointer"
            >
              <div class="flex items-center mb-3">
                <div
                  class="h-10 w-10 bg-white/30 rounded-lg flex items-center justify-center group-hover:bg-white/40 transition-all duration-300"
                >
                  <svg
                    class="h-5 w-5 text-white"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                    />
                  </svg>
                </div>
              </div>
              <h3 class="text-base font-semibold text-white mb-2">Secure & Reliable</h3>
              <p class="text-sm text-blue-100">
                Enterprise-grade security with role-based access control.
              </p>
            </div>

            <!-- Feature 4 -->
            <div
              class="bg-white/10 backdrop-blur-md rounded-xl p-5 border border-white/20 hover:bg-white/20 hover:scale-105 hover:shadow-2xl transition-all duration-300 group cursor-pointer"
            >
              <div class="flex items-center mb-3">
                <div
                  class="h-10 w-10 bg-white/30 rounded-lg flex items-center justify-center group-hover:bg-white/40 transition-all duration-300"
                >
                  <svg
                    class="h-5 w-5 text-white"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                </div>
              </div>
              <h3 class="text-base font-semibold text-white mb-2">Financial Reports</h3>
              <p class="text-sm text-blue-100">
                Generate detailed financial reports for expenses and sales.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column - Login Form -->
      <div class="w-full lg:w-1/2 flex items-center justify-center p-8 lg:p-12">
        <div class="w-full max-w-md">
          <!-- Mobile Logo -->
          <div class="lg:hidden mb-8 text-center">
            <div class="inline-flex items-center space-x-3">
              <div
                class="h-10 w-10 bg-indigo-600 rounded-xl flex items-center justify-center"
              >
                <svg
                  class="h-6 w-6 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13 10V3L4 14h7v7l9-11h-7z"
                  />
                </svg>
              </div>
              <span class="text-xl font-bold text-gray-900">JPOS System</span>
            </div>
          </div>

          <!-- Login Card -->
          <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 lg:p-10">
            <!-- Header -->
            <div class="mb-8">
              <h2 class="text-3xl font-bold text-gray-900 mb-2">Sign In</h2>
              <p class="text-gray-600">Welcome back! Please enter your credentials.</p>
            </div>

            <!-- Status Message -->
            <div
              v-if="status"
              class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4"
            >
              <div class="flex items-center">
                <svg
                  class="h-5 w-5 text-green-600 mr-2"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"
                  />
                </svg>
                <p class="text-sm font-medium text-green-800">{{ status }}</p>
              </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
              <!-- Email Field -->
              <div>
                <InputLabel
                  for="email"
                  value="Email Address"
                  class="text-sm font-medium text-gray-700 mb-2"
                />
                <TextInput
                  id="email"
                  type="email"
                  class="block w-full px-4 py-3 rounded-lg border border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-400"
                  v-model="form.email"
                  required
                  autofocus
                  autocomplete="username"
                  placeholder="Enter your email"
                />
                <InputError class="mt-2" :message="form.errors.email" />
              </div>

              <!-- Password Field -->
              <div>
                <InputLabel
                  for="password"
                  value="Password"
                  class="text-sm font-medium text-gray-700 mb-2"
                />
                <div class="relative">
                  <TextInput
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    class="block w-full px-4 py-3 pr-12 rounded-lg border border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 text-gray-900 placeholder-gray-400"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                  />
                  <button
                    type="button"
                    @click="togglePasswordVisibility"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none transition-colors duration-200"
                  >
                    <!-- Eye Icon (Show Password) -->
                    <svg
                      v-if="!showPassword"
                      class="h-5 w-5"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                      />
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                      />
                    </svg>
                    <!-- Eye Off Icon (Hide Password) -->
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
                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                      />
                    </svg>
                  </button>
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
              </div>

              <!-- Submit Button -->
              <div class="pt-2">
                <PrimaryButton
                  class="w-full flex justify-center items-center py-3.5 px-4 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm transition-all duration-200"
                  :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                  :disabled="form.processing"
                >
                  <span v-if="!form.processing">Sign In</span>
                  <span v-else class="flex items-center">
                    <svg
                      class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
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
                    Signing in...
                  </span>
                </PrimaryButton>
              </div>
            </form>
          </div>

          <!-- Footer Text -->
          <p class="mt-8 text-center text-sm text-gray-500">
            © {{ currentYear }} JPOS System. All rights reserved.
          </p>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>
