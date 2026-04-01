<script setup>
/**
 * Dashboard Component Script
 *
 * Main dashboard for POS system users
 * Uses AppLayout for consistent navigation
 */
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, Link, usePage, router } from "@inertiajs/vue3";
import { computed, ref, onMounted } from "vue";

const page = usePage();
const pageTitle = computed(() => {
  const appName = page.props.appSettings?.app_name || "POS";
  return appName;
});

// Set default tab based on user role
const getDefaultTab = () => {
  const userRole = page.props.auth.user.role;
  if ([0, 1, 3].includes(userRole)) {
    return "products"; // Products section for these roles
  }
  return "shops"; // Default to shops for other roles
};

// Track active tab
const activeTab = ref(getDefaultTab());

// Switch tabs and persist selection
const setActiveTab = (tab) => {
  activeTab.value = tab;
};

// Set default tab on mount
onMounted(() => {
  const savedTab = localStorage.getItem("activeTab");
  const fromNavigation = sessionStorage.getItem("fromNavigation");

  if (savedTab && fromNavigation === "true") {
    activeTab.value = savedTab;
    sessionStorage.removeItem("fromNavigation");
  } else {
    activeTab.value = getDefaultTab();
    localStorage.removeItem("activeTab");
  }
});
</script>

<template>
  <!-- Page Title for Browser Tab -->
  <Head :title="pageTitle" />

  <AppLayout>
    <div class="min-h-screen bg-gray-50 p-6">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-4xl font-bold text-blue-600 mb-2">POS System Dashboard</h1>
        <p class="text-gray-600">Manage your products, stores, and shops</p>
      </div>

      <!-- Tab Navigation -->
      <div class="mb-8 flex justify-center">
        <div class="inline-flex gap-2 bg-white rounded-lg p-2 border border-gray-200">

          <button
            v-if="[0, 1, 3].includes($page.props.auth.user.role)"
            @click="setActiveTab('products')"
            :class="[
              'flex items-center gap-2 px-5 py-2.5 rounded-md font-medium text-sm transition-all duration-200',
              activeTab === 'products'
                ? 'bg-blue-600 text-white'
                : 'text-gray-700 hover:bg-gray-50',
            ]"
          >
            <span class="text-lg">📦</span>
            <span>Products</span>
          </button>

          <button
            v-if="[0, 1, 3].includes($page.props.auth.user.role)"
            @click="setActiveTab('stores')"
            :class="[
              'flex items-center gap-2 px-5 py-2.5 rounded-md font-medium text-sm transition-all duration-200',
              activeTab === 'stores'
                ? 'bg-blue-600 text-white'
                : 'text-gray-700 hover:bg-gray-50',
            ]"
          >
            <span class="text-lg">🛒</span>
            <span>Stores</span>
          </button>

          <button
            @click="setActiveTab('shops')"
            :class="[
              'flex items-center gap-2 px-5 py-2.5 rounded-md font-medium text-sm transition-all duration-200',
              activeTab === 'shops'
                ? 'bg-blue-600 text-white'
                : 'text-gray-700 hover:bg-gray-50',
            ]"
          >
            <span class="text-lg">💰</span>
            <span>Shops</span>
          </button>

          <button
            v-if="[0, 1, 2, 3].includes($page.props.auth.user.role)"
            @click="setActiveTab('reports')"
            :class="[
              'flex items-center gap-2 px-5 py-2.5 rounded-md font-medium text-sm transition-all duration-200',
              activeTab === 'reports'
                ? 'bg-blue-600 text-white'
                : 'text-gray-700 hover:bg-gray-50',
            ]"
          >
            <span class="text-lg">📊</span>
            <span>Reports</span>
          </button>

          <!-- <button
            v-if="[0].includes($page.props.auth.user.role)"
            @click="setActiveTab('system')"
            :class="[
              'flex items-center gap-2 px-5 py-2.5 rounded-md font-medium text-sm transition-all duration-200',
              activeTab === 'system'
                ? 'bg-blue-600 text-white'
                : 'text-gray-700 hover:bg-gray-50',
            ]"
          >
            <span class="text-lg">⚙️</span>
            <span>System</span>
          </button> -->

          <button
            v-if="![2, 3].includes($page.props.auth.user.role)"
            @click="setActiveTab('settings')"
            :class="[
              'flex items-center gap-2 px-5 py-2.5 rounded-md font-medium text-sm transition-all duration-200',
              activeTab === 'settings'
                ? 'bg-blue-600 text-white'
                : 'text-gray-700 hover:bg-gray-50',
            ]"
          >
            <span class="text-lg">🔧</span>
            <span>Settings</span>
          </button>
        </div>
      </div>

      <!-- Products Section -->
      <div
        v-if="activeTab === 'products' && [0, 1, 3].includes($page.props.auth.user.role)"
        class="bg-white rounded-lg p-6 border border-gray-200"
      >
        <h3
          class="text-xl font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200 flex items-center gap-2"
        >
          <span>📦</span> Inventory Management
        </h3>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
          <Link
            v-if="[0, 1 ,3].includes($page.props.auth.user.role)"
            :href="route('products.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">📦</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Products</div>
            <div class="text-sm text-gray-600">Manage the products & information.</div>
          </Link>

          <Link
            v-if="[0, 1].includes($page.props.auth.user.role)"
            :href="route('brands.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">🏷️</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Brands</div>
            <div class="text-sm text-gray-600">Manage brands</div>
          </Link>

          <Link
            v-if="[0, 1, 3].includes($page.props.auth.user.role)"
            :href="route('categories.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">📂</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Categories</div>
            <div class="text-sm text-gray-600">Manage categories</div>
          </Link>

          <Link
            v-if="[0, 1, 3].includes($page.props.auth.user.role)"
            :href="route('types.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">🔹</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Types</div>
            <div class="text-sm text-gray-600">Manage types</div>
          </Link>

          <Link
            v-if="[0, 1].includes($page.props.auth.user.role)"
            :href="route('measurement-units.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">📏</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Units</div>
            <div class="text-sm text-gray-600">Measurement units</div>
          </Link>

          <Link
            v-if="[0, 1 ,3].includes($page.props.auth.user.role)"
            :href="route('suppliers.index')"
            class="group bg-white hover:bg-gray-50 p-6 rounded-xl border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">🏭</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Suppliers</div>
            <div class="text-sm text-gray-600">Manage suppliers</div>
          </Link>
        </div>
      </div>

      <!-- Stores Section -->
      <div
        v-if="activeTab === 'stores' && [0, 1, 3, 4].includes($page.props.auth.user.role)"
        class="bg-white rounded-lg p-6 border border-gray-200"
      >
        <h3
          class="text-xl font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200 flex items-center gap-2"
        >
          <span>🛍️</span> Stores
        </h3>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">


           <Link
            v-if="[0, 1, 3].includes($page.props.auth.user.role)"
            :href="route('store-inventory.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">📊</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">
              Inventory
            </div>
            <div class="text-sm text-gray-600">Track store quantity adjustments and changes</div>
          </Link>

          <Link
            v-if="[0, 1, 3].includes($page.props.auth.user.role)"
            :href="route('purchase-orders.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">🛒</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Purchase Orders</div>
            <div class="text-sm text-gray-600">Create and manage formal purchase orders to suppliers.</div>
          </Link>

          <Link
            v-if="[0, 1, 3].includes($page.props.auth.user.role)"
            :href="route('good-receive-notes.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">📦</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">
              Goods Received Notes
            </div>
            <div class="text-sm text-gray-600">
              Track the received goods from the purchase orders.
            </div>
          </Link>

          <Link
            v-if="[0, 1, 3].includes($page.props.auth.user.role)"
            :href="route('good-receive-note-returns.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">📦</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Goods Return Notes</div>
            <div class="text-sm text-gray-600">
              Track the return goods from the purchase orders.
            </div>
          </Link>

          <Link
            v-if="[0, 1, 3,4].includes($page.props.auth.user.role)"
            :href="route('product-release-notes.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">📝</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">
              Goods Transfer Release Notes
            </div>
            <div class="text-sm text-gray-600">
              Manage goods transfers from stores to shop.
            </div>
          </Link>

          <Link
            v-if="[0, 1].includes($page.props.auth.user.role)"
            :href="route('purchase-expenses.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">💸</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Supplier Payments</div>
            <div class="text-sm text-gray-600">Track the supplier payments</div>
          </Link>

        </div>
      </div>

      <!-- Shops Section -->
      <div
        v-if="activeTab === 'shops'"
        class="bg-white rounded-lg p-6 border border-gray-200"
      >
        <h3
          class="text-xl font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200 flex items-center gap-2"
        >
          <span>💰</span> Sales Management
        </h3>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
          <Link
            v-if="[0, 1].includes($page.props.auth.user.role)"
            :href="route('customers.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">👥</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Customers</div>
            <div class="text-sm text-gray-600">Manage customers</div>
          </Link>

          <Link
            v-if="[0, 1].includes($page.props.auth.user.role)"
            :href="route('discounts.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">🏷️</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Discounts</div>
            <div class="text-sm text-gray-600">Manage discounts</div>
          </Link>

          <Link
            v-if="[0, 1].includes($page.props.auth.user.role)"
            :href="route('taxes.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">📊</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Taxes</div>
            <div class="text-sm text-gray-600">Manage tax rates</div>
          </Link>

          <Link
            v-if="[0, 1, 2].includes($page.props.auth.user.role)"
            :href="route('sales.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">💳</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Sales</div>
            <div class="text-sm text-gray-600">Manage sales transactions</div>
          </Link>

          <Link
            v-if="[0, 1, 2].includes($page.props.auth.user.role)"
            :href="route('sales.all')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">📜</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Sales History</div>
            <div class="text-sm text-gray-600">View all sales records</div>
          </Link>

          <Link
            v-if="[0, 1, 2].includes($page.props.auth.user.role)"
            :href="route('quotations.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">📄</div>

            <div class="font-semibold text-lg text-gray-800 mb-1">Quotations</div>

            <div class="text-sm text-gray-600">Create and manage quotations</div>
          </Link>

          <Link
            v-if="[0, 1, 2].includes($page.props.auth.user.role)"
            :href="route('quotation.edit')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">✏️</div>

            <div class="font-semibold text-lg text-gray-800 mb-1">Edit Quotations</div>

            <div class="text-sm text-gray-600">View, update and manage quotations</div>
          </Link>

          <Link
            v-if="[0, 1, 2].includes($page.props.auth.user.role)"
            :href="route('shift-management.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">⏰</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Shifts</div>
            <div class="text-sm text-gray-600">Manage cashier start/end sessions</div>
          </Link>

          <Link
            v-if="[0, 1, 2].includes($page.props.auth.user.role)"
            :href="route('till-management.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">💵</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Till</div>
            <div class="text-sm text-gray-600">Record cash in/out with controls</div>
          </Link>

          <Link
            v-if="[0, 1].includes($page.props.auth.user.role)"
            :href="route('product-transfer-requests.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">📤</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">
              Products Transfer Request
            </div>
            <div class="text-sm text-gray-600">
              Manage the Products transfer request from shop.
            </div>
          </Link>

          <a
            v-if="[0, 1].includes($page.props.auth.user.role)"
            href="/stock-transfer-returns"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200 block"
          >
            <div class="text-4xl mb-3">🔄</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">
              Products Transfer Returns
            </div>
            <div class="text-sm text-gray-600">
              Manage the transfer from shop to store.
            </div>
          </a>

          <Link
            v-if="[0, 1].includes($page.props.auth.user.role)"
            :href="route('return.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">↩️</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Sales Return</div>
            <div class="text-sm text-gray-600">Manage sales returns</div>
          </Link>
        </div>
      </div>

      <!-- Report Management -->
      <div
        v-if="
          activeTab === 'reports' && [0, 1, 2, 3].includes($page.props.auth.user.role)
        "
        class="bg-white rounded-lg p-6 border border-gray-200"
      >
        <h3
          class="text-xl font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200 flex items-center gap-2"
        >
          <span> </span> Report Management
        </h3>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
          <Link
            v-if="[0, 1, 3].includes($page.props.auth.user.role)"
            :href="route('reports.low-stock-shop')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">🏪</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">
              Shop Low Stock Report
            </div>
            <div class="text-sm text-gray-600">Products low in shop</div>
          </Link>
          <Link
            v-if="[0, 1, 3].includes($page.props.auth.user.role)"
            :href="route('reports.low-stock-store')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">🏬</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">
              Store Low Stock Report
            </div>
            <div class="text-sm text-gray-600">Products low in store</div>
          </Link>

          <Link
            v-if="[0, 1, 2, 3].includes($page.props.auth.user.role)"
            :href="route('reports.stock')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">📈</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Stock Report</div>
            <div class="text-sm text-gray-600">Current inventory status</div>
          </Link>

          <Link
            v-if="[0].includes($page.props.auth.user.role)"
            :href="route('reports.expenses')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">💸</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">
              Supplier Payment Report
            </div>
            <div class="text-sm text-gray-600">Supplier payment details & summary</div>
          </Link>

          <Link
            v-if="[0, 2].includes($page.props.auth.user.role)"
            :href="route('reports.sales-income')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">💰</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">
              Order History Report
            </div>
            <div class="text-sm text-gray-600">Sales income & returns transactions</div>
          </Link>

          <Link
            v-if="[0, 2, 3].includes($page.props.auth.user.role)"
            :href="route('reports.unpaid-sales')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">⏳</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">
              Unpaid Sales Report
            </div>
            <div class="text-sm text-gray-600">Pending invoices and balances</div>
          </Link>
          <!-- <Link
            v-if="[0, 1,3].includes($page.props.auth.user.role)"
            :href="route('reports.product-release')"
            class="group bg-white hover:bg-gray-50 p-6 rounded-xl border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div
              class="text-4xl mb-3"
            >
              📦
            </div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Goods Transfer Report</div>
            <div class="text-sm text-gray-600">Release notes report</div>
          </Link>

          <Link
            v-if="[0, 1,3].includes($page.props.auth.user.role)"
            :href="route('reports.stock-transfer-return')"
            class="group bg-white hover:bg-gray-50 p-6 rounded-xl border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div
              class="text-4xl mb-3"
            >
              🔄
            </div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Goods Transfer Return Report</div>
            <div class="text-sm text-gray-600">Transfer return report</div>
          </Link>


          <Link
            v-if="[0, 1,3].includes($page.props.auth.user.role)"
            :href="route('reports.grn')"
            class="group bg-white hover:bg-gray-50 p-6 rounded-xl border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div
              class="text-4xl mb-3"
            >
              📥
            </div>
            <div class="font-semibold text-lg text-gray-800 mb-1">
              Goods Received Report
            </div>
            <div class="text-sm text-gray-600">All inbound receipts and totals</div>
          </Link>
          <Link
            v-if="[0, 1,3].includes($page.props.auth.user.role)"
            :href="route('reports.grn-returns')"
            class="group bg-white hover:bg-gray-50 p-6 rounded-xl border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div
              class="text-4xl mb-3"
            >
              ↩️
            </div>
            <div class="font-semibold text-lg text-gray-800 mb-1">
              Goods Return Report
            </div>
            <div class="text-sm text-gray-600">Returned receipts and quantities</div>
          </Link> -->
          <Link
            v-if="[0, 1, 3].includes($page.props.auth.user.role)"
            :href="route('reports.product-movements')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">🔀</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">
              Product Movement Report
            </div>
            <div class="text-sm text-gray-600">Track inbound/outbound stock flows</div>
          </Link>

          <Link
            v-if="[0, 3].includes($page.props.auth.user.role)"
            :href="route('reports.product-movement-sales-optimization')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">📈</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">
              Sales Optimization Report
            </div>
            <div class="text-sm text-gray-600">Product movement based sales insights</div>
          </Link>

          <Link
            v-if="[0].includes($page.props.auth.user.role)"
            :href="route('reports.activity-log')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">📝</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Activity Log</div>
            <div class="text-sm text-gray-600">User activity & audit trail</div>
          </Link>

          <Link
            v-if="[0].includes($page.props.auth.user.role)"
            :href="route('reports.sync')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">🔄</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Sync Report</div>
            <div class="text-sm text-gray-600">View sync activity logs</div>
          </Link>
        </div>
      </div>

      <!-- System Management
      <div
        v-if="activeTab === 'system' && [0, 1].includes($page.props.auth.user.role)"
        class="bg-white rounded-lg p-6 border border-gray-200"
      >
        <h3
          class="text-xl font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200 flex items-center gap-2"
        >
          <span>⚙️</span> System Management
        </h3>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
          <Link
            v-if="[0, 1].includes($page.props.auth.user.role)"
            :href="route('users.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">👤</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Users</div>
            <div class="text-sm text-gray-600">Manage system users</div>
          </Link>
        </div>
      </div> -->

      <!-- Settings -->
      <div
        v-if="activeTab === 'settings' && ![1, 2, 3].includes($page.props.auth.user.role)"
        class="bg-white rounded-lg p-6 border border-gray-200"
      >
        <h3
          class="text-xl font-semibold text-gray-800 mb-4 pb-3 border-b border-gray-200 flex items-center gap-2"
        >
          <span>🔧</span> Settings
        </h3>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
          <!-- <Link
            :href="route('settings.company')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">🏢</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">
              Company Information
            </div>
            <div class="text-sm text-gray-600">Company information & settings</div>
          </Link> -->

          <Link
            v-if="[0, 1].includes($page.props.auth.user.role)"
            :href="route('users.index')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">👤</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Users</div>
            <div class="text-sm text-gray-600">Manage system users</div>
          </Link>

          <Link
            :href="route('settings.app')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">⚙️</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">App Settings</div>
            <div class="text-sm text-gray-600">
              Application preferences & configuration
            </div>
          </Link>
          <!-- <Link
            :href="route('settings.smtp')"
            class="group bg-white hover:bg-gray-50 p-6 rounded-xl border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div
              class="text-4xl mb-3"
            >
              📧
            </div>
            <div class="font-semibold text-lg text-gray-800 mb-1">SMTP Settings</div>
            <div class="text-sm text-gray-600">Email server configuration</div>
          </Link>-->
          <Link
            :href="route('settings.sync')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">🔄</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Sync Setting</div>
            <div class="text-sm text-gray-600">Synchronization configuration</div>
          </Link>
          <!-- <Link
            :href="route('settings.bill')"
            class="group bg-white hover:bg-gray-50 p-6 rounded-xl border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div
              class="text-4xl mb-3"
            >
              🧾
            </div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Bill Setting</div>
            <div class="text-sm text-gray-600">Bill logo, company info, print size</div>
          </Link> -->
          <Link
            v-if="![1].includes($page.props.auth.user.role)"
            :href="route('backup.settings')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">🧾</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Database Backup</div>
            <div class="text-sm text-gray-600">Bill logo, company info, print size</div>
          </Link>
          <!-- <Link
            :href="route('settings.sync')"
            class="group bg-white hover:bg-gray-50 p-6 rounded-xl border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div
              class="text-4xl mb-3"
            >
              🔄
            </div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Sync Setting</div>
            <div class="text-sm text-gray-600">Configure sync options</div>
          </Link> -->
          <Link
            :href="route('settings.bill')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">🧾</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Bill Settings</div>
            <div class="text-sm text-gray-600">Configure bill options</div>
          </Link>
          <Link
            :href="route('import-export')"
            class="group bg-white hover:bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            <div class="text-4xl mb-3">🔄</div>
            <div class="font-semibold text-lg text-gray-800 mb-1">Import & Export</div>
            <div class="text-sm text-gray-600">Manage data import and export</div>
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Smooth transitions */
a {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}
</style>
