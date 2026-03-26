<template>
  <button
    @click="createBackup"
    :disabled="isCreating"
    :class="buttonClasses"
    class="inline-flex items-center gap-2 px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-150"
  >
    <svg v-if="isCreating" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
    </svg>
    
    <slot>
      {{ isCreating ? (loadingText || 'Creating...') : (text || 'Export Backup') }}
    </slot>
  </button>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import { logActivity } from '@/composables/useActivityLog';

// Props
const props = defineProps({
  variant: {
    type: String,
    default: 'primary', // primary, secondary, success, danger
    validator: (value) => ['primary', 'secondary', 'success', 'danger'].includes(value)
  },
  text: {
    type: String,
    default: 'Export Backup'
  },
  loadingText: {
    type: String,
    default: 'Creating...'
  },
  showNotification: {
    type: Boolean,
    default: true
  }
});

// Emits
const emit = defineEmits(['success', 'error', 'start']);

// Reactive data
const isCreating = ref(false);

// Computed classes based on variant
const buttonClasses = computed(() => {
  const baseClasses = 'focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-25';
  
  switch (props.variant) {
    case 'primary':
      return `${baseClasses} bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 text-white disabled:hover:bg-blue-600`;
    case 'secondary':
      return `${baseClasses} bg-gray-600 hover:bg-gray-700 focus:ring-gray-500 text-white disabled:hover:bg-gray-600`;
    case 'success':
      return `${baseClasses} bg-green-600 hover:bg-green-700 focus:ring-green-500 text-white disabled:hover:bg-green-600`;
    case 'danger':
      return `${baseClasses} bg-red-600 hover:bg-red-700 focus:ring-red-500 text-white disabled:hover:bg-red-600`;
    default:
      return `${baseClasses} bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 text-white disabled:hover:bg-blue-600`;
  }
});

/**
 * Create and download database backup
 */
const createBackup = async () => {
  isCreating.value = true;
  emit('start');
  
  try {
    const response = await axios.post('/backup/create', {}, {
      responseType: 'blob'
    });
    
    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    
    // Get filename from response headers or create default
    const contentDisposition = response.headers['content-disposition'];
    let filename = 'database_backup.sql';
    
    if (contentDisposition) {
      const match = contentDisposition.match(/filename="?(.+)"?/);
      if (match) {
        filename = match[1];
      }
    }
    
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

    // Log activity to activity_log table
    await logActivity('download', 'database backup', filename);

    if (props.showNotification) {
      // You can integrate with your notification system here
    }
    
    emit('success', { filename });
    
  } catch (error) {
    console.error('Backup failed:', error);
    
    if (props.showNotification) {
      // You can integrate with your notification system here
      console.error('Failed to create backup:', error.response?.data?.error || error.message);
    }
    
    emit('error', error.response?.data?.error || 'Failed to create backup. Please try again.');
  } finally {
    isCreating.value = false;
  }
};

// Expose methods for parent component access
defineExpose({
  createBackup,
  isCreating
});
</script>