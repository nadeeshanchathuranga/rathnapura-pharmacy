<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="space-y-6"
        >
            <div>
                <label class="block mb-2 text-sm font-semibold text-gray-700">
                    Name <span class="text-red-500">*</span>
                </label>
                <input
                    id="name"
                    type="text"
                    v-model="form.name"
                    class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-[5px] text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Enter your name"
                />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                    {{ form.errors.name }}
                </p>
            </div>

            <div>
                <label class="block mb-2 text-sm font-semibold text-gray-700">
                    Email <span class="text-red-500">*</span>
                </label>
                <input
                    id="email"
                    type="email"
                    v-model="form.email"
                    class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-[5px] text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    required
                    autocomplete="username"
                    placeholder="Enter your email"
                />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">
                    {{ form.errors.email }}
                </p>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="p-4 bg-yellow-50 border-2 border-yellow-400 rounded-[5px]">
                <p class="text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="text-blue-600 underline hover:text-blue-700 font-medium"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-8 py-3 rounded-[5px] font-medium text-sm bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                >
                    Save Changes
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-green-600 font-medium"
                    >
                        ✓ Saved successfully
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
