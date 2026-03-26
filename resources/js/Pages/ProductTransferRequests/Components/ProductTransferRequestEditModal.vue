<template>
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black bg-opacity-75">
        <div
            class="relative w-full max-w-6xl p-6 mx-4 my-8 bg-black border-4 border-blue-600 rounded-lg max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-white">Edit Purchase Order Request</h2>
                <button @click="closeModal" class="text-white hover:text-gray-300">
                    <i class="text-2xl fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="submitForm">
                <!-- Order Information -->
                <div class="mb-6 overflow-hidden border-2 border-blue-500 rounded-lg">
                    <div class="px-6 py-3 bg-blue-600">
                        <h5 class="font-bold text-white">Order Information</h5>
                    </div>
                    <div class="p-6 bg-gray-900">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="block mb-2 text-sm font-medium text-white">Order Number</label>
                                <input type="text"
                                    class="w-full px-4 py-2 text-white bg-gray-800 border border-gray-700 rounded focus:outline-none focus:border-blue-500"
                                    v-model="form.product_transfer_request_no ">
                                <div v-if="form.errors.product_transfer_request_no " class="mt-1 text-sm text-red-500">
                                    {{ form.errors.product_transfer_request_no  }}
                                </div>
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-white">
                                    Order Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date"
                                    class="w-full px-4 py-2 text-white bg-gray-800 border rounded focus:outline-none focus:border-blue-500"
                                    :class="form.errors.request_date ? 'border-red-500' : 'border-gray-700'"
                                    v-model="form.request_date">
                                <div v-if="form.errors.request_date" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.request_date }}
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block mb-2 text-sm font-medium text-white">
                                    User <span class="text-red-500">*</span>
                                </label>
 
                                <select
                                    class="w-full px-4 py-2 text-white bg-gray-800 border rounded focus:outline-none focus:border-blue-500"
                                    :class="form.errors.user_id ? 'border-red-500' : 'border-gray-700'"
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
                <div class="overflow-hidden border-2 border-blue-500 rounded-lg">
                    <div class="px-6 py-3 bg-blue-600">
                        <h5 class="font-bold text-white">Products</h5>
                    </div>
                    <div class="p-6 bg-gray-900">
                        <div v-for="(product, index) in form.products" :key="index"
                            class="pb-6 mb-6 border-b border-gray-700 last:border-b-0">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                                <!-- Product -->
                                <div class="md:col-span-4">
                                    <label class="block mb-2 text-sm font-medium text-white">
                                        Product <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        class="w-full px-4 py-2 text-white bg-gray-800 border rounded focus:outline-none focus:border-blue-500"
                                        :class="form.errors[`products.${index}.product_id`] ? 'border-red-500' : 'border-gray-700'"
                                        v-model="product.product_id" @change="onProductSelect(index)">
                                        <option value="">Select Product</option>
                                        <option v-for="prod in products" :key="prod.id" :value="prod.id">
                                            {{ prod.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors[`products.${index}.product_id`]"
                                        class="mt-1 text-sm text-red-500">
                                        {{ form.errors[`products.${index}.product_id`] }}
                                    </div>
                                </div>

                                <!-- Unit -->
                                <div class="md:col-span-3">
                                    <label class="block mb-2 text-sm font-medium text-white">
                                        Unit <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        class="w-full px-4 py-2 text-white bg-gray-800 border rounded focus:outline-none focus:border-blue-500"
                                        :class="form.errors[`products.${index}.unit_id`] ? 'border-red-500' : 'border-gray-700'"
                                        v-model="product.unit_id" 
                                    >
                                        <option value="">Select Unit</option>
                                        <option v-for="unit in getUnitsForProduct(index)" :key="unit.id" :value="unit.id">
                                            {{ unit.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors[`products.${index}.unit_id`]"
                                        class="mt-1 text-sm text-red-500">
                                        {{ form.errors[`products.${index}.unit_id`] }}
                                    </div>
                                </div>

                                <!-- Quantity -->
                                <div class="md:col-span-3">
                                    <label class="block mb-2 text-sm font-medium text-white">
                                        Quantity <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number"
                                        class="w-full px-4 py-2 text-white bg-gray-800 border rounded focus:outline-none focus:border-blue-500"
                                        :class="form.errors[`products.${index}.requested_quantity`] ? 'border-red-500' : 'border-gray-700'"
                                        v-model.number="product.requested_quantity" min="1">
                                    <div v-if="form.errors[`products.${index}.requested_quantity`]"
                                        class="mt-1 text-sm text-red-500">
                                        {{ form.errors[`products.${index}.requested_quantity`] }}
                                    </div>
                                </div>

 

                                <!-- Remove -->
                                <div class="flex items-end">
                                    <button v-if="form.products.length > 1" type="button" @click="removeProduct(index)"
                                        class="w-full px-4 py-2 text-white bg-red-500 rounded hover:bg-red-600">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="button" @click="addProduct"
                            class="px-6 py-2 text-white bg-gray-700 rounded hover:bg-gray-600">
                            <i class="fas fa-plus me-2"></i>Add Product
                        </button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-4 mt-6">
                    <button type="button" @click="closeModal"
                        class="px-6 py-2 text-white bg-gray-600 rounded hover:bg-gray-700">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2 text-white bg-blue-600 rounded hover:bg-blue-700 disabled:opacity-50"
                        :disabled="form.processing">
                        <span v-if="form.processing">
                            <i class="fas fa-spinner fa-spin me-2"></i>Updating...
                        </span>
                        <span v-else>
                            <i class="fas fa-save me-2"></i>Update Ptr
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { watch, ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    productTransferRequest: {
        type: Object,
        required: true,
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
});

const emit = defineEmits(['update:open']);

const productUnits = ref({});

const form = useForm({
    product_transfer_request_no : '',
    request_date: '',
    user_id: '',
    products: [],
});

watch(
    () => props.open,
    (newVal) => {
        if (newVal && props.productTransferRequest) {
            form.product_transfer_request_no  = props.productTransferRequest.product_transfer_request_no ;
            form.request_date = props.productTransferRequest.request_date;
            form.user_id = props.productTransferRequest.user_id;

            if (props.productTransferRequest.product_transfer_request_products && props.productTransferRequest.product_transfer_request_products.length > 0) {
                form.products = props.productTransferRequest.product_transfer_request_products.map(p => ({
                    product_id: p.product_id,
                    requested_quantity: p.requested_quantity,
                    unit_id: p.unit_id,
                }));
            } else {
                form.products = [{ product_id: '', requested_quantity: 1, unit_id: '' }];
            }

            form.products.forEach((p, i) => {
                initUnitsForProduct(i);
            });
        }
    }
);

const initUnitsForProduct = (index) => {
    const selectedProductId = form.products[index].product_id;
    if (!selectedProductId) {
        productUnits.value[index] = [];
        return;
    }

    const product = props.products.find(p => p.id == selectedProductId);

    if (!product) return;

    if (product.measurement_units && product.measurement_units.length > 0) {
        productUnits.value[index] = product.measurement_units;
    } else if (product.measurement_unit && product.measurement_unit.id) {
        productUnits.value[index] = [product.measurement_unit];
    } else if (product.measurement_unit_id) {
        const defaultUnit = props.measurementUnits.find(u => u.id == product.measurement_unit_id);
        productUnits.value[index] = defaultUnit ? [defaultUnit] : props.measurementUnits;
    } else {
        productUnits.value[index] = props.measurementUnits || [];
    }
};

const addProduct = () => {
    form.products.push({
        product_id: '',
        requested_quantity: 1,
        unit_id: '',
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
    form.patch(route('product-transfer-requests.update', props.productTransferRequest.id), {
        onSuccess: () => {
            closeModal();
            router.reload();
        },
    });
};

const onProductSelect = (index) => {
    const selectedProductId = form.products[index].product_id;

    if (!selectedProductId) {
        form.products[index].unit_id = '';
        productUnits.value[index] = [];
        return;
    }

    const product = props.products.find(p => p.id === parseInt(selectedProductId));

    if (product) {
        if (product.measurement_units && Array.isArray(product.measurement_units) && product.measurement_units.length > 0) {
            productUnits.value[index] = product.measurement_units;
            form.products[index].unit_id = product.measurement_unit_id || product.measurement_units[0].id;
        } 
        else if (product.measurement_unit && product.measurement_unit.id) {
            productUnits.value[index] = [product.measurement_unit];
            form.products[index].unit_id = product.measurement_unit.id;
        }
        else if (product.measurement_unit_id) {
            const defaultUnit = props.measurementUnits.find(u => u.id === product.measurement_unit_id);
            productUnits.value[index] = defaultUnit ? [defaultUnit] : props.measurementUnits;
            form.products[index].unit_id = product.measurement_unit_id;
        } 
        else {
            productUnits.value[index] = props.measurementUnits || [];
            form.products[index].unit_id = '';
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
