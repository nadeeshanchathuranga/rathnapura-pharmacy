<script setup>
import { ref, watch, onMounted, watchEffect } from "vue";
import { usePage } from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import { Link, Head } from "@inertiajs/vue3";

defineProps({
  title: String,
});

const showingNavigationDropdown = ref(false);

// Simple toast for global success/error flashes
const page = usePage();

// Dynamically set document title and favicon based on appSettings
watchEffect(() => {
  const appSettings = page.props.appSettings;
  if (appSettings && appSettings.app_name) {
    document.title = appSettings.app_name;
  } else {
    document.title = 'POS System';
  }
  if (appSettings && appSettings.app_icon) {
    let link = document.querySelector("link[rel~='icon']");
    if (!link) {
      link = document.createElement('link');
      link.rel = 'icon';
      document.head.appendChild(link);
    }
    link.type = 'image/png';
    link.href = `/storage/${appSettings.app_icon}`;
  }
});
const toast = ref({ type: null, message: null, visible: false });
let toastTimer = null;

const showToast = (type, message) => {
  if (!message) return;
  toast.value = { type, message, visible: true };
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    toast.value.visible = false;
  }, 3500);
};

watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) showToast("success", flash.success);
    if (flash?.error) showToast("error", flash.error);
  },
  { deep: true, immediate: true }
);
</script>

<template>
  <div>
    <!-- The favicon and title are set dynamically in the script block above -->

    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50">
      <nav class="bg-white border-b border-gray-200 shadow-md">
        <!-- Primary Navigation Menu -->
        <div class="mx-auto max-w px-6 sm:px-8 lg:px-10">
          <div class="flex h-20 justify-between items-center">
            <div class="flex">
              <!-- Logo - Uses App Settings if available, otherwise Company Info -->
              <div class="flex shrink-0 items-center gap-4">
                <Link
                  :href="route('dashboard')"
                  class="flex items-center gap-3 hover:opacity-80 transition-opacity duration-200"
                >
                  <!-- App Logo (from App Settings) takes priority -->
                  <img
                    v-if="$page.props.appSettings && $page.props.appSettings.app_logo"
                    :src="`/storage/${$page.props.appSettings.app_logo}`"
                    alt="App Logo"
                    class="block h-12 w-auto"
                  />
                  <!-- Fallback to Company Logo -->
                  <img
                    v-else-if="$page.props.companyInfo && $page.props.companyInfo.logo"
                    :src="`/storage/${$page.props.companyInfo.logo}`"
                    alt="Company Logo"
                    class="block h-12 w-auto"
                  />
                  <!-- Final fallback to default ApplicationLogo -->
                  <!-- <ApplicationLogo
                                        v-else
                                        class="block h-9 w-auto fill-current text-white"
                                    />
                                     -->
                  <!-- App Name (from App Settings) takes priority over Company Name -->
                  <span
                    v-if="$page.props.appSettings && $page.props.appSettings.app_name"
                    class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent"
                  >
                    {{ $page.props.appSettings.app_name }}
                  </span>
                  <span
                    v-else-if="
                      $page.props.companyInfo && $page.props.companyInfo.company_name
                    "
                    class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent"
                  >
                    {{ $page.props.companyInfo.company_name }}
                  </span>
                </Link>
              </div>

              <!-- Navigation Links -->
              <!-- <div class="hidden space-x-8 sm:ms-12 sm:flex sm:items-center">
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    Dashboard
                                </NavLink>
                            </div> -->
            </div>

            <div class="hidden sm:ms-6 sm:flex sm:items-center gap-4">
              <!-- User Profile Display -->
              <div
                class="inline-flex items-center gap-3 h-12 px-4 rounded-lg border border-gray-200 bg-gradient-to-r from-white to-gray-50 shadow-sm"
              >
                <div
                  class="flex items-center justify-center w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white font-bold text-sm shadow-md"
                >
                  {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                </div>
                <div class="flex flex-col justify-center">
                  <span class="text-sm font-semibold text-gray-900 leading-tight">
                    {{ $page.props.auth.user.name }}
                  </span>
                  <span class="text-xs text-gray-500 leading-tight">Logged in</span>
                </div>
              </div>

              <!-- POS Button -->
              <Link
                :href="route('sales.index')"
                class="inline-flex items-center justify-center gap-2 h-12 px-6 rounded-lg border border-blue-600 bg-blue-600 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-blue-700 hover:border-blue-700 hover:shadow-lg hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
              >
                <span class="text-lg">🏪</span>
                <span>POS</span>
              </Link>

              <!-- Logout Button -->
              <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="inline-flex items-center justify-center gap-2 h-12 px-6 rounded-lg border border-gray-600 bg-gray-600 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-gray-700 hover:border-gray-700 hover:shadow-lg hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
              >
                <svg
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                  ></path>
                </svg>
                <span>Logout</span>
              </Link>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
              <button
                @click="showingNavigationDropdown = !showingNavigationDropdown"
                class="inline-flex items-center justify-center rounded-lg p-2.5 text-gray-600 transition-all duration-200 hover:bg-gray-100 hover:text-gray-900 focus:bg-gray-100 focus:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <svg
                  class="h-6 w-6"
                  stroke="currentColor"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <path
                    :class="{
                      hidden: showingNavigationDropdown,
                      'inline-flex': !showingNavigationDropdown,
                    }"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16"
                  />
                  <path
                    :class="{
                      hidden: !showingNavigationDropdown,
                      'inline-flex': showingNavigationDropdown,
                    }"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Responsive Navigation Menu -->
        <div
          :class="{
            block: showingNavigationDropdown,
            hidden: !showingNavigationDropdown,
          }"
          class="sm:hidden"
        >
          <div class="space-y-1 pb-3 pt-2">
            <ResponsiveNavLink
              :href="route('dashboard')"
              :active="route().current('dashboard')"
            >
              Dashboard
            </ResponsiveNavLink>
          </div>

          <!-- Responsive Settings Options -->
          <div class="border-t border-gray-700 pb-1 pt-4">
            <div class="px-4">
              <div class="text-base font-medium text-white">
                {{ $page.props.auth.user.name }}
              </div>
              <div class="text-sm font-medium text-gray-400">
                {{ $page.props.auth.user.email }}
              </div>
            </div>

            <div class="mt-3 space-y-1">
              <ResponsiveNavLink :href="route('profile.edit')">
                Profile
              </ResponsiveNavLink>
              <ResponsiveNavLink :href="route('logout')" method="post" as="button">
                Log Out
              </ResponsiveNavLink>
            </div>
          </div>
        </div>
      </nav>

      <!-- Page Content -->
      <main>
        <slot />
      </main>

      <!-- Global Toast -->
      <div
        v-if="toast.visible"
        class="fixed top-6 right-6 z-50 max-w-sm w-full shadow-lg rounded-md border p-4 flex items-start gap-3"
        :class="toast.type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
      >
        <span class="text-xl">{{ toast.type === 'success' ? '✅' : '⚠️' }}</span>
        <div class="text-sm leading-5">{{ toast.message }}</div>
        <button class="ml-auto text-gray-500 hover:text-gray-700" @click="toast.visible = false">✖</button>
      </div>

      <!-- App Footer (if configured in App Settings) -->
      <footer
        v-if="$page.props.appSettings && $page.props.appSettings.app_footer"
        class="bg-secondary border-t border-gray-700 py-4 mt-8"
      >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <p class="text-center text-sm text-gray-400">
            {{ $page.props.appSettings.app_footer }}
          </p>
        </div>
      </footer>
    </div>
  </div>
</template>
