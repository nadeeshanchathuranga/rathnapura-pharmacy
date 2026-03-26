import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import AppLayout from './Layouts/AppLayout.vue';

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
