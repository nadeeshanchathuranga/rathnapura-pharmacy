import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import AppLayout from './Layouts/AppLayout.vue';

const ASSIGNED_SHORTCUT_KEYS = new Set(['Tab', 'Backspace', 'Enter', 'Escape', 'ArrowUp', 'ArrowDown']);
const FUNCTION_KEY_PATTERN = /^F([1-9]|1[0-2])$/;
const FUNCTION_KEYS = Array.from({ length: 12 }, (_, index) => `F${index + 1}`);
const SHORTCUT_KEY_ALIASES = {
    Esc: 'Escape',
    AudioVolumeMute: 'F1',
    AudioVolumeDown: 'F2',
    AudioVolumeUp: 'F3',
    VolumeDown: 'F2',
    VolumeUp: 'F3',
    BrightnessDown: 'F11',
    BrightnessUp: 'F12',
};

const selectNavigationState = {
    active: false,
    element: null,
};

const normalizeShortcutKey = (event) => {
    if (event.code && /^F\d{1,2}$/i.test(event.code)) {
        return event.code.toUpperCase();
    }

    if (event.code && SHORTCUT_KEY_ALIASES[event.code]) {
        return SHORTCUT_KEY_ALIASES[event.code];
    }

    const key = event.key;
    return SHORTCUT_KEY_ALIASES[key] || key;
};

const lockFunctionKeysIfSupported = async () => {
    if (!navigator?.keyboard?.lock) {
        return;
    }

    try {
        await navigator.keyboard.lock(FUNCTION_KEYS);
    } catch {
        // Ignore lock errors (unsupported browser/context/permission)
    }
};

const consumeEvent = (event) => {
    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation?.();
};

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

const isInHeader = (target) => {
    if (!(target instanceof HTMLElement)) {
        return false;
    }

    return target.closest('nav,[data-shortcut-exclude]') !== null;
};

const getOpenDialogElement = () => {
    const openDialogElements = Array.from(document.querySelectorAll('dialog[open]'));

    for (let index = openDialogElements.length - 1; index >= 0; index -= 1) {
        const dialogElement = openDialogElements[index];

        if (!(dialogElement instanceof HTMLDialogElement)) {
            continue;
        }

        if (dialogElement.getAttribute('data-modal-active') === 'false') {
            continue;
        }

        return dialogElement;
    }

    return null;
};

const hasOpenDialog = () => getOpenDialogElement() !== null;

const getHomeUrl = () => {
    try {
        if (typeof window.route === 'function') {
            return window.route('dashboard');
        }
    } catch {
        return '/dashboard';
    }

    return '/dashboard';
};

const getShortcutScopeRoot = () => document.getElementById('app-core-content') || document.body;

const parseShortcutKeys = (value) => {
    if (!value) {
        return [];
    }

    return value
        .split(',')
        .map((entry) => entry.trim())
        .filter(Boolean)
        .map((entry) => {
            if (/^F\d{1,2}$/i.test(entry)) {
                return entry.toUpperCase();
            }

            if (entry.toLowerCase() === 'esc') {
                return 'Escape';
            }

            return entry;
        });
};

const isVisible = (element) => {
    if (!(element instanceof HTMLElement)) {
        return false;
    }

    return element.offsetParent !== null;
};

const getDialogPrimaryElement = (dialogElement) => {
    if (!(dialogElement instanceof HTMLElement)) {
        return null;
    }

    const primaryElement = dialogElement.querySelector('[data-dialog-primary="true"]');

    if (!(primaryElement instanceof HTMLElement)) {
        return null;
    }

    if (!isVisible(primaryElement) || isDisabled(primaryElement)) {
        return null;
    }

    return primaryElement;
};

const getFocusableElements = (rootElement) => {
    if (!(rootElement instanceof HTMLElement)) {
        return [];
    }

    const selectors = [
        'a[href]',
        'button:not([disabled])',
        'input:not([type="hidden"]):not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
        '[contenteditable="true"]',
    ].join(', ');

    const focusableCandidates = Array.from(rootElement.querySelectorAll(selectors)).filter((element) => {
        if (!(element instanceof HTMLElement)) {
            return false;
        }

        if (element.closest('[data-tab-skip="true"]')) {
            return false;
        }

        if (!isVisible(element)) {
            return false;
        }

        return element.tabIndex >= 0;
    });

    return focusableCandidates
        .map((element, domIndex) => ({
            element,
            domIndex,
            tabIndex: element.tabIndex,
        }))
        .sort((firstItem, secondItem) => {
            const firstPositive = firstItem.tabIndex > 0;
            const secondPositive = secondItem.tabIndex > 0;

            if (firstPositive && secondPositive) {
                return firstItem.tabIndex - secondItem.tabIndex || firstItem.domIndex - secondItem.domIndex;
            }

            if (firstPositive !== secondPositive) {
                return firstPositive ? -1 : 1;
            }

            return firstItem.domIndex - secondItem.domIndex;
        })
        .map((item) => item.element);
};

const focusFirstElementIn = (rootElement) => {
    if (rootElement instanceof HTMLElement && rootElement.tagName.toLowerCase() === 'dialog') {
        const dialogPrimaryElement = getDialogPrimaryElement(rootElement);
        if (dialogPrimaryElement instanceof HTMLElement) {
            dialogPrimaryElement.focus();
            return true;
        }
    }

    const focusableElements = getFocusableElements(rootElement);
    const firstElement = focusableElements[0];

    if (firstElement instanceof HTMLElement) {
        firstElement.focus();
        return true;
    }

    if (rootElement instanceof HTMLElement) {
        rootElement.focus?.();
    }

    return false;
};

const trapTabWithinScope = (event) => {
    const dialogElement = getOpenDialogElement();
    const scopeRoot = dialogElement || getShortcutScopeRoot();

    if (!(scopeRoot instanceof HTMLElement)) {
        event.preventDefault();
        return true;
    }

    const focusableElements = getFocusableElements(scopeRoot);

    if (focusableElements.length === 0) {
        if (!dialogElement) {
            return false;
        }

        event.preventDefault();

        focusFirstElementIn(scopeRoot);

        return true;
    }

    const activeElement = document.activeElement;
    const currentIndex = focusableElements.indexOf(activeElement);
    const isShift = event.shiftKey;

    let nextIndex;
    if (currentIndex === -1) {
        nextIndex = isShift ? focusableElements.length - 1 : 0;
    } else {
        nextIndex = isShift
            ? (currentIndex - 1 + focusableElements.length) % focusableElements.length
            : (currentIndex + 1) % focusableElements.length;
    }

    const nextElement = focusableElements[nextIndex] || focusableElements[0];
    nextElement?.focus();
    event.preventDefault();
    return true;
};

const enforceOpenDialogFocusTrap = (targetElement) => {
    const dialogElement = getOpenDialogElement();

    if (!(dialogElement instanceof HTMLElement)) {
        return;
    }

    if (targetElement instanceof HTMLElement && dialogElement.contains(targetElement)) {
        return;
    }

    focusFirstElementIn(dialogElement);
};

const isDisabled = (element) => {
    if (element instanceof HTMLButtonElement || element instanceof HTMLInputElement || element instanceof HTMLSelectElement || element instanceof HTMLTextAreaElement) {
        return element.disabled;
    }

    return element.getAttribute('aria-disabled') === 'true';
};

const findShortcutElement = (key) => {
    const scopeRoot = getShortcutScopeRoot();
    const shortcutElements = scopeRoot.querySelectorAll('[data-shortcut]');

    for (const shortcutElement of shortcutElements) {
        const keys = parseShortcutKeys(shortcutElement.getAttribute('data-shortcut'));

        if (!keys.includes(key)) {
            continue;
        }

        if (!isVisible(shortcutElement) || isDisabled(shortcutElement)) {
            continue;
        }

        return shortcutElement;
    }

    return null;
};

const triggerShortcutElement = (shortcutElement) => {
    if (!(shortcutElement instanceof HTMLElement)) {
        return false;
    }

    shortcutElement.click();
    return true;
};

const closeTopDialog = () => {
    document.dispatchEvent(new CustomEvent('app:close-top-dialog'));
};

const isSelectableElement = (target) => target instanceof HTMLSelectElement && !target.disabled;

const setSelectNavigationVisualState = (selectElement, active) => {
    if (!(selectElement instanceof HTMLSelectElement)) {
        return;
    }

    if (active) {
        selectElement.setAttribute('data-select-navigation-active', 'true');
    } else {
        selectElement.removeAttribute('data-select-navigation-active');
    }
};

const collapseSelectFallbackMenu = (selectElement) => {
    if (!(selectElement instanceof HTMLSelectElement)) {
        return;
    }

    if (selectElement.dataset.keyboardExpanded !== 'true') {
        return;
    }

    const originalSize = Number(selectElement.dataset.keyboardOriginalSize || '0');
    selectElement.size = Number.isFinite(originalSize) ? originalSize : 0;
    delete selectElement.dataset.keyboardExpanded;
    delete selectElement.dataset.keyboardOriginalSize;
};

const clearSelectNavigationState = () => {
    if (selectNavigationState.element instanceof HTMLSelectElement) {
        setSelectNavigationVisualState(selectNavigationState.element, false);
        collapseSelectFallbackMenu(selectNavigationState.element);
    }

    selectNavigationState.active = false;
    selectNavigationState.element = null;
};

const hasUsableSelectNavigationState = () => {
    const selectElement = selectNavigationState.element;

    if (!selectNavigationState.active || !(selectElement instanceof HTMLSelectElement)) {
        return false;
    }

    if (!selectElement.isConnected || !document.contains(selectElement) || selectElement.disabled || !isVisible(selectElement)) {
        clearSelectNavigationState();
        return false;
    }

    return true;
};

const resetKeyboardTransientState = () => {
    clearSelectNavigationState();
};

const navigateToDashboardIfNeeded = () => {
    const homeUrl = getHomeUrl();
    const resolvedHomeUrl = new URL(homeUrl, window.location.origin);
    const currentPathWithSearch = window.location.pathname + window.location.search;
    const homePathWithSearch = resolvedHomeUrl.pathname + resolvedHomeUrl.search;

    if (currentPathWithSearch !== homePathWithSearch) {
        window.location.assign(homeUrl);
    }
};

const navigateToPosIfNeeded = () => {
    let posUrl = '/sales';

    try {
        if (typeof window.route === 'function') {
            posUrl = window.route('sales.index');
        }
    } catch {
        posUrl = '/sales';
    }

    const resolvedPosUrl = new URL(posUrl, window.location.origin);
    const currentPathWithSearch = window.location.pathname + window.location.search;
    const posPathWithSearch = resolvedPosUrl.pathname + resolvedPosUrl.search;

    if (currentPathWithSearch !== posPathWithSearch) {
        window.location.assign(posUrl);
    }
};

const handleGlobalEscapeNavigation = (event) => {
    const shortcutKey = normalizeShortcutKey(event);

    if (shortcutKey !== 'Escape') {
        return;
    }

    if (event.ctrlKey || event.metaKey || event.altKey) {
        return;
    }

    consumeEvent(event);
    resetKeyboardTransientState();
    navigateToDashboardIfNeeded();
};

const handleGlobalPosNavigation = (event) => {
    const shortcutKey = normalizeShortcutKey(event);

    if (shortcutKey !== 'F5') {
        return;
    }

    if (event.ctrlKey || event.metaKey || event.altKey) {
        return;
    }

    const pageShortcutElement = findShortcutElement('F5');

    if (pageShortcutElement) {
        return;
    }

    consumeEvent(event);
    resetKeyboardTransientState();
    navigateToPosIfNeeded();
};

const activateSelectNavigationState = (selectElement) => {
    if (!(selectElement instanceof HTMLSelectElement)) {
        return;
    }

    if (selectNavigationState.element && selectNavigationState.element !== selectElement) {
        clearSelectNavigationState();
    }

    selectNavigationState.active = true;
    selectNavigationState.element = selectElement;
    setSelectNavigationVisualState(selectElement, true);
};

const openSelectOptions = (selectElement) => {
    if (!(selectElement instanceof HTMLSelectElement)) {
        return;
    }

    let openedByPicker = false;
    const pickerOpener = selectElement.showPicker;

    if (typeof pickerOpener === 'function') {
        try {
            pickerOpener.call(selectElement);
            openedByPicker = true;
        } catch {
            openedByPicker = false;
        }
    }

    if (openedByPicker || selectElement.multiple || selectElement.options.length <= 1) {
        return;
    }

    selectElement.dataset.keyboardExpanded = 'true';
    selectElement.dataset.keyboardOriginalSize = String(selectElement.size || 0);
    selectElement.size = Math.min(8, selectElement.options.length);
};

const moveSelectOption = (selectElement, direction) => {
    if (!(selectElement instanceof HTMLSelectElement)) {
        return;
    }

    const options = Array.from(selectElement.options);

    if (options.length === 0) {
        return;
    }

    let nextIndex = selectElement.selectedIndex;

    for (let counter = 0; counter < options.length; counter += 1) {
        nextIndex = (nextIndex + direction + options.length) % options.length;

        if (!options[nextIndex].disabled) {
            selectElement.selectedIndex = nextIndex;
            break;
        }
    }
};

const confirmSelectOption = (selectElement) => {
    if (!(selectElement instanceof HTMLSelectElement)) {
        return;
    }

    selectElement.dispatchEvent(new Event('change', { bubbles: true }));
    collapseSelectFallbackMenu(selectElement);
};

const getDialogFocusedSecondaryAction = (dialogElement) => {
    if (!(dialogElement instanceof HTMLElement)) {
        return null;
    }

    const activeElement = document.activeElement;

    if (!(activeElement instanceof HTMLElement) || !dialogElement.contains(activeElement)) {
        return null;
    }

    if (activeElement.getAttribute('data-dialog-secondary') !== 'true') {
        return null;
    }

    if (!isVisible(activeElement) || isDisabled(activeElement)) {
        return null;
    }

    return activeElement;
};

const findDialogEnterAction = () => {
    const dialogElement = getOpenDialogElement();

    if (!dialogElement) {
        return null;
    }

    const primaryElement = getDialogPrimaryElement(dialogElement);

    if (primaryElement instanceof HTMLElement) {
        return primaryElement;
    }

    const shortcutEnterElement = Array.from(dialogElement.querySelectorAll('[data-shortcut]')).find((shortcutElement) => {
        if (!(shortcutElement instanceof HTMLElement) || !isVisible(shortcutElement) || isDisabled(shortcutElement)) {
            return false;
        }

        const keys = parseShortcutKeys(shortcutElement.getAttribute('data-shortcut'));
        return keys.includes('Enter');
    });

    if (shortcutEnterElement instanceof HTMLElement) {
        return shortcutEnterElement;
    }

    const submitButton = dialogElement.querySelector('button[type="submit"]:not([disabled])');

    if (submitButton instanceof HTMLElement && isVisible(submitButton)) {
        return submitButton;
    }

    const namedDoneButton = Array.from(dialogElement.querySelectorAll('button:not([disabled])')).find((buttonElement) => {
        if (!(buttonElement instanceof HTMLElement) || !isVisible(buttonElement)) {
            return false;
        }

        const label = buttonElement.textContent?.trim().toLowerCase() || '';
        return label === 'done' || label.includes(' done') || label.startsWith('done ');
    });

    if (namedDoneButton instanceof HTMLElement) {
        return namedDoneButton;
    }

    return null;
};

if (!window.__globalShortcutManagerInitialized) {
    void lockFunctionKeysIfSupported();

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            void lockFunctionKeysIfSupported();
            resetKeyboardTransientState();
        }
    });

    window.addEventListener('focus', () => {
        void lockFunctionKeysIfSupported();
        resetKeyboardTransientState();
    });

    window.addEventListener('pageshow', resetKeyboardTransientState);
    document.addEventListener('inertia:start', resetKeyboardTransientState);
    document.addEventListener('inertia:finish', resetKeyboardTransientState);
    document.addEventListener('inertia:navigate', resetKeyboardTransientState);
    window.addEventListener('keydown', handleGlobalEscapeNavigation, true);
    window.addEventListener('keydown', handleGlobalPosNavigation, true);

    window.addEventListener('keydown', (event) => {
        const shortcutKey = normalizeShortcutKey(event);
        const isFunctionKey = FUNCTION_KEY_PATTERN.test(shortcutKey);

        if (!isFunctionKey && !ASSIGNED_SHORTCUT_KEYS.has(shortcutKey)) {
            return;
        }

        if (event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        if (isFunctionKey) {
            consumeEvent(event);
        }

        const eventTarget = event.target instanceof HTMLElement ? event.target : document.activeElement;

        if (shortcutKey === 'Tab') {
            if (hasUsableSelectNavigationState()) {
                consumeEvent(event);

                const direction = event.shiftKey ? -1 : 1;
                moveSelectOption(selectNavigationState.element, direction);
                return;
            }

            trapTabWithinScope(event);
            return;
        }

        if (isInHeader(eventTarget)) {
            return;
        }

        if (shortcutKey === 'Backspace') {
            if (event.repeat || isTypingContext(eventTarget)) {
                return;
            }

            consumeEvent(event);
            clearSelectNavigationState();

            if (hasOpenDialog()) {
                closeTopDialog();
                return;
            }

            if (window.history.length > 1) {
                window.history.back();
            }

            return;
        }

        if (shortcutKey === 'Enter' && hasUsableSelectNavigationState()) {
            consumeEvent(event);
            confirmSelectOption(selectNavigationState.element);
            clearSelectNavigationState();
            return;
        }

        if (shortcutKey === 'Enter' && isSelectableElement(eventTarget)) {
            consumeEvent(event);

            activateSelectNavigationState(eventTarget);
            openSelectOptions(eventTarget);

            return;
        }

        if ((shortcutKey === 'ArrowDown' || shortcutKey === 'ArrowUp') && hasUsableSelectNavigationState()) {
            consumeEvent(event);
            moveSelectOption(selectNavigationState.element, shortcutKey === 'ArrowDown' ? 1 : -1);
            return;
        }

        if (shortcutKey === 'Enter') {
            if (isTypingContext(eventTarget)) {
                return;
            }

            if (hasOpenDialog()) {
                const openDialogElement = getOpenDialogElement();
                const focusedSecondaryAction = getDialogFocusedSecondaryAction(openDialogElement);
                const dialogEnterAction = focusedSecondaryAction || findDialogEnterAction();
                if (dialogEnterAction) {
                    consumeEvent(event);
                    triggerShortcutElement(dialogEnterAction);
                }
                return;
            }
        }

        const shortcutElement = findShortcutElement(shortcutKey);

        if (shortcutElement) {
            consumeEvent(event);
            triggerShortcutElement(shortcutElement);
            if (shortcutKey === 'Enter') {
                clearSelectNavigationState();
            }
            return;
        }

    }, true);

    document.addEventListener('focusin', (event) => {
        const focusedElement = event.target;

        enforceOpenDialogFocusTrap(focusedElement);

        if (!isSelectableElement(focusedElement)) {
            clearSelectNavigationState();
        }
    });

    window.__globalShortcutManagerInitialized = true;
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
