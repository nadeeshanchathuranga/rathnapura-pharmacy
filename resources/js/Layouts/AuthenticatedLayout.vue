<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, Head } from '@inertiajs/vue3';

defineProps({
  title: String,
});

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
        <!-- Set App Icon as Favicon if available -->
        <Head>
            <link 
                v-if="$page.props.appSettings && $page.props.appSettings.app_icon" 
                rel="icon" 
                type="image/x-icon" 
                :href="`/storage/${$page.props.appSettings.app_icon}`" 
            />
        </Head>
        
        <div class="min-h-screen bg-secondary">
            <nav class="border-b border-gray-700 bg-secondary">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo - Uses App Settings if available, otherwise Company Info -->
                            <div class="flex shrink-0 items-center gap-3">
                                <Link :href="route('dashboard')" class="flex items-center gap-3">
                                    <!-- App Logo (from App Settings) takes priority -->
                                    <img
                                        v-if="$page.props.appSettings && $page.props.appSettings.app_logo"
                                        :src="`/storage/${$page.props.appSettings.app_logo}`"
                                        alt="App Logo"
                                        class="block h-9 w-auto"
                                    />
                                    <!-- Fallback to Company Logo -->
                                    <img
                                        v-else-if="$page.props.companyInfo && $page.props.companyInfo.logo"
                                        :src="`/storage/${$page.props.companyInfo.logo}`"
                                        alt="Company Logo"
                                        class="block h-9 w-auto"
                                    />
                                    <!-- Final fallback to default ApplicationLogo -->
                                    <!-- <ApplicationLogo
                                        v-else
                                        class="block h-9 w-auto fill-current text-white"
                                    />
                                     -->
                                    <!-- App Name (from App Settings) takes priority over Company Name -->
                                    <span v-if="$page.props.appSettings && $page.props.appSettings.app_name" class="text-xl font-semibold text-white">
                                        {{ $page.props.appSettings.app_name }}
                                    </span>
                                    <span v-else-if="$page.props.companyInfo && $page.props.companyInfo.company_name" class="text-xl font-semibold text-white">
                                        {{ $page.props.companyInfo.company_name }}
                                    </span>
                                </Link>
                            </div>


                            <!-- Navigation Links -->
                            <div
                                class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
                            >
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                    class="text-white"
                                >
                                    Dashboard
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-secondary px-3 py-2 text-sm font-medium leading-4 text-white transition duration-150 ease-in-out hover:text-gray-300 focus:outline-none"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Profile
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
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
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
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
                    <div
                        class="border-t border-gray-200 pb-1 pt-4"
                    >
                        <div class="px-4">
                            <div
                                class="text-base font-medium text-gray-800"
                            >
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-gray-500">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                class="bg-black shadow"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>

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
