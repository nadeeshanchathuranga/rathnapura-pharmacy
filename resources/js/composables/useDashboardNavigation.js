import { router } from '@inertiajs/vue3';

/**
 * Dashboard Navigation Composable
 * 
 * Provides navigation functions to return to dashboard with the correct tab
 */

export function useDashboardNavigation() {
  /**
   * Navigate back to dashboard with a specific tab
   * @param {string} tabName - Name of the tab to open (products, stores, shops, reports, settings)
   */
  const goBackToDashboard = (tabName) => {
    localStorage.setItem('activeTab', tabName);
    sessionStorage.setItem('fromNavigation', 'true');
    router.visit(route('dashboard'));
  };

  // Predefined navigation functions for each tab
  const goToProductsTab = () => goBackToDashboard('products');
  const goToStoresTab = () => goBackToDashboard('stores');
  const goToShopsTab = () => goBackToDashboard('shops');
  const goToReportsTab = () => goBackToDashboard('reports');
  const goToSettingsTab = () => goBackToDashboard('settings');

  return {
    goBackToDashboard,
    goToProductsTab,
    goToStoresTab,
    goToShopsTab,
    goToReportsTab,
    goToSettingsTab
  };
}
