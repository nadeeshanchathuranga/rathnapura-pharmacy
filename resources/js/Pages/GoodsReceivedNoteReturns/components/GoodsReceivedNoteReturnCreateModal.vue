<template>
  <Modal :show="open" @close="close" max-width="6xl">
    <div class="p-6 bg-gray-50">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
        <h2 class="text-2xl font-bold text-blue-600">✨ New Goods Received Notes Return</h2>
        <button type="button" @click="close" class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-200 rounded-full transition-all duration-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form @submit.prevent="submitForm">

        <!-- GRN DETAILS -->
        <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
          <h3 class="mb-3 text-lg font-semibold text-blue-600 flex items-center gap-2">
            📋 GRN Details
          </h3>

          <div class="grid grid-cols-2 gap-3 mb-4">

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">GRN Number <span class="text-red-600">*</span></label>
              <select v-model="form.grn_id" @change="onGrnSelect" class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                <option value="">Select GRN Number</option>
                <option v-for="g in grns" :key="g.id" :value="g.id">{{ g.goods_received_note_no || g.grn_no || g.grnNo }}</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Date <span class="text-red-600">*</span></label>
            <input
                  type="date"
                  class="w-full px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-lg focus:outline-none cursor-not-allowed font-medium"
                  :value="form.grn_date"
                  readonly
                  disabled
                />
            </div> 
          </div>
        </div>

         <!-- PRODUCTS SECTION -->
        <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
          <h3 class="mb-3 text-lg font-semibold text-blue-600 flex items-center gap-2">
            📦 Products
          </h3>
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b-2 border-blue-600">
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Product</th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Unit</th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Available Qty</th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Purchase Price ({{ page.props.currency || '' }})</th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Discount</th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Total ({{ page.props.currency || '' }})</th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Return Qty</th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Action</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(product, index) in products" :key="index" class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200">

                  <td class="px-4 py-4">
                    <div class="text-gray-900 font-medium">{{ product.product_name || (availableProducts.find(p => p.id === product.product_id)?.name) || 'N/A' }}</div>
                  </td>

                  <td class="px-4 py-4">
                    <select v-model.number="product.unit_id" @change="onUnitSelect(index)" class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                      <option value="">Select Unit</option>
                      <option v-if="getProductPurchaseUnit(product)" :value="getProductPurchaseUnit(product).id">
                        {{ getProductPurchaseUnit(product).name }} (Purchase)
                      </option>
                      <option v-if="getProductTransferUnit(product)" :value="getProductTransferUnit(product).id">
                        {{ getProductTransferUnit(product).name }} (Transfer)
                      </option>
                      <option v-if="getProductSalesUnit(product)" :value="getProductSalesUnit(product).id">
                        {{ getProductSalesUnit(product).name }} (Sales)
                      </option>
                    </select>
                  </td>

                  <td class="px-4 py-4">
                    <div class="space-y-1 flex flex-wrap gap-1">
                      <!-- If PURCHASE unit selected: Show Box + Bundle available -->
                      <template v-if="getProductPurchaseUnit(product) && !isTransferUnitSelected(product) && !isSalesUnitSelected(product)">
                        <div class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">
                          <span class="text-[10px] font-semibold">{{ getProductPurchaseUnit(product).name }}:</span>
                          <span class="font-bold">{{ formatNumber(product.product?.store_quantity_in_purchase_unit ?? 0) }}</span>
                        </div>
                        <div class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-100 text-amber-700 rounded text-xs">
                          <span class="text-[10px] font-semibold">{{ getProductTransferUnit(product).name }}:</span>
                          <span class="font-bold">{{ formatNumber(product.product?.store_quantity_in_transfer_unit ?? 0) }}</span>
                        </div>
                         <div class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-100 text-amber-700 rounded text-xs">
                          <span class="text-[10px] font-semibold">{{ getProductSalesUnit(product).name }}:</span>
                          <span class="font-bold">{{ formatNumber(product.product?.store_quantity_in_sale_unit ?? 0) }}</span>
                        </div>

                      </template>
                      
                      <!-- If TRANSFER unit selected: Show converted Bundle + converted Bottle -->
                      <template v-else-if="getProductTransferUnit(product) && isTransferUnitSelected(product) && !isSalesUnitSelected(product)">
                        <div class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-100 text-amber-700 rounded text-xs">
                          <span class="text-[10px] font-semibold">{{ getProductTransferUnit(product).name }}:</span>
                          <span class="font-bold">{{ formatNumber(getConvertedTransferQuantity(product)) }}</span>
                        </div>
                        <div class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs">
                          <span class="text-[10px] font-semibold">{{ getProductSalesUnit(product).name }}:</span>
                          <span class="font-bold">{{ formatNumber(product.product?.store_quantity_in_sale_unit ?? 0) }}</span>
                        </div>
                      </template>
                      
                      <!-- If SALES unit selected: Show converted Bottle only -->
                      <template v-else-if="getProductSalesUnit(product) && isSalesUnitSelected(product)">
                        <div class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs">
                          <span class="text-[10px] font-semibold">{{ getProductSalesUnit(product).name }}:</span>
                          <span class="font-bold">{{ formatNumber(getSalesUnitQuantity(product)) }}</span>
                        </div>
                      </template>
                    </div>
                  </td>

                  <td class="px-4 py-4">
                    <div class="text-gray-900">{{ formatNumber(getUnitPrice(product)) }}</div>
                  </td>

                  <td class="px-4 py-4">
                    <div class="text-gray-900">{{ formatNumber(product.discount) }}</div>
                  </td>

                  <td class="px-4 py-4">
                    <span class="font-semibold text-gray-900">
                    {{ formatNumber(product.total) }}
                    </span>
                  </td>

                  <td class="px-4 py-4">
                    <input v-model.number="product.returnQty" type="number" step="0.01" min="0.01"
                           @input="calculateTotal(index)"
                           class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                  </td>

                  <td class="px-4 py-4">
                    <button type="button" @click="removeProduct(index)"
                            class="px-4 py-2 text-xs font-medium text-white bg-red-600 rounded-[5px] hover:bg-red-700 transition-all duration-200">
                      Remove
                    </button>
                  </td>

                </tr>

                <tr v-if="products.length === 0">
                  <td colspan="8" class="px-6 py-8 text-center text-gray-500 font-medium">
                    No products added yet. Click "Add Product" to start.
                  </td>
                </tr>

              </tbody>

              <tfoot v-if="products.length > 0" class="bg-gray-100 border-t-2 border-gray-300">
                <tr>
                  <td colspan="5" class="px-4 py-3 text-right font-semibold text-gray-900">Grand Total:</td>
                  <td class="px-4 py-3 font-bold text-lg text-gray-900">
                  {{ formatNumber(grandTotal) }}
                  ({{ page.props.currency || '' }})
                  </td>
                  <td></td>
                </tr>
              </tfoot>

            </table>
          </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
          <button type="button" @click="close"
                  class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-[5px] hover:bg-gray-50 transition-all duration-200">
            Cancel
          </button>

          <button type="submit" :disabled="products.length === 0"
                  class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-[5px] hover:bg-blue-700 disabled:opacity-50 transition-all duration-200">
            ✨ Create GRN Return
          </button>
        </div>

      </form>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
const page = usePage()
import axios from 'axios'

const props = defineProps({
  open: Boolean,
  suppliers: Array,
  purchaseOrders: Array,
  availableProducts: Array,
  measurementUnits: Array,
  grns: Array,
  user: Object,
})

const emit = defineEmits(['update:open'])

const form = ref({
  grn_id: '',
  grn_date: new Date().toISOString().split('T')[0],
  por_id: '',
  discount: 0,
  tax_total: 0,
  remarks: '',
  user_id: props.user?.id || null,
  grn_subtotal: 0,
})

const products = ref([])
const formErrors = ref({})

const grandTotal = computed(() => {
  return products.value.reduce((sum, product) => sum + (parseFloat(product.total) || 0), 0)
})

const close = () => {
  emit('update:open', false)
  resetForm()
}

const resetForm = () => {
  form.value = {
    grn_id: '',
    grn_date: new Date().toISOString().split('T')[0],
    por_id: '',
    discount: 0,
    tax_total: 0,
    remarks: '',
    user_id: props.user?.id || null,
    grn_subtotal: 0,
  }
  products.value = []
}

const addProduct = () => {
  products.value.push({
    product_id: null,
    product_name: '',
    qty: 1,
    purchase_price: 0,
    discount: 0,
    unit_id: null,
    unit_name: '',
    total: 0,
  })
}

const loadPOData = () => {
    if (!form.value.por_id) {
        products.value = [];
        return;
    }

    router.get(`/po/${form.value.por_id}/details`, {}, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
            const poProducts = page.props.poProducts || [];

            if (poProducts.length === 0) {
                console.warn('No products found in this PO');
                return;
            }

            products.value = poProducts.map(item => {
                const qty = parseFloat(item.quantity) || 1;
                const purchasePrice = parseFloat(item.price) || parseFloat(item.unit_price) || 0;
                const total = qty * purchasePrice;
                
                return {
                    product_id: item.product_id,
                    qty: qty,
                    purchase_price: purchasePrice,
                    discount: 0,
                    unit: item.unit || '',
                    total: total,
                };
            });

            console.log('Loaded PO products:', products.value.length);
        },
        onError: (errors) => {
            console.error('Failed to load PO data:', errors);
            alert('Failed to load Purchase Order details');
        },
    });
};

const onGrnSelect = async () => {
  console.log('🔍 onGrnSelect called')
  console.log('grn_id=', form.value.grn_id)

  if (!form.value.grn_id) {
    console.warn('⚠️ No GRN ID selected')
    products.value = [];
    return;
  }

  try {
    const url = `/good-receive-note-returns/${form.value.grn_id}/details`;
    console.log('📡 Making API request to:', url)
    
    // Call API endpoint to get GRN details with available quantities from product_available_quantities table
    const response = await axios.get(url);
    
    console.log('✅ API Response from goodsReceivedNoteReturnDetails:', response.data)
    
    if (!response.data || !response.data.goodsReceivedNote) {
      console.error('❌ Invalid response from API - missing goodsReceivedNote');
      products.value = [];
      return;
    }

    const goodsReceivedNote = response.data.goodsReceivedNote;
    const grnProducts = response.data.goodsReceivedNoteProducts || [];

    if (!grnProducts.length) {
      products.value = [];
      return;
    }

    // Map API response to product items
    products.value = grnProducts.map(item => {
      const purchasePrice = parseFloat(item.purchase_price) || 0;
      const discount = parseFloat(item.discount) || 0;
      
      return {
        product_id: item.product_id,
        product_name: item.name || 'N/A',
        qty: parseFloat(item.quantity) || 1,
        purchase_price: purchasePrice,
        discount: discount,
        unit_id: item.purchase_unit?.id || null,
        unit_name: item.purchase_unit?.name || '',
        total: parseFloat(item.total) || 0,
        returnQty: 0,
        product: {
          id: item.product_id,
          name: item.name,
          purchase_unit_id: item.purchase_unit?.id,
          transfer_unit_id: item.transfer_unit?.id,
          sales_unit_id: item.sales_unit?.id,
          purchase_unit: item.purchase_unit,
          transfer_unit: item.transfer_unit,
          sales_unit: item.sales_unit,
          purchase_to_transfer_rate: item.purchase_to_transfer_rate || 1,
          transfer_to_sales_rate: item.transfer_to_sales_rate || 1,
          // Store available quantities from product_available_quantities table
          store_quantity_in_purchase_unit: item.available_quantity_in_purchase_unit || 0,
          store_quantity_in_transfer_unit: item.available_quantity_in_transfer_unit || 0,
          store_quantity_in_sale_unit: item.available_quantity_in_sales_unit || 0,
        },
      };

    });   


    // Update GRN date
    if (goodsReceivedNote.grn_date) {
      form.value.grn_date = goodsReceivedNote.grn_date;
    }

    // Recalculate totals
    products.value.forEach((_, idx) => calculateTotal(idx));

    console.log('Loaded GRN products from API:', products.value.length);
    console.log('Products with available quantities:', products.value);

  } catch (error) {
    console.error('Failed to load GRN details:', error);
    alert('Failed to load GRN details. Please try again.');
    products.value = [];
  }
};

const removeProduct = (index) => {
  products.value.splice(index, 1)
}

const onProductSelect = (index) => {
  const product = products.value[index]
  const selectedProduct = props.availableProducts.find(p => p.id === product.product_id)

  if (selectedProduct) {
    product.product = selectedProduct // Store nested product reference
    product.purchase_price = selectedProduct.price || selectedProduct.purchase_price || 0
    product.unit_id = selectedProduct.purchase_unit_id ?? null
    const purchaseUnit = getProductPurchaseUnit(product)
    product.unit_name = purchaseUnit?.name ?? ''
    product.product_name = selectedProduct.name || selectedProduct.product_name || ''
    calculateTotal(index)
  }
}

const calculateTotal = (index) => {
  const p = products.value[index]
  const nestedProduct = p.product || {}
  console.log('Calculating total for product index', nestedProduct)
  const basePrice = parseFloat(p.purchase_price) || 0
  const transferRate = parseFloat(nestedProduct.purchase_to_transfer_rate) || 1
  const salesRate = parseFloat(nestedProduct.transfer_to_sales_rate) || 1
  const selectedUnitId = p.unit_id ?? null
  const returnQty = parseFloat(p.returnQty) || 0

  // If no unit selected, return 0
  if (!selectedUnitId) {
    p.total = 0
    return
  }

  let totalBeforeReturn = 0
  let unitPrice = 0

  // If PURCHASE unit selected: (purchase_qty × purchase_price) + (transfer_qty × transfer_price)
  if (Number(selectedUnitId) === Number(nestedProduct.purchase_unit_id)) {
    const purchaseQty = parseFloat(nestedProduct.store_quantity_in_purchase_unit) || 0
    const transferQty = parseFloat(nestedProduct.store_quantity_in_transfer_unit) || 0
    const transferPrice = basePrice / transferRate
    totalBeforeReturn = (purchaseQty * basePrice) + (transferQty * transferPrice)
    unitPrice = basePrice
  }
  // If TRANSFER unit selected: (transfer_qty × transfer_price) + (sales_qty × sales_price)
  else if (Number(selectedUnitId) === Number(nestedProduct.transfer_unit.id)) {
    const purchaseQty = parseFloat(nestedProduct.store_quantity_in_purchase_unit) || 0
    const transferQty = parseFloat(nestedProduct.store_quantity_in_transfer_unit) || 0
    const salesQty = parseFloat(nestedProduct.store_quantity_in_sale_unit) || 0
    const transferQtyFromPurchase = purchaseQty * transferRate
    const totalTransferQty = transferQtyFromPurchase + transferQty
    const transferPrice = basePrice / transferRate
    const salesPrice = basePrice / (transferRate * salesRate)
    totalBeforeReturn = (totalTransferQty * transferPrice) + (salesQty * salesPrice)
    unitPrice = transferPrice
  }
  // If SALES unit selected: sales_qty × sales_price
  else if (Number(selectedUnitId) === Number(nestedProduct.sales_unit_id)) {
    const purchaseQty = parseFloat(nestedProduct.store_quantity_in_purchase_unit) || 0
    const transferQty = parseFloat(nestedProduct.store_quantity_in_transfer_unit) || 0
    const transferQtyFromPurchase = purchaseQty * transferRate
    const totalTransferQty = transferQtyFromPurchase + transferQty
    const salesQty = parseFloat(nestedProduct.store_quantity_in_sale_unit) || 0
    const totalSalesQty = totalTransferQty * salesRate + salesQty
    const salesPrice = basePrice / (transferRate * salesRate)
    totalBeforeReturn = totalSalesQty * salesPrice
    unitPrice = salesPrice
  }

  // Deduct return quantity value from total
  p.total = totalBeforeReturn - (returnQty * unitPrice)
}

const getProductPurchaseUnit = (product) => {
  const nestedProduct = product.product || {}
  // Try direct purchaseUnit first (eager-loaded from controller)
  if (nestedProduct.purchase_unit) return nestedProduct.purchase_unit
  if (nestedProduct.purchaseUnit) return nestedProduct.purchaseUnit
  // Fallback: find in measurementUnits by ID
  const purchaseUnitId = nestedProduct.purchase_unit_id
  if (purchaseUnitId && Array.isArray(props.measurementUnits)) {
    return props.measurementUnits.find(u => Number(u.id) === Number(purchaseUnitId))
  }
  return null
}

const getProductTransferUnit = (product) => {
  const nestedProduct = product.product || {}
  // Try direct transferUnit first (eager-loaded from controller)
  if (nestedProduct.transfer_unit) return nestedProduct.transfer_unit
  if (nestedProduct.transferUnit) return nestedProduct.transferUnit

  // Fallback: find in measurementUnits by ID
  const transferUnitId = nestedProduct.transfer_unit_id
  if (transferUnitId && Array.isArray(props.measurementUnits)) {
    return props.measurementUnits.find(u => Number(u.id) === Number(transferUnitId))
  }
  return null
}

const getProductSalesUnit = (product) => {
  const nestedProduct = product.product || {}
  // Try direct salesUnit first (eager-loaded from controller)
  if (nestedProduct.sales_unit) return nestedProduct.sales_unit
  if (nestedProduct.salesUnit) return nestedProduct.salesUnit
  // Fallback: find in measurementUnits by ID
  const salesUnitId = nestedProduct.sales_unit_id
  if (salesUnitId && Array.isArray(props.measurementUnits)) {
    return props.measurementUnits.find(u => Number(u.id) === Number(salesUnitId))
  }
  return null
}

const onUnitSelect = (index) => {
  const product = products.value[index]
  const nestedProduct = product.product || {}

  // Find which unit was selected and update unit_id
  if (product.unit_id === nestedProduct.purchase_unit_id) {
    product.unit_name = nestedProduct.purchase_unit?.name || ''
  } else if (product.unit_id === nestedProduct.transfer_unit_id) {
    product.unit_name = nestedProduct.transfer_unit?.name || ''
  } else if (product.unit_id === nestedProduct.sales_unit_id) {
    product.unit_name = nestedProduct.sales_unit?.name || ''
  }

  // Always recalculate total when unit changes
  calculateTotal(index)
}

const getUnitName = (product) => {
  // Prefer explicit unit_name if already present
  if (product.unit_name && String(product.unit_name).trim() !== '') return product.unit_name

  // Resolve by unit_id against measurementUnits (allow string/number mismatches)
  const uid = product.unit_id ?? product.unitId ?? null
  if (uid != null && Array.isArray(props.measurementUnits)) {
    const asNumber = Number(uid)
    const found = props.measurementUnits.find(u => {
      // handle case where u.id may be string or number
      return (u.id == uid) || (Number(u.id) === asNumber)
    })
    if (found && (found.name || found.unit_name)) return found.name || found.unit_name
  }

  // Try nested product data shapes (some payloads have nested purchaseUnit / measurementUnit)
  if (product.product) {
    const nested = product.product
    if (nested.purchaseUnit?.name) return nested.purchaseUnit.name
    if (nested.measurementUnit?.name) return nested.measurementUnit.name
    if (nested.measurement_unit?.name) return nested.measurement_unit.name
  }

  return 'N/A'
}

const getPurchaseUnitQuantity = (product) => {
  const nestedProduct = product.product || {}
  return nestedProduct.store_quantity_in_purchase_unit ?? 0
}

const getTransferUnitQuantity = (product) => {
  const nestedProduct = product.product || {}
  return nestedProduct.loose_bundles
 ?? 0
}

const getSalesUnitQuantity = (product) => {
  const nestedProduct = product.product || {}
  const purchaseQty = parseFloat(nestedProduct.store_quantity_in_purchase_unit) || 0
  const transferQty = parseFloat(nestedProduct.store_quantity_in_transfer_unit) || 0
  const transferRate = parseFloat(nestedProduct.purchase_to_transfer_rate) || 1
  const salesRate = parseFloat(nestedProduct.transfer_to_sales_rate) || 1
  const salesQty = parseFloat(nestedProduct.store_quantity_in_sale_unit) || 0

  return (purchaseQty * transferRate * salesRate) + (transferQty * salesRate) + salesQty
}

const getConvertedTransferQuantity = (product) => {
  const nestedProduct = product.product || {}
  const purchaseQty = parseFloat(nestedProduct.store_quantity_in_purchase_unit) || 0
  const transferQty = parseFloat(nestedProduct.store_quantity_in_transfer_unit) || 0
  const transferRate = parseFloat(nestedProduct.purchase_to_transfer_rate) || 1
  const salesRate = parseFloat(nestedProduct.transfer_to_sales_rate) || 1
  const salesQty = parseFloat(nestedProduct.store_quantity_in_sale_unit) || 0
  
  // Convert: (purchase_qty × rate) + transfer_qty
  // Example: (5 boxes × 5) + 4 bundles = 25 + 4 = 29 bundles
  return (purchaseQty * transferRate) + transferQty
}

const isTransferUnitSelected = (product) => {
  const nestedProduct = product.product || {}
  const selectedUnitId = Number(product.unit_id ?? null)
  const transferUnitId = Number(nestedProduct.transfer_unit_id)
  return selectedUnitId === transferUnitId && selectedUnitId !== 0
}

const isSalesUnitSelected = (product) => {
  const nestedProduct = product.product || {}
  const selectedUnitId = Number(product.unit_id ?? null)
  const salesUnitId = Number(nestedProduct.sales_unit_id)
  return selectedUnitId === salesUnitId && selectedUnitId !== 0
}

const getConvertedSalesQuantity = (product) => {
  const nestedProduct = product.product || {}
  const purchaseQty = parseFloat(nestedProduct.store_quantity_in_purchase_unit) || 0
  const transferQty = parseFloat(nestedProduct.loose_bundles) || 0
  const salesQty = parseFloat(nestedProduct.store_quantity_in_sale_unit) || 0
  const transferRate = parseFloat(nestedProduct.purchase_to_transfer_rate) || 1
  const saleRate = parseFloat(nestedProduct.transfer_to_sales_rate) || 1
  
  console.log('Calculating converted sales quantity:', {
    purchaseQty,
    transferQty,
    salesQty,
    transferRate,
    saleRate,
  })
  // Convert: (purchase_qty × rate1 × rate2) + (transfer_qty × rate2) + sales_qty
  return (purchaseQty * transferRate * saleRate) + (transferQty * saleRate) + salesQty
}

const getProductPurchaseUnitSymbol = (product) => {
  const nestedProduct = product.product || {}
  return nestedProduct.purchase_unit?.symbol || 'Box'
}

const getProductTransferUnitSymbol = (product) => {
  const nestedProduct = product.product || {}
  return nestedProduct.transfer_unit?.symbol || 'Bundle'
}

const getAvailableQuantity = (product) => {
  const nestedProduct = product.product || {}
  const selectedUnitId = product.unit_id ?? null

  // If no unit is selected yet, return 0
  if (!selectedUnitId) {
    return 0
  }

  // If selected unit is PURCHASE unit
  if (Number(selectedUnitId) === Number(nestedProduct.purchase_unit_id)) {
    return nestedProduct.store_quantity_in_purchase_unit ?? 0
  }

  // If selected unit is TRANSFER unit
  if (Number(selectedUnitId) === Number(nestedProduct.transfer_unit_id)) {
    return nestedProduct.store_quantity_in_transfer_unit ?? nestedProduct.loose_bundles ?? 0
  }

  // If selected unit is SALES unit
  if (Number(selectedUnitId) === Number(nestedProduct.sales_unit_id)) {
    return nestedProduct.store_quantity_in_sale_unit ?? 0
  }

  return 0
}

const getAvailableUnitSymbol = (product) => {
  const nestedProduct = product.product || {}
  const selectedUnitId = product.unit_id ?? null

  if (!selectedUnitId) {
    return ''
  }

  if (Number(selectedUnitId) === Number(nestedProduct.purchase_unit_id)) {
    return nestedProduct.purchase_unit?.symbol || 'Box'
  }

  if (Number(selectedUnitId) === Number(nestedProduct.transfer_unit_id)) {
    return nestedProduct.transfer_unit?.symbol || 'Bundle'
  }

  if (Number(selectedUnitId) === Number(nestedProduct.sales_unit_id)) {
    return nestedProduct.sales_unit?.symbol || 'Piece'
  }

  return ''
}

const getUnitPrice = (product) => {
  const nestedProduct = product.product || {}
  const basePrice = parseFloat(product.purchase_price) || 0
  const selectedUnitId = product.unit_id ?? null

  // If no unit selected, return base price
  if (!selectedUnitId) {
    return basePrice
  }

  const transferRate = parseFloat(nestedProduct.purchase_to_transfer_rate) || 1
  const salesRate = parseFloat(nestedProduct.transfer_to_sales_rate) || 1

  // If PURCHASE unit selected: return price as is
  if (Number(selectedUnitId) === Number(nestedProduct.purchase_unit_id)) {
    return basePrice
  }

  // If TRANSFER unit selected: price / purchase_to_transfer_rate
  if (Number(selectedUnitId) === Number(nestedProduct.transfer_unit_id)) {
    return basePrice / transferRate
  }

  // If SALES unit selected: price / (purchase_to_transfer_rate * transfer_to_sales_rate)
  if (Number(selectedUnitId) === Number(nestedProduct.sales_unit_id)) {
    return basePrice / (transferRate * salesRate)
  }

  return basePrice
}

const formatNumber = (number) => {
  return parseFloat(number || 0).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

// Reset form when modal opens
watch(
    () => props.open,
    (newVal) => {
        if (newVal) {
      resetForm()
      console.log('CreateModal opened — measurementUnits prop:', props.measurementUnits)
        }
    }
);

const submitForm = () => {
  // Build payload with keys that match backend validation (note: backend expects `date`, not `grn_date`)
  const payload = {
    goods_received_note_id: form.value.grn_id,
    date: form.value.grn_date,
    user_id: form.value.user_id,
    remarks: form.value.remarks,
    // send products with `qty` set to the return quantity entered by the user
    products: products.value.map(p => {
      // Use the return quantity that the user entered in the modal
      const returnQty = parseFloat(p.returnQty ?? 0)
      return {
        product_id: p.product_id,
        unit_id: p.unit_id,
        qty: returnQty,
        remarks: p.remarks || null,
      }
    }),
  }

  console.log('Submitting GRN Return payload:', payload)

  router.post(route('good-receive-note-returns.store'), payload, {
    onSuccess: () => {
      formErrors.value = {}
      close()
    },
    onError: (errors) => {
      // errors may be an object of validation errors (field => [msgs]) or an Error-like object
      formErrors.value = errors || {}
      try {
        console.error('GRN return create error (details):', errors ? JSON.parse(JSON.stringify(errors)) : errors)
      } catch (err) {
        console.error('GRN return create error (raw):', errors)
      }
      // show a simple alert so the user notices (optional)
      if (errors && typeof errors === 'object' && Object.keys(errors).length) {
        const firstKey = Object.keys(errors)[0]
        const firstMsg = Array.isArray(errors[firstKey]) ? errors[firstKey][0] : errors[firstKey]
        alert('Failed to create GRN Return: ' + (firstMsg || 'See console for details'))
      } else {
        alert('Failed to create GRN Return. See console for details.')
      }
    },
  })
}
</script>
