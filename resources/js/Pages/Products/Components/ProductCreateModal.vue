<template>
  <Modal :show="open" @close="closeModal" max-width="4xl">
    <div class="p-6 bg-gray-50">
      <div class="flex items-center justify-between mb-6">
        <h2
          class="text-2xl font-bold text-blue-600"
        >
          ✨ Add New Product
        </h2>
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
        <div
          class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200"
        >
          <h3 class="mb-3 text-lg font-semibold text-blue-600 flex items-center gap-2">
            📋 Basic Information
          </h3>
          <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
            <!-- Product Name -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Product Name <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.name"
                type="text"
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Enter product name"
                required
                pattern="^[A-Za-z0-9\s]+$"
                @input="onNameInput"
                title="Only alphabetic characters, numbers, and spaces are allowed."
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
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Enter or scan barcode"
              />
              <span v-if="form.errors.barcode" class="text-sm text-red-500">{{
                form.errors.barcode
              }}</span>
            </div>

            <div class="min-w-0">
              <label class="block mb-2 text-sm font-medium text-gray-700">Brand</label>
              <div class="flex gap-2">
                <select
                  v-model="form.brand_id"
                  class="flex-1 min-w-0 px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
            <div class="min-w-0">
              <label class="block mb-2 text-sm font-medium text-gray-700">Category</label>
              <div class="flex gap-2">
                <select
                  v-model="form.category_id"
                  class="flex-1 min-w-0 px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  title="Select Category"
                >
                  <option value="">Select Category</option>
                  <option
                    v-for="category in categories"
                    :key="category.id"
                    :value="category.id"
                  >
                    {{ category.hierarchy_string ? category.hierarchy_string + ' → ' + category.name : category.name }}
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
            <div class="min-w-0">
              <label class="block mb-2 text-sm font-medium text-gray-700">Type</label>
              <div class="flex gap-2">
                <select
                  v-model="form.type_id"
                  class="flex-1 min-w-0 px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>

            <!-- Division -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Division <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.division_id"
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required
              >
                <option value="">-- Select Division --</option>
                <option v-for="division in divisions" :key="division.id" :value="division.id">
                  {{ division.name }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- Pricing Section -->
        <div
          class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200"
        >
          <h3 class="mb-3 text-lg font-semibold text-green-600 flex items-center gap-2">
            💰 Pricing Information ({{ page.props.currency || "" }})
          </h3>
          <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
            <!-- Purchase Price -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Purchase Price</label
              >
              <input
                v-model="form.purchase_price"
                type="number"
                step="0.01"
                min="0"
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="0.00"
              />
              <span v-if="form.errors.purchase_price" class="text-sm text-red-500">{{ form.errors.purchase_price }}</span>
            </div>

            <!-- Wholesale Price -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Wholesale Price(Wholesale Price per one sales unit)</label
              >
              <input
                v-model="form.wholesale_price"
                type="number"
                step="0.01"
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="0.00"
              />
            </div>

            <!-- Retail Price -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Retail Price(Retail Price per one sales unit)
              </label>
              <input
                v-model="form.retail_price"
                type="number"
                step="0.01"
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="0.00"
              />
            </div>


             <!-- Tax -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">Tax</label>
              <div class="flex gap-2">
                <select
                  v-model="form.tax_id"
                  class="flex-1 px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="">Select Tax</option>
                  <option v-for="tax in taxes" :key="tax.id" :value="tax.id">
                    {{ tax.name }} - {{ tax.percentage }}%
                  </option>
                </select>
                <button
                  type="button"
                  @click="openTaxModal"
                  class="px-3 py-2.5 text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition duration-200 flex-shrink-0"
                  title="Add New Tax"
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
            <!-- Discount -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Discount Type</label
              >
              <div class="flex gap-2">
                <select
                  v-model="form.discount_id"
                  class="flex-1 px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="">Select Discount</option>
                  <option
                    v-for="discount in discounts"
                    :key="discount.id"
                    :value="discount.id"
                  >
                    {{ discount.name }} -
                      {{ discount.value }} {{ discount.type === 0 ? '%' : (page.props.currency || '') }}
                  </option>
                </select>
                <button
                  type="button"
                  @click="openDiscountModal"
                  class="px-3 py-2.5 text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition duration-200 flex-shrink-0"
                  title="Add New Discount"
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
        </div>

        <!-- Inventory Section -->
        <div
          class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200"
        >
          <h3 class="mb-3 text-lg font-semibold text-orange-600 flex items-center gap-2">
            📦 Inventory & Units
          </h3>

          <!-- Units Row -->
          <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <!-- Purchase Unit -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Purchase Unit</label
              >
              <div class="flex gap-2">
                <select
                  v-model="form.purchase_unit_id"
                  class="flex-1 px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
              <span v-if="form.errors.purchase_unit_id" class="text-sm text-red-500">{{ form.errors.purchase_unit_id }}</span>
            </div>

            <!-- Transfer Unit -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Transfer Unit</label
              >
              <div class="flex gap-2">
                <select
                  v-model="form.transfer_unit_id"
                  class="flex-1 px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
              <span v-if="form.errors.transfer_unit_id" class="text-sm text-red-500">{{ form.errors.transfer_unit_id }}</span>
            </div>

            <!-- Sales Unit -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Sales Unit</label
              >
              <div class="flex gap-2">
                <select
                  v-model="form.sales_unit_id"
                  class="flex-1 px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
              <span v-if="form.errors.sales_unit_id" class="text-sm text-red-500">{{ form.errors.sales_unit_id }}</span>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3 mt-3">
            <!-- Store Quantity -->
            <!-- <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Store Quantity
                <span v-if="form.purchase_unit_id" class="blue-600">
                  ({{ purchaseUnitDisplayName }})
                </span>
              </label>
              <input
                v-model="form.store_quantity_in_purchase_unit"
                type="number"
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="0"
                readonly
                              />
              <span class="text-xs text-gray-600">Stock quantity in main store</span>
              <p v-if="storeQuantityAsSalesUnit" class="text-xs text-gray-300">
                ≈ {{ storeQuantityAsSalesUnit }} (sales unit)
              </p>
            </div> -->

            <!-- Store Low Stock Margin -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Store Low Stock Alert
                <span v-if="form.purchase_unit_id" class="blue-600">
                  ({{ purchaseUnitDisplayName }})
                </span></label
              >

              <input
                v-model="form.store_low_stock_margin"
                type="number"
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="10"
              />
              <span class="text-xs text-gray-600"
                >Alert when store stock falls below this level</span
              >
            </div>
          </div>

          <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3 mt-3">
            <!-- Shop Quantity -->
            <!-- <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Shop Quantity <span class="text-red-500">*</span>
                <span v-if="form.sales_unit_id" class="green-600">
                  ({{ getSalesUnitName(form.sales_unit_id) }})
                </span>
              </label>
              <input
                v-model="form.shop_quantity_in_sales_unit"
                v-model="form.shop_quantity_in_sales_unit"
                type="number"
                required
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="0"
                readonly
              />
              <span class="text-xs text-gray-600">Stock quantity in shop (sales units)</span>
            </div> -->

            <!-- Shop Low Stock Margin -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Shop Low Stock Alert

                <span v-if="form.sales_unit_id" class="green-600">
                  ({{ getSalesUnitName(form.sales_unit_id) }})
                </span>
              </label>
              <input
                v-model="form.shop_low_stock_margin"
                type="number"
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="10"
              />
              <span class="text-xs text-gray-600"
                >Alert when shop stock falls below this level</span
              >
            </div>
          </div>
        </div>

        <!-- Conversion Rates Section -->
        <div
          class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200"
        >
          <h3 class="mb-3 text-lg font-semibold text-purple-600 flex items-center gap-2">
            🔄 Unit Conversion Rates
          </h3>
          <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <!-- Purchase to Transfer Rate -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Purchase → Transfer Rate
                <span
                  v-if="form.purchase_unit_id && form.transfer_unit_id"
                  class="text-purple-300"
                >
                  (1 {{ getPurchaseUnitShortName(form.purchase_unit_id) }} = ?
                  {{ getTransferUnitName(form.transfer_unit_id) }})
                </span>
              </label>
                <input
                  v-model="form.purchase_to_transfer_rate"
                  type="number"
                  step="0.01"
                  class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="1.00"
                />
              <span class="text-xs text-gray-600"
                >How many transfer units in one purchase unit</span
              >
            </div>

            <!-- Transfer to Sales Rate -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700">
                Transfer → Sales Rate
                <span
                  v-if="form.transfer_unit_id && form.sales_unit_id"
                  class="text-purple-300"
                >
                  (1 {{ getTransferUnitName(form.transfer_unit_id) }} = ?
                  {{ getSalesUnitName(form.sales_unit_id) }})
                </span>
              </label>
                <input
                  v-model="form.transfer_to_sales_rate"
                  type="number"
                  step="0.01"
                  class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="1.00"
                />
              <span class="text-xs text-gray-600"
                >How many sales units in one transfer unit</span
              >
            </div>
          </div>

          <!-- Conversion Calculation Display -->
          <div
            v-if="form.store_quantity_in_purchase_unit > 0 && form.purchase_to_transfer_rate > 0"
            class="mt-4 p-4 bg-gray-800 rounded-lg border border-purple-500"
          >
            <h4 class="text-sm font-semibold text-purple-400 mb-2">
              Store Stock Conversion:
            </h4>
            <div class="text-white">
              <p class="text-sm">
                <span class="font-bold">{{ form.store_quantity_in_purchase_unit }}</span>
                <span class="blue-600">
                  {{
                    getPurchaseUnitConvertedName(form.purchase_unit_id) || "Purchase Unit"
                  }}</span
                >
                <span class="mx-2">=</span>
                <span class="font-bold green-600"> {{ calculateStoreInTransfer }}</span>
                <span class="orange-600">
                  {{
                    getTransferUnitName(form.transfer_unit_id) || "Transfer Unit"
                  }}</span
                >
              </p>
              <p v-if="form.transfer_to_sales_rate > 0" class="text-sm mt-2">
                <span class="mx-2">=</span>
                <span class="font-bold green-600"> {{ calculateStoreInSales }}</span>
                <span class="indigo-600">
                  {{ getSalesUnitName(form.sales_unit_id) || "Sales Unit" }}</span
                >
              </p>
            </div>
          </div>
        </div>

        <!-- Additional Options Section -->
        <div
          class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200"
        >
          <h3 class="mb-3 text-lg font-semibold text-indigo-600 flex items-center gap-2">
            ⚙️ Additional Options
          </h3>
          <div class="space-y-3">
            <!-- Return Product Checkbox -->
            <div
              class="flex items-center p-4 bg-white/50 backdrop-blur-sm rounded-xl border border-gray-300"
            >
              <input
                v-model="form.return_product"
                type="checkbox"
                id="return-product"
                class="w-5 h-5 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
              />
              <label for="return-product" class="ml-3 text-sm font-medium text-gray-800">
                Allow Product Returns
              </label>
            </div>

            <!-- Image Upload -->
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-700"
                >Product Image</label
              >
              <input
                @input="form.image = $event.target.files[0]"
                type="file"
                accept="image/*"
                class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700"
              />
              <span v-if="form.errors.image" class="text-sm text-red-500">{{
                form.errors.image
              }}</span>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end pt-4 mt-4 space-x-3 border-t border-gray-300">
          <button
            type="button"
            @click="closeModal"
            class="px-8 py-2.5 rounded-[5px] font-semibold text-sm bg-gray-500 text-white hover:bg-gray-600 transition-all duration-200"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-8 py-2.5 rounded-[5px] font-semibold text-sm bg-blue-600 text-white hover:bg-blue-700 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ form.processing ? "✨ Creating..." : "✨ Create Product" }}
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
    :extra-fields="['parent_id', 'status']"
    :parent-categories="categories"
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
  <!-- Quick Add Tax Modal -->
  <QuickAddModal
    :show="quickAddModal.tax"
    type="tax"
    route-name="taxes.store"
    @close="quickAddModal.tax = false"
    @created="(item) => handleQuickCreated('tax', item)"
  />
  <!-- Quick Add Discount Modal -->
  <QuickAddModal
    :show="quickAddModal.discount"
    type="discount"
    route-name="discounts.store"
    @close="quickAddModal.discount = false"
    @created="(item) => handleQuickCreated('discount', item)"
  />
</template>

<script setup>
// Only allow alphabetic characters, numbers, and spaces in product name
const onNameInput = (e) => {
  // Replace any character that is not a letter, number, or space
  e.target.value = e.target.value.replace(/[^A-Za-z0-9\s]/g, "");
  form.name = e.target.value;
};
import { ref, computed, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useForm } from "@inertiajs/vue3";
import { logActivity } from "@/composables/useActivityLog";
import Modal from "@/Components/Modal.vue";
import QuickAddModal from "@/Pages/Products/Components/QuickAddModal.vue";

// Track which field opened the unit modal (purchase/sales/transfer)
const unitTargetField = ref(null);

const quickAddModal = ref({
  brand: false,
  category: false,
  type: false,
  unit: false,
  tax: false,
  discount: false,
});

const props = defineProps({
  open: Boolean,
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
  divisions: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(["update:open"]);

const form = useForm({
  name: "",
  barcode: "",
  brand_id: "",
  category_id: "",
  type_id: "",
  discount_id: "",
  discount_value: null,
  discount_type: null,
  tax_id: "",
  tax_value: null,
  tax_percentage: null,
  shop_quantity_in_sales_unit: 0,
  shop_low_stock_margin: 0,
  store_quantity_in_purchase_unit: 0,
  store_low_stock_margin: 0,
  purchase_price: 0,
  wholesale_price: null,
  retail_price: null,
  return_product: false,
  purchase_unit_id: "",
  sales_unit_id: "",
  transfer_unit_id: "",
  purchase_to_transfer_rate: null,
  transfer_to_sales_rate: null,
  status: 1,
  division_id: "",
  image: null,
});

// Helper function to get Purchase Unit name by ID
const getPurchaseUnitName = (unitId) => {
  if (!unitId) return "";
  const unit = props.measurementUnits.find((u) => u.id === unitId);
  return unit ? unit.name : "";
};

// Helper function to get Purchase Unit short name by ID
const getPurchaseUnitShortName = (unitId) => {
  if (!unitId) return "";
  const unit = props.measurementUnits.find((u) => u.id === unitId);
  return unit ? unit.short_name || unit.name : "";
};

// Helper function to get Purchase Unit converted name by ID
const getPurchaseUnitConvertedName = (unitId) => {
  if (!unitId) return "";
  const unit = props.measurementUnits.find((u) => u.id === unitId);
  return unit ? unit.name : "";
};

// Helper function to get Sales Unit name by ID
const getSalesUnitName = (unitId) => {
  if (!unitId) return "";
  const unit = props.measurementUnits.find((u) => u.id === unitId);
  return unit ? unit.name : "";
};

// Helper function to get Transfer Unit name by ID
const getTransferUnitName = (unitId) => {
  if (!unitId) return "";
  const unit = props.measurementUnits.find((u) => u.id === unitId);
  return unit ? unit.name : "";
};

// Computed property for purchase unit display name
const purchaseUnitDisplayName = computed(() => {
  return getPurchaseUnitName(form.purchase_unit_id);
});

// Computed property to calculate store stock in transfer units
const calculateStoreInTransfer = computed(() => {
  const store = parseFloat(form.store_quantity_in_purchase_unit) || 0;
  const rate = parseFloat(form.purchase_to_transfer_rate) || 0;
  return (store * rate).toFixed(2);
});

// Computed property to calculate store stock in sales units
const calculateStoreInSales = computed(() => {
  const store = parseFloat(form.store_quantity_in_purchase_unit) || 0;
  const purchaseToTransfer = parseFloat(form.purchase_to_transfer_rate) || 0;
  const transferToSales = parseFloat(form.transfer_to_sales_rate) || 0;
  return (store * purchaseToTransfer * transferToSales).toFixed(2);
});

const storeQuantityAsSalesUnit = computed(() => {
  const store = Number(form.store_quantity_in_purchase_unit);
  const p2t = Number(form.purchase_to_transfer_rate || 0);
  const t2s = Number(form.transfer_to_sales_rate || 0);
  if (!store || !p2t || !t2s) return "";
  const qty = store * p2t * t2s;
  const unit = getSalesUnitName(form.sales_unit_id);
  return unit ? `${qty} ${unit}` : `${qty}`;
});

// Computed property to calculate shop stock in transfer units
const calculateShopInTransfer = computed(() => {
  const shop = parseFloat(form.shop_quantity_in_sales_unit) || 0;
  const transferToSales = parseFloat(form.transfer_to_sales_rate) || 0;
  if (transferToSales === 0) return "0.00";
  return (shop / transferToSales).toFixed(2);
});

// Computed property to calculate shop stock in purchase units
const calculateShopInPurchase = computed(() => {
  const shop = parseFloat(form.shop_quantity_in_sales_unit) || 0;
  const purchaseToTransfer = parseFloat(form.purchase_to_transfer_rate) || 0;
  const transferToSales = parseFloat(form.transfer_to_sales_rate) || 0;
  if (purchaseToTransfer === 0 || transferToSales === 0) return "0.00";
  return (shop / (purchaseToTransfer * transferToSales)).toFixed(2);
});

// Open functions
const openBrandModal = () => (quickAddModal.value.brand = true);
const openCategoryModal = () => (quickAddModal.value.category = true);
const openTypeModal = () => (quickAddModal.value.type = true);
const openTaxModal = () => (quickAddModal.value.tax = true);
const openDiscountModal = () => (quickAddModal.value.discount = true);

// Special function for unit + buttons
const openUnitModal = (field) => {
  unitTargetField.value = field;
  quickAddModal.value.unit = true;
};

// Handle all quick creations (brand, category, type, unit, tax, AND discount)
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
  } else if (field === "tax") {
    props.taxes.push(newItem);
    form.tax_id = newItem.id;
  } else if (field === "discount") {
    props.discounts.push(newItem);
    form.discount_id = newItem.id;
  }

  quickAddModal.value[field] = false;
};

const submit = () => {
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
    shop_quantity_in_sales_unit: parseFloat(form.shop_quantity_in_sales_unit) || 0,
    shop_low_stock_margin: parseFloat(form.shop_low_stock_margin) || 0,

    store_quantity_in_purchase_unit: parseFloat(form.store_quantity_in_purchase_unit) || 0,
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
    division_id: form.division_id || null,
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
    .post(route("products.store"), {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: async () => {
        // Log product creation activity
        await logActivity("create", "products", {
          product_name: form.name,
          barcode: form.barcode || "Auto-generated",
          brand_id: form.brand_id,
          category_id: form.category_id,
          purchase_price: form.purchase_price,
          selling_price: form.selling_price,
          store_quantity_in_purchase_unit: form.store_quantity_in_purchase_unit,
          shop_quantity_in_sales_unit: form.shop_quantity_in_sales_unit,
          status: form.status,
        });

        closeModal();
        form.reset();
      },
      onError: (errors) => {
        console.error("Validation errors:", errors);
      },
    });
};

// expose page props for template access (currency, currencySymbol)
const page = usePage();

// Computed property to lock price fields when discount or tax is applied
// const isPriceLocked = computed(() => {
//   return !!form.discount_id || !!form.tax_id;
// });

// Selected discount object from discounts table
// const selectedDiscount = computed(() => {
//   if (!form.discount_id) return null;
//   return props.discounts.find((d) => d.id == form.discount_id) || null;
// });

// When discount selection changes, populate discount value/type fields and calculate prices
// watch(() => form.discount_id, (newVal) => {
//   const d = selectedDiscount.value;
//   if (d) {
//     form.discount_value = d.value;
//     form.discount_type = d.type;
//   } else {
//     form.discount_value = null;
//     form.discount_type = null;
//   }
//   calculatePrices();
// });

// Selected tax object from taxes table
// const selectedTax = computed(() => {
//   if (!form.tax_id) return null;
//   return props.taxes.find((t) => t.id == form.tax_id) || null;
// });

// Store original prices before any calculations
// const originalWholesalePrice = ref(null);
// const originalRetailPrice = ref(null);

// Watch for manual changes to price inputs to update original prices
// watch(() => form.wholesale_price, (newVal) => {
//     if (!form.discount_id && !form.tax_id) {
//         originalWholesalePrice.value = parseFloat(newVal) || 0;
//     }
// });

// watch(() => form.retail_price, (newVal) => {
//     if (!form.discount_id && !form.tax_id) {
//         originalRetailPrice.value = parseFloat(newVal) || 0;
//     }
// });


// When tax selection changes, update prices
// watch(() => form.tax_id, (newVal) => {
//   const t = selectedTax.value;
//   if (t) {
//     form.tax_value = t.percentage;
//     form.tax_percentage = t.percentage;
//   } else {
//     form.tax_value = null;
//     form.tax_percentage = null;
//   }
//   calculatePrices();
// });

// Central function to calculate prices based on discount and tax
// const calculatePrices = () => {
//   const discount = selectedDiscount.value;
//   const tax = selectedTax.value;

//   // Capture original prices if they haven't been set yet
//   if (originalWholesalePrice.value === null && form.wholesale_price) {
//     originalWholesalePrice.value = parseFloat(form.wholesale_price) || 0;
//   }
//   if (originalRetailPrice.value === null && form.retail_price) {
//     originalRetailPrice.value = parseFloat(form.retail_price) || 0;
//   }

//   let wholesale = originalWholesalePrice.value || 0;
//   let retail = originalRetailPrice.value || 0;

//   // Apply discount first
//   if (discount) {
//     if (discount.type === 0) { // Percentage
//       wholesale -= wholesale * (discount.value / 100);
//       retail -= retail * (discount.value / 100);
//     } else { // Fixed amount
//       wholesale -= discount.value;
//       retail -= discount.value;
//     }
//   }

//   // Apply tax on the discounted price
//   if (tax) {
//     wholesale += wholesale * (tax.percentage / 100);
//     retail += retail * (tax.percentage / 100);
//   }

//   // Update form values
//   // Only update if there is a discount or tax, otherwise keep user's input
//   if (discount || tax) {
//       form.wholesale_price = wholesale > 0 ? wholesale.toFixed(2) : "0.00";
//       form.retail_price = retail > 0 ? retail.toFixed(2) : "0.00";
//   } else {
//       // If no discount or tax, restore original prices
//       if (originalWholesalePrice.value !== null) {
//           form.wholesale_price = originalWholesalePrice.value.toFixed(2);
//       }
//       if (originalRetailPrice.value !== null) {
//           form.retail_price = originalRetailPrice.value.toFixed(2);
//       }
//   }
// };

/**
 * Fetch purchase price from goods_received_notes_products table
 * based on product_id and batch_number
 */
const fetchPurchasePriceByBatch = async (productId, batchNumber) => {
  if (!productId || !batchNumber) {
    form.purchase_price = 0;
    return;
  }

  try {
    const response = await fetch('/products/pricing-by-batch', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
      },
      body: JSON.stringify({
        product_id: productId,
        batch_number: batchNumber,
      }),
    });

    if (response.ok) {
      const data = await response.json();
      if (data.success && data.purchase_price) {
        form.purchase_price = parseFloat(data.purchase_price).toFixed(2);
      } else {
        form.purchase_price = 0;
      }
    } else {
      form.purchase_price = 0;
    }
  } catch (error) {
    console.error('Error fetching purchase price:', error);
    form.purchase_price = 0;
  }
};

// Watch for modal open and fetch purchase price if product has batch
const watchOpen = watch(() => props.open, (newVal) => {
  if (newVal && props.selectedProduct && props.selectedProduct.current_batch) {
    fetchPurchasePriceByBatch(props.selectedProduct.id, props.selectedProduct.current_batch.batch_number);
  }
});

const closeModal = () => {
  emit("update:open", false);
  form.reset();
  form.clearErrors();
  // Reset original prices on close
  // originalWholesalePrice.value = null;
  // originalRetailPrice.value = null;
};
</script>