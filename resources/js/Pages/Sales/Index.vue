<template>

    <Head title="New Sale" />

    <AppLayout>
        <div class="min-h-screen bg-gray-50 p-6">
            <div>
                <!-- Header -->
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <div class="flex items-center gap-4 mb-2">
                            <button @click="goToShopsTab"
                                class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200">
                                ← Back
                            </button>
                            <h1 class="text-3xl font-bold text-black">Sales</h1>
                        </div>
                        <p class="text-gray-400">
                            Create new invoice (F2: Focus Barcode | F4: Cash | F9: Card | F8: Clear | F12: Sales Return
                            | ESC: Home)
                        </p>
                    </div>
                    <div v-if="canAccessBillingActions" class="flex items-center gap-3">
                        <button @click="openUnpaidModal" data-shortcut="F11" :disabled="isShiftLocked"
                            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-orange-500 hover:bg-orange-600 text-white transition-all duration-200 shadow-sm"
                            :class="isShiftLocked ? 'opacity-60 cursor-not-allowed' : ''">
                            ⏳ Unpaid Sales (F11)
                        </button>
                        <button @click="goToCreateSalesReturn" data-shortcut="F12" :disabled="isShiftLocked"
                            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-blue-600 hover:bg-blue-700 text-white transition-all duration-200 shadow-sm"
                            :class="isShiftLocked ? 'opacity-60 cursor-not-allowed' : ''">
                            Create Sales Return (F12)
                        </button>
                        <div class="text-right">
                            <div class="text-sm text-gray-400">Invoice No.</div>
                            <div class="text-2xl font-bold text-blue-400">{{ invoice_no }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="isShiftLocked" class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5">
                    <div class="flex items-center gap-4">
                        <p class="text-red-700 text-sm font-medium">
                            No active shift found for your user. Start a shift before processing sales.
                        </p>
                        <button @click="openStartShiftModal" :disabled="startShiftForm.processing"
                            class="px-6 py-2 rounded-[8px] font-semibold text-sm bg-red-600 hover:bg-red-700 text-white transition-all duration-200 disabled:opacity-60 disabled:cursor-not-allowed">
                            {{ startShiftForm.processing ? 'Starting...' : 'Start Shift' }}
                        </button>
                    </div>

                    <p v-if="startShiftForm.errors.opening_till_amount" class="mt-2 text-xs text-red-700">
                        {{ startShiftForm.errors.opening_till_amount }}
                    </p>
                </div>

                <div :class="isShiftLocked ? 'pointer-events-none opacity-60 select-none' : ''">

                    <!-- Quotation Selector - Convert Quotation to Sale -->
                    <div v-if="quotations && quotations.length > 0"
                        class="bg-white rounded-2xl p-6 shadow-md mb-6 border border-gray-200">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-end">
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">📋 Load from Quotation
                                    (Convert to Sale)</label>
                                <select v-model="selectedQuotationId"
                                    class="w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-medium">
                                    <option value="">-- Select a Quotation --</option>
                                    <option v-for="q in quotations" :key="q.id" :value="q.id">
                                        {{ q.quotation_no }} - {{ q.customer_name }} - ({{
                                            page.props.currency || "Rs."
                                        }}) {{ parseFloat(q.total_amount || 0).toFixed(2) }} - {{ q.quotation_date }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <button @click="loadQuotationData" :disabled="!selectedQuotationId"
                                    class="w-full px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-[5px] transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    📥 Load Quotation
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Top Row - All Controls -->
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
                        <!-- Barcode Scanner -->
                        <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg p-4 shadow-lg">
                            <label class="block text-sm font-medium text-blue-100 mb-2">🔍 Scan Barcode</label>
                            <div class="flex gap-2">
                                <input ref="barcodeField" type="text" v-model="barcodeInput"
                                    data-shortcut="F2, AudioVolumeDown, VolumeDown" @click="focusBarcodeField"
                                    @keyup.enter="addByBarcode" placeholder="Scan barcode..."
                                    class="flex-1 px-3 py-2 bg-white text-gray-900 rounded-lg focus:ring-2 focus:ring-blue-300 font-mono" />
                                <button @click="addByBarcode" type="button"
                                    class="px-4 bg-white hover:bg-blue-50 text-blue-700 font-semibold rounded-lg transition">
                                    Add
                                </button>
                            </div>
                        </div>

                        <!-- Customer Information -->
                        <div class="bg-white rounded-xl p-4 shadow-md border border-gray-200">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">👤 Customer & Date</label>
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <select v-model="form.customer_id"
                                        class="no-arrow w-full px-4 py-2.5 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm pr-12 font-medium"
                                        title="Select Customer">
                                        <option value="">-- Select Customer --</option>
                                        <option v-for="customer in activeCustomers" :key="customer.id"
                                            :value="customer.id">
                                            {{ customer.name }}
                                        </option>
                                    </select>
                                    <button type="button" @click="openCustomerModal"
                                        class="absolute right-2 top-1/2 transform -translate-y-1/2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-full p-1 transition"
                                        title="Add New Customer">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </button>
                                </div>
                                <input type="date" v-model="form.sale_date"
                                    class="px-4 py-2.5 bg-gray-100 text-gray-800 border border-gray-300 rounded-[5px] text-sm font-medium"
                                    readonly tabindex="-1" @keydown.prevent />
                            </div>
                        </div>

                        <!-- Customer Type / Price -->
                        <div class="bg-white rounded-xl p-4 shadow-md border border-gray-200">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">💰 Price Type</label>
                            <div class="flex gap-2">
                                <label
                                    class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-3 py-2 rounded-[5px] transition-all duration-200 text-sm"
                                    :class="form.customer_type === 'retail'
                                            ? 'bg-blue-700 text-white font-semibold'
                                            : 'bg-blue-100 text-blue-700 hover:bg-blue-200 font-medium'
                                        ">
                                    <input type="radio" v-model="form.customer_type" value="retail"
                                        @change="updateCartPrices" class="sr-only" />
                                    <span>🛒</span>
                                    <span>Retail</span>
                                </label>
                                <label
                                    class="flex-1 flex items-center justify-center gap-2 cursor-pointer px-3 py-2 rounded-[5px] transition-all duration-200 text-sm"
                                    :class="form.customer_type === 'wholesale'
                                            ? 'bg-blue-700 text-white font-semibold'
                                            : 'bg-blue-100 text-blue-700 hover:bg-blue-200 font-medium'
                                        ">
                                    <input type="radio" v-model="form.customer_type" value="wholesale"
                                        @change="updateCartPrices" class="sr-only" />
                                    <span>🏢</span>
                                    <span>Wholesale</span>
                                </label>
                            </div>
                        </div>

                        <!-- Add Products Manually -->
                        <div class="bg-white rounded-xl p-4 shadow-md border border-gray-200">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">➕ Add Products</label>
                            <button @click="openProductModal" type="button"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-[5px] transition">
                                🔍 Browse Products
                            </button>
                        </div>
                    </div>
                    <div v-if="showNormalPosUI" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left Side - Cart -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Cart Items -->
                            <div class="bg-white rounded-2xl p-6 shadow-md border border-gray-200">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold text-gray-800">
                                        Cart Items ({{ form.items.length }})
                                    </h3>
                                    <button v-if="form.items.length > 0" @click="clearCart" data-shortcut="F8"
                                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-[5px] transition font-medium">
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
                                                <th class="px-4 py-3 text-right text-sm font-semibold text-blue-700">
                                                    Price
                                                </th>
                                                <th class="px-4 py-3 text-center text-sm font-semibold text-blue-700">
                                                    Qty
                                                </th>
                                                <th class="px-4 py-3 text-right text-sm font-semibold text-blue-700">
                                                    Total
                                                </th>
                                                <th class="px-4 py-3 text-center text-sm font-semibold text-blue-700">
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            <tr v-if="form.payments && form.payments.length">
                                                <td colspan="5" class="text-left py-1">
                                                    <span v-for="(p, idx) in form.payments" :key="idx" class="mr-4">
                                                        {{ getPaymentTypeText(p.payment_type, p.card_type) }}: {{
                                                        page.props.currency || '' }}{{ p.amount }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr v-for="(item, index) in form.items" :key="index"
                                                class="text-gray-700 hover:bg-gray-50 transition">
                                                <td class="px-4 py-3">
                                                    <div class="flex flex-col gap-1">
                                                        <span class="font-medium">{{ item.product_name }}</span>
                                                        <span v-if="item.discount && item.discountApplied"
                                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-green-500 text-white w-fit">
                                                            Apply {{ item.discount.value
                                                            }}{{
                                                                item.discount.type === 0 ? "%" : page.props.currency || ""
                                                            }}
                                                            Off
                                                            <button @click="removeItemDiscount(index)"
                                                                class="ml-1 hover:bg-green-600 rounded-full w-4 h-4 flex items-center justify-center"
                                                                title="Remove Discount">
                                                                ✕
                                                            </button>
                                                        </span>
                                                        <button v-else-if="item.discount && !item.discountApplied"
                                                            @click="applyItemDiscount(index)"
                                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-500 hover:bg-blue-600 text-white w-fit">
                                                            Apply {{ item.discount.value
                                                            }}{{
                                                                item.discount.type === 0 ? "%" : page.props.currency || ""
                                                            }}
                                                            Off
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <div v-if="item.discountApplied" class="flex flex-col">
                                                        <span class="line-through text-gray-500 text-xs">({{
                                                            page.props.currency || "Rs." }})
                                                            {{ (item.originalPrice || 0).toFixed(2) }}</span>
                                                        <span class="text-green-600 font-semibold">({{
                                                            page.props.currency || "Rs." }})
                                                            {{ (item.price || 0).toFixed(2) }}</span>
                                                    </div>
                                                    <span v-else class="font-medium">({{ page.props.currency || "Rs."
                                                        }})
                                                        {{ (item.price || 0).toFixed(2) }}</span>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <input type="number"
                                                        :value="item.quantity === 1 ? '' : item.quantity"
                                                        @input="updateQuantity(index, Number($event.target.value))"
                                                        class="w-20 text-center font-semibold bg-white text-gray-800 border border-gray-300 rounded px-2 py-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                        min="1" step="1"  />
                                                </td>
                                                <td class="px-4 py-3 text-right font-semibold text-green-600">
                                                    ({{ page.props.currency || "Rs." }})
                                                    {{ ((item.price * item.quantity) || 0).toFixed(2) }}
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <button @click="removeItem(index)"
                                                        class="text-red-600 hover:text-red-700 hover:bg-red-50 rounded p-1 transition text-xl">
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

                            <!-- Pre-billing Token -->
                            <div class="bg-white rounded-2xl p-4 shadow-md border border-gray-200">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">🎫 Pre-billing
                                    Token</label>
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <input ref="tokenBarcodeField" v-if="canLoadTokenCart" type="text" v-model="tokenBarcodeInput"
                                        @keyup.enter="loadCartByToken" placeholder="Scan / enter token barcode"
                                        class="flex-1 px-4 py-2.5 bg-white text-gray-900 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-indigo-500 font-mono" />
                                    <button v-if="canLoadTokenCart" @click="loadCartByToken" type="button"
                                        :disabled="loadingTokenCart"
                                        class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-[5px] transition disabled:opacity-50 disabled:cursor-not-allowed">
                                        {{ loadingTokenCart ? 'Loading...' : 'Load Token Cart' }}
                                    </button>
                                    <button v-if="canPrintToken" @click="generateAndPrintToken" type="button"
                                        :disabled="form.items.length === 0 || generatingToken"
                                        class="px-4 py-2.5 bg-slate-700 hover:bg-slate-800 text-white font-semibold rounded-[5px] transition disabled:opacity-50 disabled:cursor-not-allowed">
                                        {{ generatingToken ? 'Generating...' : 'Print Token' }}
                                    </button>
                                </div>
                                <p v-if="preBillingTokenId" class="mt-2 text-xs text-gray-600">
                                    Active token: <span class="font-mono font-semibold">{{ preBillingTokenId }}</span>
                                </p>
                                <div v-if="canSeeTokenBarcode && preBillingTokenId" class="mt-3">
                                    <svg ref="tokenBarcodeSvg"></svg>
                                </div>
                            </div>


                        </div>
                        <div class="lg:col-span-1">
                            <div class="bg-white rounded-2xl p-6 shadow-md border border-gray-200 sticky top-6">
                                <h3 class="text-lg font-semibold text-white mb-6">Bill Summary</h3>

                                <!-- Calculations -->
                                <div class="space-y-4">
                                    <div class="flex justify-between text-black text-lg font-bold">
                                        <span>Sub Total:</span>
                                        <span class="font-semibold">({{ page.props.currency || "Rs." }})
                                            {{ (originalTotal || 0).toFixed(2) }}</span>
                                    </div>

                                    <div v-if="totalProductDiscount > 0" class="flex justify-between text-green-400">
                                        <span>Product Discounts:</span>
                                        <span class="font-semibold">- ({{ page.props.currency || "Rs." }})
                                            {{ (totalProductDiscount || 0).toFixed(2) }}</span>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-400 mb-2">Custom Discount ({{
                                            page.props.currency || "Rs." }})</label>
                                        <input type="number" v-model.number="form.discount" min="0" :max="totalAmount"
                                            class="w-[100px] px-4 py-2 text-black rounded-lg focus:ring-2 focus:ring-blue-500"
                                            placeholder="0.00" />
                                    </div>

                                    <div class="pt-4 border-t-2 border-gray-700">
                                        <div class="flex justify-between text-white text-xl font-bold">
                                            <span>Net Amount:</span>
                                            <span class="text-blue-400">({{ page.props.currency || "Rs." }})
                                                {{ (netAmount || 0).toFixed(2) }}</span>
                                        </div>
                                    </div>

                                    <!-- Multiple Payments List -->
                                    <div v-if="form.payments.length > 0" class="bg-gray-200 rounded-lg p-3">
                                        <div class="flex justify-between items-center mb-2">
                                            <h4 class="text-sm font-semibold text-black">Payments</h4>
                                            <span class="text-xs text-black-400">{{ form.payments.length }}
                                                method(s)</span>
                                        </div>
                                        <div class="space-y-2">
                                            <div v-for="(payment, index) in form.payments" :key="index"
                                                class="flex justify-between items-center text-sm bg-gray-600 rounded px-3 py-2">
                                                <div>
                                                    <span class="font-medium text-white">{{
                                                        getPaymentTypeText(payment.payment_type, payment.card_type)
                                                        }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-white font-semibold">({{ page.props.currency ||
                                                        "Rs." }})
                                                        {{ parseFloat(payment.amount || 0).toFixed(2) }}</span>
                                                    <button @click="removePayment(index)"
                                                        class="text-red-400 hover:text-red-300">
                                                        ✕
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2 pt-2 border-t border-gray-600 flex justify-between text-sm">
                                            <span class="text-black">Total Paid:</span>
                                            <span class="text-black font-semibold">({{ page.props.currency || "Rs." }})
                                                {{ (totalPaid || 0).toFixed(2) }}</span>
                                        </div>
                                    </div>

                                    <div class="pt-4 border-t border-gray-700">
                                        <div v-if="change > 0"
                                            class="flex justify-between text-lg font-semibold text-blue-400">
                                            <span>Change:</span>
                                            <span>({{ page.props.currency || "Rs." }}) {{ (change || 0).toFixed(2)
                                                }}</span>
                                        </div>
                                        <div v-else class="flex justify-between text-lg font-semibold" :class="{
                                            'text-red-600': balance > 0,
                                            'text-blue-600': balance <= 0,
                                        }">
                                            <span>{{ balance > 0 ? "Balance Due:" : "Change:" }}</span>
                                            <span>({{ page.props.currency || "Rs." }})
                                                {{ Math.abs(balance || 0).toFixed(2) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Buttons -->
                                <div v-if="canAccessBillingActions" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <button @click="openPaymentModalForMethod(0)" data-shortcut="F4"
                                        :disabled="form.items.length === 0"
                                        class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-200 disabled:cursor-not-allowed text-white font-bold py-4 px-4 rounded-lg transition text-lg shadow-lg">
                                        💵 Cash (F4)
                                    </button>
                                    <button @click="openPaymentModalForMethod(1)" data-shortcut="F9"
                                        :disabled="form.items.length === 0"
                                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-200 disabled:cursor-not-allowed text-white font-bold py-4 px-4 rounded-lg transition text-lg shadow-lg">
                                        💳 Card (F9)
                                    </button>
                                </div>

                                <!-- Submit Buttons: Paid / Unpaid -->
                                <div v-if="canAccessBillingActions" class="mt-3 grid grid-cols-2 gap-3">
                                    <button @click="submitSale(1)" data-shortcut="F3"
                                        :disabled="form.items.length === 0 || form.payments.length === 0 || form.processing"
                                        class="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-bold py-4 px-4 rounded-lg transition text-lg shadow-lg">
                                        <span v-if="form.processing">⏳ Processing...</span>
                                        <span v-else>✅ Paid (F3)</span>
                                    </button>
                                    <button @click="submitSale(0)" data-shortcut="F5"
                                        :disabled="form.items.length === 0 || form.processing"
                                        class="w-full bg-orange-500 hover:bg-orange-600 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-bold py-4 px-4 rounded-lg transition text-lg shadow-lg">
                                        <span v-if="form.processing">⏳ Processing...</span>
                                        <span v-else>📋 Unpaid (F5)</span>
                                    </button>
                                </div>

                                <!-- Quick Actions -->
                                <div class="mt-4 text-xs text-gray-400 text-center">
                                    <p>Keyboard Shortcuts:</p>
                                    <p v-if="canAccessBillingActions">F2: Focus Barcode | F3: Paid Sale | F4: Cash
                                        Payment | F5: Unpaid Sale | F8: Clear Cart | F9: Card Payment | F11: Unpaid
                                        Sales List | F12: Create Sales Return | ESC: Home</p>
                                    <p v-else>F2: Focus Barcode | F8: Clear Cart | ESC: Home</p>
                                    <button type="button" class="hidden" data-shortcut="F2, AudioVolumeDown, VolumeDown"
                                        @click="focusBarcodeField"></button>
                                </div>
                            </div>
                        </div>


                    </div>



                </div>
            </div>
        </div>

        <Modal :show="showStartShiftModal" @close="closeStartShiftModal" max-width="lg">
            <div class="bg-white p-6">
                <div class="mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Start Shift</h2>
                    <p class="text-sm text-gray-500 mt-1">Enter opening details to unlock billing.</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Opening Balance</label>
                        <input v-model="startShiftForm.opening_till_amount" type="number" min="0" step="0.01"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-[5px] text-sm focus:ring-2 focus:ring-blue-500"
                            placeholder="0.00" />
                        <p v-if="startShiftForm.errors.opening_till_amount" class="mt-2 text-xs text-red-700">
                            {{ startShiftForm.errors.opening_till_amount }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Opening Note (Optional)</label>
                        <textarea v-model="startShiftForm.start_note" rows="3"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-[5px] text-sm focus:ring-2 focus:ring-blue-500"
                            placeholder="Add a note"></textarea>
                        <p v-if="startShiftForm.errors.start_note" class="mt-2 text-xs text-red-700">
                            {{ startShiftForm.errors.start_note }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <button @click="startShiftFromBilling" :disabled="startShiftForm.processing"
                        class="px-6 py-2.5 rounded-[5px] bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold transition disabled:opacity-60 disabled:cursor-not-allowed">
                        {{ startShiftForm.processing ? 'Starting...' : 'Start Shift' }}
                    </button>
                    <button @click="closeStartShiftModal" :disabled="startShiftForm.processing"
                        class="px-6 py-2.5 rounded-[5px] bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold transition disabled:opacity-60">
                        Cancel
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Unpaid Sales Modal -->
        <Modal :show="showUnpaidModal" @close="showUnpaidModal = false" max-width="3xl">
            <div class="bg-white p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">⏳ Unpaid Sales</h2>
                    <button @click="showUnpaidModal = false"
                        class="text-gray-500 hover:text-gray-700 text-xl font-bold">✕</button>
                </div>
                <div v-if="unpaidLoading" class="text-center py-8 text-gray-500">Loading...</div>
                <div v-else-if="unpaidSales.length === 0" class="text-center py-8 text-gray-500">No unpaid sales found.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2 border-blue-600">
                                <th class="px-3 py-2 text-left text-blue-700 font-semibold">Invoice</th>
                                <th class="px-3 py-2 text-left text-blue-700 font-semibold">Customer</th>
                                <th class="px-3 py-2 text-left text-blue-700 font-semibold">Date</th>
                                <th class="px-3 py-2 text-right text-blue-700 font-semibold">Amount</th>
                                <th class="px-3 py-2 text-center text-blue-700 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="sale in unpaidSales" :key="sale.id" class="hover:bg-orange-50">
                                <td class="px-3 py-3 font-medium text-gray-800">{{ sale.invoice_no }}</td>
                                <td class="px-3 py-3 text-gray-700">{{ sale.customer_name }}</td>
                                <td class="px-3 py-3 text-gray-600">{{ sale.sale_date }}</td>
                                <td class="px-3 py-3 text-right font-semibold text-orange-600">{{ page.props.currency ||
                                    'Rs.' }} {{ sale.net_amount }}</td>
                                <td class="px-3 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="loadUnpaidSale(sale)"
                                            :disabled="loadingUnpaidSaleId === sale.id"
                                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white text-xs font-semibold rounded-[5px] transition">
                                            {{ loadingUnpaidSaleId === sale.id ? 'Loading...' : '📥 Load' }}
                                        </button>
                                        <button @click="markAsPaid(sale)" :disabled="sale.marking"
                                            class="px-3 py-1.5 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white text-xs font-semibold rounded-[5px] transition">
                                            {{ sale.marking ? 'Saving...' : '✅ Mark as Paid' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
                        <button @click="closeProductModal"
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-[5px] transition">
                            Done
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="p-6 bg-gray-50 border-b border-gray-200">
                    <!-- Search Input -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">🔍 Search Products</label>
                        <input ref="productSearchInput" type="text" v-model="productFilters.search"
                            @input="filterProducts" placeholder="Search by product name..."
                            class="w-full px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                            <select v-model="productFilters.brand_id" @change="filterProducts"
                                class="w-full px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Brands</option>
                                <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                    {{ brand.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select v-model="productFilters.category_id" @change="filterProducts"
                                class="w-full px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Categories</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                            <select v-model="productFilters.type_id" @change="filterProducts"
                                class="w-full px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Types</option>
                                <option v-for="type in types" :key="type.id" :value="type.id">
                                    {{ type.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Discount</label>
                            <select v-model="productFilters.discount_id" @change="filterProducts"
                                class="w-full px-4 py-2 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Discounts</option>
                                <option v-for="discount in discounts" :key="discount.id" :value="discount.id">
                                    {{ discount.name }} ({{ discount.percentage }}%)
                                </option>
                            </select>
                        </div>
                    </div>
                    <button @click="clearFilters"
                        class="mt-3 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-[5px] transition font-medium">
                        Clear Filters
                    </button>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="text-sm text-gray-600 mr-2">Stock:</span>
                        <button @click="() => { productFilters.stock_filter = ''; filterProducts(); }"
                            :class="productFilters.stock_filter === '' ? 'bg-blue-700 text-white' : 'bg-white text-gray-700'"
                            class="px-3 py-1 text-sm rounded border border-gray-300">
                            All
                        </button>
                        <button @click="() => { productFilters.stock_filter = 'low'; filterProducts(); }"
                            :class="productFilters.stock_filter === 'low' ? 'bg-blue-700 text-white' : 'bg-white text-gray-700'"
                            class="px-3 py-1 text-sm rounded border border-gray-300">
                            Low
                        </button>
                        <button @click="() => { productFilters.stock_filter = 'out'; filterProducts(); }"
                            :class="productFilters.stock_filter === 'out' ? 'bg-blue-700 text-white' : 'bg-white text-gray-700'"
                            class="px-3 py-1 text-sm rounded border border-gray-300">
                            Out
                        </button>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="p-6 overflow-y-auto max-h-[calc(90vh-280px)] bg-white">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div v-for="product in paginatedProducts" :key="product.id"
                            @click="!isOutOfStock(product) && addToCart(product)"
                            class="bg-white border border-gray-200 rounded-lg overflow-hidden transition-all relative hover:shadow-md cursor-pointer"
                            :class="{
                                'opacity-50 cursor-not-allowed': isOutOfStock(product),
                                'ring-2 ring-blue-500 shadow-md':
                                    isProductInCart(product.id) && !isOutOfStock(product),
                            }">
                            <!-- Out of Stock / Low Stock Badges -->
                            <div v-if="isOutOfStock(product)"
                                class="absolute top-2 left-2 bg-gray-800 text-white text-xs font-bold px-2 py-1 rounded-full z-10 flex items-center gap-1">
                                ❌ Out of Stock
                            </div>
                            <div v-else-if="isLowStock(product)"
                                class="absolute top-2 left-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full z-10 flex items-center gap-1">
                                🔒 Low Stock
                            </div>
                            <!-- Added to Cart Badge -->
                            <div v-if="isProductInCart(product.id) && !isOutOfStock(product)"
                                class="absolute top-2 right-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full z-10 flex items-center gap-1">
                                ✓ {{ getProductCartQuantity(product.id) }}
                            </div>
                            <div class="aspect-square bg-gray-100 flex items-center justify-center overflow-hidden">
                                <img v-if="product.image" :src="'/storage/' + product.image" :alt="product.name"
                                    class="w-full h-full object-cover"
                                    @error="$event.target.src = '/storage/products/default.png'" />
                                <span v-else class="text-6xl">📦</span>
                            </div>
                            <div class="p-3">
                                <h3 class="text-gray-800 font-semibold text-sm mb-2 truncate" :title="product.name">
                                    {{ product.name }}
                                </h3>
                                <div class="space-y-1 text-xs text-gray-700">
                                    <div class="flex justify-between">
                                        <span>Retail:</span>
                                        <span class="font-semibold text-green-600">({{ page.props.currency || "Rs." }})
                                            {{ parseFloat(product.retail_price || 0).toFixed(2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Wholesale:</span>
                                        <span class="font-semibold text-blue-600">({{ page.props.currency || "Rs." }})
                                            {{ parseFloat(product.wholesale_price || 0).toFixed(2) }}</span>
                                    </div>
                                    <div class="flex justify-between mt-2 pt-2 border-t border-gray-200">
                                        <span>Stock:</span>
                                        <span class="font-semibold" :class="isLowStock(product)
                                                ? 'text-red-600'
                                                : product.shop_quantity_in_sales_unit > 10
                                                    ? 'text-green-600'
                                                    : 'text-yellow-600'
                                            ">
                                            {{ product.shop_quantity_in_sales_unit }}
                                            <span v-if="isLowStock(product)" class="text-[10px]"> (Low)</span>
                                        </span>
                                    </div>
                                </div>

                                <div v-if="!isOutOfStock(product)" class="mt-3 pt-3 border-t border-gray-200">
                                    <button @click.stop="addToCart(product)"
                                        class="w-full px-3 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded transition">
                                        Add to Cart
                                    </button>
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
                <div v-if="filteredProducts.length > 0" class="p-6 bg-blue-50 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <div class="text-gray-700 text-sm font-medium">
                            Showing {{ startIndex + 1 }} to
                            {{ Math.min(endIndex, filteredProducts.length) }} of
                            {{ filteredProducts.length }} products
                        </div>
                        <div class="flex gap-2">
                            <button @click="prevPage" :disabled="currentPage === 1"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-[5px] transition font-semibold">
                                ← Previous
                            </button>
                            <div
                                class="flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-800 rounded-[5px]">
                                <span class="font-semibold">{{ currentPage }} / {{ totalPages }}</span>
                            </div>
                            <button @click="nextPage" :disabled="currentPage === totalPages"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-[5px] transition font-semibold">
                                Next →
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Payment Modal -->
        <Modal :show="showPaymentModal" @close="() => (showPaymentModal = false)" max-width="md">
            <div class="p-8 bg-white">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Add Payment</h2>
                    <p class="text-gray-600 text-sm">
                        Remaining:
                        <span class="text-red-600 font-semibold">({{ page.props.currency || "Rs." }})
                            {{ balance > 0 ? balance.toFixed(2) : "0.00" }}</span>
                    </p>
                    <p class="text-sm mt-1 font-medium text-blue-600">
                        Selected Method: {{ paymentMethod === 0 ? 'Cash (F4)' : 'Card (F9)' }}
                    </p>
                </div>

                <div class="space-y-4">
                    <div v-if="paymentMethod === 1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Card Type</label>
                        <select v-model="paymentCardType"
                            class="w-full px-4 py-3 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="visa">Visa</option>
                            <option value="mastercard">MasterCard</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Amount ({{ page.props.currency ||
                            "Rs." }})</label>
                        <input type="number" v-model.number="paymentAmount" min="0" :max="balance > 0 ? balance : 0"
                            class="w-full px-4 py-3 bg-white text-gray-800 border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                            placeholder="0.00" />
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button @click="addPayment" data-dialog-primary="true" autofocus
                        class="flex-1 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-[5px] transition">
                        Add Payment
                    </button>
                    <button @click="showPaymentModal = false" data-dialog-secondary="true"
                        class="flex-1 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-[5px] transition">
                        Close
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Success Modal -->
        <Modal :show="showSuccessModal" @close="closeModal" max-width="md">
            <div class="p-8 bg-white">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-black mb-4">
                        <svg class="h-12 w-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Payment Successful!</h2>
                    <p class="text-gray-600 mb-6">Order Payment is Successful!</p>
                    <p class="text-sm text-gray-500 mb-6">
                        Invoice: <span class="font-semibold">{{ completedInvoice }}</span>
                    </p>

                    <div class="flex gap-3 justify-center">
                        <button @click="printAndClose" data-shortcut="Enter" data-dialog-primary="true" autofocus
                            class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-lg">
                            PRINT RECEIPT
                        </button>
                        <button @click="closeModal" data-dialog-secondary="true"
                            class="px-8 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition shadow-lg">
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
                    <div v-if="bill.logo_path" style="margin-bottom: 6px">
                        <img :src="'/storage/' + bill.logo_path" alt="logo"
                            style="max-height: 40px; max-width: 100%; object-fit: contain" />
                    </div>
                    <h1 class="text-xl font-bold">{{ bill.company_name || "RECEIPT" }}</h1>
                    <p class="text-sm">Invoice: {{ completedInvoice }}</p>
                    <p class="text-sm">Date: {{ completedSaleDate }}</p>
                </div>
                <hr class="my-2 border-black" />
                <div class="mb-2 text-sm">
                    <p v-if="!hideVatNumber"><strong>VAT No:</strong> 933686833-7000</p>
                    <p><strong>Customer:</strong> {{ completedCustomer }}</p>
                    <p><strong>Payment:</strong> {{ getPaymentTypeText(completedPaymentType, completedCardType) }}</p>
                    <p v-if="bill.address">{{ bill.address }}</p>
                    <p v-if="bill.mobile_1 || bill.mobile_2">
                        Tel: {{ [bill.mobile_1, bill.mobile_2].filter(Boolean).join(" / ") }}
                    </p>
                    <p v-if="bill.email">{{ bill.email }}</p>
                    <p v-if="bill.website_url">{{ bill.website_url }}</p>
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
                        <tr v-for="item in completedItems" :key="item.product_id">
                            <td class="text-left py-1">{{ item.product_name }}</td>
                            <td class="item-qty">${item.quantity} ${item.sale_unit || 'N/A'}</td>
                            <td class="text-right py-1">{{ (item.price || 0).toFixed(2) }}</td>
                            <td class="text-right py-1">
                                {{ ((item.price * item.quantity) || 0).toFixed(2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <hr class="my-2 border-black" />
                <div class="text-sm space-y-1">
                    <div class="flex justify-between">
                        <span>Subtotal:</span>
                        <span>({{ page.props.currency || "Rs." }}) {{ completedTotal }}</span>
                    </div>
                    <div v-if="parseFloat(completedProductDiscount) > 0" class="flex justify-between text-green-600">
                        <span>Product Discounts:</span>
                        <span>- ({{ page.props.currency || "Rs." }}) {{ completedProductDiscount }}</span>
                    </div>
                    <div v-if="parseFloat(completedDiscount) > 0" class="flex justify-between">
                        <span>Custom Discount:</span>
                        <span>- ({{ page.props.currency || "Rs." }}) {{ completedDiscount }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-base pt-2 border-t border-black">
                        <span>Net Total:</span>
                        <span>({{ page.props.currency || "Rs." }}) {{ completedNetAmount }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Paid:</span>
                        <span>({{ page.props.currency || "Rs." }}) {{ completedPaid }}</span>
                    </div>
                    <div class="flex justify-between font-bold">
                        <span>{{
                            parseFloat(completedBalance) > 0 ? "Balance Due:" : "Change:"
                            }}</span>
                        <span>({{ page.props.currency || "Rs." }})
                            {{ (Math.abs(parseFloat(completedBalance)) || 0).toFixed(2) }}</span>
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
import axios from "axios";
import JsBarcode from "jsbarcode";
const page = usePage();
import { ref, computed, onMounted, nextTick } from "vue";
import { logActivity } from "@/composables/useActivityLog";
import Modal from "@/Components/Modal.vue";
import CustomerCreateModal from "@/Pages/Customers/Components/CustomerCreateModal.vue";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const props = defineProps({
    invoice_no: String,
    customers: Array,
    products: Array,
    brands: Array,
    categories: Array,
    types: Array,
    discounts: Array,
    billSetting: Object,
    quotations: Array,
    activeShift: {
        type: Object,
        default: null,
    },
});

// Only show active customers (status == '1' or 1)
const activeCustomers = computed(() => {
    return props.customers.filter(
        (c) => c.status === '1' || c.status === 1
    );
});

const { goToShopsTab } = useDashboardNavigation();

const form = useForm({
    invoice_no: props.invoice_no,
    customer_id: '', // Ensure default is empty
    customer_type: "retail", // retail or wholesale
    sale_date: new Date().toISOString().split("T")[0],
    items: [],
    discount: 0,
    pre_billing_token_id: null,
    payment_type: 0,
    paid_amount: 0,
    payments: [], // Multiple payments
    quotation_id: null, // Track loaded quotation for status update
    paid_status: 1, // 1 = Paid, 0 = Pending
});

const startShiftForm = useForm({
    opening_till_amount: 0,
    start_note: "",
    redirect_to: "sales.index",
});

const selectedProduct = ref(null);
const selectedQuantity = ref(1);
const barcodeInput = ref("");
const barcodeField = ref(null);
const tokenBarcodeField = ref(null);
const productSearchInput = ref(null);
const tokenBarcodeSvg = ref(null);
const showSuccessModal = ref(false);
const showPaymentModal = ref(false);
const showUnpaidModal = ref(false);
const showStartShiftModal = ref(false);
const unpaidSales = ref([]);
const unpaidLoading = ref(false);
const loadingUnpaidSaleId = ref(null);
const showProductModal = ref(false);
const showQuickAddCustomer = ref(false);
const paymentMethod = ref(0);
const paymentCardType = ref("visa");
const paymentAmount = ref(0);
const completedInvoice = ref("");
const completedSaleDate = ref("");
const completedCustomer = ref("");
const completedPaymentType = ref(0);
const completedCardType = ref(null);
const completedPayments = ref([]);
const completedItems = ref([]);
const completedTotal = ref("0.00");
const completedProductDiscount = ref("0.00");
const completedDiscount = ref("0.00");
const completedNetAmount = ref("0.00");
const completedPaid = ref("0.00");
const completedBalance = ref("0.00");
const completedPaidStatus = ref(1);
const editingUnpaidSaleId = ref(null);
const preBillingTokenId = ref("");
const tokenBarcodeInput = ref("");
const generatingToken = ref(false);
const loadingTokenCart = ref(false);

const currentUserRole = computed(() => Number(page.props.auth?.user?.role ?? -1));
const isAdminRole = computed(() => currentUserRole.value === 0);
const isCashierRole = computed(() => currentUserRole.value === 2);
const isTokenCashierRole = computed(() => currentUserRole.value === 3);

const canGenerateToken = computed(() => isAdminRole.value || isTokenCashierRole.value);
const canPrintToken = computed(() => isAdminRole.value || isTokenCashierRole.value);
const canLoadTokenCart = computed(() => isAdminRole.value || isCashierRole.value);
const canSeeTokenBarcode = computed(() => isAdminRole.value || isTokenCashierRole.value);
const showNormalPosUI = computed(() => isAdminRole.value || isCashierRole.value || isTokenCashierRole.value);
const canAccessBillingActions = computed(() => isAdminRole.value || isCashierRole.value);

// Quotation selector
const selectedQuotationId = ref("");

// Load quotation data into the sale form
const loadQuotationData = () => {
    if (!selectedQuotationId.value) {
        return;
    }

    const quotation = props.quotations.find((q) => q.id == selectedQuotationId.value);
    if (!quotation) {
        alert("Quotation not found");
        return;
    }

    // Confirm before loading
    if (form.items.length > 0) {
        if (!confirm("This will replace current cart items. Continue?")) {
            selectedQuotationId.value = "";
            return;
        }
    }

    // Load quotation data into form
    form.customer_id = quotation.customer_id || "";
    form.customer_type = quotation.customer_type || "retail";
    form.discount = quotation.discount || 0;
    form.quotation_id = quotation.id; // Store quotation ID for status update when sale completes
    form.pre_billing_token_id = null;
    preBillingTokenId.value = "";
    tokenBarcodeInput.value = "";
    form.items = quotation.items.map((item) => ({
        product_id: item.product_id,
        product_name: item.product_name,
        price: parseFloat(item.price),
        retail_price: parseFloat(item.retail_price),
        wholesale_price: parseFloat(item.wholesale_price),
        quantity: item.quantity,
        sale_unit: item.sale_unit || 'N/A',
    }));

    // Reset quotation selector
    selectedQuotationId.value = "";

    alert("Quotation data loaded! Add payment to complete the sale.");
};

// Bill settings helper
const bill = props.billSetting || {};
const billWidth = computed(() => {
    const allowed = ["58mm", "80mm", "112mm", "210mm"];
    const raw = (bill.print_size || "80mm").toString();
    return allowed.includes(raw) ? raw : "80mm";
});

const getProductDivisionId = (productId) => {
    return props.products.find((product) => product.id === productId)?.division_id ?? null;
};

const hideVatNumber = computed(() => {
    const items = completedItems.value || [];

    if (items.length === 0) {
        return false;
    }

    return items.every((item) => {
        const divisionId = Number(item.division_id ?? getProductDivisionId(item.product_id));
        return divisionId === 1;
    });
});

const getDivisionMetaForItems = (items = []) => {
    const divisionIds = [
        ...new Set(
            (items || [])
                .map((item) => Number(item.division_id ?? getProductDivisionId(item.product_id)))
                .filter((divisionId) => Number.isFinite(divisionId) && divisionId > 0)
        ),
    ];

    if (divisionIds.length !== 1) {
        return { name: null, slug: null };
    }

    const divisionId = divisionIds[0];

    if (divisionId === 1) {
        return { name: 'Pharmacy', slug: 'pharmacy' };
    }

    if (divisionId === 2) {
        return { name: 'Sports', slug: 'sports' };
    }

    const matchedProduct = (items || []).find((item) => {
        return Number(item.division_id ?? getProductDivisionId(item.product_id)) === divisionId;
    });

    return {
        name: matchedProduct?.division_name || null,
        slug: matchedProduct?.division_slug || null,
    };
};

// Product modal filters and pagination
const productFilters = ref({
    search: "",
    brand_id: "",
    category_id: "",
    type_id: "",
    discount_id: "",
    stock_filter: "", // '', 'low', 'out'
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

// Calculations
// Original total before product discounts
const originalTotal = computed(() => {
    return form.items.reduce((sum, item) => {
        const price =
            item.discountApplied && item.originalPrice ? item.originalPrice : item.price;
        return sum + price * item.quantity;
    }, 0);
});

const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => sum + item.price * item.quantity, 0);
});

// Total product discounts applied
const totalProductDiscount = computed(() => {

    const result = form.items.reduce((sum, item, index) => {
        if (item.discountApplied && item.originalPrice) {
            const itemDiscount = (item.originalPrice - item.price) * item.quantity;
            return sum + itemDiscount;
        }

        return sum;
    }, 0);
    return result;
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

const isShiftLocked = computed(() => !props.activeShift && !isTokenCashierRole.value);

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

// Get payment type text
const getCardTypeText = (cardType) => {
    const cardTypes = {
        visa: "Visa",
        mastercard: "MasterCard",
    };
    return cardTypes[cardType] || "Card";
};

const getPaymentTypeText = (type, cardType = null) => {
    const types = {
        0: "Cash",
        1: cardType ? `Card (${getCardTypeText(cardType)})` : "Card",
        //  2: 'Credit'
    };
    return types[type] || "Cash";
};

// Get current price based on customer type
const getCurrentPrice = (product) => {
    return form.customer_type === "wholesale"
        ? product.wholesale_price
        : product.retail_price;
};

// Add product by barcode
const addByBarcode = () => {
    if (isShiftLocked.value) {
        alert("Start a shift first to unlock billing.");
        return;
    }

    if (!barcodeInput.value.trim()) return;

    const product = props.products.find((p) => p.barcode === barcodeInput.value.trim());

    if (product) {
        // Prevent adding if product is out of stock
        if (product.shop_quantity_in_sales_unit <= 0) {
            alert(`Product "${product.name}" is out of stock`);
            barcodeInput.value = "";
            barcodeField.value?.focus();
            return;
        }
        const existingIndex = form.items.findIndex((item) => item.product_id === product.id);
        const price = getCurrentPrice(product);

        if (existingIndex !== -1) {
            // Don't exceed available stock
            if (form.items[existingIndex].quantity < product.shop_quantity_in_sales_unit) {
                form.items[existingIndex].quantity += 1;
            } else {
                alert(`Cannot add more. Only ${product.shop_quantity_in_sales_unit} in stock.`);
            }
        } else {
            form.items.push({
                product_id: product.id,
                product_name: product.name,
                division_id: product.division_id ?? null,
                price: parseFloat(price),
                quantity: 1,
                sale_unit: product.salesUnit ? product.salesUnit.name : 'Not found',
                discount: product.discount
                    ? {
                        name: product.discount.name,
                        value: product.discount.value,
                        type: product.discount.type,
                    }
                    : null,
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

const renderTokenBarcode = async (tokenId) => {
    await nextTick();

    if (!tokenBarcodeSvg.value || !tokenId) return;

    JsBarcode(tokenBarcodeSvg.value, tokenId, {
        format: "CODE39",
        displayValue: true,
        lineColor: "#000",
        width: 1.4,
        height: 34,
        margin: 0,
        fontSize: 11,
        textMargin: 2,
    });
};

const createTokenBarcodeDataUrl = (tokenId) => {
    const canvas = document.createElement("canvas");

    JsBarcode(canvas, tokenId, {
        format: "CODE39",
        displayValue: true,
        lineColor: "#000",
        width: 1.5,
        height: 52,
        margin: 0,
        fontSize: 12,
        textMargin: 2,
    });

    return canvas.toDataURL("image/png");
};

const generateAndPrintToken = async () => {
    await generatePreBillingToken();
    if (preBillingTokenId.value) {
        printPreBillingToken();
    }
};

const generatePreBillingToken = async () => {
    if (!canGenerateToken.value) {
        alert("You do not have permission to generate tokens.");
        return;
    }

    if (isShiftLocked.value) {
        alert("Start a shift first to unlock billing.");
        return;
    }

    if (form.items.length === 0) {
        alert("Please add items to cart before generating a token.");
        return;
    }

    generatingToken.value = true;

    try {
        const response = await axios.post(route("sales.pre-billing-tokens.store"), {
            customer_id: form.customer_id || null,
            customer_type: form.customer_type,
            sale_date: form.sale_date,
            discount: Number(form.discount || 0),
            items: form.items.map((item) => ({
                product_id: item.product_id,
                product_name: item.product_name,
                quantity: Number(item.quantity || 0),
                price: Number(item.price || 0),
                sale_unit: item.sale_unit || "Not found",
            })),
        });

        const tokenId = response.data?.token_id || "";
        preBillingTokenId.value = tokenId;
        tokenBarcodeInput.value = tokenId;
        form.pre_billing_token_id = tokenId;

        await renderTokenBarcode(tokenId);
    } catch (error) {
        console.error("Failed to generate pre-billing token", error);
        const validationError = error?.response?.data?.errors
            ? Object.values(error.response.data.errors)?.[0]?.[0]
            : null;
        const backendError = error?.response?.data?.error;
        alert(validationError || backendError || "Failed to generate pre-billing token.");
    } finally {
        generatingToken.value = false;
    }
};

const loadCartByToken = async () => {
    if (!canLoadTokenCart.value) {
        alert("You do not have permission to load token carts.");
        return;
    }

    const tokenId = tokenBarcodeInput.value.replace(/\*/g, "").trim();

    if (!tokenId) {
        alert("Please scan or enter a token barcode.");
        return;
    }

    if (form.items.length > 0) {
        if (!confirm("This will replace current cart items. Continue?")) {
            return;
        }
    }

    loadingTokenCart.value = true;

    try {
        const response = await axios.get(route("sales.pre-billing-tokens.show", tokenId));
        const data = response.data || {};

        form.customer_id = data.customer_id || "";
        form.customer_type = data.customer_type || "retail";
        form.sale_date = data.sale_date || form.sale_date;
        form.discount = parseFloat(data.discount || 0);
        form.items = (data.items || []).map((item) => ({
            product_id: item.product_id,
            product_name: item.product_name,
            division_id: getProductDivisionId(item.product_id),
            price: parseFloat(item.price || 0),
            quantity: parseFloat(item.quantity || 1),
            sale_unit: item.sale_unit || "Not found",
            discount: null,
            discountApplied: false,
        }));

        form.payments = [];
        form.quotation_id = null;
        form.pre_billing_token_id = data.token_id || tokenId;
        editingUnpaidSaleId.value = null;

        preBillingTokenId.value = data.token_id || tokenId;
        await renderTokenBarcode(preBillingTokenId.value);

        barcodeField.value?.focus();
    } catch (error) {
        console.error("Failed to load token cart", error);
        alert(error?.response?.data?.error || "Failed to load token cart.");
    } finally {
        loadingTokenCart.value = false;
    }
};

const printPreBillingToken = () => {
    if (!canPrintToken.value) {
        alert("You do not have permission to print tokens.");
        return;
    }

    if (!preBillingTokenId.value) {
        alert("Generate token first.");
        return;
    }

    const barcodeDataUrl = createTokenBarcodeDataUrl(preBillingTokenId.value);
    const printWindow = window.open("", "_blank", "width=340,height=520");

    if (!printWindow) {
        alert("Please allow pop-ups to print token.");
        return;
    }

    const now = new Date();
    const dateText = now.toLocaleDateString("en-GB");
    const timeText = now.toLocaleTimeString("en-GB", { hour12: false });
    const cashier = page.props.auth?.user?.name || "Cashier";
    const total = (netAmount.value || 0).toFixed(2);

    const html = `
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <title>Pre-billing Token</title>

  <style>
    @page {
      size: 80mm auto;
      margin: 2mm;

    }

    * {
      box-sizing: border-box;
      color: #000;
    }

    body {
      font-family: "Courier New", monospace;
      font-size: 12px;
      margin: 0;
      padding: 4px 0.1mm; /* 🔥 left/right very small */
      font-weight: 700;
    }

    .row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 3px;
    }

    .row.center {
      justify-content: center;
      text-align: center;
    }

    .line {
      border-top: 1px dashed #000;
      margin: 6px 0;
    }

    .total-label {
      text-align: center;
      font-size: 13px;
      margin-top: 4px;
    }

    .total {
      text-align: center;
      font-size: 20px;
      font-weight: 900;
      margin: 4px 0;
    }

    .barcode {
      display: block;
      width: 75%;
      margin: 8px auto 0;
    }

    .small {
      font-size: 11px;
    }

    .bold {
      font-weight: 900;
    }

  </style>
</head>

<body>

  <!-- Date & Time -->
  <div class="row">
    <span>On: ${dateText}</span>
    <span>At: ${timeText}</span>
  </div>

  <!-- Cashier -->
  <div class="row">
    <span>Cashier: ${cashier}</span>
  </div>

  <!-- Bill No -->
  <div class="row center bold">
    <span>Bill No: ${form.invoice_no}</span>
  </div>

  <div class="line"></div>

  <!-- Total -->
  <div class="total-label">TOTAL</div>
  <div class="total">${total}</div>

  <div class="line"></div>

  <!-- Barcode -->
  <img class="barcode" src="${barcodeDataUrl}" alt="Token Barcode" />

  <!-- Optional Footer -->
  <div class="row center small" style="margin-top:6px;">
    <span>Thank You!</span>
  </div>

</body>
</html>
`;


    printWindow.document.write(html);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
        router.visit(route("sales.index"));
    }, 250);
};

// Add product to cart
const addToCart = (product) => {
    if (isShiftLocked.value) {
        alert("Start a shift first to unlock billing.");
        return;
    }

    // Check if product is already in cart
    const existingIndex = form.items.findIndex((item) => item.product_id === product.id);

    if (existingIndex !== -1) {
        return;
    }

    const price = getCurrentPrice(product);

    form.items.push({
        product_id: product.id,
        product_name: product.name,
        division_id: product.division_id ?? null,
        price: parseFloat(price),
        quantity: 1,
        sale_unit: product.salesUnit ? product.salesUnit.name : 'Not found',
        discount: product.discount
            ? {
                name: product.discount.name,
                value: product.discount.value,
                type: product.discount.type,
            }
            : null,
        discountApplied: false,
    });
};

const applyDiscount = (index) => {
    const item = form.items[index];

    if (item.discountApplied) return;

    item.price = item.price - 20; // Rs.100 → Rs.80
    item.discountApplied = true;
};



// Remove item from cart
const removeItem = (index) => {
    form.items.splice(index, 1);
    barcodeField.value?.focus();
};

// Apply discount to cart item
const applyItemDiscount = (index) => {
    const item = form.items[index];
    if (item.discount && !item.discountApplied) {
        // Store original price
        item.originalPrice = item.price;

        // Calculate discounted price
        if (item.discount.type === 0) {
            // Percentage discount
            item.price = item.originalPrice - item.originalPrice * (item.discount.value / 100);
        } else {
            // Fixed amount discount
            item.price = Math.max(0, item.originalPrice - item.discount.value);
        }

        item.discountApplied = true;
    }
};

const removeItemDiscount = (index) => {
    const item = form.items[index];
    if (!item.discountApplied) return;

    item.price =
        item.price_type === 'wholesale'
            ? item.wholesale_price
            : item.originalPrice;

    item.discountApplied = false;
};


// Update quantity in cart
const updateQuantity = (index, newQty) => {
    const quantity = Number.isFinite(newQty) ? Math.floor(newQty) : 1;
    form.items[index].quantity = quantity > 0 ? quantity : 1;
};

// Clear cart
const clearCart = () => {
    if (confirm("Are you sure you want to clear the cart?")) {
        form.items = [];
        form.discount = 0;
        form.payments = [];
        form.quotation_id = null; // Reset quotation reference
        form.pre_billing_token_id = null;
        editingUnpaidSaleId.value = null;
        preBillingTokenId.value = "";
        tokenBarcodeInput.value = "";
        form.invoice_no = props.invoice_no;
        if (tokenBarcodeSvg.value) {
            tokenBarcodeSvg.value.innerHTML = "";
        }
        barcodeField.value?.focus();
    }
};

// Add payment
const addPayment = async () => {
    if (paymentAmount.value <= 0) {
        alert("Please enter a valid amount");
        return;
    }

    if (paymentMethod.value === 1 && !paymentCardType.value) {
        alert("Please select card type");
        return;
    }

    const remaining = netAmount.value - totalPaid.value;
    // Allow overpayment only for cash
    if (paymentMethod.value !== 0 && paymentAmount.value > remaining) {
        alert(`Amount cannot exceed remaining balance: Rs. ${(remaining || 0).toFixed(2)}`);
        return;
    }

    form.payments.push({
        payment_type: paymentMethod.value,
        amount: parseFloat(paymentAmount.value),
        card_type: paymentMethod.value === 1 ? paymentCardType.value : null,
    });

    await logActivity("create", "sales", {
        action: "add_payment",
        payment_type: getPaymentTypeText(
            paymentMethod.value,
            paymentMethod.value === 1 ? paymentCardType.value : null
        ),
        amount: paymentAmount.value,
    });

    paymentAmount.value = 0;
    paymentMethod.value = 0;
    paymentCardType.value = "visa";

    // Auto-close modal if fully paid or overpaid (for cash)
    if (balance.value <= 0) {
        showPaymentModal.value = false;
    }
};

// Remove payment
const removePayment = (index) => {
    form.payments.splice(index, 1);
};

// Open payment modal with selected method
const openPaymentModalForMethod = (method = 0) => {
    if (isShiftLocked.value) {
        alert("Start a shift first to unlock billing.");
        return;
    }

    if (form.items.length === 0) {
        alert("Please add items to cart");
        return;
    }

    paymentMethod.value = method;
    showPaymentModal.value = true;
    paymentAmount.value = balance.value > 0 ? balance.value : 0;
};

// Product modal methods
const openProductModal = () => {
    if (isShiftLocked.value) {
        alert("Start a shift first to unlock billing.");
        return;
    }

    showProductModal.value = true;
    filterProducts();
    nextTick(() => productSearchInput.value?.focus());
    // Initialize all product quantities to 1
    props.products.forEach((product) => {
        if (!productQuantities.value[product.id]) {
            productQuantities.value[product.id] = 1;
        }
    });
};

const closeProductModal = () => {
    showProductModal.value = false;
    barcodeField.value?.focus();
};

const focusBarcodeField = () => {
    if (barcodeField.value) {
        barcodeField.value.focus();
        barcodeField.value.select?.();
    }
};

const focusInitialBillingField = () => {
    if (canLoadTokenCart.value && tokenBarcodeField.value) {
        tokenBarcodeField.value.focus();
        tokenBarcodeField.value.select?.();
        return;
    }

    focusBarcodeField();
};

const goToCreateSalesReturn = () => {
    if (!canAccessBillingActions.value) {
        alert("You do not have permission to create sales returns.");
        return;
    }

    router.visit(route("return.index", { open_create: 1 }));
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

    // Stock filter: 'low' => low stock (but not out), 'out' => out of stock
    if (productFilters.value.stock_filter === "low") {
        filtered = filtered.filter((p) => isLowStock(p));
    }

    if (productFilters.value.stock_filter === "out") {
        filtered = filtered.filter((p) => isOutOfStock(p));
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
        stock_filter: "",
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

    // Add product directly to cart
    const existingIndex = form.items.findIndex((item) => item.product_id === product.id);
    const price = getCurrentPrice(product);

    if (existingIndex !== -1) {
        form.items[existingIndex].quantity += quantity;
    } else {
        form.items.push({
            product_id: product.id,
            product_name: product.name,
            division_id: product.division_id ?? null,
            price: parseFloat(price),
            quantity: quantity,
            discount: product.discount
                ? {
                    name: product.discount.name,
                    value: product.discount.value,
                    type: product.discount.type,
                }
                : null,
        });
    }

    await logActivity("create", "sales", {
        action: "add_to_cart_from_modal",
        product_id: product.id,
        product_name: product.name,
        quantity: quantity,
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
    const margin = product.shop_low_stock_margin || 0;
    return product.shop_quantity_in_sales_unit > 0 && product.shop_quantity_in_sales_unit <= margin;
};

const isOutOfStock = (product) => {
    return product.shop_quantity_in_sales_unit <= 0;
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

// Submit sale with multiple payments
const submitSale = (paidStatus = 1) => {
    if (!showNormalPosUI.value) {
        alert("You do not have permission to complete sales.");
        return;
    }

    if (isShiftLocked.value) {
        alert("Start a shift first to unlock billing.");
        return;
    }

    if (form.items.length === 0) {
        alert("Please add items to cart");
        return;
    }

    // Unpaid sales don't require a payment entry
    if (paidStatus === 1 && form.payments.length === 0) {
        alert("Please click the Payment button to proceed.");
        return;
    }

    form.paid_status = paidStatus;
    completedPaidStatus.value = paidStatus;

    if (balance.value > 0) {
        if (!confirm(`Unpaid balance: Rs. ${(balance.value || 0).toFixed(2)}. Continue?`)) {
            return;
        }
    }

    // Store sale data before submitting
    completedInvoice.value = form.invoice_no;
    completedSaleDate.value = form.sale_date;
    completedCustomer.value =
        props.customers.find((c) => c.id === form.customer_id)?.name || "";
    completedPaymentType.value =
        form.payments.length > 0 ? form.payments[0].payment_type : 0;
    completedCardType.value =
        form.payments.length > 0 ? (form.payments[0].card_type || null) : null;
    completedPayments.value = form.payments.map((payment) => ({
        payment_type: payment.payment_type,
        amount: parseFloat(payment.amount) || 0,
        card_type: payment.card_type || null,
    }));
    completedItems.value = form.items.map((item) => ({
        ...item,
        division_id: item.division_id ?? getProductDivisionId(item.product_id),
    }));
    completedTotal.value = (originalTotal.value || 0).toFixed(2);
    completedProductDiscount.value = (totalProductDiscount.value || 0).toFixed(2);
    completedDiscount.value = (Number(form.discount) || 0).toFixed(2);
    completedNetAmount.value = (netAmount.value || 0).toFixed(2);
    completedPaid.value = (totalPaid.value || 0).toFixed(2);
    completedBalance.value = (balance.value || 0).toFixed(2);

    const submitRoute = editingUnpaidSaleId.value
        ? route("sales.complete-unpaid", editingUnpaidSaleId.value)
        : route("sales.store");

    const submitMethod = editingUnpaidSaleId.value
        ? form.patch.bind(form)
        : form.post.bind(form);

    submitMethod(submitRoute, {
        preserveScroll: true,
        onSuccess: async () => {
            await logActivity("create", "sales", {
                action: editingUnpaidSaleId.value ? "complete_unpaid_sale" : "complete_sale",
                invoice_no: form.invoice_no,
                customer_id: form.customer_id,
                items_count: form.items.length,
                total_amount: totalAmount.value,
                payments_count: form.payments.length,
                net_amount: netAmount.value,
            });

            // Auto-print only when bill setting enables it
            const shouldAutoPrint = !!(
                bill &&
                (bill.auto_print === 1 || bill.auto_print === "1" || bill.auto_print === true)
            );

            if (shouldAutoPrint) {
                try {
                    printReceipt();
                } catch (e) {
                    console.error("Auto print failed:", e);
                }

                // Small delay to allow print dialog to open before navigating away
                setTimeout(() => {
                    router.visit(route("sales.index"));
                }, 800);
            } else {
                // Show confirmation modal when auto-print is disabled
                showSuccessModal.value = true;
            }

            showPaymentModal.value = false;
        },
        onError: (errors) => {
            console.error("Sale error:", errors);
            let errorMsg = "Sale failed. Please try again.";
            if (errors.invoice_no) errorMsg = errors.invoice_no[0];
            else if (errors.items) errorMsg = errors.items[0];
            alert(errorMsg);
        },
    });
};

const startShiftFromBilling = () => {
    startShiftForm.post(route("shift-management.start"), {
        preserveScroll: true,
        onSuccess: () => {
            showStartShiftModal.value = false;
            startShiftForm.reset();
            startShiftForm.opening_till_amount = 0;
            startShiftForm.start_note = "";
            startShiftForm.redirect_to = "sales.index";
            router.visit(route("sales.index"));
        },
    });
};

const openStartShiftModal = () => {
    showStartShiftModal.value = true;
};

const closeStartShiftModal = () => {
    showStartShiftModal.value = false;
};

// Unpaid sales modal
const openUnpaidModal = async () => {
    if (!canAccessBillingActions.value) {
        alert("You do not have permission to view unpaid sales.");
        return;
    }

    showUnpaidModal.value = true;
    unpaidLoading.value = true;
    try {
        const response = await axios.get(route('sales.unpaid-list'));
        unpaidSales.value = (response.data || []).map(s => ({ ...s, marking: false }));
    } catch (e) {
        console.error('Failed to load unpaid sales', e);
    } finally {
        unpaidLoading.value = false;
    }
};

const markAsPaid = async (sale) => {
    sale.marking = true;
    try {
        await axios.patch(route('sales.mark-paid', sale.id));
        unpaidSales.value = unpaidSales.value.filter(s => s.id !== sale.id);
    } catch (e) {
        alert('Failed to mark as paid.');
        sale.marking = false;
    }
};

const loadUnpaidSale = async (sale) => {
    if (!sale?.id) return;

    if (form.items.length > 0) {
        if (!confirm("This will replace current cart items. Continue?")) {
            return;
        }
    }

    loadingUnpaidSaleId.value = sale.id;
    try {
        const response = await axios.get(route('sales.unpaid-details', sale.id));
        const data = response.data || {};

        form.invoice_no = data.invoice_no || form.invoice_no;
        form.customer_id = data.customer_id || "";
        form.customer_type = data.customer_type || "retail";
        form.sale_date = data.sale_date || form.sale_date;
        form.discount = parseFloat(data.discount || 0);
        form.items = (data.items || []).map((item) => ({
            product_id: item.product_id,
            product_name: item.product_name,
            division_id: item.division_id ?? getProductDivisionId(item.product_id),
            price: parseFloat(item.price || 0),
            quantity: parseFloat(item.quantity || 1),
            sale_unit: item.sale_unit || "Not found",
            discount: null,
            discountApplied: false,
        }));
        form.payments = [];
        form.paid_status = 0;
        form.pre_billing_token_id = null;
        preBillingTokenId.value = "";
        tokenBarcodeInput.value = "";
        editingUnpaidSaleId.value = data.id || sale.id;

        showUnpaidModal.value = false;
        alert(`Loaded unpaid invoice: ${data.invoice_no || sale.invoice_no}`);
        barcodeField.value?.focus();
    } catch (e) {
        console.error('Failed to load unpaid sale', e);
        alert('Failed to load unpaid sale.');
    } finally {
        loadingUnpaidSaleId.value = null;
    }
};

// Print receipt
const printReceipt = () => {
    const printWindow = window.open("", "_blank", "width=302,height=600");

    if (!printWindow) {
        alert("Please allow pop-ups to print receipt");
        return;
    }

    const bill = props.billSetting || {};
    const rawSize = (bill.print_size || "80mm").toString();
    const width = rawSize.includes("58") ? "58mm" : "80mm";
    const authUser = page.props.auth?.user;
    const userDivision = authUser?.division;
    const saleDivision = getDivisionMetaForItems(completedItems.value || []);
    const divisionName = saleDivision.name || userDivision?.name || null;
    // Department subtitle follows the sold items when the sale belongs to a single division.
    const deptSubtitle = divisionName ? `${divisionName} Department` : '';
    // Footer note follows the sold items when possible, otherwise falls back to cashier division.
    const divisionSlug = saleDivision.slug || userDivision?.slug || '';
    const divisionFooter = divisionSlug === 'sports'
        ? 'සැ:යු: භාණ්ඩ අලෙවි කිරීමෙන් පසු නැවත මාරු කිරීමක් හෝ නැවත මුදල් ලබා දීමක් සිදුනොවන බව කාරුණිකව දන්වමි.'
        : (divisionSlug === 'pharmacy'
            ? 'Medicines cannot be returned or exchanged after purchase. Please check your items carefully before leaving.'
            : (bill.footer_description || 'Thank you for your business.'));
    const currency = page.props.currency || "Rs.";
    const paymentStatusLabel = completedPaidStatus.value === 0 ? "Unpaid" : "Paid";
    const paymentDetailsList = completedPayments.value || [];
    const vatNumberHtml = hideVatNumber.value
        ? "<div class=\"left\" style=\"visibility:hidden\">VAT No:<br>933686833-7000</div>"
        : "<div class=\"left\">VAT No:<br>933686833-7000</div>";
    const paymentDetailsHtml = paymentDetailsList.length
        ? paymentDetailsList
            .map(
                (payment) => `
                    <div class="total-row">
                        <span>${getPaymentTypeText(payment.payment_type, payment.card_type)}:</span>
                    <span>${currency} ${(parseFloat(payment.amount) || 0).toFixed(2)}</span>
                    </div>
                    `
            )
            .join("")
        : completedPaidStatus.value === 0
            ? ""
            : `
                    <div class="total-row">
                        <span>Payment Method:</span>
                    <span>${getPaymentTypeText(completedPaymentType.value, completedCardType.value)}</span>
                    </div>
                    `;
    const completedItemsList = completedItems.value || [];
    const itemRowsHtml = completedItemsList
        .map(
            (item, index) => `
        <tr>
          <td class="col-no">${index + 1}</td>
          <td class="col-item">${item.product_name || "-"}</td>
          <td class="col-qty">${item.quantity || 0}</td>
          <td class="col-rate">${(item.price || 0).toFixed(2)}</td>
          <td class="col-total">${((item.price || 0) * (item.quantity || 0)).toFixed(2)}</td>
        </tr>
      `
        )
        .join("");

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
                    font-family: Arial, sans-serif;
                    font-size: 13px;
                    width: ${width};
                    margin: 0;
                    padding: 3mm 4mm;
                    background: white;
                    color: #000;
                    line-height: 1.4;
                    font-weight: 700;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .receipt-container {
                  width: 100%;
                  max-width: ${width};
                }
                .meta-top {
                  display: flex;
                  justify-content: space-between;
                  font-size: 11px;
                  margin-bottom: 4px;
                  line-height: 1.25;
                }
                .meta-top .left,
                .meta-top .right {
                  width: 48%;
                }
                .meta-top .right {
                  text-align: right;
                }
                .header {
                  text-align: center;
                  margin-bottom: 6px;
                }
                .header h1 {
                  font-size: 18px;
                  font-weight: 900;
                  margin-bottom: 0;
                  text-transform: uppercase;
                  line-height: 1.1;
                }
                .header .sub-title {
                  font-size: 11px;
                  margin-top: 1px;
                  margin-bottom: 2px;
                }
                .header .address {
                  font-size: 12px;
                  font-weight: 800;
                  line-height: 1.2;
                  text-transform: uppercase;
                }
                .identity-line {
                  margin: 8px 0 4px;
                  font-size: 11px;
                  border-bottom: 1px dotted #000;
                  height: 16px;
                }
                .bill-meta {
                  border: 1px solid #000;
                  border-bottom: 0;
                  display: grid;
                  grid-template-columns: 1fr 1fr 1fr;
                  font-size: 11px;
                  font-weight: 700;
                }
                .bill-meta div {
                  padding: 4px 6px;
                  border-right: 1px solid #000;
                }
                .bill-meta div:last-child {
                  border-right: 0;
                }
                .items-table {
                  width: 100%;
                  border-collapse: collapse;
                  border: 1px solid #000;
                  table-layout: fixed;
                  font-size: 11px;
                }
                .items-table thead th {
                  border-bottom: 1px solid #000;
                  border-right: 1px solid #000;
                  text-align: center;
                  font-weight: 800;
                  padding: 4px 2px;
                }
                .items-table thead th:last-child {
                  border-right: 0;
                }
                .items-table tbody td {
                  border-top: 1px dotted #444;
                  border-right: 1px solid #000;
                  padding: 3px 2px;
                  height: 18px;
                  vertical-align: middle;
                  font-weight: 600;
                }
                .items-table tbody td:last-child {
                  border-right: 0;
                }
                .col-no { width: 8%; text-align: center; }
                .col-item { width: 44%; text-align: left; word-wrap: break-word; }
                .col-qty { width: 12%; text-align: center; }
                .col-rate { width: 18%; text-align: right; }
                .col-total { width: 18%; text-align: right; }
                .totals {
                  border: 1px solid #000;
                  border-top: 0;
                  padding: 6px;
                  margin-top: 0;
                  font-size: 11px;
                }
                .total-row {
                  display: flex;
                  justify-content: space-between;
                  margin: 2px 0;
                  line-height: 1.35;
                }
                .total-row.grand {
                  border-top: 1px solid #000;
                  border-bottom: 1px solid #000;
                  padding: 4px 0;
                  margin: 5px 0;
                  font-size: 12px;
                  font-weight: 900;
                }
                .notes {
                  margin-top: 6px;
                  font-size: 10px;
                  line-height: 1.3;
                  border-top: 1px solid #000;
                  padding-top: 6px;
                  text-align: center;
                }
            </style>
        </head>
        <body>
            <div class="receipt-container">
                <div class="meta-top">
                  ${vatNumberHtml}
                  <div class="right">

ESTD:1926
<br>


                    TEL: ${[bill.mobile_1, bill.mobile_2].filter(Boolean).join(" / ") || "-"}</div>
                </div>
                <div class="header">

                  <h1>${bill.company_name || "THE RATNAPURA STORES"}</h1>
                  ${deptSubtitle ? `<div class="sub-title">${deptSubtitle}</div>` : ''}
                  <div class="address">${bill.address || "57, MAIN STREET, RATNAPURA."}</div>
                </div>

                <div class="identity-line">Mr./Customer: ${completedCustomer.value || "Walk-in Customer"}</div>

                <div class="bill-meta">
                  <div>Served by: ${page.props.auth?.user?.name || "-"}</div>
                  <div>Invoice: ${completedInvoice.value}</div>
                  <div>Date: ${completedSaleDate.value}</div>
                </div>

                <table class="items-table">
                    <thead>
                        <tr>
                      <th class="col-no">#</th>
                      <th class="col-item">Items</th>
                      <th class="col-qty">Qty</th>
                      <th class="col-rate">Rate</th>
                      <th class="col-total">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                    ${itemRowsHtml}
                    </tbody>
                </table>


                <div class="totals">
                    <div class="total-row">
                        <span>Subtotal:</span>
                    <span>${currency} ${completedTotal.value
        }</span>
                    </div>
                    ${parseFloat(completedProductDiscount.value) > 0
            ? `
                    <div class="total-row" style="color: green;">
                        <span>Product Discounts:</span>
                    <span>- ${currency} ${completedProductDiscount.value
            }</span>
                    </div>
                    `
            : ""
        }
                    ${parseFloat(completedDiscount.value) > 0
            ? `
                    <div class="total-row">
                        <span>Custom Discount:</span>
                    <span>- ${currency} ${completedDiscount.value
            }</span>
                    </div>
                    `
            : ""
        }
                    <div class="total-row grand">
                    <span>NET TOTAL:</span>
                    <span>${currency} ${completedNetAmount.value
        }</span>
                    </div>
                    <div class="total-row">
                      <span>Payment Status:</span>
                    <span>${paymentStatusLabel}</span>
                    </div>
                    ${paymentDetailsHtml}
                    <div class="total-row">
                        <span>Paid Amount:</span>
                    <span>${currency} ${completedPaid.value
        }</span>
                    </div>
                    <div class="total-row" style="font-weight: bold;">
                        <span>${parseFloat(completedBalance.value) > 0
            ? "Balance Due:"
            : "Change:"
        }</span>
                        <span>${currency} ${Math.abs(
            parseFloat(completedBalance.value)
        ).toFixed(2)}</span>
                    </div>
                </div>

                    <div class="notes">
                      <p style="margin-top: 8px; font-size: 10px; text-align: left;">${divisionFooter}</p>
                    <p style="margin-top: 8px; font-size: 10px; text-align: left;">භාණ්ඩ පරීශාකර ලබා ගත් බවට අත්සන .....................................</p>
                    <p style="margin-top: 6px; font-size: 9px;">Powered by JAAN Network (PVT) Ltd</p>
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
    router.visit(route("sales.index"));
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
        router.visit(route("sales.index"));
    }, 800);
};

// Focus barcode input on mount
onMounted(() => {
    focusInitialBillingField();
    nextTick(() => {
        focusInitialBillingField();
    });
    setTimeout(() => {
        focusInitialBillingField();
    }, 80);

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
