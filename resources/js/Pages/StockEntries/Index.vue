<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";

const props = defineProps({
    entries: Object,
    suppliers: Array,
    categories: Array,
    availableProducts: Array,
    entryNumber: String,
});

const page = usePage();
const currentUserRole = computed(() => Number(page.props.auth?.user?.role ?? -1));
const currentUserDivisionId = computed(() => {
    const divisionId = page.props.auth?.user?.division_id;
    return divisionId ? String(divisionId) : "";
});
const divisions = computed(() => page.props.divisions ?? []);
const canChooseDivision = computed(() => [0, 1].includes(currentUserRole.value));

// --- Form state ---
const showForm = ref(false);
const editingEntry = ref(null);
const isEditing = computed(() => !!editingEntry.value);
const submitting = ref(false);
const errors = ref({});

const form = ref({
    entry_number: props.entryNumber,
    invoice_number: "",
    supplier_id: "",
    entry_type: "addition",
    entry_date: new Date().toISOString().slice(0, 10),
    remarks: "",
    products: [],
});

// --- Product search ---
const searchQuery = ref("");
const showDropdown = ref(false);

const filteredProducts = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return props.availableProducts.slice(0, 30);
    return props.availableProducts
        .filter(
            (p) =>
                p.name.toLowerCase().includes(q) ||
                (p.barcode && p.barcode.toLowerCase().includes(q))
        )
        .slice(0, 30);
});

const canCreateNew = computed(() => !isEditing.value && form.value.entry_type === 'addition' && searchQuery.value.trim().length >= 2);

function getDefaultDivisionId() {
    return currentUserDivisionId.value;
}

function selectProduct(product) {
    if (form.value.products.find((p) => !p.isNew && p.product_id === product.id)) {
        showDropdown.value = false;
        searchQuery.value = "";
        return;
    }
    form.value.products.push({
        isNew: false,
        product_id: product.id,
        name: product.name,
        barcode: product.barcode ?? "",
        current_stock: Number(product.shop_quantity_in_sales_unit ?? 0),
        quantity: "",
        purchase_price: product.purchase_price ?? "",
    });
    showDropdown.value = false;
    searchQuery.value = "";
}

function addNewProduct() {
    const name = searchQuery.value.trim();
    if (!name) return;
    form.value.products.push({
        isNew: true,
        product_id: null,
        new_name: name,
        new_barcode: "",
        new_category_id: "",
        new_division_id: getDefaultDivisionId(),
        new_retail_price: "",
        quantity: "",
        purchase_price: "",
    });
    showDropdown.value = false;
    searchQuery.value = "";
}

function removeProduct(index) {
    form.value.products.splice(index, 1);
}

// --- Submit ---
function submit() {
    submitting.value = true;
    errors.value = {};

    const payload = {
        entry_number: form.value.entry_number,
        invoice_number: form.value.invoice_number || null,
        supplier_id: form.value.supplier_id || null,
        entry_date: form.value.entry_date,
        remarks: form.value.remarks,
            products: form.value.products.map((p) =>
            p.isNew
                ? {
                      is_new: true,
                      product_id: null,
                      new_name: p.new_name,
                      new_barcode: p.new_barcode || null,
                      new_category_id: p.new_category_id || null,
                      new_division_id: p.new_division_id || null,
                      new_retail_price: p.new_retail_price || null,
                      purchase_price: p.purchase_price || null,
                      quantity: p.quantity,
                  }
                : {
                      is_new: false,
                      product_id: p.product_id,
                      purchase_price: p.purchase_price || null,
                      quantity: p.quantity,
                  }
        ),
    };

    if (isEditing.value) {
        router.put(route('stock-entries.update', editingEntry.value.id), payload, {
            onSuccess: () => {
                showForm.value = false;
                editingEntry.value = null;
                submitting.value = false;
            },
            onError: (errs) => {
                errors.value = errs;
                submitting.value = false;
            },
        });
    } else {
        router.post(route("stock-entries.store"), {
            ...payload,
            entry_type: form.value.entry_type,
        }, {
            onSuccess: (pageResponse) => {
                showForm.value = false;
                submitting.value = false;

                const printId = pageResponse?.props?.flash?.print_stock_entry_id;
                if (printId) {
                    window.open(route('stock-entries.print', printId), '_blank');
                }
            },
            onError: (errs) => {
                errors.value = errs;
                submitting.value = false;
            },
        });
    }
}

function openForm() {
    editingEntry.value = null;
    form.value = {
        entry_number: props.entryNumber,
        invoice_number: "",
        supplier_id: "",
        entry_type: "addition",
        entry_date: new Date().toISOString().slice(0, 10),
        remarks: "",
        products: [],
    };
    errors.value = {};
    searchQuery.value = "";
    showForm.value = true;
}

function openEditForm(entry) {
    editingEntry.value = entry;
    form.value = {
        entry_number: entry.entry_number,
        invoice_number: entry.invoice_number ?? '',
        supplier_id: entry.supplier?.id ? String(entry.supplier.id) : '',
        entry_type: entry.entry_type,
        entry_date: entry.entry_date ? String(entry.entry_date).slice(0, 10) : new Date().toISOString().slice(0, 10),
        remarks: entry.remarks ?? '',
        products: entry.products.map(p => ({
            isNew: false,
            product_id: p.product_id,
            name: p.product?.name ?? 'Unknown',
            barcode: p.product?.barcode ?? '',
            current_stock: Number(p.product?.shop_quantity_in_sales_unit ?? 0),
            quantity: String(parseFloat(p.quantity)),
            purchase_price: p.purchase_price ?? '',
        })),
    };
    errors.value = {};
    searchQuery.value = '';
    showForm.value = true;
}

function deleteEntry(id) {
    if (!confirm("Delete this stock entry record?")) return;
    router.delete(route("stock-entries.destroy", id));
}

function formatDate(d) {
    if (!d) return "-";
    return new Date(d).toLocaleDateString("en-GB");
}

watch(
    () => page.props?.flash?.print_stock_entry_id,
    (printId) => {
        if (printId) {
            window.open(route('stock-entries.print', printId), '_blank');
        }
    }
);
</script>

<template>
    <Head title="Stock Management" />
    <AppLayout>
        <div class="min-h-screen bg-gray-50 p-6">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Stock Management</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Add or deduct stock. New products can be created inline during Stock In.
                    </p>
                </div>
                <button
                    @click="openForm"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-medium text-sm transition"
                >
                    + New Stock Entry
                </button>
            </div>

            <!-- Global error -->
            <div
                v-if="errors.error"
                class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm"
            >
                {{ errors.error }}
            </div>

            <!-- Entry Form -->
            <div
                v-if="showForm"
                class="bg-white rounded-xl border border-gray-200 shadow-sm mb-8 p-6"
            >
                <h2 class="text-lg font-semibold text-gray-800 mb-5 border-b pb-3">
                    {{ isEditing ? 'Edit Stock Entry' : 'New Stock Entry' }}
                </h2>

                <!-- Stock In / Stock Out toggle -->
                <div class="flex gap-3 mb-5">
                    <button
                        @click="!isEditing && (form.entry_type = 'addition', form.products = [])"
                        :disabled="isEditing"
                        :class="[
                            'flex-1 py-3 rounded-lg font-medium text-sm border-2 transition',
                            form.entry_type === 'addition'
                                ? 'bg-green-600 text-white border-green-600'
                                : 'bg-white text-gray-600 border-gray-200 hover:border-green-400',
                            isEditing ? 'cursor-not-allowed opacity-70' : '',
                        ]"
                    >Stock In (Addition)</button>
                    <button
                        @click="!isEditing && (form.entry_type = 'deduction', form.products = [])"
                        :disabled="isEditing"
                        :class="[
                            'flex-1 py-3 rounded-lg font-medium text-sm border-2 transition',
                            form.entry_type === 'deduction'
                                ? 'bg-red-600 text-white border-red-600'
                                : 'bg-white text-gray-600 border-gray-200 hover:border-red-400',
                            isEditing ? 'cursor-not-allowed opacity-70' : '',
                        ]"
                    >Stock Out (Deduction)</button>
                </div>

                <!-- Header fields -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Entry Number</label>
                        <input
                            v-model="form.entry_number"
                            type="text"
                            :readonly="isEditing"
                            :class="['w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none', isEditing ? 'bg-gray-100 cursor-not-allowed' : '']"
                        />
                        <p v-if="errors.entry_number" class="text-xs text-red-600 mt-1">{{ errors.entry_number }}</p>
                    </div>

                   <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Invoice Number <span class="text-gray-400">(optional)</span></label>
                        <input
                            v-model="form.invoice_number"
                            type="text"
                            placeholder="e.g. INV-2026-001"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                        <p v-if="errors.invoice_number" class="text-xs text-red-600 mt-1">{{ errors.invoice_number }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
                        <input
                            v-model="form.entry_date"
                            type="date"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Supplier <span class="text-gray-400">(optional)</span></label>
                        <select
                            v-model="form.supplier_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        >
                            <option value="">- No Supplier -</option>
                            <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Remarks <span class="text-gray-400">(optional)</span></label>
                        <input
                            v-model="form.remarks"
                            type="text"
                            placeholder="e.g. Monthly restock"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                        />
                    </div>
                </div>

                <!-- Product search -->
                <div class="mb-3 relative">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Search Product</label>
                    <input
                        v-model="searchQuery"
                        @focus="showDropdown = true"
                        @blur="setTimeout(() => (showDropdown = false), 200)"
                        type="text"
                        placeholder="Type product name or barcode..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    />
                    <div
                        v-if="showDropdown && (filteredProducts.length || canCreateNew)"
                        class="absolute z-20 top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-y-auto mt-1"
                    >
                        <button
                            v-for="p in filteredProducts"
                            :key="p.id"
                            @mousedown.prevent="selectProduct(p)"
                            class="w-full text-left px-4 py-2.5 hover:bg-blue-50 text-sm border-b border-gray-100 last:border-0 flex justify-between items-center"
                        >
                            <span>
                                <span class="font-medium text-gray-800">{{ p.name }}</span>
                                <span v-if="p.barcode" class="text-gray-400 ml-2 text-xs">{{ p.barcode }}</span>
                            </span>
                            <span class="text-xs text-gray-500">Stock: {{ p.shop_quantity_in_sales_unit }}</span>
                        </button>
                        <button
                            v-if="canCreateNew"
                            @mousedown.prevent="addNewProduct"
                            class="w-full text-left px-4 py-2.5 hover:bg-green-50 text-sm flex items-center gap-2 border-t border-gray-200 text-green-700 font-medium"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Create new product: "{{ searchQuery.trim() }}"
                        </button>
                    </div>
                </div>

                <p v-if="errors.products" class="text-xs text-red-600 mb-2">{{ errors.products }}</p>

                <!-- Products table -->
                <div v-if="form.products.length" class="overflow-x-auto rounded-lg border border-gray-200 mb-5">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left">Product</th>
                                <th class="px-4 py-3 text-left">Current Stock</th>
                                <th class="px-4 py-3 text-left">Qty *</th>
                                <th v-if="form.entry_type === 'addition'" class="px-4 py-3 text-left">Purchase Price</th>
                                <th v-if="form.entry_type === 'addition'" class="px-4 py-3 text-left">Retail Price</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="(line, i) in form.products"
                                :key="i"
                                :class="line.isNew ? 'bg-green-50' : 'bg-white hover:bg-gray-50'"
                            >
                                <!-- Product cell -->
                                <td class="px-4 py-3">
                                    <template v-if="line.isNew">
                                        <div class="flex items-center gap-2 mb-1.5">
                                            <span class="text-xs bg-green-100 text-green-700 font-semibold px-2 py-0.5 rounded">NEW</span>
                                        </div>
                                        <input
                                            v-model="line.new_name"
                                            type="text"
                                            placeholder="Product name *"
                                            class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm mb-1.5 focus:ring-2 focus:ring-green-400 focus:outline-none"
                                        />
                                        <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
                                            <input
                                                v-model="line.new_barcode"
                                                type="text"
                                                placeholder="Barcode (optional)"
                                                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-gray-400 focus:outline-none"
                                            />
                                            <select
                                                v-model="line.new_category_id"
                                                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-gray-400 focus:outline-none"
                                            >
                                                <option value="">Category</option>
                                                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                                            </select>
                                            <select
                                                v-model="line.new_division_id"
                                                :disabled="!canChooseDivision && !!currentUserDivisionId"
                                                class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs focus:ring-1 focus:ring-gray-400 focus:outline-none disabled:bg-gray-100 disabled:text-gray-500"
                                            >
                                                <option value="">Division *</option>
                                                <option v-for="division in divisions" :key="division.id" :value="String(division.id)">
                                                    {{ division.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <p v-if="errors['products.' + i + '.new_division_id']" class="text-xs text-red-600 mt-1">
                                            {{ errors['products.' + i + '.new_division_id'] }}
                                        </p>
                                    </template>
                                    <template v-else>
                                        <span class="font-medium text-gray-800">{{ line.name }}</span>
                                        <div v-if="line.barcode" class="text-xs text-gray-400">{{ line.barcode }}</div>
                                    </template>
                                </td>

                                <!-- Current stock -->
                                <td class="px-4 py-3 text-gray-600">
                                    <span v-if="line.isNew" class="text-xs text-gray-400">-</span>
                                    <span
                                        v-else
                                        :class="line.current_stock === 0 ? 'text-red-600 font-semibold' : ''"
                                    >{{ line.current_stock }}</span>
                                </td>

                                <!-- Quantity -->
                                <td class="px-4 py-3">
                                    <input
                                        v-model="line.quantity"
                                        type="number"
                                        min="0.01"
                                        step="any"
                                        placeholder="0"
                                        class="w-24 border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    />
                                    <p v-if="errors['products.' + i + '.quantity']" class="text-xs text-red-600 mt-0.5">
                                        {{ errors['products.' + i + '.quantity'] }}
                                    </p>
                                </td>

                                <!-- Purchase price (addition only) -->
                                <td v-if="form.entry_type === 'addition'" class="px-4 py-3">
                                    <input
                                        v-model="line.purchase_price"
                                        type="number"
                                        min="0"
                                        step="any"
                                        placeholder="0.00"
                                        class="w-28 border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    />
                                </td>

                                <!-- Retail price (new products in addition mode only) -->
                                <td v-if="form.entry_type === 'addition'" class="px-4 py-3">
                                    <input
                                        v-if="line.isNew"
                                        v-model="line.new_retail_price"
                                        type="number"
                                        min="0"
                                        step="any"
                                        placeholder="0.00 *"
                                        class="w-28 border border-orange-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-orange-400 focus:outline-none"
                                    />
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </td>

                                <!-- Remove -->
                                <td class="px-4 py-3">
                                    <button
                                        @click="removeProduct(i)"
                                        class="text-red-400 hover:text-red-600 transition p-1 rounded"
                                        title="Remove"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-else
                    class="mb-5 py-8 text-center text-gray-400 text-sm border border-dashed border-gray-300 rounded-lg"
                >
                    No products added yet. Search above to add products.
                </div>

                <!-- Actions -->
                <div class="flex gap-3 justify-end">
                    <button
                        @click="showForm = false"
                        class="px-5 py-2.5 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 transition"
                    >
                        Cancel
                    </button>
                    <button
                        @click="submit"
                        :disabled="submitting || form.products.length === 0"
                        :class="[
                            'px-6 py-2.5 rounded-lg text-sm font-medium text-white transition',
                            form.entry_type === 'addition'
                                ? 'bg-green-600 hover:bg-green-700 disabled:bg-green-300'
                                : 'bg-red-600 hover:bg-red-700 disabled:bg-red-300',
                        ]"
                    >
                        <span v-if="submitting">Saving...</span>
                        <span v-else>
                            <template v-if="isEditing">
                                Save Changes ({{ form.products.length }} product{{ form.products.length !== 1 ? "s" : "" }})
                            </template>
                            <template v-else>
                                {{ form.entry_type === 'addition' ? 'Confirm Stock In' : 'Confirm Stock Out' }}
                                ({{ form.products.length }} product{{ form.products.length !== 1 ? "s" : "" }})
                            </template>
                        </span>
                    </button>
                </div>
            </div>

            <!-- Entries List -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-800">Stock Entry History</h2>
                    <span class="text-sm text-gray-400">{{ entries.total }} total entries</span>
                </div>

                <div v-if="entries.data.length === 0" class="py-16 text-center text-gray-400">
                    No stock entries yet. Click "+ New Stock Entry" to get started.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase border-b border-gray-100">
                            <tr>
                                <th class="px-5 py-3 text-left">Entry #</th>
                                <th class="px-5 py-3 text-left">Invoice #</th>
                                <th class="px-5 py-3 text-left">Type</th>
                                <th class="px-5 py-3 text-left">Date</th>
                                <th class="px-5 py-3 text-left">Supplier</th>
                                <th class="px-5 py-3 text-left">Products</th>
                                <th class="px-5 py-3 text-left">Remarks</th>
                                <th class="px-5 py-3 text-left">Created By</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="entry in entries.data" :key="entry.id" class="hover:bg-gray-50">
                                <td class="px-5 py-3 font-mono text-gray-700 font-medium text-xs">{{ entry.entry_number }}</td>
                                <td class="px-5 py-3 font-mono text-gray-700 text-xs">{{ entry.invoice_number || '-' }}</td>
                                <td class="px-5 py-3">
                                    <span
                                        :class="entry.entry_type === 'addition' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                        class="px-2 py-0.5 rounded text-xs font-medium"
                                    >
                                        {{ entry.entry_type === 'addition' ? 'Stock In' : 'Stock Out' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-gray-600">{{ formatDate(entry.entry_date) }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ entry.supplier?.name ?? "-" }}</td>
                                <td class="px-5 py-3">
                                    <div class="text-xs text-gray-500 space-y-0.5">
                                        <div v-for="p in entry.products" :key="p.id" class="flex gap-1 items-center">
                                            <span class="font-medium text-gray-700">{{ p.product?.name }}</span>
                                            <span>x</span>
                                            <span>{{ parseFloat(p.quantity) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-gray-500 text-xs">{{ entry.remarks || "-" }}</td>
                                <td class="px-5 py-3 text-gray-500 text-xs">{{ entry.user?.name ?? "-" }}</td>
                                <td class="px-5 py-3">
                                    <a
                                        :href="route('stock-entries.print', entry.id)"
                                        target="_blank"
                                        class="text-blue-500 hover:text-blue-700 text-xs transition mr-3"
                                    >Print Invoice</a>
                                    <button
                                        @click="openEditForm(entry)"
                                        class="text-yellow-500 hover:text-yellow-700 text-xs transition mr-3"
                                    >Edit</button>
                                    <button
                                        @click="deleteEntry(entry.id)"
                                        class="text-red-400 hover:text-red-600 text-xs transition"
                                    >Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="entries.last_page > 1"
                    class="px-6 py-4 border-t border-gray-100 flex items-center justify-between"
                >
                    <span class="text-sm text-gray-400">Page {{ entries.current_page }} of {{ entries.last_page }}</span>
                    <div class="flex gap-2">
                        <a v-if="entries.prev_page_url" :href="entries.prev_page_url" class="px-3 py-1.5 text-sm border border-gray-300 rounded hover:bg-gray-50">Previous</a>
                        <a v-if="entries.next_page_url" :href="entries.next_page_url" class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Next</a>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
