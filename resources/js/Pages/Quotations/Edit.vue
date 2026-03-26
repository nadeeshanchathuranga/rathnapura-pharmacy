<template>
  <Head title="Edit Quotation" />

  <AppLayout>
    <div class="min-h-screen bg-gray-50 p-6">
      <div>
        <!-- Success/Error Messages -->
        <div
          v-if="$page.props.flash?.success"
          class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800"
        >
          ✅ {{ $page.props.flash.success }}
        </div>
        <div
          v-if="$page.props.flash?.error"
          class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-800"
        >
          ❌ {{ $page.props.flash.error }}
        </div>
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
          <div>
            <div class="flex items-center gap-4 mb-2">
              <button
                @click="goToShopsTab"
                class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
              >
                ← Back
              </button>
              <h1 class="text-3xl font-bold text-black">Edit Quotations</h1>
              <!-- <Link
                :href="route('quotation.edit')"
                class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200"
              >
                📋 All Quotations
              </Link> -->
            </div>
            <p class="text-gray-400">
              Select a quotation to edit (F9: Update | F8: Clear | ESC: Focus Barcode)
            </p>
          </div>
          <div class="text-right">
            <div class="text-sm text-gray-400">Selected Quotation</div>
            <div class="text-2xl font-bold text-blue-400">
              {{ form.quotation_no || "None Selected" }}
            </div>
          </div>
        </div>

        <!-- Quotation Selector -->
        <div class="bg-white rounded-2xl p-6 shadow-md mb-6 border border-gray-200">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-end">
            <div class="lg:col-span-2">
              <label class="block text-sm font-semibold text-gray-700 mb-2"
                >📋 Select Quotation to Edit</label
              >
              <select
                v-model="selectedQuotationId"
                @change="loadQuotation"
                :disabled="isLoading"
                class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-medium disabled:opacity-50"
              >
                <option value="">-- Select a Quotation --</option>
                <option v-for="q in quotations" :key="q.id" :value="q.id">
                  {{ q.quotation_no }} - {{ q.customer?.name || "Walk-in" }} - ({{
                    page.props.currency || "Rs."
                  }}) {{ parseFloat(q.total_amount).toFixed(2) }} - {{ q.quotation_date }}
                </option>
              </select>
            </div>
            <div>
              <button
                v-if="selectedQuotationId"
                @click="loadQuotation"
                :disabled="isLoading"
                class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-[5px] transition disabled:opacity-50"
              >
                <span v-if="isLoading">⏳ Loading...</span>
                <span v-else>🔄 Reload Data</span>
              </button>
            </div>
          </div>

          <!-- Loading Indicator -->
          <div
            v-if="isLoading"
            class="mt-4 flex items-center justify-center text-gray-700"
          >
            <svg
              class="animate-spin h-5 w-5 mr-2"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              ></circle>
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              ></path>
            </svg>
            Loading quotation data...
          </div>
        </div>

        <!-- Top Row - All Controls -->
        <div v-if="form.quotation_no" class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
          <!-- Barcode Scanner -->
          <div
            class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg p-4 shadow-lg"
          >
            <label class="block text-sm font-medium text-blue-100 mb-2"
              >🔍 Scan Barcode</label
            >
            <div class="flex gap-2">
              <input
                ref="barcodeField"
                type="text"
                v-model="barcodeInput"
                @keyup.enter="addByBarcode"
                placeholder="Scan barcode..."
                class="flex-1 px-3 py-2 bg-white text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-300 font-mono"
              />
              <button
                @click="addByBarcode"
                class="px-4 bg-white hover:bg-blue-50 text-blue-700 font-semibold rounded-lg transition"
              >
                Add
              </button>
            </div>
          </div>

          <!-- Customer Information -->
          <div class="bg-white rounded-xl p-4 shadow-md border border-gray-200">
            <label class="block text-sm font-semibold text-gray-700 mb-2"
              >👤 Customer & Date</label
            >
            <div class="flex gap-2">
              <div class="relative flex-1">
                <select
                  v-model="form.customer_id"
                  class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium"
                  title="Select Customer"
                >
                  <option value="">Walk-in Customer</option>
                  <option
                    v-for="customer in customers"
                    :key="customer.id"
                    :value="customer.id"
                  >
                    {{ customer.name }}
                  </option>
                </select>
              </div>
              <input
                type="date"
                v-model="form.quotation_date"
                class="px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium"
              />
            </div>
          </div>

          <!-- Customer Type / Price -->
          <div class="bg-white rounded-xl p-4 shadow-md border border-gray-200">
            <label class="block text-sm font-semibold text-gray-700 mb-2"
              >💰 Price Type</label
            >
            <div class="flex gap-2">
              <label
                class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-3 py-2 rounded-[5px] transition-all duration-200 text-sm"
                :class="
                  form.customer_type === 'retail'
                    ? 'bg-blue-700 text-white font-semibold'
                    : 'bg-blue-100 text-blue-700 hover:bg-blue-200 font-medium'
                "
              >
                <input
                  type="radio"
                  v-model="form.customer_type"
                  value="retail"
                  @change="updateCartPrices"
                  class="sr-only"
                />
                <span>🛒</span>
                <span>Retail</span>
              </label>
              <label
                class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-3 py-2 rounded-[5px] transition-all duration-200 text-sm"
                :class="
                  form.customer_type === 'wholesale'
                    ? 'bg-blue-700 text-white font-semibold'
                    : 'bg-blue-100 text-blue-700 hover:bg-blue-200 font-medium'
                "
              >
                <input
                  type="radio"
                  v-model="form.customer_type"
                  value="wholesale"
                  @change="updateCartPrices"
                  class="sr-only"
                />
                <span>🏢</span>
                <span>Wholesale</span>
              </label>
            </div>
          </div>

          <!-- Add Products Manually -->
          <div class="bg-white rounded-xl p-4 shadow-md border border-gray-200">
            <label class="block text-sm font-semibold text-gray-700 mb-2"
              >➕ Add Products</label
            >
            <button
              @click="openProductModal"
              type="button"
              class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-[5px] transition"
            >
              🔍 Browse Products
            </button>
          </div>
        </div>

        <!-- No Quotation Selected Message -->
        <div
          v-if="!form.quotation_no"
          class="bg-white rounded-2xl p-12 shadow-md border border-gray-200 text-center"
        >
          <div class="text-6xl mb-4">📋</div>
          <h3 class="text-2xl font-bold text-gray-800 mb-2">Select a Quotation</h3>
          <p class="text-gray-600">
            Please select a quotation from the dropdown above to start editing.
          </p>
        </div>

        <div v-if="form.quotation_no" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Left Side - Cart -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Cart Items -->
            <div class="bg-white rounded-2xl p-6 shadow-md border border-gray-200">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">
                  Cart Items ({{ form.items.length }})
                </h3>
                <button
                  v-if="form.items.length > 0"
                  @click="clearCart"
                  class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-[5px] transition font-medium"
                >
                  Clear Cart (F8)
                </button>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full">
                  <thead class="border-b-2 border-blue-600">
                    <tr>
                      <th class="px-4 py-3 text-left text-sm font-semibold text-blue-700">
                        Product
                      </th>
                      <th
                        class="px-4 py-3 text-right text-sm font-semibold text-blue-700"
                      >
                        Price
                      </th>
                      <th
                        class="px-4 py-3 text-center text-sm font-semibold text-blue-700"
                      >
                        Qty
                      </th>
                      <th
                        class="px-4 py-3 text-right text-sm font-semibold text-blue-700"
                      >
                        Total
                      </th>
                      <th
                        class="px-4 py-3 text-center text-sm font-semibold text-blue-700"
                      >
                        Action
                      </th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <tr
                      v-for="(item, index) in form.items"
                      :key="index"
                      class="text-gray-700 hover:bg-gray-50 transition"
                    >
                      <td class="px-4 py-3 font-medium">{{ item.product_name }}</td>
                      <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                          <span class="text-sm text-gray-400"
                            >({{ page.props.currency || "Rs." }})</span
                          >
                          <input
                            type="number"
                            :value="item.price"
                            @input="updateItemPrice(index, $event.target.value)"
                            step="0.01"
                            min="0"
                            class="w-24 px-2 py-1 bg-white text-gray-800 border border-gray-300 rounded font-semibold focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-right"
                            disabled
                          />
                        </div>
                      </td>
                      <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                          <button
                            @click="updateQuantity(index, item.quantity - 1)"
                            class="w-7 h-7 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded font-bold transition"
                          >
                            -
                          </button>
                          <input
                            type="number"
                            :value="item.quantity"
                            @input="
                              updateQuantity(index, parseInt($event.target.value) || 1)
                            "
                            class="w-16 text-center font-semibold bg-white text-gray-800 border border-gray-300 rounded px-2 py-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            min="1"
                          />
                          <button
                            @click="updateQuantity(index, item.quantity + 1)"
                            class="w-7 h-7 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded font-bold transition"
                          >
                            +
                          </button>
                        </div>
                      </td>
                      <td class="px-4 py-3 text-right font-semibold text-green-600">
                        ({{ page.props.currency || "Rs." }})
                        {{ (item.price * item.quantity).toFixed(2) }}
                      </td>
                      <td class="px-6 py-3 text-center">
                        <button
                          @click="removeItem(index)"
                          class="px-6 text-white bg-red-600 hover:bg-red-700 rounded p-1 transition text-l"
                        >
                          Delete
                        </button>
                      </td>
                    </tr>
                    <tr v-if="form.items.length === 0">
                      <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                        No items in cart - Scan barcode or select product to add
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Right Side - Bill Summary -->
          <div class="lg:col-span-1">
            <div
              class="bg-white rounded-2xl p-6 shadow-md border border-gray-200 sticky top-6"
            >
              <h3 class="text-lg font-semibold text-gray-800 mb-6">Bill Summary</h3>

              <!-- Calculations -->
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-400 mb-2"
                    >Discount ({{ page.props.currency || "Rs." }})</label
                  >
                  <input
                    type="number"
                    v-model.number="form.discount"
                    min="0"
                    :max="totalAmount"
                    class="w-[100px] px-4 py-2 text-black rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="0.00"
                  />
                </div>

                <div class="pt-4 border-t-2 border-gray-700">
                  <div class="flex justify-between text-black text-lg mb-4">
                    <span>Subtotal:</span>
                    <span class="font-semibold"
                      >({{ page.props.currency || "Rs." }})
                      {{ totalAmount.toFixed(2) }}</span
                    >
                  </div>
                  <div class="flex justify-between text-black text-lg mb-4">
                    <span>Discount:</span>
                    <span class="font-semibold text-red-400"
                      >-({{ page.props.currency || "Rs." }})
                      {{ (Number(form.discount) || 0).toFixed(2) }}</span
                    >
                  </div>
                  <div
                    class="flex justify-between text-dark text-xl font-bold border-t border-gray-700 pt-2"
                  >
                    <span>Total:</span>
                    <span class="text-black"
                      >({{ page.props.currency || "Rs." }})
                      {{ netAmount.toFixed(2) }}</span
                    >
                  </div>
                </div>
              </div>

              <!-- Buttons Section -->
              <div class="mt-6 space-y-4">
                <!-- Primary Action -->
                <button
                  @click="updateQuotation"
                  :disabled="form.items.length === 0 || form.processing"
                  class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed text-white font-bold py-4 px-6 rounded-xl transition-all duration-200 text-lg shadow-lg hover:shadow-xl transform hover:scale-[1.02]"
                >
                  <span
                    v-if="form.processing"
                    class="flex items-center justify-center gap-2"
                  >
                    <svg
                      class="animate-spin h-5 w-5"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="none"
                      viewBox="0 0 24 24"
                    >
                      <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                      ></circle>
                      <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                      ></path>
                    </svg>
                    Updating...
                  </span>
                  <span v-else class="flex items-center justify-center gap-2">
                    <svg
                      class="w-5 h-5"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7"
                      ></path>
                    </svg>
                    Update Quotation
                    <span class="text-xs opacity-75">(F9)</span>
                  </span>
                </button>

                <!-- Secondary Actions -->
                <div class="grid grid-cols-2 gap-3">
                  <button
                    @click="printQuotation"
                    :disabled="form.items.length === 0"
                    class="flex flex-col items-center justify-center gap-0.5 bg-white hover:bg-green-50 disabled:bg-gray-100 disabled:cursor-not-allowed text-green-600 hover:text-green-700 disabled:text-gray-400 font-semibold py-1.5 px-3 rounded-xl transition-all duration-200 border-2 border-green-600 hover:border-green-700 disabled:border-gray-300 hover:shadow-md"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                      ></path>
                    </svg>
                    <span class="text-xs">Print</span>
                  </button>
                  <!-- <button
                    @click="deleteQuotation"
                    :disabled="form.processing"
                    class="flex flex-col items-center justify-center gap-0.5 bg-white hover:bg-red-50 disabled:bg-gray-100 disabled:cursor-not-allowed text-red-600 hover:text-red-700 disabled:text-gray-400 font-semibold py-1.5 px-3 rounded-xl transition-all duration-200 border-2 border-red-600 hover:border-red-700 disabled:border-gray-300 hover:shadow-md"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                      ></path>
                    </svg>
                    <span class="text-xs">Delete</span>
                  </button> -->
                </div>
              </div>

              <!-- Quick Actions -->
              <div class="mt-4 text-xs text-gray-400 text-center">
                <p>Keyboard Shortcuts:</p>
                <p>F9: Update Quotation | F8: Clear Cart | ESC: Focus Barcode</p>
              </div>
            </div>


            <!-- Product Selection Modal -->
    <Modal :show="showProductModal" @close="closeProductModal" max-width="6xl">
      <div class="bg-white">
        <!-- Modal Header -->
        <div class="bg-white border-b-2 border-blue-600 p-6">
          <div class="flex justify-between items-center">
            <div>
              <h2 class="text-2xl font-bold text-gray-800">🔍 Browse Products</h2>
              <p class="text-gray-600 text-sm mt-1">
                Click products to add to cart • {{ form.items.length }} items in cart
              </p>
            </div>
            <button
              @click="closeProductModal"
              class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-[5px] transition"
            >
              Done
            </button>
          </div>
        </div>

        <!-- Filters -->
        <div class="p-6 bg-gray-50 border-b border-gray-200">
          <!-- Search Input -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">🔍 Search Products</label>
            <input
              type="text"
              v-model="productFilters.search"
              @input="filterProducts"
              placeholder="Search by product name..."
              class="w-full px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
              <select
                v-model="productFilters.brand_id"
                @change="filterProducts"
                class="w-full px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">All Brands</option>
                <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                  {{ brand.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
              <select
                v-model="productFilters.category_id"
                @change="filterProducts"
                class="w-full px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">All Categories</option>
                <option
                  v-for="category in categories"
                  :key="category.id"
                  :value="category.id"
                >
                  {{ category.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
              <select
                v-model="productFilters.type_id"
                @change="filterProducts"
                class="w-full px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">All Types</option>
                <option v-for="type in types" :key="type.id" :value="type.id">
                  {{ type.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Discount</label>
              <select
                v-model="productFilters.discount_id"
                @change="filterProducts"
                class="w-full px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">All Discounts</option>
                <option
                  v-for="discount in discounts"
                  :key="discount.id"
                  :value="discount.id"
                >
                  {{ discount.name }} ({{ discount.percentage }}%)
                </option>
              </select>
            </div>
          </div>
          <button
            @click="clearFilters"
            class="mt-3 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-[5px] transition font-medium"
          >
            Clear Filters
          </button>
        </div>

        <!-- Products Grid -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-280px)] bg-white">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
              v-for="product in paginatedProducts"
              :key="product.id"
              class="bg-white border border-gray-200 rounded-lg overflow-hidden transition-all relative hover:shadow-md"
              :class="{
                'opacity-50 cursor-not-allowed': isOutOfStock(product),
                'ring-2 ring-blue-500 shadow-md': isProductInCart(product.id) && !isOutOfStock(product),
              }"
            >
              <!-- Out of Stock Badge -->
              <div
                v-if="isOutOfStock(product)"
                class="absolute top-2 left-2 bg-gray-700 text-white text-xs font-bold px-2 py-1 rounded-full z-10 flex items-center gap-1"
              >
                ⛔ Out of Stock
              </div>
              <!-- Low Stock Badge -->
              <div
                v-else-if="isLowStock(product)"
                class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full z-10 flex items-center gap-1"
              >
                🔒 Low Stock
              </div>
              <!-- Added to Cart Badge -->
              <div
                v-if="isProductInCart(product.id) && !isOutOfStock(product)"
                class="absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full z-10 flex items-center gap-1"
              >
                ✓ {{ getProductCartQuantity(product.id) }}
              </div>
              <div
                class="aspect-square bg-gray-100 flex items-center justify-center overflow-hidden"
              >
                <img
                  v-if="product.image"
                  :src="'/storage/' + product.image"
                  :alt="product.name"
                  class="w-full h-full object-cover"
                  @error="$event.target.src = '/storage/products/default.png'"
                />
                <span v-else class="text-6xl">📦</span>
              </div>
              <div class="p-3">
                <h3
                  class="text-gray-800 font-semibold text-sm mb-2 truncate"
                  :title="product.name"
                >
                  {{ product.name }}
                </h3>
                <div class="space-y-2 text-xs text-gray-700">
                  <div>
                    <label class="block font-medium text-gray-600 mb-1">💚 Retail Price:</label>
                    <input
                      type="number"
                      :value="productPriceOverrides[product.id]?.retail_price || parseFloat(product.retail_price)"
                      @input="e => updateProductPrice(product.id, 'retail_price', parseFloat(e.target.value))"
                      step="0.01"
                      min="0"
                      class="w-full px-2 py-1 bg-white text-gray-800 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500"
                      @click.stop
                    />
                  </div>
                  <div>
                    <label class="block font-medium text-gray-600 mb-1">💙 Wholesale Price:</label>
                    <input
                      type="number"
                      :value="productPriceOverrides[product.id]?.wholesale_price || parseFloat(product.wholesale_price)"
                      @input="e => updateProductPrice(product.id, 'wholesale_price', parseFloat(e.target.value))"
                      step="0.01"
                      min="0"
                      class="w-full px-2 py-1 bg-white text-gray-800 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      @click.stop
                    />
                  </div>
                  <div class="flex justify-between mt-2 pt-2 border-t border-gray-200">
                    <span>Stock:</span>
                    <span
                      class="font-semibold"
                      :class="
                        isLowStock(product)
                          ? 'text-red-600'
                          : getShopQuantity(product) > 10
                          ? 'text-green-600'
                          : 'text-yellow-600'
                      "
                    >
                      {{ getShopQuantity(product) }}
                      <span v-if="isLowStock(product)" class="text-[10px]"> (Low)</span>
                    </span>
                  </div>
                </div>

                <!-- Quantity Input -->
                <div
                  v-if="!isOutOfStock(product)"
                  class="mt-3 pt-3 border-t border-gray-200"
                >
                  <div class="flex items-center gap-2">
                    <input
                      type="number"
                      v-model.number="productQuantities[product.id]"
                      min="1"
                      :max="getShopQuantity(product)"
                      class="flex-1 px-2 py-1 bg-white text-gray-800 border border-gray-300 text-center rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      @click.stop
                    />
                    <button
                      @click.stop="selectProductFromModal(product)"
                      class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded transition"
                    >
                      Add
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- No products message -->
          <div v-if="filteredProducts.length === 0" class="text-center py-12">
            <div class="text-6xl mb-4">📭</div>
            <p class="text-gray-500 text-lg">No products found</p>
          </div>
        </div>

        <!-- Pagination -->
        <div
          v-if="filteredProducts.length > 0"
          class="p-6 bg-blue-50 border-t border-gray-200"
        >
          <div class="flex justify-between items-center">
            <div class="text-gray-700 text-sm font-medium">
              Showing {{ startIndex + 1 }} to
              {{ Math.min(endIndex, filteredProducts.length) }} of
              {{ filteredProducts.length }} products
            </div>
            <div class="flex gap-2">
              <button
                @click="prevPage"
                :disabled="currentPage === 1"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-[5px] transition font-semibold"
              >
                ← Previous
              </button>
              <div
                class="flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-800 rounded-[5px]"
              >
                <span class="font-semibold">{{ currentPage }} / {{ totalPages }}</span>
              </div>
              <button
                @click="nextPage"
                :disabled="currentPage === totalPages"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-[5px] transition font-semibold"
              >
                Next →
              </button>
            </div>
          </div>
        </div>
      </div>
    </Modal>
          </div>
        </div>
      </div>
    </div>


  </AppLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router, usePage, Link } from "@inertiajs/vue3";
const page = usePage();
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import Modal from "@/Components/Modal.vue";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const { goToShopsTab } = useDashboardNavigation();

const props = defineProps({
  quotation: Object,
  quotations: Array,
  customers: Array,
  products: Array,
  brands: Array,
  categories: Array,
  types: Array,
  discounts: Array,
  billSetting: Object,
  currencySymbol: Object,
});

// Selected quotation ID for dropdown
const selectedQuotationId = ref(props.quotation?.id || "");
const isLoading = ref(false);
const autoLoadTriggered = ref(false);

const normalizeDateForInput = (value) => {
  if (!value) return new Date().toISOString().split("T")[0];

  if (typeof value === "string") {
    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) return value;
    if (value.includes("T")) return value.split("T")[0];
    if (value.includes(" ")) return value.split(" ")[0];
  }

  const parsedDate = new Date(value);
  if (Number.isNaN(parsedDate.getTime())) {
    return new Date().toISOString().split("T")[0];
  }

  return parsedDate.toISOString().split("T")[0];
};

const cloneQuotationItems = (items = []) => {
  return items.map((item) => ({
    ...item,
    product_id: item.product_id,
    product_name: item.product_name || item.product?.name || "Unknown Product",
    quantity: Number(item.quantity) > 0 ? Number(item.quantity) : 1,
    price: Number(item.price) || 0,
    total: Number(item.total) || (Number(item.quantity) || 0) * (Number(item.price) || 0),
  }));
};

const hydrateFormFromQuotation = (quotation) => {
  if (!quotation) return;

  selectedQuotationId.value = quotation.id || "";
  form.quotation_no = quotation.quotation_no || "";
  form.customer_id = quotation.customer_id || "";
  form.customer_type = quotation.customer_type || "retail";
  form.quotation_date = normalizeDateForInput(quotation.quotation_date);
  form.items = cloneQuotationItems(quotation.items || []);
  form.discount = Number(quotation.discount) || 0;
};

const resetQuotationForm = () => {
  form.quotation_no = "";
  form.customer_id = "";
  form.customer_type = "retail";
  form.quotation_date = new Date().toISOString().split("T")[0];
  form.items = [];
  form.discount = 0;
};

const form = useForm({
  quotation_no: props.quotation?.quotation_no || "",
  customer_id: props.quotation?.customer_id || "",
  customer_type: props.quotation?.customer_type || "retail",
  quotation_date: normalizeDateForInput(props.quotation?.quotation_date),
  items: cloneQuotationItems(props.quotation?.items || []),
  discount: props.quotation?.discount || 0,
});

const bill = props.billSetting || {};
const companyDetails = computed(() => {
  const info = page.props.companyInfo || {};
  const fallbackPhone = [bill.mobile_1, bill.mobile_2].filter(Boolean).join(" / ");

  return {
    logo: info.logo || bill.logo_path || "",
    name: info.company_name || bill.company_name || "",
    address: info.address || bill.address || "",
    phone: info.phone || fallbackPhone,
    email: info.email || bill.email || "",
    website: info.website || bill.website_url || "",
  };
});

// Load quotation data when selection changes - fetch via API and populate fields
const loadQuotation = async () => {
  if (!selectedQuotationId.value) {
    // Reset form if no quotation selected
    resetQuotationForm();
    return;
  }

  isLoading.value = true;

  try {
    // Use Inertia router to navigate with data reload
    router.visit(route("quotations.edit", selectedQuotationId.value), {
      preserveState: false,
      preserveScroll: true,
      onSuccess: () => {
        isLoading.value = false;
        // Focus barcode field after successful load
        setTimeout(() => {
          barcodeField.value?.focus();
        }, 100);
      },
      onError: () => {
        isLoading.value = false;
        alert("Failed to load quotation data");
      },
    });
  } catch (error) {
    isLoading.value = false;
    console.error("Error loading quotation:", error);
    alert("Failed to load quotation data");
  }
};

// Watch for quotation prop changes (when navigating to a new quotation)
watch(
  () => props.quotation,
  (newQuotation) => {
    if (newQuotation) {
      hydrateFormFromQuotation(newQuotation);
    } else {
      resetQuotationForm();
    }
  },
  { immediate: true }
);

const barcodeInput = ref("");
const barcodeField = ref(null);
const showProductModal = ref(false);

// Product modal filters and pagination
const productFilters = ref({
  search: "",
  brand_id: "",
  category_id: "",
  type_id: "",
  discount_id: "",
});

const filteredProducts = ref([]);
const currentPage = ref(1);
const itemsPerPage = ref(8);
const productQuantities = ref({});
const productPriceOverrides = ref({}); // Track custom prices for products

// Calculations
const totalAmount = computed(() => {
  return form.items.reduce((sum, item) => sum + item.price * item.quantity, 0);
});

const netAmount = computed(() => {
  return totalAmount.value - (Number(form.discount) || 0);
});

// Product modal pagination computed properties
const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return filteredProducts.value.slice(start, end);
});

const totalPages = computed(() => {
  return Math.ceil(filteredProducts.value.length / itemsPerPage.value);
});

// Stock helpers
const isOutOfStock = (product) => {
  return Number(getShopQuantity(product)) === 0;
};

const isLowStock = (product) => {
  if (isOutOfStock(product)) return false;
  const lowMargin = product?.shop_low_stock_margin ?? product?.shop_low_stock ?? 0;
  return Number(getShopQuantity(product)) <= Number(lowMargin);
};

// Pagination range helpers for display in modal
const startIndex = computed(() => {
  return (currentPage.value - 1) * itemsPerPage.value;
});

const endIndex = computed(() => {
  return startIndex.value + itemsPerPage.value;
});

// Get current price based on customer type (with overrides)
const getCurrentPrice = (product) => {
  const override = productPriceOverrides.value[product.id];
  if (form.customer_type === "wholesale") {
    return override ? override.wholesale_price : parseFloat(product.wholesale_price);
  } else {
    return override ? override.retail_price : parseFloat(product.retail_price);
  }
};

// Update product price override
const updateProductPrice = (productId, priceType, value) => {
  if (!productPriceOverrides.value[productId]) {
    const product = props.products.find(p => p.id === productId);
    productPriceOverrides.value[productId] = {
      retail_price: parseFloat(product.retail_price),
      wholesale_price: parseFloat(product.wholesale_price),
    };
  }
  productPriceOverrides.value[productId][priceType] = value || 0;
};

// Get product shop quantity with fallback
const getShopQuantity = (product) => {
  return product.shop_quantity_in_sales_unit || product.shop_quantity || 0;
};

// Add product by barcode
const addByBarcode = () => {
  if (!barcodeInput.value.trim()) return;

  const product = props.products.find((p) => p.barcode === barcodeInput.value.trim());

  if (product) {
    if (isOutOfStock(product)) {
      alert("Product is out of stock");
      barcodeInput.value = "";
      barcodeField.value?.focus();
      return;
    }

    const existingIndex = form.items.findIndex((item) => item.product_id === product.id);
    const price = getCurrentPrice(product);
    const availableStock = getShopQuantity(product);

    if (existingIndex !== -1) {
      const newQuantity = form.items[existingIndex].quantity + 1;
      if (newQuantity > availableStock) {
        alert(`Cannot add more. Available stock: ${availableStock}, Current cart: ${form.items[existingIndex].quantity}`);
        barcodeInput.value = "";
        barcodeField.value?.focus();
        return;
      }
      form.items[existingIndex].quantity = newQuantity;
    } else {
      if (availableStock < 1) {
        alert("Product is out of stock");
        barcodeInput.value = "";
        barcodeField.value?.focus();
        return;
      }
      form.items.push({
        product_id: product.id,
        product_name: product.name,
        price: parseFloat(price),
        quantity: 1,
      });
    }

    barcodeInput.value = "";
    barcodeField.value?.focus();
  } else {
    alert("Product not found with barcode: " + barcodeInput.value);
    barcodeInput.value = "";
    barcodeField.value?.focus();
  }
};

// Remove item from cart
const removeItem = (index) => {
  form.items.splice(index, 1);
  barcodeField.value?.focus();
};

// Update quantity in cart
const updateQuantity = (index, newQty) => {
  if (newQty > 0) {
    form.items[index].quantity = newQty;
  } else {
    removeItem(index);
  }
};

// Update item price in cart
const updateItemPrice = (index, newPrice) => {
  const price = parseFloat(newPrice) || 0;
  if (price < 0) {
    form.items[index].price = 0;
  } else {
    form.items[index].price = price;
  }
};

// Clear cart
const clearCart = () => {
  if (confirm("Are you sure you want to clear the cart?")) {
    form.items = [];
    form.discount = 0;
    barcodeField.value?.focus();
  }
};

// Product modal methods
const openProductModal = () => {
  showProductModal.value = true;
  filterProducts();
  // Initialize all product quantities to 1 and price overrides
  props.products.forEach((product) => {
    if (!productQuantities.value[product.id]) {
      productQuantities.value[product.id] = 1;
    }
    // Initialize price overrides with original prices
    if (!productPriceOverrides.value[product.id]) {
      productPriceOverrides.value[product.id] = {
        retail_price: parseFloat(product.retail_price),
        wholesale_price: parseFloat(product.wholesale_price),
      };
    }
  });
};

const closeProductModal = () => {
  showProductModal.value = false;
  barcodeField.value?.focus();
};

const filterProducts = () => {
  let filtered = [...props.products];

  // Search filter
  if (productFilters.value.search.trim()) {
    const searchTerm = productFilters.value.search.toLowerCase().trim();
    filtered = filtered.filter((p) =>
      p.name.toLowerCase().includes(searchTerm) ||
      (p.barcode && p.barcode.toLowerCase().includes(searchTerm))
    );
  }

  if (productFilters.value.brand_id) {
    filtered = filtered.filter((p) => p.brand_id == productFilters.value.brand_id);
  }
  if (productFilters.value.category_id) {
    filtered = filtered.filter((p) => p.category_id == productFilters.value.category_id);
  }
  if (productFilters.value.type_id) {
    filtered = filtered.filter((p) => p.type_id == productFilters.value.type_id);
  }
  if (productFilters.value.discount_id) {
    filtered = filtered.filter((p) => p.discount_id == productFilters.value.discount_id);
  }

  filteredProducts.value = filtered;
  currentPage.value = 1;
};

const clearFilters = () => {
  productFilters.value = {
    search: "",
    brand_id: "",
    category_id: "",
    type_id: "",
    discount_id: "",
  };
  filterProducts();
};

const selectProductFromModal = (product) => {
  const quantity = productQuantities.value[product.id] || 1;

  if (quantity <= 0 || !Number.isInteger(quantity)) {
    alert("Please enter a valid quantity (positive integer)");
    productQuantities.value[product.id] = 1;
    return;
  }

  if (isOutOfStock(product)) {
    alert("This product is out of stock");
    return;
  }

  // Check if requested quantity exceeds available stock
  const availableStock = getShopQuantity(product);
  if (quantity > availableStock) {
    alert(`Only ${availableStock} units available in stock`);
    productQuantities.value[product.id] = Math.min(availableStock, 1);
    return;
  }

  const existingIndex = form.items.findIndex((item) => item.product_id === product.id);
  const price = getCurrentPrice(product); // Uses override if available

  if (existingIndex !== -1) {
    const newTotalQuantity = form.items[existingIndex].quantity + quantity;
    if (newTotalQuantity > availableStock) {
      alert(`Cannot add ${quantity} more. Current cart quantity: ${form.items[existingIndex].quantity}. Available: ${availableStock}`);
      return;
    }
    form.items[existingIndex].quantity = newTotalQuantity;
    form.items[existingIndex].price = parseFloat(price);
  } else {
    form.items.push({
      product_id: product.id,
      product_name: product.name,
      price: parseFloat(price),
      quantity: quantity,
    });
  }

  productQuantities.value[product.id] = 1;
};

// Check if product is in cart
const isProductInCart = (productId) => {
  return form.items.some((item) => item.product_id === productId);
};

// Get product quantity in cart
const getProductCartQuantity = (productId) => {
  const item = form.items.find((item) => item.product_id === productId);
  return item ? item.quantity : 0;
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
  }
};

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
  }
};

const updateCartPrices = () => {
  form.items.forEach((item) => {
    const product = props.products.find((p) => p.id === item.product_id);
    if (product) {
      item.price = parseFloat(getCurrentPrice(product));
    }
  });
};

// Print quotation after update
const printQuotationAfterUpdate = () => {
  try {
    const printWindow = window.open("", "_blank");
    if (!printWindow) {
      console.warn("Print window blocked. Print unavailable.");
      return;
    }

    const billSetting = props.billSetting || {};
    const company = companyDetails.value;
    const rawSize = (billSetting.print_size || "80mm").toString();
    const width = rawSize.includes("58") ? "58mm" : "80mm";
    const customerName =
      props.customers.find((c) => c.id === form.customer_id)?.name || "Walk-in Customer";
    const currency = page.props.currency || "Rs.";
    const footerMessage =
      billSetting.footer_description || "Thank you for your business!";

    const quotationContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Quotation - ${form.quotation_no}</title>
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body { font-family: Arial, sans-serif; font-size: 12px; }
                    .quotation-container { width: ${width}; padding: 10px; margin: 0 auto; }
                    .header { text-align: center; margin-bottom: 10px; }
                    .header h1 { font-size: 16px; font-weight: bold; margin-bottom: 2px; }
                    .document-type { text-align: center; font-size: 14px; font-weight: bold; margin: 8px 0; border: 1px solid #000; padding: 3px; }
                    .company-info { text-align: center; font-size: 10px; margin-bottom: 8px; }
                    .info-section { margin: 8px 0; font-size: 11px; }
                    .info-row { display: flex; justify-content: space-between; margin-bottom: 3px; }
                    .divider { border-bottom: 1px dashed #000; margin: 8px 0; }
                    table { width: 100%; font-size: 11px; border-collapse: collapse; }
                    th, td { padding: 4px 2px; text-align: left; }
                    th { border-bottom: 1px solid #000; font-weight: bold; }
                    .text-right { text-align: right; }
                    .text-center { text-align: center; }
                    .totals { margin-top: 10px; font-size: 11px; }
                    .total-row { display: flex; justify-content: space-between; margin-bottom: 3px; }
                    .total-row.grand { font-size: 14px; font-weight: bold; border-top: 1px solid #000; padding-top: 5px; margin-top: 5px; }
                    .footer { text-align: center; font-size: 10px; margin-top: 15px; padding-top: 10px; border-top: 1px dashed #000; }
                    @media print {
                        body { margin: 0; padding: 0; }
                        .quotation-container { width: 100%; }
                    }
                </style>
            </head>
            <body>
                <div class="quotation-container">
                    <div class="header">
                  ${company.logo
                    ? `<div style="margin-bottom:6px;"><img src="/storage/${company.logo}" alt="logo" style="max-height:40px; max-width:100%; object-fit:contain;"/></div>`
                    : ""}
                  <h1>${company.name || "QUOTATION"}</h1>
                    </div>
                    <div class="document-type">QUOTATION (Updated)</div>
                    <div class="company-info">
                  ${company.address ? `<p>${company.address}</p>` : ""}
                  ${company.phone ? `<p>Tel: ${company.phone}</p>` : ""}
                  ${company.email ? `<p>${company.email}</p>` : ""}
                  ${company.website ? `<p>${company.website}</p>` : ""}
                    </div>

                    <div class="info-section">
                        <div class="info-row">
                            <span><strong>No:</strong></span>
                            <span>${form.quotation_no}</span>
                        </div>
                        <div class="info-row">
                            <span><strong>Date:</strong></span>
                            <span>${form.quotation_date}</span>
                        </div>
                        <div class="info-row">
                            <span><strong>Customer:</strong></span>
                            <span>${customerName}</span>
                        </div>
                        <div class="info-row">
                            <span><strong>Type:</strong></span>
                            <span>${
                              form.customer_type === "wholesale" ? "Wholesale" : "Retail"
                            }</span>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <table>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Price</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${form.items
                              .map(
                                (item) => `
                                <tr>
                                    <td>${item.product_name}</td>
                                    <td class="text-center">${item.quantity}</td>
                                    <td class="text-right">${parseFloat(
                                      item.price
                                    ).toFixed(2)}</td>
                                    <td class="text-right">${(
                                      item.price * item.quantity
                                    ).toFixed(2)}</td>
                                </tr>
                            `
                              )
                              .join("")}
                        </tbody>
                    </table>

                    <div class="divider"></div>

                    <div class="totals">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <span>${currency} ${totalAmount.value.toFixed(2)}</span>
                        </div>
                        <div class="total-row">
                            <span>Discount:</span>
                            <span>- ${currency} ${(Number(form.discount) || 0).toFixed(
      2
    )}</span>
                        </div>
                        <div class="total-row grand">
                            <span>TOTAL:</span>
                            <span>${currency} ${netAmount.value.toFixed(2)}</span>
                        </div>
                    </div>

                    <div class="footer">
                        <p>${footerMessage}</p>
                        <p style="margin-top: 5px; font-size: 9px;">This is a quotation, not a tax invoice.</p>
                    </div>
                </div>

                <script>
                    window.onload = function() {
                        setTimeout(function() {
                            window.print();
                        }, 300);
                    };
                    window.onafterprint = function() {
                        setTimeout(function() {
                            window.close();
                        }, 200);
                    };
                <\/script>
            </body>
            </html>
        `;

    printWindow.document.write(quotationContent);
    printWindow.document.close();
  } catch (error) {
    console.error("Print error:", error);
  }
};

// Print quotation (without updating)
const printQuotation = () => {
  if (form.items.length === 0) {
    alert("Please add items to cart");
    return;
  }
  printQuotationAfterUpdate();
};

// Delete quotation
const deleteQuotation = () => {
  if (!selectedQuotationId.value) {
    alert("No quotation selected");
    return;
  }

  if (
    confirm(
      "Are you sure you want to delete this quotation? This action cannot be undone."
    )
  ) {
    router.delete(route("quotations.destroy", selectedQuotationId.value), {
      onSuccess: () => {
        alert("Quotation deleted successfully!");
        router.visit(route("quotations.index"));
      },
      onError: (errors) => {
        console.error("Delete error:", errors);
        alert("Failed to delete quotation");
      },
    });
  }
};

// Update quotation
const updateQuotation = async () => {
  if (form.items.length === 0) {
    alert("Please add items to cart");
    return;
  }

  if (!selectedQuotationId.value) {
    alert("No quotation selected");
    return;
  }

  // Validate all required fields
  if (!form.quotation_date) {
    alert("Please select a quotation date");
    return;
  }

  if (!form.customer_type) {
    alert("Please select customer type (retail/wholesale)");
    return;
  }

  form.put(route("quotations.update", selectedQuotationId.value), {
    preserveScroll: true,
    onSuccess: (page) => {
      // Print quotation after successful update
      setTimeout(() => {
        printQuotationAfterUpdate();
      }, 500);
      // Show success message from server flash
      // alert("Quotation updated successfully!");
    },
    onError: (errors) => {
      console.error("Update error:", errors);
      let errorMsg = "Update failed. Please check your data and try again.";

      if (errors.items && Array.isArray(errors.items)) {
        errorMsg = "Items validation failed: " + errors.items.join(", ");
      } else if (errors.items) {
        errorMsg = errors.items;
      } else if (errors.customer_id) {
        errorMsg = "Invalid customer selected";
      } else if (errors.quotation_date) {
        errorMsg = "Invalid quotation date";
      } else if (Object.keys(errors).length > 0) {
        errorMsg = "Validation error: " + Object.values(errors)[0];
      }

      alert(errorMsg);
    },
  });
};

// Keyboard shortcuts
const handleKeyboard = (event) => {
  // F9 - Update quotation
  if (event.key === "F9") {
    event.preventDefault();
    updateQuotation();
  }
  // F8 - Clear cart
  if (event.key === "F8") {
    event.preventDefault();
    clearCart();
  }
  // ESC - Focus barcode
  if (event.key === "Escape") {
    event.preventDefault();
    barcodeField.value?.focus();
  }
};

// Auto-load quotation if passed from index page
onMounted(() => {
  window.addEventListener("keydown", handleKeyboard);
  filterProducts();

  // Initialize product quantities
  props.products.forEach((product) => {
    productQuantities.value[product.id] = 1;
  });

  // Auto-load if quotation is already in props (from route)
  if (props.quotation?.id && !autoLoadTriggered.value) {
    autoLoadTriggered.value = true;
    // Focus barcode field when quotation is loaded
    setTimeout(() => {
      barcodeField.value?.focus();
    }, 300);
  }
});

onUnmounted(() => {
  window.removeEventListener("keydown", handleKeyboard);
});
</script>
