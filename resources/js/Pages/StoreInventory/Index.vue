<template>
  <AppLayout>
    <!-- Main Container -->
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 p-6">
      <!-- Header Section with Navigation and Actions -->
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
          <!-- Back to Dashboard Button -->
          <button
            @click="$inertia.visit(route('dashboard'))"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-4xl font-bold text-gray-800">
            {{ inventoryViewType === 'store' ? 'Store' : 'Shop' }} Inventory
          </h1>
        </div>
        <div class="flex items-center gap-4">
          <!-- Inventory View Type Selector -->
          <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700">View:</label>
            <select
              v-model="inventoryViewType"
              class="px-4 py-2.5 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white font-medium"
            >
              <option value="shop">Shop Inventory</option>
              <option value="store">Store Inventory</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Current Inventory Levels Table -->
      <div class="bg-white rounded-2xl border border-gray-200 p-6 mb-6">
        <div class="mb-4">
          <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
            <span v-if="inventoryViewType === 'shop'">🏪</span>
            <span v-else>🏬</span>
            Current {{ inventoryViewType === 'shop' ? 'Shop' : 'Store' }} Inventory Levels
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            Complete list of all products and their current quantities in 
            <strong>{{ inventoryViewType === 'shop' ? 'Shop' : 'Store' }}</strong>
          </p>
        </div>

        <!-- Combined Product Filter (Search + Dropdown) -->
        <div class="mb-4 relative" ref="searchContainer">
          <label class="block text-sm font-medium text-gray-700 mb-2">Search or Select Product:</label>
          <div class="relative">
            <div class="relative flex items-center">
              <input
                type="text"
                ref="searchInput"
                v-model="productFilterInput"
                @focus="showProductDropdown = true"
                @keyup.esc="clearProductFilter"
                placeholder="🔍 Type to search or click to select product..."
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white"
              />
              
              <!-- Clear Button -->
              <button
                v-if="productFilterInput"
                @click="clearProductFilter"
                class="absolute right-3 text-gray-400 hover:text-gray-600 transition-colors"
                type="button"
                title="Clear search"
              >
                ✕
              </button>
            </div>
            
            <!-- Dropdown List -->
            <div
              v-if="showProductDropdown && filteredProductList.length > 0"
              class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-y-auto z-50"
            >
              <div
                v-for="product in filteredProductList"
                :key="product.id"
                @mousedown="selectProduct(product)"
                class="px-4 py-2.5 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors"
              >
                <div class="font-semibold text-gray-900">{{ product.name }}</div>
                <div class="text-xs text-gray-600">Barcode: {{ product.barcode || 'N/A' }}</div>
              </div>
            </div>

            <!-- No Results Message -->
            <div
              v-if="showProductDropdown && productFilterInput && filteredProductList.length === 0"
              class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-4 text-center text-gray-600 z-50"
            >
              No products found
            </div>
          </div>
        </div>

        <!-- Inventory Levels Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b-2 border-blue-600">
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">#</th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Product Name</th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Barcode</th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Category</th>
                <th class="px-4 py-3 text-blue-600 font-semibold text-sm text-right">
                  {{ inventoryViewType === 'shop' ? 'Shop Quantity' : 'Store Quantity' }}
                </th>
<th
      v-if="inventoryViewType === 'shop'"
      class="px-4 py-3 text-blue-600 font-semibold text-sm"
    >
      Unit
    </th>                <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(product, index) in filteredInventoryProducts"
                :key="product.id"
                class="border-b border-gray-200 hover:bg-blue-50/50 transition-colors duration-200"
              >
                <!-- Index -->
                <td class="px-4 py-4">
                  <span
                    class="inline-flex items-center justify-center w-8 h-8 rounded-[10px] bg-blue-100 text-blue-700 font-bold text-sm"
                  >
                    {{ index + 1 }}
                  </span>
                </td>

                <!-- Product Name -->
                <td class="px-4 py-4">
                  <div class="font-semibold text-gray-900">{{ product.name }}</div>
                </td>

                <!-- Barcode -->
                <td class="px-4 py-4">
                  <div class="text-sm text-gray-600">
                    {{ product.barcode || '-' }}
                  </div>
                </td>

                <!-- Category -->
                <td class="px-4 py-4">
                  <div class="text-sm text-gray-600">
                    {{ product.category?.name || '-' }}
                  </div>
                </td>

                <!-- Quantity -->
                <td class="px-4 py-4 text-right">
                  <div  v-if="inventoryViewType === 'shop'"
                  class="font-bold text-lg" :class="getQuantityColor(getProductQuantity(product))">
                    {{ Number(getProductQuantity(product)).toFixed() }}
                  </div>
                  <div v-else class="space-y-1 text-right">
                      <div>
                        <span class="font-semibold">{{ getUnitName(product).purchase }}: </span>
                        <span :class="getQuantityColor(getProductQuantity(product).purchase)">
                          {{ Number(getProductQuantity(product).purchase).toFixed() }}
                        </span>
                      </div>
                      <div>
                        <span class="font-semibold">{{ getUnitName(product).transfer }}:</span>
                        <span :class="getQuantityColor(getProductQuantity(product).transfer)">
                          {{ Number(getProductQuantity(product).transfer).toFixed() }}
                        </span>
                      </div>
                      <div>
                        <span class="font-semibold">{{ getUnitName(product).sales }}:</span>
                        <span :class="getQuantityColor(getProductQuantity(product).purchase)">
                          {{ Number(getProductQuantity(product).sales).toFixed() }}
                        </span>
                      </div>
                  </div>
                </td>

                <!-- Unit -->
                 <td
      v-if="inventoryViewType === 'shop'"
      class="px-4 py-4"
    >
                  <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    {{ getUnitName(product) }}
                  </span>
                </td>

                <!-- Status Badge -->
                <td class="px-4 py-4">
                  <span
                    class="px-3 py-1 rounded-full text-xs font-medium"
                    :class="getInventoryStatusClass(product)"
                  >
                    {{ getInventoryStatus(product) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- No Products Message -->
          <div v-if="filteredInventoryProducts.length === 0" class="text-center py-12">
            <div class="text-4xl mb-4">📭</div>
            <p class="text-gray-600 text-lg">No products found</p>
          </div>
        </div>
      </div>
    </div>

    <!-- View Record Modal -->
    <ViewRecordModal
      :show="showViewModal"
      :record="selectedRecord"
      @close="closeViewModal"
    />
  </AppLayout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import ViewRecordModal from "./Components/ViewRecordModal.vue";

export default {
  components: {
    AppLayout,
    ViewRecordModal,
  },
  props: {
    inventoryRecords: Object,
    products: Array,
    filters: Object,
    inventoryType: String,
  },
  data() {
    return {
      showViewModal: false,
      selectedRecord: null,
      inventoryViewType: 'shop', // 'shop' or 'store'
      productFilterInput: '', // Combined search/select input
      showProductDropdown: false, // Show/hide dropdown
      selectedProductId: '', // Selected product ID for filtering
      closeDropdownTimeout: null, // Timeout for closing dropdown
    };
  },
  created() {
    if (!this.filters.inventory_type) {
      this.filters.inventory_type = 'shop';
    }
  },
  mounted() {
    // Add click outside listener
    document.addEventListener('click', this.handleClickOutside);
    // Add escape key listener
    document.addEventListener('keydown', this.handleEscapeKey);
  },
  beforeUnmount() {
    // Cleanup event listeners
    document.removeEventListener('click', this.handleClickOutside);
    document.removeEventListener('keydown', this.handleEscapeKey);
    if (this.closeDropdownTimeout) {
      clearTimeout(this.closeDropdownTimeout);
    }
  },
  computed: {
    filteredProductList() {
      return this.products
        .filter(product => {
          if (!this.productFilterInput.trim()) {
            return true;
          }
          const searchTerm = this.productFilterInput.toLowerCase();
          return (
            product.name.toLowerCase().includes(searchTerm) ||
            (product.barcode && product.barcode.toLowerCase().includes(searchTerm))
          );
        })
        .sort((a, b) => a.name.localeCompare(b.name));
    },
    filteredInventoryProducts() {
      return this.products
        .filter(product => {
          // Filter by selected product ID (if any)
          if (this.selectedProductId) {
            if (product.id != this.selectedProductId) {
              return false;
            }
          }
          return true;
        })
        .sort((a, b) => a.name.localeCompare(b.name));
    },
  },
  methods: {
    handleClickOutside(event) {
      const searchContainer = this.$refs.searchContainer;
      const searchInput = this.$refs.searchInput;
      
      // Check if click is outside the search container
      if (searchContainer && !searchContainer.contains(event.target)) {
        this.showProductDropdown = false;
      }
      
      // If clicking on the input, ensure dropdown stays open
      if (searchInput && searchInput.contains(event.target)) {
        this.showProductDropdown = true;
      }
    },
    
    handleEscapeKey(event) {
      if (event.key === 'Escape') {
        if (this.showProductDropdown) {
          this.showProductDropdown = false;
          if (this.$refs.searchInput) {
            this.$refs.searchInput.blur();
          }
        } else if (this.productFilterInput) {
          this.clearProductFilter();
        }
      }
    },
    
    selectProduct(product) {
      this.selectedProductId = product.id;
      this.productFilterInput = product.name;
      
      // Close dropdown after selection
      this.showProductDropdown = false;
      
      // Focus back on input
      if (this.$refs.searchInput) {
        this.$refs.searchInput.focus();
      }
    },
    
    clearProductFilter() {
      this.productFilterInput = '';
      this.selectedProductId = '';
      this.showProductDropdown = false;
      
      // Focus on input after clearing
      if (this.$refs.searchInput) {
        this.$refs.searchInput.focus();
      }
    },
    getRawTransferQuantity(product) {
  const purchaseQty = Number(product.store_quantity_in_purchase_unit) || 0;
  const totalTransfer = Number(product.store_quantity_in_transfer_unit) || 0;
  const rate = Number(product.purchase_to_transfer_rate) || 1;

  return totalTransfer - (purchaseQty * rate);
},

getProductQuantity(product) {
  const toNumber = v => Number(v) || 0;

  if (this.inventoryViewType === 'shop') {
    return toNumber(product.shop_quantity_in_sales_unit);
  }

  const purchase = toNumber(product.store_quantity_in_purchase_unit);
  const totalTransfer = toNumber(product.store_quantity_in_transfer_unit);
  const rate = toNumber(product.purchase_to_transfer_rate);

  const transfer = totalTransfer - (purchase * rate); // raw transfer qty
  const sales = toNumber(product.store_quantity_in_sale_unit);

  return {
    purchase,
    transfer,
    sales,
  };
},

    getUnitName(product) {
      if (this.inventoryViewType === 'shop') {
        return product.sales_unit?.symbol || 'N/A';
      } else {
        return {
          purchase: product.purchase_unit?.symbol || 'N/A',
          transfer: product.transfer_unit?.symbol || 'N/A',
          sales: product.sales_unit?.symbol || 'N/A',
        };
      }
    },
    
    getQuantityColor(quantity) {
      const qty = Number(quantity);
      if (qty === 0) return 'text-red-600';
      if (qty < 10) return 'text-orange-600';
      return 'text-green-600';
    },
    
    getInventoryStatus(product) {
      const quantity = this.getProductQuantity(product);
      const lowStockMargin = product.shop_low_stock_margin || product.shop_low_stock || 5;
      
      if (quantity === 0) return 'Out of Stock';
      if (quantity <= lowStockMargin) return 'Low Stock';
      return 'In Stock';
    },
    
    getInventoryStatusClass(product) {
      const quantity = this.getProductQuantity(product);
      const lowStockMargin = product.shop_low_stock_margin || product.shop_low_stock || 5;
      
      if (quantity === 0) {
        return 'bg-red-100 text-red-800';
      }
      if (quantity <= lowStockMargin) {
        return 'bg-orange-100 text-orange-800';
      }
      return 'bg-green-100 text-green-800';
    },
    
    viewRecord(record) {
      this.selectedRecord = record;
      this.showViewModal = true;
    },
    
    closeViewModal() {
      this.showViewModal = false;
      this.selectedRecord = null;
    },
    
    deleteRecord(id) {
      if (confirm("Are you sure you want to delete this inventory record? This will reverse the inventory change.")) {
        this.$inertia.delete(route("store-inventory.destroy", id));
      }
    },
    
    applyFilters() {
      this.$inertia.get(route("store-inventory.index"), this.filters, {
        preserveState: true,
        preserveScroll: true,
      });
    },
    
    formatDate(date) {
      return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
      });
    },
    
    formatTransactionType(type) {
      const types = {
        adjustment: "Adjustment",
        physical_count: "Physical Count",
        damage: "Damage",
        expired: "Expired",
        return: "Return",
        transfer_in: "Transfer In",
        transfer_out: "Transfer Out",
      };
      return types[type] || type;
    },
    
    getTransactionTypeBadgeClass(type) {
      const classes = {
        adjustment: "bg-blue-100 text-blue-800",
        physical_count: "bg-purple-100 text-purple-800",
        damage: "bg-red-100 text-red-800",
        expired: "bg-orange-100 text-orange-800",
        return: "bg-green-100 text-green-800",
        transfer_in: "bg-emerald-100 text-emerald-800",
        transfer_out: "bg-yellow-100 text-yellow-800",
      };
      return classes[type] || "bg-gray-100 text-gray-800";
    },
  },
};
</script>