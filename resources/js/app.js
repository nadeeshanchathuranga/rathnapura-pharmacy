import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import AppLayout from './Layouts/AppLayout.vue';

const isTypingContext = (target) => {
    if (!(target instanceof HTMLElement)) {
        return false;
    }

    const editableContainer = target.closest('input, textarea, select, [contenteditable="true"]');

    if (!editableContainer) {
        return false;
    }

    if (editableContainer instanceof HTMLInputElement || editableContainer instanceof HTMLTextAreaElement) {
        return !editableContainer.readOnly && !editableContainer.disabled;
    }

    if (editableContainer instanceof HTMLSelectElement) {
        return !editableContainer.disabled;
    }

    return editableContainer.isContentEditable;
};

const hasOpenDialog = () => {
    return document.querySelector('dialog[open]') !== null;
};

const closeTopDialog = () => {
    document.dispatchEvent(
        new KeyboardEvent('keydown', {
            key: 'Escape',
            bubbles: true,
        }),
    );
};

if (!window.__backspaceNavigationInitialized) {
    window.addEventListener('keydown', (event) => {
        if (event.key !== 'Backspace') {
            return;
        }

        if (event.repeat) {
            return;
        }

        if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) {
            return;
        }

        if (isTypingContext(event.target)) {
            return;
        }

        if (hasOpenDialog()) {
            event.preventDefault();
            closeTopDialog();
            return;
        }

        if (window.history.length > 1) {
            event.preventDefault();
            window.history.back();
        }
    });

    window.__backspaceNavigationInitialized = true;
}

createInertiaApp({
    title: (title) => {
        // Get app name from the initial page props
        const appSettings = window.appSettings || {};
        const appName = appSettings.app_name || 'POS';
        return appName;
    },
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        // Store app settings globally for title callback
        window.appSettings = props.initialPage.props.appSettings;
        
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component('AppLayout', AppLayout)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
