<template>
  <Head title="New Quotation" />

  <AppLayout>
    <div class="min-h-screen bg-gray-50 p-6">
      <div>
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
              <h1 class="text-3xl font-bold text-black">Quotations</h1>
            </div>
            <p class="text-gray-400">
              Create new quotation (F9: Complete | F8: Clear | ESC: Focus Barcode)
            </p>
          </div>
          <div class="text-right">
            <div class="text-sm text-gray-400">Quotation No.</div>
            <div class="text-2xl font-bold text-blue-400">{{ quotation_no }}</div>
          </div>
        </div>

        <!-- Top Row - All Controls -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
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
                autofocus
              />
              <button
                @click="addByBarcode"
                type="button"
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
                  class="no-arrow w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm pr-12 font-medium"
                  title="Select Customer"
                >
                  <option value="">-- Select Customer --</option>
                  <option
                    v-for="customer in activeCustomers"
                    :key="customer.id"
                    :value="customer.id"
                  >
                    {{ customer.name }}
                  </option>
                </select>
                <button
                  type="button"
                  @click="openCustomerModal"
                  class="absolute right-2 top-1/2 transform -translate-y-1/2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-full p-1 transition"
                  title="Add New Customer"
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
              <input
                type="date"
                v-model="form.sale_date"
                class="px-4 py-2.5 bg-gray-100 text-gray-800 border border-gray-300 rounded-[5px] text-sm font-medium"
                readonly
                tabindex="-1"
                @keydown.prevent
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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
                            @input="
                              form.items[index].price =
                                parseFloat($event.target.value) || 0
                            "
                            step="0.01"
                            disabled
                            min="0"
                            class="w-24 px-2 py-1 bg-white text-gray-800 border border-gray-300 rounded font-semibold focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-right"
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
                      <td class="px-4 py-3 text-center">
                        <button
                          @click="removeItem(index)"
                          class="text-red-600 hover:text-red-700 hover:bg-red-50 rounded p-1 transition text-xl"
                        >
                          🗑️
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
                    <span class="text-blue-400"
                      >({{ page.props.currency || "Rs." }})
                      {{ netAmount.toFixed(2) }}</span
                    >
                  </div>
                </div>

                <!-- Multiple Payments List -->
                <div
                  v-if="form.payments.length > 0"
                  class="bg-gray-700 rounded-lg p-3 mt-4"
                >
                  <h4 class="text-sm font-semibold text-white mb-2">Payments Added:</h4>
                  <div
                    v-for="(payment, idx) in form.payments"
                    :key="idx"
                    class="flex justify-between text-xs text-gray-300 mb-1"
                  >
                    <span class="flex items-center gap-1">
                      ({{ page.props.currency || "Rs." }}) {{ payment.amount.toFixed(2) }}
                      <button
                        @click="removePayment(idx)"
                        class="text-red-400 hover:text-red-300 ml-1"
                      >
                        ✕
                      </button>
                    </span>
                  </div>
                  <div
                    class="mt-2 pt-2 border-t border-gray-600 flex justify-between text-sm"
                  >
                    <span class="text-gray-300">Total Paid:</span>
                    <span class="text-green-400 font-semibold"
                      >({{ page.props.currency || "Rs." }})
                      {{ totalPaid.toFixed(2) }}</span
                    >
                  </div>
                  <div v-if="balance !== 0" class="mt-1 flex justify-between text-sm">
                    <span class="text-gray-300">Balance:</span>
                    <span
                      :class="balance > 0 ? 'text-orange-400' : 'text-green-400'"
                      class="font-semibold"
                      >{{ balance > 0 ? "+" : "" }}({{ page.props.currency || "Rs." }})
                      {{ balance.toFixed(2) }}</span
                    >
                  </div>
                </div>
              </div>

              <!-- Buttons Section -->
              <div class="mt-6 space-y-3">
                <button
                  @click="submitSale"
                  :disabled="form.items.length === 0 || form.processing"
                  class="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-600 disabled:cursor-not-allowed text-white font-bold py-4 px-4 rounded-lg transition text-lg shadow-lg"
                >
                  <span v-if="form.processing">⏳ Processing...</span>
                  <span v-else>✅ Complete Quotation (F9)</span>
                </button>
              </div>

              <!-- Quick Actions -->
              <div class="mt-4 text-xs text-gray-400 text-center">
                <p>Keyboard Shortcuts:</p>
                <p>F9: Complete Quotation | F8: Clear Cart | ESC: Focus Barcode</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Modal -->
    <Modal :show="showPaymentModal" @close="() => showPaymentModal = false" max-width="md">
      <div class="p-8 bg-white">
        <div class="mb-6">
          <h2 class="text-2xl font-bold text-gray-800 mb-2">Add Payment Method</h2>
          <p class="text-gray-600 text-sm">
            Remaining:
            <span class="text-red-600 font-semibold"
              >({{ page.props.currency || "Rs." }})
              {{ balance > 0 ? balance.toFixed(2) : "0.00" }}</span
            >
          </p>
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2"
              >Payment Method</label
            >
            <select
              v-model.number="paymentMethod"
              class="w-full px-4 py-3 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:border-blue-500"
            >
              <option :value="0">💵 Cash</option>
              <option :value="1">💳 Card</option>
              <option :value="2">📝 Credit</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2"
              >Amount ({{ page.props.currency || "Rs." }})</label
            >
            <input
              type="number"
              v-model.number="paymentAmount"
              min="0"
              :max="balance > 0 ? balance : 0"
              class="w-full px-4 py-3 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:border-blue-500 text-lg"
              placeholder="0.00"
            />
          </div>
        </div>

        <div class="flex gap-3 mt-6">
          <button
            @click="addPayment"
            class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-[5px] transition"
          >
            Add Payment
          </button>
          <button
            @click="showPaymentModal = false"
            class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-[5px] transition"
          >
            Close
          </button>
        </div>
      </div>
    </Modal>

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
              placeholder="Search by product name or barcode..."
              class="w-full px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
              <select
                v-model="productFilters.brand_id"
                @change="filterProducts"
                class="w-full px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500"
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
                class="w-full px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500"
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
                class="w-full px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500"
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
                class="w-full px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500"
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
            class="mt-3 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-[5px] transition"
          >
            Clear Filters
          </button>
        </div>

        <!-- Products Grid -->
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-280px)]">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
              v-for="product in paginatedProducts"
              :key="product.id"
              @click="!isLowStock(product) && addToCart(product)"
              class="bg-white border border-gray-200 rounded-lg overflow-hidden transition-all relative hover:shadow-md cursor-pointer"
              :class="{
                'opacity-50 cursor-not-allowed': isLowStock(product),
                'ring-2 ring-green-500':
                  isProductInCart(product.id) && !isLowStock(product),
              }"
            >
              <!-- Low Stock Badge -->
              <div
                v-if="isLowStock(product)"
                class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full z-10 flex items-center gap-1"
              >
                🔒 Low Stock
              </div>
              <!-- Added to Cart Badge -->
              <div
                v-if="isProductInCart(product.id) && !isLowStock(product)"
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
                    <label class="block font-medium text-gray-600 mb-1"> Retail Price:</label>
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
                    <label class="block font-medium text-gray-600 mb-1">Wholesale Price:</label>
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
                </div>

                <!-- Quantity Input -->
                <div
                  v-if="!isLowStock(product)"
                  class="mt-3 pt-3 border-t border-gray-200"
                >
                  <div class="flex items-center gap-2">
                    <input
                      type="number"
                      v-model.number="productQuantities[product.id]"
                      min="1"
                      :max="product.shop_quantity_in_sales_unit"
                      class="flex-1 px-2 py-1 bg-white text-gray-800 border border-gray-300 text-center rounded text-sm focus:ring-2 focus:ring-blue-500"
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
            <p class="text-gray-600 text-lg">No products found</p>
          </div>
        </div>

        <!-- Pagination -->
        <div
          v-if="filteredProducts.length > 0"
          class="p-6 bg-blue-50 border-t border-gray-200"
        >
          <div class="flex justify-between items-center">
            <div class="text-gray-700 text-sm">
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

    <!-- Payment Modal -->
    <Modal :show="showSuccessModal" @close="closeModal" max-width="md">
      <div class="p-8 bg-white">
        <div class="text-center">
          <div
            class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-black mb-4"
          >
            <svg
              class="h-12 w-12 text-white"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="3"
                d="M5 13l4 4L19 7"
              ></path>
            </svg>
          </div>
          <h2 class="text-3xl font-bold text-gray-900 mb-2">Payment Successful!</h2>
          <p class="text-gray-600 mb-6">Order Payment is Successful!</p>
          <p class="text-sm text-gray-500 mb-6">
            Invoice: <span class="font-semibold">{{ completedInvoice }}</span>
          </p>

          <div class="flex gap-3 justify-center">
            <button
              @click="printAndClose"
              class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-lg"
            >
              PRINT RECEIPT
            </button>
            <button
              @click="closeModal"
              class="px-8 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition shadow-lg"
            >
              CLOSE
            </button>
          </div>
        </div>
      </div>
    </Modal>

    <!-- Print Receipt (Hidden) -->
    <div id="printReceipt" class="hidden">
      <div class="p-4 mx-auto" :style="{ width: billWidth, fontFamily: 'monospace' }">
        <div class="text-center mb-2">
          <div v-if="companyDetails.logo" style="margin-bottom: 6px">
            <img
              :src="`/storage/${companyDetails.logo}`"
              alt="logo"
              style="max-height: 40px; max-width: 100%; object-fit: contain"
            />
          </div>
          <h1 class="text-xl font-bold">{{ companyDetails.name || "RECEIPT" }}</h1>
          <p class="text-sm">Invoice: {{ completedInvoice }}</p>
          <p class="text-sm">Date: {{ completedSaleDate }}</p>
        </div>
        <hr class="my-2 border-black" />
        <div class="mb-2 text-sm">
          <p><strong>Customer:</strong> {{ completedCustomer }}</p>
          <p v-if="companyDetails.address">{{ companyDetails.address }}</p>
          <p v-if="companyDetails.phone">Tel: {{ companyDetails.phone }}</p>
          <p v-if="companyDetails.email">{{ companyDetails.email }}</p>
          <p v-if="companyDetails.website">{{ companyDetails.website }}</p>
        </div>
        <hr class="my-2 border-black" />
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-black">
              <th class="text-left py-1">Item</th>
              <th class="text-center py-1">Qty</th>
              <th class="text-right py-1">Price</th>
              <th class="text-right py-1">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, index) in completedItems" :key="index" class="border-b">
              <td class="py-1">{{ item.product_name }}</td>
              <td class="text-center py-1">{{ item.quantity }}</td>
              <td class="text-right py-1">{{ item.price.toFixed(2) }}</td>
              <td class="text-right py-1">
                {{ (item.price * item.quantity).toFixed(2) }}
              </td>
            </tr>
          </tbody>
        </table>
        <hr class="my-2 border-black" />
        <div class="text-sm space-y-1">
          <div class="flex justify-between">
            <span>Discount:</span>
            <span>({{ page.props.currency || "Rs." }}) {{ completedDiscount }}</span>
          </div>

          <div class="flex justify-between">
            <span>Total Amount:</span>
            <span>({{ page.props.currency || "Rs." }}) {{ completedTotal }}</span>
          </div>
        </div>
        <hr class="my-4 border-black" />
        <p class="text-center text-xs">
          {{ bill.footer_description || "Thank you for your business!" }}
        </p>
      </div>
    </div>

    <!-- Customer Create Modal -->
    <CustomerCreateModal v-model:open="showQuickAddCustomer" />
  </AppLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
const page = usePage();
import { ref, computed, onMounted, watch } from "vue";
import { logActivity } from "@/composables/useActivityLog";
import Modal from "@/Components/Modal.vue";
import CustomerCreateModal from "@/Pages/Customers/Components/CustomerCreateModal.vue";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const { goToShopsTab } = useDashboardNavigation();

const props = defineProps({
  quotation_no: String,
  customers: Array,
  products: Array,
  brands: Array,
  categories: Array,
  types: Array,
  discounts: Array,
  billSetting: Object,
});

// Only show active customers (status == '1' or 1)
const activeCustomers = computed(() => {
  return props.customers.filter(
    (c) => c.status === '1' || c.status === 1
  );
});

const form = useForm({
  quotation_no: props.quotation_no,
  customer_id: "",
  customer_type: "retail", // retail or wholesale
  sale_date: new Date().toISOString().split("T")[0],
  items: [],
  discount: 0,
  payment_type: 0,
  paid_amount: 0,
  payments: [], // Multiple payments
});

watch(
  () => props.quotation_no,
  (newQuotationNo) => {
    if (newQuotationNo) {
      form.quotation_no = newQuotationNo;
    }
  },
  { immediate: true }
);

const selectedProduct = ref(null);
const selectedQuantity = ref(1);
const barcodeInput = ref("");
const barcodeField = ref(null);
const showSuccessModal = ref(false);
const showPaymentModal = ref(false);
const showProductModal = ref(false);
const showQuickAddCustomer = ref(false);
const paymentMethod = ref(0);
const paymentAmount = ref(0);
const completedInvoice = ref("");
const completedSaleDate = ref("");
const completedCustomer = ref("");
const completedItems = ref([]);
const completedTotal = ref("0.00");
const completedDiscount = ref("0.00");
const completedNetAmount = ref("0.00");
const completedPaid = ref("0.00");
const completedBalance = ref("0.00");

// Bill settings helper
const bill = props.billSetting || {};
const companyDetails = computed(() => {
  const info = page.props.companyInfo || {};
  const billSetting = props.billSetting || {};
  const fallbackPhone = [billSetting.mobile_1, billSetting.mobile_2]
    .filter(Boolean)
    .join(" / ");

  return {
    logo: info.logo || billSetting.logo_path || "",
    name: info.company_name || billSetting.company_name || "",
    address: info.address || billSetting.address || "",
    phone: info.phone || fallbackPhone,
    email: info.email || billSetting.email || "",
    website: info.website || billSetting.website_url || "",
  };
});
const billWidth = computed(() => {
  const allowed = ["58mm", "80mm", "112mm", "210mm"];
  const raw = (bill.print_size || "80mm").toString();
  return allowed.includes(raw) ? raw : "80mm";
});

// Product modal filters and pagination
const productFilters = ref({
  search: "",
  brand_id: "",
  category_id: "",
  type_id: "",
  discount_id: "",
});

const handleCustomerCreated = (customer) => {
  // Add the new customer to the dropdown immediately
  if (customer && customer.name) {
    props.customers.push({
      id: Date.now(), // Temporary ID, will be replaced on reload
      name: customer.name,
    });
    form.customer_id = props.customers[props.customers.length - 1].id;
  }
  showQuickAddCustomer.value = false;
};
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
  return totalAmount.value - form.discount;
});

const totalPaid = computed(() => {
  return form.payments.reduce((sum, payment) => sum + parseFloat(payment.amount), 0);
});

const balance = computed(() => {
  return netAmount.value - totalPaid.value;
});

const change = computed(() => {
  // Only show change if cash payment and overpaid
  const cashPaid = form.payments
    .filter((p) => p.payment_type === 0)
    .reduce((sum, p) => sum + parseFloat(p.amount), 0);
  return cashPaid > netAmount.value ? cashPaid - netAmount.value : 0;
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

// Add product by barcode
const addByBarcode = () => {
  if (!barcodeInput.value.trim()) return;

  const product = props.products.find((p) => p.barcode === barcodeInput.value.trim());

  if (product) {
    const existingIndex = form.items.findIndex((item) => item.product_id === product.id);
    const price = getCurrentPrice(product);

    if (existingIndex !== -1) {
      form.items[existingIndex].quantity += 1;
    } else {
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

// Add product to cart (with toggle support)
const addToCart = (product) => {
  const existingIndex = form.items.findIndex(
    (item) => item.product_id === product.id
  );

  if (existingIndex !== -1) {
    // Product already in cart - remove it
    form.items.splice(existingIndex, 1);
  } else {
    // Product not in cart - add it
    const price = getCurrentPrice(product);
    const quantity = productQuantities.value[product.id] || 1;

    form.items.push({
      product_id: product.id,
      product_name: product.name,
      price: parseFloat(price),
      quantity: quantity,
    });
    // Reset quantity input
    productQuantities.value[product.id] = 1;
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

// Clear cart
const clearCart = () => {
  if (confirm("Are you sure you want to clear the cart?")) {
    form.items = [];
    form.discount = 0;
    form.payments = [];
    barcodeField.value?.focus();
  }
};

// Add payment
const addPayment = async () => {
  if (paymentAmount.value <= 0) {
    alert("Please enter a valid amount");
    return;
  }

  const remaining = netAmount.value - totalPaid.value;
  // Allow overpayment only for cash
  if (paymentMethod.value !== 0 && paymentAmount.value > remaining) {
    alert(`Amount cannot exceed remaining balance: Rs. ${remaining.toFixed(2)}`);
    return;
  }

  paymentAmount.value = 0;

  // Auto-close modal if fully paid or overpaid (for cash)
  if (balance.value <= 0) {
    showPaymentModal.value = false;
  }
};

// Remove payment
const removePayment = (index) => {
  form.payments.splice(index, 1);
};

// Open payment modal
const openPaymentModal = () => {
  if (form.items.length === 0) {
    alert("Please add items to cart");
    return;
  }
  showPaymentModal.value = true;
  paymentAmount.value = balance.value > 0 ? balance.value : 0;
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

  // Text search (name or barcode)
  if (productFilters.value.search && productFilters.value.search.trim()) {
    const searchTerm = productFilters.value.search.toLowerCase().trim();
    filtered = filtered.filter((p) =>
      (p.name && p.name.toLowerCase().includes(searchTerm)) ||
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

const openCustomerModal = () => {
  showQuickAddCustomer.value = true;
};

const selectProductFromModal = async (product) => {
  // Get quantity from input or default to 1
  const quantity = productQuantities.value[product.id] || 1;

  if (quantity <= 0 || quantity > product.shop_quantity_in_sales_unit) {
    alert(`Please enter a valid quantity (1-${product.shop_quantity_in_sales_unit})`);
    return;
  }

  // Add product directly to cart with custom price
  const existingIndex = form.items.findIndex((item) => item.product_id === product.id);
  const price = getCurrentPrice(product); // Uses override if available

  if (existingIndex !== -1) {
    // If product already in cart, only update the price to the new custom price
    form.items[existingIndex].quantity += quantity;
    form.items[existingIndex].price = parseFloat(price);
  } else {
    form.items.push({
      product_id: product.id,
      product_name: product.name,
      price: parseFloat(price),
      quantity: quantity,
    });
  }

  await logActivity("create", "sales", {
    action: "add_to_cart_from_modal",
    product_id: product.id,
    product_name: product.name,
    quantity: quantity,
    custom_price: parseFloat(price),
  });

  // Reset quantity input
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

// Check if product has low stock
const isLowStock = (product) => {
  return product.shop_quantity <= (product.shop_low_stock_margin || 0);
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

// Complete quotation (stores quotation only)
const submitSale = () => {
  if (form.items.length === 0) {
    alert("Please add items to cart");
    return;
  }

  // Snapshot data for success modal/printing before we mutate/reset anything
  completedInvoice.value = form.quotation_no;
  completedSaleDate.value = form.sale_date;
  completedCustomer.value =
    props.customers.find((c) => c.id === form.customer_id)?.name || "";
  completedItems.value = form.items.map((item) => ({ ...item }));
  completedTotal.value = totalAmount.value.toFixed(2);
  completedDiscount.value = (Number(form.discount) || 0).toFixed(2);
  completedNetAmount.value = netAmount.value.toFixed(2);
  completedPaid.value = totalPaid.value.toFixed(2);
  completedBalance.value = balance.value.toFixed(2);

  const quotationForm = useForm({
    quotation_no: form.quotation_no,
    customer_id: form.customer_id,
    quotation_date: form.sale_date,
    customer_type: form.customer_type,
    items: form.items.map((item) => ({
      product_id: item.product_id,
      quantity: item.quantity,
      price: item.price,
    })),
    discount: form.discount,
  });

  quotationForm.post(route("quotations.store"), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: async () => {
      await logActivity("create", "quotations", {
        action: "complete_quotation",
        quotation_no: form.quotation_no,
        customer_id: form.customer_id,
        items_count: completedItems.value.length,
        total_amount: Number(completedTotal.value),
      });

      // Print quotation with completed data
      printQuotationAfterComplete();

      // Reset form for next quotation
      setTimeout(() => {
        form.items = [];
        form.discount = 0;
        form.payments = [];
        paymentAmount.value = 0;
        paymentMethod.value = 0;
        showPaymentModal.value = false;
        barcodeInput.value = "";
        barcodeField.value?.focus();

        // Navigate to quotations list after a short delay
        setTimeout(() => {
          router.visit(route("quotations.index"));
        }, 1500);
      }, 500);
    },
    onError: (errors) => {
      console.error("Quotation error:", errors);
      let errorMsg = "Quotation failed. Please try again.";
      if (errors.quotation_no) errorMsg = errors.quotation_no[0];
      else if (errors.items) errorMsg = errors.items[0];
      alert(errorMsg);
    },
  });
};

// Print quotation using completed data (called after quotation is stored)
const printQuotationAfterComplete = async () => {
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

    const quotationContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Quotation - ${completedInvoice.value}</title>
                <style>
                    * { margin: 0; padding: 0; }
                    body { font-family: Arial, sans-serif; }
                    .quotation-container { width: ${width}; padding: 10px; margin: 0 auto; }
                    .header { text-align: center; margin-bottom: 10px; }
                    .header h1 { font-size: 16px; font-weight: bold; margin-bottom: 2px; }
                    .document-type { text-align: center; font-size: 14px; font-weight: bold; margin: 5px 0; border: 1px solid #000; padding: 2px; }
                    .company-info { text-align: center; font-size: 10px; margin-bottom: 8px; }
                    .divider { border-bottom: 1px solid #000; margin: 5px 0; }
                    table { width: 100%; font-size: 11px; border-collapse: collapse; }
                    th, td { padding: 3px; text-align: left; }
                    th { border-bottom: 1px solid #000; font-weight: bold; }
                    .amount { text-align: right; }
                    .footer { text-align: center; font-size: 10px; margin-top: 10px; }
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
                    <div class="document-type">QUOTATION</div>
                    <div class="company-info">
                  ${company.address ? `<p>Address: ${company.address}</p>` : ""}
                  ${company.phone ? `<p>Phone: ${company.phone}</p>` : ""}
                  ${company.email ? `<p>Email: ${company.email}</p>` : ""}
                  ${company.website ? `<p>${company.website}</p>` : ""}
                    </div>
                    <hr class="divider">
                    <table>
                        <tr style="font-size: 10px;">
                            <td><strong>Quotation No:</strong> ${
                              completedInvoice.value
                            }</td>
                            <td style="text-align: right;"><strong>Date:</strong> ${
                              completedSaleDate.value
                            }</td>
                        </tr>
                        <tr style="font-size: 10px;">
                            <td colspan="2"><strong>Customer:</strong> ${customerName}</td>
                        </tr>
                    </table>
                    <hr class="divider">
                    <table>
                        <thead>
                            <tr style="border-bottom: 1px solid #000;">
                                <th>Item</th>
                                <th style="text-align: center;">Qty</th>
                                <th style="text-align: right;">Price</th>
                                <th style="text-align: right;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${completedItems.value
                              .map(
                                (item) => `
                                <tr>
                                    <td>${
                                      item.product_name || item.name || "Product"
                                    }</td>
                                    <td style="text-align: center;">${item.quantity}</td>
                                    <td style="text-align: right;">${item.price.toFixed(
                                      2
                                    )}</td>
                                    <td style="text-align: right;">${(
                                      item.price * item.quantity
                                    ).toFixed(2)}</td>
                                </tr>
                            `
                              )
                              .join("")}
                        </tbody>
                    </table>
                    <hr class="divider">
                    <table>
                        <tr>
                            <td><strong>Subtotal:</strong></td>
                            <td style="text-align: right;"><strong>${
                              completedTotal.value
                            }</strong></td>
                        </tr>
                        ${
                          Number(completedDiscount.value) > 0
                            ? `
                            <tr>
                                <td><strong>Discount:</strong></td>
                                <td style="text-align: right;"><strong>-${completedDiscount.value}</strong></td>
                            </tr>
                        `
                            : ""
                        }
                        <tr style="border-top: 1px solid #000; border-bottom: 1px solid #000; font-weight: bold;">
                            <td><strong>Total:</strong></td>
                            <td style="text-align: right;"><strong>${
                              completedNetAmount.value
                            }</strong></td>
                        </tr>
                    </table>
                    <div class="footer">
                        <p>${
                          billSetting.footer_description || "Thank you for your business!"
                        }</p>
                        <p style="margin-top: 8px; font-size: 9px;">Quotation printed on ${new Date().toLocaleString()}</p>
                    </div>
                </div>
                <script type="text/javascript">
                    let printExecuted = false;
                    window.onload = function() {
                        setTimeout(function() {
                            if (!printExecuted) {
                                printExecuted = true;
                                window.print();
                            }
                        }, 300);
                    }
                    window.onafterprint = function() {
                        setTimeout(function() {
                            window.close();
                        }, 200);
                    }
                    setTimeout(function() {
                        if (!window.closed) {
                            window.close();
                        }
                    }, 5000);
                <\/script>
            </body>
            </html>
        `;

    printWindow.document.write(quotationContent);
    printWindow.document.close();
  } catch (e) {
    console.error("Print quotation failed:", e);
  }
};

// Print quotation (before completing) and save to database
const printQuotation = async () => {
  if (form.items.length === 0) {
    alert("Please add items to cart before printing");
    return;
  }

  const printWindow = window.open("", "_blank");

  if (!printWindow) {
    alert("Please allow pop-ups to print quotation");
    return;
  }

  // Prepare quotation data for saving
  const quotationData = useForm({
    quotation_no: form.quotation_no,
    customer_id: form.customer_id,
    quotation_date: form.sale_date,
    customer_type: form.customer_type,
    items: form.items.map((item) => ({
      product_id: item.product_id,
      quantity: item.quantity,
      price: item.price,
    })),
    discount: form.discount,
  });

  // Save quotation to database
  quotationData.post(route("quotations.store"), {
    preserveScroll: true,
    onSuccess: async () => {
      await logActivity("create", "quotations", {
        action: "print_quotation",
        quotation_no: form.quotation_no,
        customer_id: form.customer_id,
        items_count: form.items.length,
        total_amount: totalAmount.value,
      });
    },
    onError: (errors) => {
      console.warn("Could not save quotation:", errors);
      // Continue with printing even if saving fails
    },
  });

  const billSetting = props.billSetting || {};
  const company = companyDetails.value;
  const rawSize = (billSetting.print_size || "80mm").toString();
  const width = rawSize.includes("58") ? "58mm" : "80mm";
  const currentDate = new Date().toISOString().split("T")[0];
  const customerName =
    props.customers.find((c) => c.id === form.customer_id)?.name || "Walk-in Customer";

  const quotationContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Quotation - ${form.quotation_no}</title>
            <style>
                @page {
                    size: ${width} auto;
                    margin: 0;
                }
                @media print {
                    body {
                        margin: 0;
                        padding: 0;
                    }
                }
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    font-family: 'Poppins', Poppins, monospace;
                    font-size: 13px;
                    width: ${width};
                    margin: 0;
                    padding: 3mm 5mm;
                    background: white;
                    color: #000;
                    line-height: 1.4;
                    font-weight: 700;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .quotation-container {
                    width: 100%;
                    max-width: 80mm;
                }
                .header {
                    text-align: center;
                    margin-bottom: 8px;
                    padding-bottom: 8px;
                    border-bottom: 2px dashed #000;
                }
                .header h1 {
                    font-size: 18px;
                    font-weight: 900;
                    margin-bottom: 4px;
                    text-transform: uppercase;
                    color: #000;
                }
                .header p {
                    font-size: 11px;
                    margin: 1px 0;
                    line-height: 1.2;
                    font-weight: 600;
                    color: #000;
                }
                .document-type {
                    text-align: center;
                    font-size: 14px;
                    font-weight: 900;
                    margin: 6px 0;
                    color: #000;
                    text-transform: uppercase;
                    border: 2px solid #000;
                    padding: 3px;
                }
                .info {
                    margin: 8px 0;
                    font-size: 11px;
                    font-weight: 600;
                    color: #000;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    margin: 2px 0;
                    line-height: 1.3;
                    color: #000;
                }
                .items-table {
                    width: 100%;
                    margin: 8px 0;
                    font-size: 11px;
                    border-collapse: collapse;
                    font-weight: 600;
                    color: #000;
                }
                .items-table th {
                    text-align: left;
                    border-bottom: 2px solid #000;
                    padding: 3px 2px;
                    font-weight: 800;
                    color: #000;
                    font-size: 10px;
                }
                .items-table td {
                    padding: 3px 2px;
                    border-bottom: 1px dotted #000;
                    vertical-align: top;
                    font-weight: 600;
                    color: #000;
                }
                .item-name {
                    width: 38%;
                    word-wrap: break-word;
                }
                .item-qty {
                    width: 12%;
                    text-align: center;
                }
                .item-price {
                    width: 25%;
                    text-align: right;
                }
                .item-total {
                    width: 25%;
                    text-align: right;
                }
                .totals {
                    margin-top: 8px;
                    font-size: 11px;
                    font-weight: 600;
                    color: #000;
                }
                .total-row {
                    display: flex;
                    justify-content: space-between;
                    margin: 3px 0;
                    line-height: 1.4;
                    font-weight: 700;
                    color: #000;
                }
                .total-row.grand {
                    font-size: 13px;
                    font-weight: 900;
                    border-top: 2px solid #000;
                    border-bottom: 2px solid #000;
                    padding: 6px 0;
                    margin: 8px 0;
                    color: #000;
                }
                .footer {
                    text-align: center;
                    margin-top: 12px;
                    padding-top: 8px;
                    border-top: 2px dashed #000;
                    font-size: 10px;
                    font-weight: 600;
                    color: #000;
                }
                .footer p {
                    margin: 2px 0;
                    line-height: 1.3;
                    color: #000;
                }
                .quotation-note {
                    margin: 6px 0;
                    padding: 4px;
                    border: 1px solid #000;
                    font-size: 10px;
                    text-align: center;
                    font-weight: 700;
                    color: #000;
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
                    ${company.address ? `<p>${company.address}</p>` : ""}
                    ${company.phone ? `<p>Tel: ${company.phone}</p>` : ""}
                    ${company.email ? `<p>${company.email}</p>` : ""}
                    ${company.website ? `<p>${company.website}</p>` : ""}
                </div>

                <div class="document-type">QUOTATION</div>

                <div class="info">
                    <div class="info-row">
                        <span><strong>Ref No:</strong></span>
                        <span>${form.quotation_no}</span>
                    </div>
                    <div class="info-row">
                        <span><strong>Date:</strong></span>
                        <span>${currentDate}</span>
                    </div>
                    <div class="info-row">
                        <span><strong>Customer:</strong></span>
                        <span>${customerName}</span>
                    </div>
                </div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th class="item-name">Item</th>
                            <th class="item-qty">Qty</th>
                            <th class="item-price">Price</th>
                            <th class="item-total">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${form.items
                          .map(
                            (item) => `
                            <tr>
                                <td class="item-name">${item.product_name}</td>
                                <td class="item-qty">${item.quantity}</td>
                                <td class="item-price">${item.price.toFixed(2)}</td>
                                <td class="item-total">${(
                                  item.price * item.quantity
                                ).toFixed(2)}</td>
                            </tr>
                        `
                          )
                          .join("")}
                    </tbody>
                </table>

                <div class="totals">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>${page.props.currency || "Rs."} ${totalAmount.value.toFixed(
    2
  )}</span>
                    </div>
                    ${
                      form.discount > 0
                        ? `
                    <div class="total-row">
                        <span>Discount:</span>
                        <span>${page.props.currency || "Rs."} ${form.discount.toFixed(
                            2
                          )}</span>
                    </div>
                    `
                        : ""
                    }
                    <div class="total-row grand">
                        <span>TOTAL:</span>
                        <span>${page.props.currency || "Rs."} ${netAmount.value.toFixed(
    2
  )}</span>
                    </div>
                </div>

                <div class="quotation-note">
                    Valid for 7 days from issue date
                </div>

                <div class="footer">
                    <p>${
                      billSetting.footer_description || "Thank you for your interest!"
                    }</p>
                    <p style="margin-top: 4px; font-size: 9px;">Powered by POS System</p>
                </div>
            </div>

            <script type="text/javascript">
                let printExecuted = false;

                window.onload = function() {
                    setTimeout(function() {
                        if (!printExecuted) {
                            printExecuted = true;
                            window.print();
                        }
                    }, 300);
                }

                window.onafterprint = function() {
                    setTimeout(function() {
                        window.close();
                    }, 200);
                }

                setTimeout(function() {
                    if (!window.closed) {
                        window.close();
                    }
                }, 5000);
            <\/script>
        </body>
        </html>
    `;

  printWindow.document.write(quotationContent);
  printWindow.document.close();
};

// Print receipt
const printReceipt = () => {
  const printWindow = window.open("", "_blank");

  if (!printWindow) {
    alert("Please allow pop-ups to print receipt");
    return;
  }

  const billSetting = props.billSetting || {};
  const company = companyDetails.value;
  const rawSize = (billSetting.print_size || "80mm").toString();
  const width = rawSize.includes("58") ? "58mm" : "80mm";

  const receiptContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Receipt - ${completedInvoice.value}</title>
            <style>
                @page {
                    size: ${width} auto;
                    margin: 0;
                }
                @media print {
                    body {
                        margin: 0;
                        padding: 0;
                    }
                }
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    font-family: 'Poppins', Poppins, monospace;
                    font-size: 13px;
                    width: ${width};
                    margin: 0;
                    padding: 3mm 5mm;
                    background: white;
                    color: #000;
                    line-height: 1.4;
                    font-weight: 700;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .receipt-container {
                    width: 100%;
                    max-width: 80mm;
                }
                .header {
                    text-align: center;
                    margin-bottom: 8px;
                    padding-bottom: 8px;
                    border-bottom: 2px dashed #000;
                }
                .header h1 {
                    font-size: 18px;
                    font-weight: 900;
                    margin-bottom: 4px;
                    text-transform: uppercase;
                    color: #000;
                }
                .header p {
                    font-size: 12px;
                    margin: 1px 0;
                    line-height: 1.3;
                    font-weight: 600;
                    color: #000;
                }
                .info {
                    margin: 8px 0;
                    font-size: 12px;
                    font-weight: 600;
                    color: #000;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    margin: 2px 0;
                    line-height: 1.3;
                    color: #000;
                }

                .items-table {
                    width: 100%;
                    margin: 8px 0;
                    font-size: 12px;
                    border-collapse: collapse;
                    font-weight: 600;
                    color: #000;
                }
                .items-table th {
                    text-align: left;
                    border-bottom: 2px solid #000;
                    padding: 3px 2px;
                    font-weight: 800;
                    color: #000;
                }
                .items-table td {
                    padding: 3px 2px;
                    border-bottom: 1px dotted #000;
                    vertical-align: top;
                    font-weight: 600;
                    color: #000;
                }
                .item-name {
                    width: 38%;
                    word-wrap: break-word;
                }
                .item-qty {
                    width: 12%;
                    text-align: center;
                }
                .item-price {
                    width: 25%;
                    text-align: right;
                }
                .item-total {
                    width: 25%;
                    text-align: right;
                }
                .totals {
                    margin-top: 8px;
                    font-size: 12px;
                    font-weight: 600;
                    color: #000;
                }
                .total-row {
                    display: flex;
                    justify-content: space-between;
                    margin: 3px 0;
                    line-height: 1.4;
                    font-weight: 700;
                    color: #000;
                }
                .total-row.grand {
                    font-size: 15px;
                    font-weight: 900;
                    border-top: 2px solid #000;
                    border-bottom: 2px solid #000;
                    padding: 6px 0;
                    margin: 8px 0;
                    color: #000;
                }
                .footer {
                    text-align: center;
                    margin-top: 12px;
                    padding-top: 8px;
                    border-top: 2px dashed #000;
                    font-size: 12px;
                    font-weight: 600;
                    color: #000;
                }
                .footer p {
                    margin: 2px 0;
                    line-height: 1.3;
                    color: #000;
                }
            </style>
        </head>
        <body>
            <div class="receipt-container">
                <div class="header">
                    ${company.logo
                      ? `<div style="margin-bottom:6px;"><img src="/storage/${company.logo}" alt="logo" style="max-height:40px; max-width:100%; object-fit:contain;"/></div>`
                      : ""}
                    <h1>${company.name || "SALES RECEIPT"}</h1>
                    ${company.address ? `<p>${company.address}</p>` : ""}
                    ${company.phone ? `<p>Tel: ${company.phone}</p>` : ""}
                    ${company.email ? `<p>${company.email}</p>` : ""}
                    ${company.website ? `<p>${company.website}</p>` : ""}
                </div>

                <div class="info">
                    <div class="info-row">
                        <span><strong>Invoice:</strong></span>
                        <span>${completedInvoice.value}</span>
                    </div>
                    <div class="info-row">
                        <span><strong>Date:</strong></span>
                        <span>${completedSaleDate.value}</span>
                    </div>
                    <div class="info-row">
                        <span><strong>Customer:</strong></span>
                        <span>${completedCustomer.value}</span>
                    </div>
                </div>


                <table class="items-table">
                    <thead>
                        <tr>
                            <th class="item-name">Item</th>
                            <th class="item-qty">Qty</th>
                            <th class="item-price">Price</th>
                            <th class="item-total">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${completedItems.value
                          .map(
                            (item) => `
                            <tr>
                                <td class="item-name">${item.product_name}</td>
                                <td class="item-qty">${item.quantity}</td>
                                <td class="item-price">${item.price.toFixed(2)}</td>
                                <td class="item-total">${(
                                  item.price * item.quantity
                                ).toFixed(2)}</td>
                            </tr>
                        `
                          )
                          .join("")}
                    </tbody>
                </table>


                <div class="totals">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>${page.props.currency || "Rs."} ${
    completedTotal.value
  }</span>
                    </div>
                    <div class="total-row">
                        <span>Discount:</span>
                        <span>${page.props.currency || "Rs."} ${
    completedDiscount.value
  }</span>
                    </div>
                    <div class="total-row grand">
                        <span>GRAND TOTAL:</span>
                        <span>${page.props.currency || "Rs."} ${
    completedNetAmount.value
  }</span>
                    </div>
                    <div class="total-row">
                        <span>Paid Amount:</span>
                        <span>${page.props.currency || "Rs."} ${
    completedPaid.value
  }</span>
                    </div>
                    <div class="total-row" style="font-weight: bold;">
                        <span>${
                          parseFloat(completedBalance.value) > 0
                            ? "Balance Due:"
                            : "Change:"
                        }</span>
                        <span>${page.props.currency || "Rs."} ${Math.abs(
    parseFloat(completedBalance.value)
  ).toFixed(2)}</span>
                    </div>
                </div>

                <div class="footer">

                    <p>
                    ${billSetting.footer_description || "Please visit us again!"}
                        </p>
                    <p style="margin-top: 6px; font-size: 9px;">Powered by POS System</p>
                </div>
            </div>

            <script type="text/javascript">
                let printExecuted = false;

                window.onload = function() {
                    setTimeout(function() {
                        if (!printExecuted) {
                            printExecuted = true;
                            window.print();
                        }
                    }, 300);
                }

                window.onafterprint = function() {
                    setTimeout(function() {
                        window.close();
                    }, 200);
                }

                setTimeout(function() {
                    if (!window.closed) {
                        window.close();
                    }
                }, 5000);
            <\/script>
        </body>
        </html>
    `;

  printWindow.document.write(receiptContent);
  printWindow.document.close();
};

// Close modal and reload
const closeModal = () => {
  showSuccessModal.value = false;
  router.visit(route("quotations.index"));
};

// Print from success modal then close and redirect
const printAndClose = () => {
  try {
    printReceipt();
  } catch (e) {
    console.error("Print failed:", e);
  }

  // Close modal and redirect after a short delay to allow print dialog
  showSuccessModal.value = false;
  setTimeout(() => {
    router.visit(route("quotations.index"));
  }, 800);
};

// Keyboard shortcuts
const handleKeyboard = (event) => {
  // F9 - Complete sale
  if (event.key === "F9") {
    event.preventDefault();
    submitSale();
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

// Focus barcode input on mount
onMounted(() => {
  barcodeField.value?.focus();
  window.addEventListener("keydown", handleKeyboard);

  // Do not set a default customer; keep it empty to show '-- Select Customer --'
});
</script>

<style scoped>
select.no-arrow {
  background-image: none !important;
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
}
select.no-arrow::-ms-expand {
  display: none;
}
</style>
