<template>
  <Modal :show="open" @close="closeModal" max-width="6xl">
    <div class="p-6 bg-gray-50">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-blue-600">✨ Create Sales Return</h2>
        <button
          @click="closeModal"
          class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-200 rounded-full transition-all duration-200"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            ></path>
          </svg>
        </button>
      </div>

      <!-- Return Type Selection (Simple Toggle) -->
      <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
        <div class="flex items-center justify-between">
          <div class="inline-flex bg-gray-100 rounded-lg p-1">
            <button
              type="button"
              :class="[
                'px-4 py-2 rounded-md text-sm font-medium transition',
                returnType === 1
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-600 hover:text-gray-800',
              ]"
              @click="returnType = 1"
            >
              🔄 Product Return
            </button>
            <button
              type="button"
              :class="[
                'px-4 py-2 rounded-md text-sm font-medium transition',
                returnType === 2
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-600 hover:text-gray-800',
              ]"
              @click="returnType = 2"
            >
              💰 Cash Refund
            </button>
          </div>
          <div class="hidden md:block text-xs text-gray-500">
            <span v-if="returnType === 1"
              >Step 1: Select items • Step 2: Review & Submit</span
            >
            <span v-else>Step 1: Enter refund • Step 2: Review & Submit</span>
          </div>
        </div>
      </div>

      <div class="flex flex-col lg:flex-row gap-4 items-stretch">
        <div class="flex-1 space-y-4 w-full">
          <!-- Available Products -->
          <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
            <h3 class="mb-3 text-lg font-semibold text-blue-600 flex items-center gap-2">
              📦 Available Sales Products
            </h3>

            <!-- Invoice Search -->
            <div class="mb-4 flex flex-col md:flex-row gap-2 md:items-center">
              <label class="text-sm text-gray-700 md:w-40 font-medium"
                >Invoice Number</label
              >
              <div class="flex w-full md:w-auto gap-2">
                <input
                  v-model="invoiceNumber"
                  @keyup.enter="searchByInvoice"
                  type="text"
                  placeholder="Enter invoice no (e.g., INV-00001)"
                  class="flex-1 px-3 py-2 text-sm bg-white text-gray-800 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <button
                  @click="searchByInvoice"
                  class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition"
                >
                  Enter
                </button>
              </div>
            </div>
            <h3 v-if="hasSearched" class="text-sm text-gray-700 mb-2">
              Results for invoice:
              <span class="text-blue-600 font-semibold">{{ invoiceNumber }}</span>
            </h3>
            <div v-if="hasSearched" class="overflow-x-auto max-h-96">
              <table class="w-full text-gray-800 text-sm">
                <thead class="bg-white border-b-2 border-blue-600 sticky top-0">
                  <tr>
                    <th class="px-3 py-2 text-left text-blue-700 font-semibold">
                      Product
                    </th>
                    <th class="px-3 py-2 text-left text-blue-700 font-semibold">
                      Sale No
                    </th>
                    <th class="px-3 py-2 text-left text-blue-700 font-semibold">
                      Customer
                    </th>
                    <th class="px-3 py-2 text-center text-blue-700 font-semibold">
                      Qty Sold
                    </th>
                    <th class="px-3 py-2 text-center text-blue-700 font-semibold">
                      Return Qty
                    </th>
                    <th class="px-3 py-2 text-center text-blue-700 font-semibold">
                      Price
                    </th>
                    <th class="px-3 py-2 text-center text-blue-700 font-semibold">
                      Action
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="product in filteredProducts"
                    :key="product.id"
                    class="border-b border-gray-200 hover:bg-gray-50 transition"
                  >
                    <td class="px-3 py-2">
                      <div class="font-medium text-gray-800">
                        {{ product.product_name }}
                      </div>
                      <div class="text-xs text-gray-500">
                        {{ product.product_barcode }}
                      </div>
                    </td>
                    <td class="px-3 py-2">
                      <div class="font-medium text-gray-800">{{ product.sale_no }}</div>
                      <div class="text-xs text-gray-500">
                        {{ product.sale_date_formatted }}
                      </div>
                    </td>
                    <td class="px-3 py-2">
                      <div class="text-gray-800">{{ product.customer_name }}</div>
                      <div class="text-xs text-gray-500">
                        {{ product.customer_phone || "" }}
                      </div>
                    </td>
                    <td class="px-3 py-2 text-center text-gray-800">
                      {{ product.quantity_sold }}
                    </td>
                    <td class="px-3 py-2 text-center text-gray-800">
                      {{
                        isSelected(product.id)
                          ? selectedProducts.find((p) => p.id === product.id)
                              .return_quantity
                          : 0
                      }}
                    </td>
                    <td class="px-3 py-2 text-center text-gray-800">
                      {{ currencySymbol }} {{ product.formatted_price }}
                    </td>
                    <td class="px-3 py-2 text-center">
                      <button
                        v-if="!isSelected(product.id)"
                        @click="openReturnQtyModal(product)"
                        class="px-3 py-1 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 transition"
                      >
                        + Add
                      </button>
                      <span
                        v-else
                        class="px-3 py-1 bg-green-600 text-white text-xs rounded-lg inline-block"
                      >
                        Selected
                      </span>
                    </td>
                  </tr>
                  <tr v-if="filteredProducts.length === 0">
                    <td colspan="7" class="px-3 py-8 text-center text-gray-500">
                      <div class="text-4xl mb-2">🧾</div>
                      <div class="text-lg font-semibold mb-1">
                        No products found for this invoice
                      </div>
                      <div class="text-sm">Check the invoice number and try again</div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div
              v-else
              class="px-3 py-6 text-center text-gray-500 border border-dashed border-gray-300 rounded-lg bg-gray-50"
            >
              Enter an invoice number above to load products
            </div>
          </div>

          <!-- Replacement Products (moved below available products) -->
          <div
            v-if="returnType === 1"
            class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200"
          >
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-lg font-semibold text-green-600 flex items-center gap-2">
                🔄 Replacement Products
              </h3>
              <span
                class="text-xs text-white px-3 py-1.5 rounded-lg font-medium"
                :class="replacementQtyMatches ? 'bg-green-600' : 'bg-red-600'"
              >
                Qty: Return {{ totalReturnQty }} vs Replacement {{ totalReplacementQty }}
              </span>
            </div>

            <div class="mt-4 space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-2">
                  <input
                    v-model="repSearch"
                    type="text"
                    placeholder="Search by name or barcode"
                    class="w-full px-3 py-2 text-sm bg-white text-gray-800 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                  <div
                    v-if="repSearch"
                    class="mt-2 max-h-40 overflow-y-auto bg-white border border-gray-300 rounded-lg shadow-sm"
                  >
                    <button
                      v-for="p in filteredShopProducts"
                      :key="p.id"
                      class="w-full text-left px-3 py-2 hover:bg-gray-50 text-gray-800 transition"
                      @click="selectReplacement(p)"
                    >
                      <div class="font-medium">{{ p.name }}</div>
                      <div class="text-xs text-gray-500">
                        {{ p.barcode }} • In stock: {{ p.shop_quantity_in_sales_unit }}
                      </div>
                    </button>
                    <div
                      v-if="filteredShopProducts.length === 0"
                      class="px-3 py-2 text-sm text-gray-500"
                    >
                      No matches
                    </div>
                  </div>
                </div>
                <div>
                  <input
                    v-model.number="repQty"
                    type="number"
                    min="1"
                    placeholder="Qty"
                    class="w-full px-3 py-2 text-sm bg-white text-gray-800 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
                <div class="flex items-end">
                  <button
                    @click="addReplacementFromSearch"
                    class="w-full px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                  >
                    Add
                  </button>
                </div>
              </div>

              <div class="overflow-x-auto">
                <table class="w-full text-gray-800 text-sm">
                  <thead class="bg-white border-b-2 border-green-600">
                    <tr>
                      <th class="px-3 py-2 text-left text-green-700 font-semibold">
                        Product
                      </th>
                      <th class="px-3 py-2 text-center text-green-700 font-semibold">
                        Qty
                      </th>
                      <th class="px-3 py-2 text-center text-green-700 font-semibold">
                        Unit Price
                      </th>
                      <th class="px-3 py-2 text-center text-green-700 font-semibold">
                        Total
                      </th>
                      <th class="px-3 py-2 text-center text-green-700 font-semibold">
                        Action
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="item in replacementProducts"
                      :key="item.product_id"
                      class="border-b border-gray-200 hover:bg-gray-50 transition"
                    >
                      <td class="px-3 py-2">
                        <div class="font-medium text-gray-800">{{ item.name }}</div>
                        <div class="text-xs text-gray-500">{{ item.barcode }}</div>
                      </td>
                      <td class="px-3 py-2 text-center">
                        <input
                          v-model.number="item.quantity"
                          type="number"
                          min="1"
                          class="w-20 px-2 py-1 text-sm bg-white text-gray-800 border border-gray-300 rounded-lg text-center focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                      </td>
                      <td class="px-3 py-2 text-center">
                        <input
                          v-model.number="item.unit_price"
                          type="number"
                          step="0.01"
                          min="0"
                          class="w-24 px-2 py-1 text-sm bg-white text-gray-800 border border-gray-300 rounded-lg text-center focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                      </td>
                      <td class="px-3 py-2 text-center font-semibold text-gray-800">
                        {{ currencySymbol }}
                        {{
                          (
                            (item.quantity || 0) * parseFloat(item.unit_price || 0)
                          ).toFixed(2)
                        }}
                      </td>
                      <td class="px-3 py-2 text-center">
                        <button
                          @click="removeReplacement(item.product_id)"
                          class="px-2 py-1 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 transition"
                        >
                          Remove
                        </button>
                      </td>
                    </tr>
                    <tr v-if="replacementProducts.length === 0">
                      <td colspan="5" class="px-3 py-3 text-center text-gray-500">
                        No replacement products added
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Bill Summary (right side, spans height) -->
        <div class="lg:w-1/3 w-full">
          <div
            class="bg-white rounded-xl p-5 border border-gray-200 shadow-md h-full flex flex-col justify-between"
          >
            <div>
              <h4
                class="text-lg font-semibold text-blue-600 mb-4 flex items-center gap-2"
              >
                  Bill Summary
              </h4>

              <!-- Product Return Summary -->
              <div v-if="returnType === 1" class="space-y-3 text-sm text-gray-700">
                <div class="flex items-center justify-between">
                  <span>Total Return Value</span>
                  <span class="text-gray-800 font-semibold"
                    >{{ currencySymbol }} {{ returnTotal.toFixed(2) }}</span
                  >
                </div>
                <div class="flex items-center justify-between">
                  <span>Replacement Value</span>
                  <span class="text-gray-800 font-semibold"
                    >{{ currencySymbol }} {{ replacementTotal.toFixed(2) }}</span
                  >
                </div>
                <div
                  class="flex items-center justify-between text-lg font-bold pt-2 border-t border-gray-200"
                >
                  <span>{{ balanceLabel }}</span>
                  <span
                    :class="
                      balance > 0
                        ? 'text-red-600'
                        : balance < 0
                        ? 'text-green-600'
                        : 'text-gray-800'
                    "
                  >
                    {{ currencySymbol }} {{ Math.abs(balance).toFixed(2) }}
                  </span>
                </div>
                <div v-if="balance < 0" class="flex items-center justify-between">
                  <span>Refund Method</span>
                  <span class="text-gray-800 font-semibold">Cash (fixed)</span>
                </div>
              </div>

              <!-- Cash Refund Summary -->
              <div v-else class="space-y-3 text-sm text-gray-700">
                <div class="flex items-center justify-between">
                  <span>Items Selected</span>
                  <span class="text-gray-800 font-semibold">{{
                    selectedProducts.length
                  }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span>Return Value</span>
                  <span class="text-gray-800 font-semibold"
                    >{{ currencySymbol }} {{ returnTotal.toFixed(2) }}</span
                  >
                </div>
                <div class="flex items-center justify-between">
                  <span>Refund Method</span>
                  <span class="text-gray-800 font-semibold">{{
                    formatRefundMethod(refundMethod)
                  }}</span>
                </div>
                <div
                  class="flex items-center justify-between text-lg font-bold pt-2 border-t border-gray-200"
                >
                  <span>Refund Amount</span>
                  <span class="text-green-600"
                    >{{ currencySymbol }} {{ refundAmount.toFixed(2) }}</span
                  >
                </div>
              </div>
            </div>

            <div class="mt-5 space-y-3">
              <div v-if="returnType === 1" class="flex flex-col sm:flex-row gap-3">
                <input
                  v-model.number="paymentAmount"
                  type="number"
                  min="0"
                  step="0.01"
                  class="flex-1 px-3 py-3 text-sm bg-white text-gray-800 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  :placeholder="`Max: ${remainingBalance.toFixed(2)}`"
                  :max="remainingBalance"
                />
                <button
                  @click="openPaymentModal"
                  :disabled="remainingBalance <= 0"
                  class="px-5 py-3 text-sm w-full sm:w-auto bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 disabled:opacity-50 transition"
                >
                  💳 Add Payment
                </button>
              </div>

              <!-- Payments List -->
              <div
                v-if="returnType === 1 && payments.length > 0"
                class="bg-blue-50 rounded-lg p-3 space-y-2 border border-blue-200"
              >
                <div class="text-xs text-blue-700 font-semibold mb-2">
                  Payments Recorded:
                </div>
                <div
                  v-for="(payment, idx) in payments"
                  :key="idx"
                  class="flex items-center justify-between bg-white p-2 rounded-lg text-sm text-gray-800 border border-gray-200"
                >
                  <div>
                    <span class="font-semibold">{{
                      formatRefundMethod(payment.method, payment.card_type)
                    }}</span>
                    <span class="text-gray-600 ml-2"
                      >{{ currencySymbol }}
                      {{ parseFloat(payment.amount).toFixed(2) }}</span
                    >
                  </div>
                  <button
                    @click="removePayment(idx)"
                    class="px-2 py-1 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 transition"
                  >
                    Remove
                  </button>
                </div>
                <div
                  class="flex items-center justify-between bg-green-50 p-2 rounded-lg text-sm font-bold text-green-700 mt-2 border border-green-200"
                >
                  <span>Total Paid:</span>
                  <span>{{ currencySymbol }} {{ totalPaid.toFixed(2) }}</span>
                </div>
                <div
                  class="flex items-center justify-between bg-blue-100 p-2 rounded-lg text-sm font-bold text-blue-700 mt-2 border border-blue-300"
                >
                  <span>Balance:</span>
                  <span
                    >{{ currencySymbol }}
                    {{ (totalPaid - (balance > 0 ? balance : 0)).toFixed(2) }}</span
                  >
                </div>
              </div>

              <button
                @click="printDraftBill"
                class="px-5 py-3 w-full text-sm bg-green-600 text-white rounded-full font-semibold hover:bg-green-700 transition"
              >
                🧾 Print Bill
              </button>
              <button
                @click="submitReturn"
                :disabled="!canSubmit() || processing"
                class="px-6 py-3 text-sm bg-blue-600 text-white rounded-full hover:bg-blue-700 disabled:opacity-50 w-full font-semibold transition"
              >
                {{ processing ? "⏳ Creating..." : "✨ Complete Return" }}
              </button>
              <button
                @click="closeModal"
                class="w-full px-4 py-3 text-sm bg-gray-500 text-white rounded-full hover:bg-gray-600 font-semibold transition"
              >
                Cancel
              </button>
            </div>

            <div
              v-if="returnType === 1"
              class="mt-3 text-xs"
              :class="paymentSatisfied ? 'text-green-600' : 'text-yellow-600'"
            >
              {{ paymentStatusText }}
            </div>
            <div v-else class="mt-3 text-xs text-green-600">
              Refund ready for customer
            </div>

            <div class="mt-4 text-xs text-gray-500">
              Keyboard Shortcuts: F9 Complete Return • ESC Close
            </div>
          </div>
        </div>
      </div>

  <!-- Payment Method Modal -->
  <div
    v-if="showPaymentModal"
    class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-50"
  >
    <div
      class="bg-gray-50 rounded-2xl p-8 w-full max-w-md border border-gray-200 shadow-2xl"
    >
      <!-- Header -->
      <h3 class="text-2xl font-bold text-blue-600 mb-2">💳 Add Payment Method</h3>

      <!-- Remaining Balance -->
      <div class="mb-6">
        <p class="text-gray-700 text-sm">
          Remaining:
          <span class="text-red-600 font-bold text-lg"
            >({{ currencySymbol }}) {{ balance.toFixed(2) }}</span
          >
        </p>
      </div>

      <!-- Payment Method -->
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-3">Payment Method</label>
        <select
          v-model="selectedPaymentMethod"
          class="w-full px-4 py-3 text-sm bg-white text-gray-800 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none"
        >
          <option value="cash">💵 Cash</option>
          <option value="card">💳 Card</option>
          <option value="cheque">📄 Cheque</option>
          <option value="bank_transfer">🏦 Bank Transfer</option>
        </select>
      </div>

      <div v-if="selectedPaymentMethod === 'card'" class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-3">Card Type</label>
        <select
          v-model="selectedPaymentCardType"
          class="w-full px-4 py-3 text-sm bg-white text-gray-800 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none"
        >
          <option value="">Select Card Type</option>
          <option value="visa">💳 Visa</option>
          <option value="mastercard">💳 MasterCard</option>
        </select>
      </div>

      <!-- Payment Amount -->
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-3"
          >Amount ({{ currencySymbol }})</label
        >
        <input
          v-model.number="paymentModalAmount"
          type="number"
          step="0.01"
          :max="balance"
          class="w-full px-4 py-3 text-sm bg-white text-gray-800 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none"
          placeholder="0.00"
        />
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-3">
        <button
          @click="confirmPayment"
          class="flex-1 px-4 py-3 text-sm bg-green-600 text-white font-semibold rounded-full hover:bg-green-700 transition"
        >
          ✅ Add Payment
        </button>
        <button
          @click="showPaymentModal = false"
          class="flex-1 px-4 py-3 text-sm bg-gray-500 text-white font-semibold rounded-full hover:bg-gray-600 transition"
        >
          Close
        </button>
      </div>
    </div>
  </div>

  <!-- Return Quantity Modal -->
  <div
    v-if="showReturnQtyModal"
    class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-50"
  >
    <div
      class="bg-gray-50 rounded-2xl p-8 w-full max-w-md border border-gray-200 shadow-2xl"
    >
      <!-- Header -->
      <h3 class="text-2xl font-bold text-blue-600 mb-6">  Enter Return Quantity</h3>

      <!-- Product Info -->
      <div
        v-if="currentProductToAdd"
        class="mb-6 p-4 bg-white rounded-lg border border-gray-200 shadow-sm"
      >
        <p class="text-gray-800 text-sm mb-1">
          <span class="font-semibold">{{ currentProductToAdd.product_name }}</span>
        </p>
        <p class="text-gray-500 text-xs mb-3">
          {{ currentProductToAdd.product_barcode }}
        </p>
        <div class="flex justify-between text-sm">
          <span class="text-gray-600">Sold Qty:</span>
          <span class="text-gray-800 font-semibold">{{
            currentProductToAdd.quantity_sold
          }}</span>
        </div>
        <div class="flex justify-between text-sm mt-2">
          <span class="text-gray-600">Unit Price:</span>
          <span class="text-gray-800 font-semibold"
            >{{ currencySymbol }} {{ currentProductToAdd.formatted_price }}</span
          >
        </div>
      </div>

      <!-- Return Quantity Input -->
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-3"
          >Return Quantity</label
        >
        <input
          v-model.number="tempReturnQuantity"
          type="number"
          min="1"
          :max="currentProductToAdd?.quantity_sold || 1"
          class="w-full px-4 py-3 text-sm bg-white text-gray-800 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none"
        />
      </div>

      <!-- Total Price Display -->
      <div
        v-if="tempReturnQuantity > 0"
        class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200"
      >
        <div class="flex justify-between items-center">
          <span class="text-green-700 font-medium">Total Price:</span>
          <span class="text-green-600 text-2xl font-bold"
            >{{ currencySymbol }}
            {{
              (tempReturnQuantity * parseFloat(currentProductToAdd?.price || 0)).toFixed(
                2
              )
            }}</span
          >
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-3">
        <button
          @click="confirmAddProduct"
          class="flex-1 px-4 py-3 text-sm bg-green-600 text-white font-semibold rounded-full hover:bg-green-700 transition"
        >
          ✅ Add Product
        </button>
        <button
          @click="closeReturnQtyModal"
          class="flex-1 px-4 py-3 text-sm bg-gray-500 text-white font-semibold rounded-full hover:bg-gray-600 transition"
        >
          Cancel
        </button>
      </div>
    </div>
  </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import Modal from '@/Components/Modal.vue';
import { logActivity } from "@/composables/useActivityLog";

const props = defineProps({
  open: Boolean,
  salesProducts: Object,
  shopProducts: Array,
});

// Prevent background scrolling when modal is open
watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "";
    }
  }
);

// Cleanup on unmount
onUnmounted(() => {
  document.body.style.overflow = "";
});

const emit = defineEmits(["update:open", "success"]);

const page = usePage();
const returnType = ref(1); // 1 = Product Return, 2 = Cash Return
const searchQuery = ref("");
const dateFrom = ref("");
const dateTo = ref("");
const selectedProducts = ref([]);
const replacementProducts = ref([]);
const paymentAmount = ref(0);
const paymentApplied = ref(false);
const refundAmount = ref(0);
const refundMethod = ref("cash");
const notes = ref("");
const processing = ref(false);

// Multi-Payment tracking
const payments = ref([]); // Array of {amount, method}
const totalPaid = computed(() =>
  payments.value.reduce((sum, p) => sum + parseFloat(p.amount || 0), 0)
);
const remainingBalance = computed(() => Math.max(0, balance.value - totalPaid.value));

// Payment Modal
const showPaymentModal = ref(false);
const selectedPaymentMethod = ref("cash");
const selectedPaymentCardType = ref("");
const paymentModalAmount = ref(0);

// Return Quantity Modal
const showReturnQtyModal = ref(false);
const currentProductToAdd = ref(null);
const tempReturnQuantity = ref(1);

const currencySymbol = computed(() => props.currencySymbol || "Rs.");

// Invoice search
const invoiceNumber = ref("");
const hasSearched = ref(false);

// Auto-update refund amount ONLY when in Cash Refund mode and products change
watch(
  selectedProducts,
  (newProducts) => {
    // Only auto-fill refund amount for Cash Refund type (2)
    if (returnType.value === 2 && newProducts.length > 0) {
      // Calculate total from selected products
      const total = newProducts.reduce((sum, p) => {
        return sum + (p.return_quantity || 0) * parseFloat(p.price || 0);
      }, 0);
      refundAmount.value = parseFloat(total.toFixed(2));
    }
  },
  { deep: true }
);

// Clear data when switching return types
watch(returnType, (newType, oldType) => {
  // Clear invoice search and selected products when switching
  invoiceNumber.value = "";
  hasSearched.value = false;
  selectedProducts.value = [];

  if (newType === 1) {
    // Switching to Product Return: clear cash refund data
    refundAmount.value = 0;
    refundMethod.value = "cash";
  } else if (newType === 2) {
    // Switching to Cash Refund: clear product return data
    replacementProducts.value = [];
    payments.value = [];
    paymentAmount.value = 0;
  }
});

const filteredProducts = computed(() => {
  let products = props.salesProducts?.data || [];

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    products = products.filter(
      (p) =>
        p.product_name?.toLowerCase().includes(query) ||
        p.sale_no?.toLowerCase().includes(query) ||
        p.customer_name?.toLowerCase().includes(query) ||
        p.product_barcode?.toLowerCase().includes(query)
    );
  }

  if (dateFrom.value) {
    products = products.filter((p) => p.sale_date >= dateFrom.value);
  }

  if (dateTo.value) {
    products = products.filter((p) => p.sale_date <= dateTo.value);
  }

  return products;
});

// Replacement search helpers
const repSearch = ref("");
const repQty = ref(1);
const filteredShopProducts = computed(() => {
  let ps = props.shopProducts || [];
  if (repSearch.value) {
    const q = repSearch.value.toLowerCase();
    ps = ps.filter(
      (p) =>
        (p.name || "").toLowerCase().includes(q) ||
        (p.barcode || "").toLowerCase().includes(q)
    );
  }
  return ps.slice(0, 20); // limit list
});

const addReplacementFromSearch = () => {
  if (!repSearch.value) return;
  const match = (props.shopProducts || []).find(
    (p) =>
      (p.name || "").toLowerCase().includes(repSearch.value.toLowerCase()) ||
      (p.barcode || "").toLowerCase().includes(repSearch.value.toLowerCase())
  );
  if (!match) {
    alert("No matching product found in shop.");
    return;
  }

  // Check if requested quantity is available in shop stock
  const requestedQty = repQty.value || 1;
  const availableQty = parseInt(match.shop_quantity_in_sales_unit || 0);

  if (requestedQty > availableQty) {
    alert(
      `Insufficient stock! Only ${availableQty} units available in shop for ${match.name}.`
    );
    return;
  }

  replacementProducts.value.push({
    product_id: match.id,
    name: match.name,
    barcode: match.barcode,
    quantity: requestedQty,
    unit_price: parseFloat(match.retail_price || 0),
  });
  repSearch.value = "";
  repQty.value = 1;
};

const selectReplacement = (p) => {
  repSearch.value = `${p.name}`;
};

const removeReplacement = (productId) => {
  replacementProducts.value = replacementProducts.value.filter(
    (p) => p.product_id !== productId
  );
};

const calculateTotalRefund = () => {
  return selectedProducts.value
    .reduce((total, product) => {
      return total + (product.return_quantity || 0) * parseFloat(product.price || 0);
    }, 0)
    .toFixed(2);
};

const totalReturnQty = computed(() =>
  selectedProducts.value.reduce((s, p) => s + (parseInt(p.return_quantity) || 0), 0)
);
const totalReplacementQty = computed(() =>
  replacementProducts.value.reduce((s, p) => s + (parseInt(p.quantity) || 0), 0)
);
const replacementQtyMatches = computed(
  () => totalReplacementQty.value === totalReturnQty.value
);

const returnTotal = computed(() =>
  selectedProducts.value.reduce((total, product) => {
    return total + (product.return_quantity || 0) * parseFloat(product.price || 0);
  }, 0)
);

const replacementTotal = computed(() =>
  replacementProducts.value.reduce((total, item) => {
    return total + (item.quantity || 0) * parseFloat(item.unit_price || 0);
  }, 0)
);

const balance = computed(() => replacementTotal.value - returnTotal.value);

// Helpers for clarity
const isRefundToCustomer = computed(() => returnType.value === 1 && balance.value < 0);
const isPaymentFromCustomer = computed(() => returnType.value === 1 && balance.value > 0);

const balanceLabel = computed(() => {
  if (balance.value > 0) return "Payment from customer";
  if (balance.value < 0) return "Refund to customer";
  return "Settled";
});

const paymentSatisfied = computed(() => {
  if (balance.value <= 0) return true;
  return payments.value.length > 0 && totalPaid.value >= balance.value;
});

const paymentStatusText = computed(() => {
  if (balance.value <= 0) {
    return balance.value < 0
      ? `Refund customer ${currencySymbol.value} ${Math.abs(balance.value).toFixed(2)}`
      : "No payment needed";
  }
  if (payments.value.length > 0 && totalPaid.value >= balance.value) {
    return "Payment satisfied";
  }
  if (payments.value.length > 0) {
    return `Paid: ${currencySymbol.value} ${totalPaid.value.toFixed(2)} | Remaining: ${
      currencySymbol.value
    } ${remainingBalance.value.toFixed(2)}`;
  }
  return `Payment required: ${currencySymbol.value} ${balance.value.toFixed(2)}`;
});

const applyPayment = () => {
  // Deprecated direct apply; use modal to select method
  openPaymentModal();
};

const openPaymentModal = () => {
  if (balance.value <= 0) {
    alert("No payment required.");
    return;
  }
  // Prefill the modal amount with entered amount or remaining balance
  paymentModalAmount.value =
    paymentAmount.value && paymentAmount.value > 0
      ? Math.min(paymentAmount.value, remainingBalance.value)
      : remainingBalance.value;
  selectedPaymentMethod.value = "cash";
  selectedPaymentCardType.value = "";
  showPaymentModal.value = true;
};

const searchProducts = () => {
  router.get(
    route("return.index"),
    {
      sales_search: searchQuery.value,
      sales_date_from: dateFrom.value,
      sales_date_to: dateTo.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
      only: ["salesProducts"],
    }
  );
};

const searchByInvoice = () => {
  if (!invoiceNumber.value || !invoiceNumber.value.trim()) {
    alert("Please enter an invoice number.");
    return;
  }
  // Clear current selections to avoid mixing invoices
  selectedProducts.value = [];
  replacementProducts.value = [];
  hasSearched.value = true;

  router.get(
    route("return.index"),
    {
      sales_search: invoiceNumber.value.trim(),
    },
    {
      preserveState: true,
      preserveScroll: true,
      only: ["salesProducts"],
    }
  );
};

const formatRefundMethod = (method, cardType = null) => {
  if (method === "card") {
    if (cardType === "visa") return "💳 Card (Visa)";
    if (cardType === "mastercard") return "💳 Card (MasterCard)";
    return "💳 Card";
  }

  return {
    cash: "💵 Cash",
    cheque: "📄 Cheque",
    bank_transfer: "🏦 Bank Transfer",
  }[method] || method;
};

const isSelected = (productId) => {
  return selectedProducts.value.some((p) => p.id === productId);
};

const addProduct = (product) => {
  selectedProducts.value.push({
    ...product,
    return_quantity: 1,
  });
};

const removeProduct = (productId) => {
  selectedProducts.value = selectedProducts.value.filter((p) => p.id !== productId);
};

const openReturnQtyModal = (product) => {
  currentProductToAdd.value = product;
  tempReturnQuantity.value = 1;
  showReturnQtyModal.value = true;
};

const closeReturnQtyModal = () => {
  showReturnQtyModal.value = false;
  currentProductToAdd.value = null;
  tempReturnQuantity.value = 1;
};

const confirmAddProduct = () => {
  if (!tempReturnQuantity.value || tempReturnQuantity.value < 1) {
    alert("Please enter a valid return quantity.");
    return;
  }

  if (tempReturnQuantity.value > (currentProductToAdd.value?.quantity_sold || 0)) {
    alert(
      `Return quantity cannot exceed sold quantity of ${currentProductToAdd.value.quantity_sold}`
    );
    return;
  }

  // Add product with the specified return quantity
  selectedProducts.value.push({
    ...currentProductToAdd.value,
    return_quantity: tempReturnQuantity.value,
  });

  closeReturnQtyModal();
};

const clearSelection = () => {
  selectedProducts.value = [];
  replacementProducts.value = [];
};

const canSubmit = () => {
  if (returnType.value === 1) {
    // Product Return: Must have selected products, replacement products, and payment satisfied
    return (
      selectedProducts.value.length > 0 &&
      replacementProducts.value.length > 0 &&
      paymentSatisfied.value
    );
  } else {
    // Cash Return: Must have refund amount and method
    return (
      refundAmount.value > 0 && refundMethod.value && selectedProducts.value.length > 0
    );
  }
};

const buildReceiptHtml = (payload) => {
  const bill = page.props.billSetting || {};
  const allowed = ["58mm", "80mm", "112mm", "210mm"];
  const rawSize = (bill.print_size || "80mm").toString();
  const width = allowed.includes(rawSize) ? rawSize : "80mm";

  const header = {
    title: bill.company_name || "SALES RETURN",
    address: bill.address,
    phones: [bill.mobile_1, bill.mobile_2].filter(Boolean).join(" / "),
    email: bill.email,
    website: bill.website_url,
    logo: bill.logo_path,
  };

  const {
    returnNo,
    date,
    customer,
    invoice,
    typeText,
    returnedItems,
    replacementItems,
    returnedTotal,
    replacementTotal,
    subTotal,
    discount,
    total,
    cash,
    remainingBalance,
    paymentLabel,
    paymentAmount,
  } = payload;

  return `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Return Receipt - ${returnNo}</title>
      <style>
        @page { size: ${width} auto; margin: 0; }
        @media print { body{ margin:0; padding:0 } }
        *{ margin:0; padding:0; box-sizing:border-box }
        body{ font-family: Arial, Helvetica, sans-serif; font-size:13px; width:${width}; padding:6mm 5mm; color:#000; line-height:1.4; font-weight:600 }
        .header{text-align:center; margin-bottom:8px; padding-bottom:8px; border-bottom:2px dashed #000}
        .header h1{font-size:16px; font-weight:900; margin-bottom:4px}
        .info{margin:8px 0; font-size:12px}
        .info-row{display:flex; justify-content:space-between; margin:2px 0}
        .items-table{width:100%; margin:8px 0; font-size:12px; border-collapse:collapse}
        .items-table th{text-align:left; border-bottom:2px solid #000; padding:3px 2px}
        .items-table td{padding:3px 2px; border-bottom:1px dotted #000}
        .item-name{width:38%; word-wrap:break-word}
        .item-qty{width:12%; text-align:center}
        .item-price{width:25%; text-align:right}
        .item-total{width:25%; text-align:right}
        .section-title{margin-top:4px; font-weight:900}
        .totals{margin-top:8px; font-size:12px}
        .total-row{display:flex; justify-content:space-between; margin:3px 0}
        .total-row.grand{font-size:15px; font-weight:900; border-top:2px solid #000; border-bottom:2px solid #000; padding:6px 0; margin:8px 0}
        .footer{text-align:center; margin-top:12px; padding-top:8px; border-top:2px dashed #000; font-size:12px}
      </style>
    </head>
    <body>
      <div class="receipt-container">
        <div class="header">
          ${
            header.logo
              ? `<div style="margin-bottom:6px;"><img src="/storage/${header.logo}" alt="logo" style="max-height:40px; max-width:100%; object-fit:contain;"/></div>`
              : ""
          }
          <h1>${header.title}</h1>
          ${header.address ? `<p>${header.address}</p>` : ""}
          ${header.phones ? `<p>Tel: ${header.phones}</p>` : ""}
          ${header.email ? `<p>${header.email}</p>` : ""}
          ${header.website ? `<p>${header.website}</p>` : ""}
        </div>

        <div class="info">
          <div class="info-row"><span><strong>Return No:</strong></span><span>${returnNo}</span></div>
          <div class="info-row"><span><strong>Date:</strong></span><span>${date}</span></div>
          <div class="info-row"><span><strong>Customer:</strong></span><span>${customer}</span></div>
          <div class="info-row"><span><strong>Invoice:</strong></span><span>${invoice}</span></div>
          <div class="info-row"><span><strong>Type:</strong></span><span>${typeText}</span></div>
        </div>

        <div class="section-title">Returned Items</div>
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
            ${returnedItems
              .map(
                (item) => `
              <tr>
                <td class="item-name">${item.name}</td>
                <td class="item-qty">${item.qty}</td>
                <td class="item-price">${item.price.toFixed(2)}</td>
                <td class="item-total">${item.total.toFixed(2)}</td>
              </tr>
            `
              )
              .join("")}
          </tbody>
        </table>

        ${
          replacementItems.length
            ? `
          <div class="section-title">Replacement Items</div>
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
              ${replacementItems
                .map(
                  (item) => `
                <tr>
                  <td class="item-name">${item.name}</td>
                  <td class="item-qty">${item.qty}</td>
                  <td class="item-price">${item.price.toFixed(2)}</td>
                  <td class="item-total">${item.total.toFixed(2)}</td>
                </tr>
              `
                )
                .join("")}
            </tbody>
          </table>
        `
            : ""
        }

         <div class="totals">
          <div class="total-row"><span>Returned Total:</span><span>${
            page.props.currency || ""
          } ${returnedTotal.toFixed(2)}</span></div>
          <div class="total-row"><span>Replacement Total:</span><span>${
            page.props.currency || ""
          } ${replacementTotal.toFixed(2)}</span></div>
          <div class="total-row grand"><span>${paymentLabel.toUpperCase()}:</span><span>${
    page.props.currency || ""
  } ${paymentAmount.toFixed(2)}</span></div>
        </div>

        <div class="totals">
          <div class="total-row"><span>Sub Total</span><span>${
            page.props.currency || ""
          } ${subTotal.toFixed(2)}</span></div>
          <div class="total-row"><span>Discount</span><span>${
            page.props.currency || ""
          } ${discount.toFixed(2)}</span></div>
          <div class="total-row"><span>Custom Discount</span><span>0.00 %</span></div>
          <div class="total-row"><span>Total</span><span>${
            page.props.currency || ""
          } ${total.toFixed(2)}</span></div>
          <div class="total-row"><span>Cash</span><span>${
            page.props.currency || ""
          } ${cash.toFixed(2)}</span></div>
          <div class="total-row"><span>Balance</span><span>${
            page.props.currency || ""
          } ${remainingBalance.toFixed(2)}</span></div>
        </div>

        <div class="footer"><p><strong>${
          bill.footer_description || "Thank you!"
        }
      </div>

      <script type="text/javascript">
        let printExecuted = false;
        window.onload = function(){ setTimeout(function(){ if(!printExecuted){ printExecuted = true; window.print(); } }, 300); };
        window.onafterprint = function(){ setTimeout(function(){ window.close(); }, 200); };
        setTimeout(function(){ if(!window.closed){ window.close(); } }, 5000);
      <\/script>
    </body>
    </html>
  `;
};

const printDraftBill = () => {
  const returnNo = `RET-DRAFT-${Date.now()}`;
  const date = new Date().toLocaleDateString("en-GB");
  const customer = selectedProducts.value[0]?.customer_name || "Walk-in Customer";
  const invoice = selectedProducts.value[0]?.sale_no || "N/A";
  const returnedItems = selectedProducts.value.map((p) => ({
    name: p.product_name,
    qty: p.return_quantity || 0,
    price: parseFloat(p.price || 0),
    total: (p.return_quantity || 0) * parseFloat(p.price || 0),
  }));
  const replacementItems = replacementProducts.value.map((p) => ({
    name: p.name,
    qty: p.quantity || 0,
    price: parseFloat(p.unit_price || 0),
    total: (p.quantity || 0) * parseFloat(p.unit_price || 0),
  }));

  // For product return: sub total is the payment from customer (balance when > 0)
  const subTotal = returnType.value === 1 && balance.value > 0 ? balance.value : 0;
  const discount = 0; // Can be updated if discount is tracked
  const total = subTotal - discount;
  const cash = paymentAmount.value;
  const remainingBalance = Math.max(0, cash - total);

  const payload = {
    returnNo,
    date,
    customer,
    invoice,
    typeText: returnType.value === 2 ? "Cash Refund" : "Product Return",
    returnedItems,
    replacementItems,
    returnedTotal: returnTotal.value,
    replacementTotal: replacementTotal.value,
    subTotal: subTotal,
    discount: discount,
    total: total,
    cash: cash,
    remainingBalance: remainingBalance,
    paymentLabel: balanceLabel.value,
    paymentAmount: Math.abs(balance.value),
  };

  const html = buildReceiptHtml(payload);
  const w = window.open("", "_blank", "width=320,height=640");
  if (!w) {
    alert("Please allow pop-ups to print receipt");
    return;
  }
  w.document.write(html);
  w.document.close();
};
const closeModal = () => {
  emit("update:open", false);
  clearSelection();
  returnType.value = 1;
  searchQuery.value = "";
  dateFrom.value = "";
  dateTo.value = "";
  invoiceNumber.value = "";
  hasSearched.value = false;
  paymentAmount.value = 0;
  paymentApplied.value = false;
  refundAmount.value = 0;
  refundMethod.value = "cash";
  notes.value = "";
};

const confirmPayment = () => {
  if (!paymentModalAmount.value || paymentModalAmount.value <= 0) {
    alert("Please enter a valid payment amount.");
    return;
  }

  // Only enforce balance limit for non-cash payments
  if (
    selectedPaymentMethod.value !== "cash" &&
    paymentModalAmount.value > remainingBalance.value
  ) {
    alert(
      `Payment amount cannot exceed the remaining balance of ${
        currencySymbol.value
      } ${remainingBalance.value.toFixed(2)}`
    );
    return;
  }

  if (selectedPaymentMethod.value === "card" && !selectedPaymentCardType.value) {
    alert("Please select Card Type (Visa or MasterCard).");
    return;
  }

  // Add payment entry to payments array
  payments.value.push({
    amount: paymentModalAmount.value,
    method: selectedPaymentMethod.value,
    card_type:
      selectedPaymentMethod.value === "card" ? selectedPaymentCardType.value : null,
  });

  // Close the modal and reset fields
  showPaymentModal.value = false;
  paymentModalAmount.value = 0;
  selectedPaymentMethod.value = "cash";
  selectedPaymentCardType.value = "";
  paymentAmount.value = totalPaid.value;
};

const removePayment = (index) => {
  payments.value.splice(index, 1);
  paymentAmount.value = totalPaid.value;
};

const submitReturn = () => {
  if (!canSubmit()) return;

  // Require payment if customer needs to pay
  if (returnType.value === 1 && balance.value > 0 && !paymentSatisfied.value) {
    alert(
      `Please add payment of at least ${currencySymbol.value} ${balance.value.toFixed(
        2
      )} before completing.`
    );
    return;
  }

  // Validation for product returns
  if (returnType.value === 1) {
    const invalidProducts = selectedProducts.value.filter(
      (p) =>
        !p.return_quantity || p.return_quantity < 1 || p.return_quantity > p.quantity_sold
    );

    if (invalidProducts.length > 0) {
      alert("Please enter valid return quantities for all selected products.");
      return;
    }
  }

  // Validation for cash returns
  if (returnType.value === 2) {
    if (!refundAmount.value || refundAmount.value <= 0) {
      alert("Please enter a valid refund amount.");
      return;
    }
    if (!refundMethod.value) {
      alert("Please select a refund method.");
      return;
    }
  }

  processing.value = true;

  const postData = {
    return_type: returnType.value,
    refund_amount: returnType.value === 2 ? refundAmount.value : null,
    // If product return and refund due to customer, enforce cash-only refund method
    refund_method:
      returnType.value === 2 ? refundMethod.value : balance.value < 0 ? "cash" : null,
    notes: notes.value,
    // Multi-payment: send all payment entries
    payments: balance.value > 0 ? payments.value : [],
    selected_products: selectedProducts.value.map((p) => ({
      sales_product_id: p.id,
      return_quantity: p.return_quantity,
    })),
    replacement_products: replacementProducts.value.map((p) => ({
      product_id: p.product_id,
      quantity: p.quantity,
      unit_price: p.unit_price,
    })),
  };

  router.post(route("return.create-from-sales"), postData, {
    onSuccess: async () => {
      await logActivity("create", "sales_returns", {
        return_type: returnType.value === 1 ? "Product Return" : "Cash Return",
        products_count: selectedProducts.value.length,
        total_quantity: selectedProducts.value.reduce(
          (sum, p) => sum + parseInt(p.return_quantity),
          0
        ),
        refund_amount:
          returnType.value === 2 ? refundAmount.value : calculateTotalRefund(),
      });
      emit("success");
      closeModal();
      processing.value = false;
    },
    onError: (errors) => {
      processing.value = false;
      console.error("Return creation failed:", errors);
      alert("Failed to create return. Please check the form and try again.");
    },
  });
};
</script>

<style scoped>
/* Add any custom styles if needed */
</style>
