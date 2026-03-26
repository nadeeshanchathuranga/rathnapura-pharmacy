<template>
  <Modal :show="open" @close="closeModal" max-width="6xl">
    <div class="p-6 bg-gray-50">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-blue-600">Duplicate Product</h2>
        <button
          type="button"
          @click="closeModal"
          class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-200 rounded-full transition-all duration-200"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-6 h-6"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <form @submit.prevent="submit">
        <!-- Basic Information Section -->
        <div class="mb-6 bg-white rounded-xl p-4 border border-gray-200">
          <h3 class="mb-4 text-lg font-semibold text-blue-600">Basic Information</h3>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <!-- Product Name -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Product Name <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.name"
                type="text"
                class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                placeholder="Enter product name"
                required
              />
              <span v-if="form.errors.name" class="text-sm text-red-500">{{
                form.errors.name
              }}</span>
            </div>

            <!-- Barcode -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">Barcode</label>
              <input
                v-model="form.barcode"
                type="text"
                class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                placeholder="Enter or scan barcode"
              />
              <span v-if="form.errors.barcode" class="text-sm text-red-500">{{
                form.errors.barcode
              }}</span>
            </div>

            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">Brand</label>
              <div class="flex gap-2">
                <select
                  v-model="form.brand_id"
                  class="flex-1 min-w-0 px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                  title="Select Brand"
                >
                  <option value="">Select Brand</option>
                  <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                    {{ brand.name }}
                  </option>
                </select>

                <button
                  type="button"
                  @click="openBrandModal"
                  class="px-3 py-2.5 text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition duration-200 flex-shrink-0"
                  title="Add New Brand"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-5 h-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 4v16m8-8H4"
                    />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Category -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">Category</label>

              <div class="flex gap-2">
                <select
                  v-model="form.category_id"
                  class="flex-1 min-w-0 px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                  title="Select Category"
                >
                  <option value="">Select Category</option>
                  <option
                    v-for="category in categories"
                    :key="category.id"
                    :value="category.id"
                  >
                    {{
                      category.hierarchy_string
                        ? category.hierarchy_string + " → " + category.name
                        : category.name
                    }}
                  </option>
                </select>
                <button
                  type="button"
                  @click="openCategoryModal"
                  class="px-3 py-2.5 text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition duration-200 flex-shrink-0"
                  title="Add New Category"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-5 h-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 4v16m8-8H4"
                    />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Type -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">Type</label>
              <div class="flex gap-2">
                <select
                  v-model="form.type_id"
                  class="flex-1 min-w-0 px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                  title="Select Type"
                >
                  <option value="">Select Type</option>
                  <option v-for="type in types" :key="type.id" :value="type.id">
                    {{ type.name }}
                  </option>
                </select>

                <button
                  type="button"
                  @click="openTypeModal"
                  class="px-3 py-2.5 text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition duration-200 flex-shrink-0"
                  title="Add New Type"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-5 h-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 4v16m8-8H4"
                    />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Status -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">Status</label>
              <select
                v-model="form.status"
                class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
              >
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Pricing Section -->
        <div class="mb-6 bg-white rounded-xl p-4 border border-gray-200">
          <h3 class="mb-4 text-lg font-semibold text-blue-600">
            Pricing Information ({{ page.props.currency || "" }})
          </h3>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <!-- Purchase Price -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Purchase Price</label
              >
              <input
                v-model="form.purchase_price"
                type="number"
                step="0.01"
                class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                placeholder="0.00"
              />
            </div>

            <!-- Wholesale Price -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Wholesale Price</label
              >
              <input
                v-model="form.wholesale_price"
                type="number"
                step="0.01"
                :readonly="isPriceLocked"
                :class="{ 'bg-gray-100': isPriceLocked }"
                class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                placeholder="0.00"
              />
            </div>

            <!-- Retail Price -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Retail Price 
              </label>
              <input
                v-model="form.retail_price"
                type="number"
                step="0.01"
                :readonly="isPriceLocked"
                :class="{ 'bg-gray-100': isPriceLocked }"
                class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                placeholder="0.00"
              />
            </div>


             <!-- Tax -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">Tax</label>
              <select
                v-model="form.tax_id"
                class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
              >
                <option value="">No Tax</option>
                <option v-for="tax in taxes" :key="tax.id" :value="tax.id">
                  {{ tax.name }} - {{ tax.percentage }}%
                </option>
              </select>
              <div v-if="selectedTax" class="mt-1 text-xs text-green-400">
                Tax Added: <span class="font-semibold">{{ selectedTax.percentage }}%</span>
              </div>
            </div>
            <!-- Discount -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Discount Type</label
              >
              <select
                v-model="form.discount_id"
                class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
              >
                <option value="">No Discount</option>
                <option
                  v-for="discount in discounts"
                  :key="discount.id"
                  :value="discount.id"
                >
                  {{ discount.name }}
                  {{ discount.value }}
                  {{ discount.type === 0 ? "%" : page.props.currency || "" }}
                </option>
              </select>
            </div>


          </div>
        </div>

        <!-- Inventory Section -->
        <div class="mb-6 bg-white rounded-xl p-4 border border-gray-200">
          <h3 class="mb-4 text-lg font-semibold text-blue-600">Inventory & Units</h3>

          <!-- Units Row -->
          <div class="grid grid-cols-1 gap-4 mt-4 md:grid-cols-3">
            <!-- Purchase Unit -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Purchase Unit</label
              >
              <div class="flex gap-2">
                <select
                  v-model="form.purchase_unit_id"
                  class="flex-1 min-w-0 px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                >
                  <option value="">Select Unit</option>
                  <option
                    v-for="unit in measurementUnits"
                    :key="unit.id"
                    :value="unit.id"
                  >
                    {{ unit.name }}
                  </option>
                </select>

                <button
                  type="button"
                  @click="openUnitModal('purchase')"
                  class="px-3 py-2.5 text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition duration-200 flex-shrink-0"
                  title="Add New Unit"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-5 h-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 4v16m8-8H4"
                    />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Transfer Unit -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Transfer Unit</label
              >
              <div class="flex gap-2">
                <select
                  v-model="form.transfer_unit_id"
                  class="flex-1 min-w-0 px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                >
                  <option value="">Select Unit</option>
                  <option
                    v-for="unit in measurementUnits"
                    :key="unit.id"
                    :value="unit.id"
                  >
                    {{ unit.name }}
                  </option>
                </select>

                <button
                  type="button"
                  @click="openUnitModal('transfer')"
                  class="px-3 py-2.5 text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition duration-200 flex-shrink-0"
                  title="Add New Unit"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-5 h-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 4v16m8-8H4"
                    />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Sales Unit -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Sales Unit</label
              >
              <div class="flex gap-2">
                <select
                  v-model="form.sales_unit_id"
                  class="flex-1 min-w-0 px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                >
                  <option value="">Select Unit</option>
                  <option
                    v-for="unit in measurementUnits"
                    :key="unit.id"
                    :value="unit.id"
                  >
                    {{ unit.name }}
                  </option>
                </select>

                <button
                  type="button"
                  @click="openUnitModal('sales')"
                  class="px-3 py-2.5 text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition duration-200 flex-shrink-0"
                  title="Add New Unit"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-5 h-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 4v16m8-8H4"
                    />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 mt-4">
            <!-- Store Quantity -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Store Quantity
                <span v-if="form.purchase_unit_id" class="text-blue-400">
                  ({{ purchaseUnitDisplayName }})
                </span>
              </label>
              <input
                v-model="form.store_quantity"
                type="number"
                class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                placeholder="0"
              />
              <span class="text-xs text-gray-400">Stock quantity in main store</span>
            </div>

            <!-- Store Low Stock Margin -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Store Low Stock Alert
                <span v-if="form.purchase_unit_id" class="text-blue-400">
                  ({{ purchaseUnitDisplayName }})
                </span></label
              >

              <input
                v-model="form.store_low_stock_margin"
                type="number"
                class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                placeholder="10"
              />
              <span class="text-xs text-gray-400"
                >Alert when store stock falls below this level</span
              >
            </div>
          </div>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 mt-4">
            <!-- Shop Quantity -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Shop Quantity <span class="text-red-500">*</span>
                <span v-if="form.sales_unit_id" class="text-green-400">
                  ({{ getSalesUnitName(form.sales_unit_id) }})
                </span>
              </label>
              <input
                v-model="form.shop_quantity"
                type="number"
                required
                class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                placeholder="0"
              />
            </div>

            <!-- Shop Low Stock Margin -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Shop Low Stock Alert

                <span v-if="form.sales_unit_id" class="text-green-400">
                  ({{ getSalesUnitName(form.sales_unit_id) }})
                </span>
              </label>
              <input
                v-model="form.shop_low_stock_margin"
                type="number"
                class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                placeholder="10"
              />
              <span class="text-xs text-gray-400"
                >Alert when shop stock falls below this level</span
              >
            </div>
          </div>
        </div>

        <!-- Conversion Rates Section -->
        <div class="mb-6 bg-white rounded-xl p-4 border border-gray-200">
          <h3 class="mb-4 text-lg font-semibold text-blue-600">Unit Conversion Rates</h3>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <!-- Purchase to Transfer Rate -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Purchase → Transfer Rate
                <span
                  v-if="form.purchase_unit_id && form.transfer_unit_id"
                  class="text-blue-500"
                >
                  (1 {{ getPurchaseUnitShortName(form.purchase_unit_id) }} = ?
                  {{ getTransferUnitName(form.transfer_unit_id) }})
                </span>
              </label>
              <input
                v-model="form.purchase_to_transfer_rate"
                type="number"
                step="0.01"
                class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                placeholder="1.00"
              />
              <span class="text-xs text-gray-400"
                >How many transfer units in one purchase unit</span
              >
            </div>

            <!-- Transfer to Sales Rate -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Transfer → Sales Rate
                <span
                  v-if="form.transfer_unit_id && form.sales_unit_id"
                  class="text-blue-500"
                >
                  (1 {{ getTransferUnitName(form.transfer_unit_id) }} = ?
                  {{ getSalesUnitName(form.sales_unit_id) }})
                </span>
              </label>
              <input
                v-model="form.transfer_to_sales_rate"
                type="number"
                step="0.01"
                class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                placeholder="1.00"
              />
              <span class="text-xs text-gray-400"
                >How many sales units in one transfer unit</span
              >
            </div>
          </div>

          <!-- Conversion Calculation Display -->
          <div
            v-if="form.store_quantity > 0 && form.purchase_to_transfer_rate > 0"
            class="mt-4 p-4 bg-gray-800 rounded-lg border border-purple-500"
          >
            <h4 class="text-sm font-semibold text-purple-400 mb-2">
              Store Stock Conversion:
            </h4>
            <div class="text-gray-700">
              <p class="text-sm">
                <span class="font-bold">{{ form.store_quantity }}</span>
                <span class="text-blue-400">{{
                  getPurchaseUnitConvertedName(form.purchase_unit_id) || "Purchase Unit"
                }}</span>
                <span class="mx-2">=</span>
                <span class="font-bold text-green-400">{{
                  calculateStoreInTransfer
                }}</span>
                <span class="text-yellow-400">{{
                  getTransferUnitName(form.transfer_unit_id) || "Transfer Unit"
                }}</span>
              </p>
              <p v-if="form.transfer_to_sales_rate > 0" class="text-sm mt-2">
                <span class="mx-2">=</span>
                <span class="font-bold text-green-400">{{ calculateStoreInSales }}</span>
                <span class="text-pink-400">{{
                  getSalesUnitName(form.sales_unit_id) || "Sales Unit"
                }}</span>
              </p>
            </div>
          </div>
        </div>

        <!-- Additional Options Section -->
        <div class="mb-6 bg-white rounded-xl p-4 border border-gray-200">
          <h3 class="mb-4 text-lg font-semibold text-blue-600">Additional Options</h3>
          <div class="space-y-4">
            <!-- Return Product Checkbox -->
            <div
              class="flex items-center p-3 bg-gray-50 rounded-lg border border-gray-200"
            >
              <input
                v-model="form.return_product"
                type="checkbox"
                id="return-product"
                class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500"
              />
              <label for="return-product" class="ml-3 text-sm font-medium text-gray-700">
                Allow Product Returns
              </label>
            </div>

            <!-- Image Upload -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Product Image</label
              >
              <div v-if="displayImage" class="mb-3">
                <img
                  :src="displayImage"
                  alt="Current product image"
                  class="h-24 rounded border border-gray-300 object-cover"
                />
              </div>
              <input
                @input="onImageChange"
                type="file"
                accept="image/*"
                class="w-full px-4 py-2 text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700"
              />
              <span v-if="form.errors.image" class="text-sm text-red-500">{{
                form.errors.image
              }}</span>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end pt-6 mt-6 space-x-3 border-t border-gray-300">
          <button
            type="button"
            @click="closeModal"
            class="px-6 py-2 text-white transition bg-gray-500 rounded-lg hover:bg-gray-600"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2 text-white transition bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ form.processing ? "Duplicating..." : "Create Duplicate" }}
          </button>
        </div>
      </form>
    </div>
  </Modal>
  <!-- ADD THESE 3 QUICK ADD MODALS HERE (right after the main modal) -->
  <QuickAddModal
    :show="quickAddModal.brand"
    type="brand"
    route-name="brands.store"
    @close="quickAddModal.brand = false"
    @created="(item) => handleQuickCreated('brand', item)"
  />
  <QuickAddModal
    :show="quickAddModal.category"
    type="category"
    route-name="categories.store"
    @close="quickAddModal.category = false"
    @created="(item) => handleQuickCreated('category', item)"
  />
  <QuickAddModal
    :show="quickAddModal.type"
    type="type"
    route-name="types.store"
    @close="quickAddModal.type = false"
    @created="(item) => handleQuickCreated('type', item)"
  />
  <!-- Quick Add Unit Modal -->
  <QuickAddModal
    :show="quickAddModal.unit"
    type="unit"
    route-name="measurement-units.store"
    @close="quickAddModal.unit = false"
    @created="(item) => handleQuickCreated('unit', item)"
  />
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import QuickAddModal from "@/Pages/Products/Components/QuickAddModal.vue";

// Track which field opened the unit modal (purchase/sales/transfer)
const unitTargetField = ref(null);
const imagePreview = ref(null); // added

const quickAddModal = ref({
  brand: false,
  category: false,
  type: false,
  unit: false,
});

const props = defineProps({
  open: Boolean,
  product: {
    type: Object,
    default: null,
  },
  brands: Array,
  categories: Array,
  types: Array,
  measurementUnits: {
    type: Array,
    default: () => [],
  },
  suppliers: Array,
  customers: Array,
  discounts: Array,
  taxes: Array,
});

const emit = defineEmits(["update:open"]);

const form = useForm({
  name: "",
  barcode: "",
  brand_id: null,
  category_id: null,
  type_id: null,
  discount_id: null,
  discount_value: null,
  discount_type: null,
  tax_id: null,
  tax_value: null,
  tax_percentage: null,
  shop_quantity: 0,
  shop_low_stock_margin: 0,

  store_quantity: 0,
  store_low_stock_margin: 0,

  purchase_price: null,
  wholesale_price: null,
  retail_price: null,
  return_product: false,
  purchase_unit_id: null,
  sales_unit_id: null,
  transfer_unit_id: null,
  purchase_to_transfer_rate: 1,
  transfer_to_sales_rate: 1,
  status: 1,
  image: null,
});

// expose Inertia page props for template access (currency, currencySymbol)
const page = usePage();

const isPriceLocked = computed(() => {
  return !!form.discount_id || !!form.tax_id;
});

// Selected discount object from discounts table
const selectedDiscount = computed(() => {
  if (!form.discount_id) return null;
  return props.discounts.find((d) => d.id == form.discount_id) || null;
});

// When discount selection changes, populate discount value/type fields
watch(() => form.discount_id, (newVal) => {
  const d = selectedDiscount.value;
  if (d) {
    form.discount_value = d.value;
    form.discount_type = d.type;
  } else {
    form.discount_value = null;
    form.discount_type = null;
  }
  calculatePrices();
});

// Selected tax object from taxes table
const selectedTax = computed(() => {
  if (!form.tax_id) return null;
  return props.taxes.find((t) => t.id == form.tax_id) || null;
});

// Store original prices before tax
const originalWholesalePrice = ref(null);
const originalRetailPrice = ref(null);

watch(() => form.wholesale_price, (newVal) => {
    if (!form.discount_id && !form.tax_id) {
        originalWholesalePrice.value = parseFloat(newVal) || 0;
    }
});

watch(() => form.retail_price, (newVal) => {
    if (!form.discount_id && !form.tax_id) {
        originalRetailPrice.value = parseFloat(newVal) || 0;
    }
});

// When tax selection changes, update prices with tax
watch(() => form.tax_id, (newVal, oldVal) => {
    const t = selectedTax.value;
     if (t) {
        form.tax_value = t.percentage;
        form.tax_percentage = t.percentage;
    } else {
        form.tax_value = null;
        form.tax_percentage = null;
    }
    calculatePrices();
});

const calculatePrices = () => {
  const discount = selectedDiscount.value;
  const tax = selectedTax.value;

  if (originalWholesalePrice.value === null && form.wholesale_price) {
    originalWholesalePrice.value = parseFloat(form.wholesale_price) || 0;
  }
  if (originalRetailPrice.value === null && form.retail_price) {
    originalRetailPrice.value = parseFloat(form.retail_price) || 0;
  }

  let wholesale = originalWholesalePrice.value || 0;
  let retail = originalRetailPrice.value || 0;

  if (discount) {
    if (discount.type === 0) { // Percentage
      wholesale -= wholesale * (discount.value / 100);
      retail -= retail * (discount.value / 100);
    } else { // Fixed amount
      wholesale -= discount.value;
      retail -= discount.value;
    }
  }

  if (tax) {
    wholesale += wholesale * (tax.percentage / 100);
    retail += retail * (tax.percentage / 100);
  }

  if (discount || tax) {
      form.wholesale_price = wholesale > 0 ? wholesale.toFixed(2) : "0.00";
      form.retail_price = retail > 0 ? retail.toFixed(2) : "0.00";
  } else {
      if (originalWholesalePrice.value !== null) {
          form.wholesale_price = originalWholesalePrice.value.toFixed(2);
      }
      if (originalRetailPrice.value !== null) {
          form.retail_price = originalRetailPrice.value.toFixed(2);
      }
  }
};

// Helper to find unit by id with loose matching (handles string/number)
const findUnitById = (unitId) => {
  if (unitId === null || unitId === undefined || unitId === "") return null;
  return props.measurementUnits.find((u) => u.id == unitId) || null;
};

// Helper function to get Purchase Unit name by ID
const getPurchaseUnitName = (unitId) => {
  const unit = findUnitById(unitId);
  return unit ? unit.name : "";
};

// Helper function to get Purchase Unit short name by ID
const getPurchaseUnitShortName = (unitId) => {
  const unit = findUnitById(unitId);
  return unit ? unit.short_name || unit.name : "";
};

// Helper function to get Purchase Unit converted name by ID
const getPurchaseUnitConvertedName = (unitId) => {
  const unit = findUnitById(unitId);
  return unit ? unit.name : "";
};

// Helper function to get Sales Unit name by ID
const getSalesUnitName = (unitId) => {
  const unit = findUnitById(unitId);
  return unit ? unit.name : "";
};

// Helper function to get Transfer Unit name by ID
const getTransferUnitName = (unitId) => {
  const unit = findUnitById(unitId);
  return unit ? unit.name : "";
};

// Computed property for purchase unit display name
const purchaseUnitDisplayName = computed(() => {
  return getPurchaseUnitName(form.purchase_unit_id);
});

// Computed property to calculate store stock in transfer units
const calculateStoreInTransfer = computed(() => {
  const store = parseFloat(form.store_quantity) || 0;
  const rate = parseFloat(form.purchase_to_transfer_rate) || 0;
  return (store * rate).toFixed(2);
});

// Computed property to calculate store stock in sales units
const calculateStoreInSales = computed(() => {
  const store = parseFloat(form.store_quantity) || 0;
  const purchaseToTransfer = parseFloat(form.purchase_to_transfer_rate) || 0;
  const transferToSales = parseFloat(form.transfer_to_sales_rate) || 0;
  return (store * purchaseToTransfer * transferToSales).toFixed(2);
});

// Computed property to calculate shop stock in transfer units
const calculateShopInTransfer = computed(() => {
  const shop = parseFloat(form.shop_quantity) || 0;
  const transferToSales = parseFloat(form.transfer_to_sales_rate) || 0;
  if (transferToSales === 0) return "0.00";
  return (shop / transferToSales).toFixed(2);
});

// Computed property to calculate shop stock in purchase units
const calculateShopInPurchase = computed(() => {
  const shop = parseFloat(form.shop_quantity) || 0;
  const purchaseToTransfer = parseFloat(form.purchase_to_transfer_rate) || 0;
  const transferToSales = parseFloat(form.transfer_to_sales_rate) || 0;
  if (purchaseToTransfer === 0 || transferToSales === 0) return "0.00";
  return (shop / (purchaseToTransfer * transferToSales)).toFixed(2);
});

// Open functions
const openBrandModal = () => (quickAddModal.value.brand = true);
const openCategoryModal = () => (quickAddModal.value.category = true);
const openTypeModal = () => (quickAddModal.value.type = true);

// Special function for unit + buttons
const openUnitModal = (field) => {
  unitTargetField.value = field;
  quickAddModal.value.unit = true;
};

// Handle all quick creations (brand, category, type, AND unit)
const handleQuickCreated = (field, newItem) => {
  if (!newItem) return;

  if (field === "brand") {
    props.brands.push(newItem);
    form.brand_id = newItem.id;
  } else if (field === "category") {
    props.categories.push(newItem);
    form.category_id = newItem.id;
  } else if (field === "type") {
    props.types.push(newItem);
    form.type_id = newItem.id;
  } else if (field === "unit") {
    props.measurementUnits.push(newItem);

    if (unitTargetField.value === "purchase") form.purchase_unit_id = newItem.id;
    if (unitTargetField.value === "sales") form.sales_unit_id = newItem.id;
    if (unitTargetField.value === "transfer") form.transfer_unit_id = newItem.id;

    unitTargetField.value = null;
  }

  quickAddModal.value[field] = false;
};

watch(
  () => props.product,
  (product) => {
    if (!product) return;
    form.name = product.name || "";
    form.barcode = "";
    form.brand_id = product.brand_id || null;
    form.category_id = product.category_id || null;
    form.type_id = product.type_id || null;
    form.discount_id = product.discount_id || null;
    form.tax_id = product.tax_id || null;

    form.shop_quantity = 0;
    form.shop_low_stock_margin = product.shop_low_stock_margin || 0;

    form.store_quantity = 0;
    form.store_low_stock_margin = product.store_low_stock_margin || 0;

    form.purchase_price = product.purchase_price ?? null;
    form.wholesale_price = product.wholesale_price ?? null;
    form.retail_price = product.retail_price ?? null;

    form.return_product = !!product.return_product;
    form.purchase_unit_id = product.purchase_unit_id || null;
    form.sales_unit_id = product.sales_unit_id || null;
    form.transfer_unit_id = product.transfer_unit_id || null;
    form.purchase_to_transfer_rate = product.purchase_to_transfer_rate ?? 1;
    form.transfer_to_sales_rate = product.transfer_to_sales_rate ?? 1;

    form.status = 1;
    form.image = null;
    imagePreview.value = null; // ensure preview resets
  },
  { immediate: true }
);

const onImageChange = (e) => {
  const file = e.target.files?.[0] || null;
  form.image = file;
  imagePreview.value = file ? URL.createObjectURL(file) : null;
};

const submit = () => {
  if (!props.product || !props.product.id) return;

  const storeInTransfer = parseFloat(calculateStoreInTransfer.value) || 0;
  const storeInSales = parseFloat(calculateStoreInSales.value) || 0;
  const shopInTransfer = parseFloat(calculateShopInTransfer.value) || 0;
  const shopInPurchase = parseFloat(calculateShopInPurchase.value) || 0;

  const formData = {
    name: form.name,
    barcode: form.barcode,
    brand_id: form.brand_id,
    category_id: form.category_id,
    type_id: form.type_id,
    discount_id: form.discount_id,
    tax_id: form.tax_id,
    shop_quantity: parseFloat(form.shop_quantity) || 0,
    shop_low_stock_margin: parseFloat(form.shop_low_stock_margin) || 0,

    store_quantity: parseFloat(form.store_quantity) || 0,
    store_low_stock_margin: parseFloat(form.store_low_stock_margin) || 0,

    purchase_price: form.purchase_price,
    wholesale_price: form.wholesale_price,
    retail_price: form.retail_price,
    return_product: form.return_product,
    purchase_unit_id: form.purchase_unit_id,
    sales_unit_id: form.sales_unit_id,
    transfer_unit_id: form.transfer_unit_id,
    purchase_to_transfer_rate: form.purchase_to_transfer_rate,
    transfer_to_sales_rate: form.transfer_to_sales_rate,
    status: form.status,
    image: form.image,
    discount_value: form.discount_value,
    discount_type: form.discount_type,
    tax_value: form.tax_value,
    tax_percentage: form.tax_percentage,
    store_in_transfer_units: storeInTransfer,
    store_in_sales_units: storeInSales,
    shop_in_transfer_units: shopInTransfer,
    shop_in_purchase_units: shopInPurchase,
  };

  form
    .transform(() => formData)
    .post(route("products.duplicate", props.product.id), {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        closeModal(); // close after successful save
      },
      onError: (errors) => {
        console.error("Validation errors:", errors);
      },
    });
};

const closeModal = () => {
  emit("update:open", false);
  form.reset();
  form.clearErrors();
  imagePreview.value = null; // reset preview
};

const normalizeImagePath = (img) => {
  if (!img) return null;
  if (img.startsWith("http://") || img.startsWith("https://") || img.startsWith("//"))
    return img;
  if (img.startsWith("/storage")) return img;
  return `/storage/${img.replace(/^\/?/, "")}`;
};

const existingImage = computed(() => {
  const img = props.product?.image_url || props.product?.image || null;
  return normalizeImagePath(img);
});
const displayImage = computed(() => imagePreview.value || existingImage.value);
</script>
