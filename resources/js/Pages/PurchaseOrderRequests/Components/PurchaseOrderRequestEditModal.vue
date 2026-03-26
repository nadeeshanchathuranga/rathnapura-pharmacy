<template>
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4" @click.self="closeModal">
        <div
            class="relative w-full max-w-6xl bg-gray-50 rounded-2xl max-h-[90vh] overflow-y-auto shadow-xl">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-blue-600">
                    {{ por && por.id ? '✏️ Edit Purchase Order Request' : '✨ Create Purchase Order Request' }}
                </h2>
                <button @click="closeModal" class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-200 rounded-full transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submitForm" class="p-6">
                <!-- Order Information -->
                <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
                    <h3 class="mb-3 text-lg font-semibold text-blue-600 flex items-center gap-2">
                        📋 Order Information
                    </h3>
                    <div>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">Order Number</label>
                                <input type="text"
                                    class="w-full px-3 py-2 text-sm text-gray-800 bg-gray-100 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    v-model="form.order_number" readonly>
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                    Order Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date"
                                    class="w-full px-3 py-2 text-sm text-gray-800 bg-white border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    :class="form.errors.order_date ? 'border-red-500' : 'border-gray-300'"
                                    v-model="form.order_date">
                                <div v-if="form.errors.order_date" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.order_date }}
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block mb-2 text-sm font-medium text-gray-700">
                                    User <span class="text-red-500">*</span>
                                </label>
 
                                <select
                                    class="w-full px-3 py-2 text-sm text-gray-800 bg-white border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    :class="form.errors.user_id ? 'border-red-500' : 'border-gray-300'"
                                    v-model="form.user_id">
                                    <option value="">Select User</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">
                                        {{ user.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.user_id" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.user_id }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products -->
                <div class="mb-4 bg-white rounded-xl p-4 shadow-sm border border-gray-200">
                    <h3 class="mb-3 text-lg font-semibold text-blue-600 flex items-center gap-2">
                        📦 Products
                    </h3>
                    <div>
                        <div v-for="(product, index) in form.products" :key="index"
                            class="pb-4 mb-4 border-b border-gray-200 last:border-b-0">
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                                <!-- Product -->
                                <div class="md:col-span-6">
                                    <label class="block mb-2 text-sm font-medium text-gray-700">
                                        Product <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        class="w-full px-3 py-2 text-sm text-gray-800 bg-white border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        :class="form.errors[`products.${index}.product_id`] ? 'border-red-500' : 'border-gray-300'"
                                        v-model="product.product_id" @change="onProductSelect(index)">
                                        <option value="">Select Product</option>
                                        <option v-for="prod in allProducts.length ? allProducts : products" :key="prod.id" :value="prod.id">
                                            {{ prod.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors[`products.${index}.product_id`]"
                                        class="mt-1 text-sm text-red-500">
                                        {{ form.errors[`products.${index}.product_id`] }}
                                    </div>
                                </div>

                                <!-- Unit -->
                                <div class="md:col-span-2">
                                    <label class="block mb-2 text-sm font-medium text-gray-700">
                                        Unit <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        class="w-full px-3 py-2 text-sm text-gray-800 bg-white border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        :class="form.errors[`products.${index}.measurement_unit_id`] ? 'border-red-500' : 'border-gray-300'"
                                        v-model="product.measurement_unit_id" 
                                    >
                                        <option value="">Select Unit</option>
                                       <option v-for="unit in getUnitsForProduct(index)" :key="unit.id" :value="unit.id">
    {{ unit.name }}
</option>
                                    </select>
                                    <div v-if="form.errors[`products.${index}.measurement_unit_id`]"
                                        class="mt-1 text-sm text-red-500">
                                        {{ form.errors[`products.${index}.measurement_unit_id`] }}
                                    </div>
                                </div>

                                <!-- Quantity -->
                                <div class="md:col-span-2">
                                    <label class="block mb-2 text-sm font-medium text-gray-700">
                                        Quantity <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number"
                                        class="w-full px-3 py-2 text-sm text-gray-800 bg-white border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        :class="form.errors[`products.${index}.requested_quantity`] ? 'border-red-500' : 'border-gray-300'"
                                        v-model.number="product.requested_quantity" min="1">
                                    <div v-if="form.errors[`products.${index}.requested_quantity`]"
                                        class="mt-1 text-sm text-red-500">
                                        {{ form.errors[`products.${index}.requested_quantity`] }}
                                    </div>
                                </div>

                                <!-- Remove -->
                                <div class="flex items-end md:col-span-2">
                                    <button v-if="form.products.length > 1" type="button" @click="removeProduct(index)"
                                        class="w-full px-4 py-2 text-xs font-medium text-white bg-red-600 rounded-[5px] hover:bg-red-700 transition-all duration-200">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="button" @click="addProduct"
                            class="px-4 py-2 text-xs font-medium text-white bg-green-600 rounded-[5px] hover:bg-green-700 transition-all duration-200">
                            + Add Product
                        </button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <button type="button" @click="closeModal"
                        class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-[5px] hover:bg-gray-50 transition-all duration-200">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-[5px] hover:bg-blue-700 disabled:opacity-50 transition-all duration-200"
                        :disabled="form.processing">
                        <span v-if="form.processing">
                            {{ por && por.id ? 'Updating...' : 'Creating...' }}
                        </span>
                        <span v-else>
                            {{ por && por.id ? 'Update POR' : 'Create POR' }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { watch, ref, onUnmounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    por: {
        type: Object,
        default: null,
    },
    products: {
        type: Array,
        required: true,
    },
    measurementUnits: Array,
    users: {
        type: Array,
        required: true,
    },
    orderNumber: {
        type: String,
        required: true,
    },
    allProducts: {
        type: Array,
        default: () => []
    },
});

const emit = defineEmits(['update:open']);

// Handle body scroll lock
watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
);

// Cleanup on unmount
onUnmounted(() => {
    document.body.style.overflow = '';
});

const productUnits = ref({});

const form = useForm({
    order_number: '',
    order_date: '',
    user_id: '',
    products: [],
});

watch(
    () => props.open,
    (newVal) => {
        if (newVal) {
            if (props.por && props.por.id) {
                // Editing existing POR
                form.order_number = props.por.order_number;
                // Format date to YYYY-MM-DD for input type="date"
                form.order_date = new Date(props.por.order_date).toISOString().split('T')[0];
                form.user_id = props.por.user_id;
                
                // Load products with their details (normalize to requested_quantity)
                form.products = props.por.por_products ? props.por.por_products.map(p => ({
                    id: p.id,
                    product_id: p.product_id,
                    product: p.product,
                    requested_quantity: p.requested_quantity ?? p.quantity ?? 1,
                    measurement_unit_id: p.measurement_unit_id,
                    measurement_unit: p.measurement_unit
                })) : [{ product_id: '', requested_quantity: 1, measurement_unit_id: '' }];

                // Initialize units for all products
                form.products.forEach((p, i) => {
                    initUnitsForProduct(i);
                });
            } else {
                // Creating new POR
                form.reset();
                form.order_number = props.orderNumber;
                form.order_date = new Date().toISOString().split('T')[0];
                form.products = [{ product_id: '', requested_quantity: 1, measurement_unit_id: '' }];
            }
        }
    },
    { deep: true }
);

const initUnitsForProduct = (index) => {
    const selectedProductId = form.products[index].product_id;
    if (!selectedProductId) {
        productUnits.value[index] = [];
        form.products[index].measurement_unit_id = '';
        return;
    }

    // Try to find product in props.products
    let product = props.products.find(p => p.id == selectedProductId);
    
    // If not found and we're editing, try to use the product from form.products
    if (!product && form.products[index].product) {
        product = form.products[index].product;
    }

    if (!product) return;

    if (product.measurement_units && product.measurement_units.length > 0) {
        productUnits.value[index] = product.measurement_units;
        form.products[index].measurement_unit_id = form.products[index].measurement_unit_id || product.measurement_units[0].id;
    } else if (product.measurement_unit && product.measurement_unit.id) {
        productUnits.value[index] = [product.measurement_unit];
        form.products[index].measurement_unit_id = product.measurement_unit.id;
    } else if (product.measurement_unit_id) {
        const defaultUnit = props.measurementUnits.find(u => u.id == product.measurement_unit_id);
        productUnits.value[index] = defaultUnit ? [defaultUnit] : props.measurementUnits;
        form.products[index].measurement_unit_id = product.measurement_unit_id;
    } else {
        productUnits.value[index] = props.measurementUnits || [];
        form.products[index].measurement_unit_id = '';
    }
};

const addProduct = () => {
    form.products.push({
        product_id: '',
        requested_quantity: 1,
        measurement_unit_id: '',
    });
};

const removeProduct = (index) => {
    form.products.splice(index, 1);
};

const closeModal = () => {
    emit('update:open', false);
    form.reset();
};

const submitForm = () => {
    if (props.por && props.por.id) {
        // Update existing POR
        form.patch(route('purchase-order-requests.update', props.por.id), {
            onSuccess: () => {
                closeModal();
                router.reload();
            },
        });
    } else {
        // Create new POR
        form.post(route('purchase-order-requests.store'), {
            onSuccess: () => {
                closeModal();
                router.reload();
            },
        });
    }
};

const onProductSelect = (index) => {
    const selectedProductId = form.products[index].product_id;

    if (!selectedProductId) {
        form.products[index].measurement_unit_id = '';
        productUnits.value[index] = [];
        return;
    }

    const product = props.products.find(p => p.id === parseInt(selectedProductId));

    if (product) {
        if (product.measurement_units && Array.isArray(product.measurement_units) && product.measurement_units.length > 0) {
            productUnits.value[index] = product.measurement_units;
            form.products[index].measurement_unit_id = product.measurement_unit_id || product.measurement_units[0].id;
        } else if (product.measurement_unit && product.measurement_unit.id) {
            productUnits.value[index] = [product.measurement_unit];
            form.products[index].measurement_unit_id = product.measurement_unit.id;
        } else if (product.measurement_unit_id) {
            const defaultUnit = props.measurementUnits.find(u => u.id === product.measurement_unit_id);
            productUnits.value[index] = defaultUnit ? [defaultUnit] : props.measurementUnits;
            form.products[index].measurement_unit_id = product.measurement_unit_id;
        } else {
            productUnits.value[index] = props.measurementUnits || [];
            form.products[index].measurement_unit_id = '';
        }
    }
};

const getUnitsForProduct = (index) => {
    return productUnits.value[index] || props.measurementUnits || [];
};

const getUnitName = (unitId) => {
    if (!unitId) {
        return '-';
    }

    const productWithUnit = props.products.find(p => p.measurement_unit_id == unitId);
    if (productWithUnit?.measurement_unit?.name) {
        return productWithUnit.measurement_unit.name;
    }

    if (Array.isArray(props.measurementUnits)) {
        const unit = props.measurementUnits.find(u => u.id == unitId);
        return unit?.name || '-';
    }

    return '-';
};
</script>

<style scoped>
/* Tailwind CSS is used for styling */
</style>
