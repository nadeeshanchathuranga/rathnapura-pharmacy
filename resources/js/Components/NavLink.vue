<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
  href: {
    type: String,
    required: true,
  },
  active: {
    type: Boolean,
  },
});

const classes = computed(() =>
  props.active
    ? "inline-flex items-center px-5 py-2.5 rounded-full font-medium text-sm bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-lg shadow-blue-500/50 scale-105 focus:outline-none transition-all duration-300"
    : "inline-flex items-center px-5 py-2.5 rounded-full font-medium text-sm bg-white/60 backdrop-blur-sm text-gray-700 hover:bg-white/80 hover:shadow-md focus:outline-none transition-all duration-300"
);
</script>

<template>
  <Link :href="href" :class="classes">
    <slot />
  </Link>
</template>
