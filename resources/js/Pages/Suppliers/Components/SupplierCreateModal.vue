<template>
  <Modal :show="open" @close="closeModal" max-width="md">
    <div class="p-6 bg-gray-50">
      <h3 class="text-2xl font-bold text-blue-600 mb-6">✨ Add New Supplier</h3>

      <form @submit.prevent="submit" class="mt-4 space-y-5">
        <!-- Supplier Name -->
        <div class="space-y-1.5">
          <label class="block text-sm font-semibold text-gray-700">
            Supplier Name <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.name"
            type="text"
            class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder:text-gray-400"
            placeholder="e.g. Global Trading Pvt Ltd"
            required
            pattern="^[A-Za-z\s]+$"
            @input="onSupplierNameInput"
            title="Only alphabetic characters and spaces are allowed."
          />
          <p v-if="form.errors.name" class="text-xs text-red-600">
            {{ form.errors.name }}
          </p>
        </div>

        <!-- Email & phone_number in 2-column on desktop -->
        <div class="grid gap-4 md:grid-cols-2">
          <!-- Email -->
          <div class="space-y-1.5">
            <label class="block text-sm font-semibold text-gray-700"> Email </label>
            <input
              v-model="form.email"
              type="email"
              class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder:text-gray-400"
              placeholder="name@example.com"
            />
            <p v-if="form.errors.email" class="text-xs text-red-600">
              {{ form.errors.email }}
            </p>
          </div>

          <!-- phone_number -->
          <div class="space-y-1.5">
            <label class="block text-sm font-semibold text-gray-700">
              Phone Number
            </label>
            <input
              v-model="form.phone_number"
              type="text"
              maxlength="10"
              @input="form.phone_number = form.phone_number.replace(/[^0-9]/g, '')"
              class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder:text-gray-400"
              placeholder="07XXXXXXXX"
            />
            <p v-if="form.errors.phone_number" class="text-xs text-red-600">
              {{ form.errors.phone_number }}
            </p>
          </div>
        </div>

        <!-- Address -->
        <div class="space-y-1.5">
          <label class="block text-sm font-semibold text-gray-700"> Address </label>
          <textarea
            v-model="form.address"
            rows="3"
            class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder:text-gray-400 resize-none"
            placeholder="Street, City, Country"
          ></textarea>
          <p v-if="form.errors.address" class="text-xs text-red-600">
            {{ form.errors.address }}
          </p>
        </div>

        <!-- Status -->
        <div class="space-y-1.5">
          <label class="block text-sm font-semibold text-gray-700">
            Status <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.status"
            class="w-full px-3 py-2 text-sm text-gray-800 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            required
          >
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
          <p v-if="form.errors.status" class="text-xs text-red-600">
            {{ form.errors.status }}
          </p>
        </div>

        <!-- Actions -->
        <div
          class="flex items-center justify-end pt-4 space-x-3 border-t border-gray-300"
        >
          <button
            type="button"
            @click="closeModal"
            class="px-6 py-2.5 rounded-[5px] font-semibold text-sm bg-gray-500 text-white hover:bg-gray-600 transition-all duration-200"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2.5 rounded-[5px] font-semibold text-sm bg-blue-600 text-white hover:bg-blue-700 transition-all duration-200 disabled:opacity-60 disabled:cursor-not-allowed"
          >
            {{ form.processing ? "Creating..." : "✨Create Supplier" }}
          </button>
        </div>
      </form>
    </div>
  </Modal>
</template>

<script setup>
// Only allow alphabetic characters and spaces in supplier name
const onSupplierNameInput = (e) => {
  e.target.value = e.target.value.replace(/[^A-Za-z\s]/g, "");
  form.name = e.target.value;
};
import { useForm } from "@inertiajs/vue3";
import { logActivity } from "@/composables/useActivityLog";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
  open: Boolean,
});

const emit = defineEmits(["update:open"]);

const form = useForm({
  name: "",
  email: "",
  phone_number: "",
  address: "",
  status: "1",
});

const submit = () => {
  form.post(route("suppliers.store"), {
    onSuccess: async () => {
      // Log create activity
      await logActivity("create", "suppliers", {
        supplier_name: form.name,
        email: form.email,
        phone_number: form.phone_number,
        address: form.address,
        status: form.status,
      });

      closeModal();
      form.reset();
    },
  });
};

const closeModal = () => {
  emit("update:open", false);
  form.reset();
  form.clearErrors();
};
</script>
