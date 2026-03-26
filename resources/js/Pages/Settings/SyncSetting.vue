<template>
  <Head title="Sync Setting" />

  <AppLayout>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 p-6">
      <div>
        <div class="mb-6 flex items-center gap-4">
          <!-- Back to Dashboard Button -->
          <button
            @click="goToSettingsTab"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-3xl font-bold text-black">
            Sync Settings
          </h1>
          <div></div>
        </div>

        <div
          class="bg-white rounded-2xl border border-gray-200 shadow-lg overflow-hidden"
        >
          <!-- Header -->
          <div class="bg-white border-b-2 border-blue-600 p-6">
            <h2 class="text-xl font-semibold text-blue-600">Database Synchronization</h2>
            <p class="text-sm text-gray-600 mt-1">
              Configure and manage database sync settings
            </p>
          </div>

          <!-- Enable Sync Toggle -->
          <div class="p-6 bg-gray-50 border-b border-gray-200">
            <label class="inline-flex items-center cursor-pointer">
              <input
                type="checkbox"
                v-model="enableSync"
                class="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
              />
              <span class="ml-3 text-lg font-semibold text-gray-700"
                >Enable Sync Mode</span
              >
            </label>
          </div>

          <!-- Sync Settings Form -->
          <div v-if="enableSync" class="p-6 bg-white">
            <form @submit.prevent class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Host -->
                <div>
                  <label class="block mb-2 text-sm font-semibold text-gray-700"
                    >Host <span class="text-red-500">*</span></label
                  >
                  <input
                    type="text"
                    v-model="host"
                    class="w-full px-4 py-2.5 rounded-[5px] bg-white border border-gray-300 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    placeholder="Enter host..."
                  />
                </div>

                <!-- Port -->
                <div>
                  <label class="block mb-2 text-sm font-semibold text-gray-700"
                    >Port <span class="text-red-500">*</span></label
                  >
                  <input
                    type="text"
                    v-model="port"
                    class="w-full px-4 py-2.5 rounded-[5px] bg-white border border-gray-300 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    placeholder="Enter port..."
                  />
                </div>

                <!-- DB -->
                <div>
                  <label class="block mb-2 text-sm font-semibold text-gray-700"
                    >Database <span class="text-red-500">*</span></label
                  >
                  <input
                    type="text"
                    v-model="db"
                    class="w-full px-4 py-2.5 rounded-[5px] bg-white border border-gray-300 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    placeholder="Enter database name..."
                  />
                </div>

                <!-- Username -->
                <div>
                  <label class="block mb-2 text-sm font-semibold text-gray-700"
                    >Username <span class="text-red-500">*</span></label
                  >
                  <input
                    type="text"
                    v-model="username"
                    class="w-full px-4 py-2.5 rounded-[5px] bg-white border border-gray-300 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    placeholder="Enter username..."
                  />
                </div>

                <!-- Password -->
                <div class="md:col-span-2">
                  <label class="block mb-2 text-sm font-semibold text-gray-700"
                    >Password</label
                  >
                  <input
                    type="password"
                    v-model="password"
                    class="w-full px-4 py-2.5 rounded-[5px] bg-white border border-gray-300 text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    placeholder="Enter password..."
                  />
                </div>
              </div>

              <!-- Action Buttons -->
              <div
                class="flex flex-wrap justify-between items-center gap-4 pt-6 border-t border-gray-200"
              >
                <div class="flex flex-wrap items-center gap-3">
                  <button
                    type="button"
                    @click="saveCredentials"
                    class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-[5px] font-semibold transition-all duration-200 shadow-sm hover:shadow-md hover:scale-105"
                    :disabled="saving"
                  >
                    <span v-if="saving">Saving...</span>
                    <span v-else>💾 Save</span>
                  </button>

                  <button
                    type="button"
                    class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-[5px] font-semibold transition-all duration-200 shadow-sm hover:shadow-md hover:scale-105"
                    :disabled="testing"
                    @click="testConnection"
                  >
                    <span v-if="testing">Testing...</span>
                    <span v-else>🔌 Test Connection</span>
                  </button>

                  <button
                    type="button"
                    class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-[5px] font-semibold transition-all duration-200 shadow-sm hover:shadow-md hover:scale-105"
                    :disabled="migrating"
                    @click="runMigration"
                  >
                    <span v-if="migrating">Migrating...</span>
                    <span v-else>🔧 Migrate</span>
                  </button>
                </div>

                <div>
                  <button
                    type="button"
                    @click="syncData"
                    :disabled="!testSuccess || syncing"
                    :class="[
                      testSuccess && !syncing
                        ? 'bg-green-600 hover:bg-green-700 text-white hover:scale-105'
                        : 'bg-gray-300 text-gray-500 cursor-not-allowed opacity-60',
                      'px-8 py-2.5 rounded-[5px] font-semibold transition-all duration-200 shadow-sm',
                    ]"
                  >
                    <span v-if="syncing">🔄 Syncing...</span>
                    <span v-else>🚀 Start Sync</span>
                  </button>
                </div>
              </div>

              <!-- Success/Error Messages -->
              <div
                v-if="
                  saveSuccess ||
                  testSuccess ||
                  testError ||
                  migrateSuccess ||
                  migrateError ||
                  syncSuccess ||
                  syncError
                "
                class="space-y-2"
              >
                <div
                  v-if="saveSuccess"
                  class="px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-[5px] text-sm font-medium"
                >
                  ✓ Settings saved successfully!
                </div>
                <div
                  v-if="testSuccess"
                  class="px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-[5px] text-sm font-medium"
                >
                  ✓ Database connection successful!
                </div>
                <div
                  v-if="testError"
                  class="px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-[5px] text-sm font-medium"
                >
                  ✗ {{ testError }}
                </div>
                <div
                  v-if="migrateSuccess"
                  class="px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-[5px] text-sm font-medium"
                >
                  ✓ Migration completed successfully!
                </div>
                <div
                  v-if="migrateError"
                  class="px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-[5px] text-sm font-medium"
                >
                  ✗ {{ migrateError }}
                </div>
                <div
                  v-if="syncSuccess"
                  class="px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-[5px] text-sm font-medium"
                >
                  ✓ Sync completed successfully!
                </div>
                <div
                  v-if="syncError"
                  class="px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-[5px] text-sm font-medium"
                >
                  ✗ {{ syncError }}
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Sync Progress Section -->
        <div
          v-if="enableSync && syncItems.length > 0"
          class="mt-6 bg-white rounded-2xl border border-gray-200 shadow-lg overflow-hidden"
        >
          <div class="bg-white border-b-2 border-blue-600 p-6">
            <h2 class="text-xl font-semibold text-blue-600">Synchronization Progress</h2>
            <p class="text-sm text-gray-600 mt-1">
              Real-time sync status for all modules
            </p>
          </div>

          <div class="p-6 space-y-3">
            <div
              v-for="item in syncItems"
              :key="item.name"
              class="flex items-center gap-4 bg-gray-50 px-5 py-4 rounded-[5px] border transition-all duration-300"
              :class="{
                'border-red-200 bg-red-50': item.status === 'failed',
                'border-green-200 bg-green-50': item.status === 'completed',
                'border-blue-200 bg-blue-50': item.status === 'syncing',
                'border-gray-200': item.status === 'pending',
              }"
            >
              <!-- Icon Status -->
              <span class="inline-flex items-center justify-center w-8 h-8 flex-shrink-0">
                <span v-if="item.status === 'pending'" class="text-2xl">⏳</span>

                <svg
                  v-if="item.status === 'syncing'"
                  class="animate-spin h-6 w-6 text-blue-600"
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

                <span v-if="item.status === 'completed'" class="text-2xl">✅</span>
                <span v-if="item.status === 'failed'" class="text-2xl">❌</span>
              </span>

              <span class="text-gray-900 font-semibold flex-grow">{{ item.name }}</span>

              <span v-if="item.message" class="text-red-600 text-xs">
                {{ item.message }}
              </span>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div
          v-else-if="enableSync && syncItems.length === 0"
          class="mt-6 bg-white rounded-2xl border border-gray-200 shadow-lg p-12 text-center"
        >
          <div class="text-gray-400 mb-4">
            <svg
              class="mx-auto h-16 w-16"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
              />
            </svg>
          </div>
          <p class="text-gray-600 font-medium text-lg">No sync in progress</p>
          <p class="text-gray-500 text-sm mt-2">
            Click "Start Sync" to begin database synchronization
          </p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch, defineProps, onMounted } from "vue";
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import axios from "axios";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const { goToSettingsTab } = useDashboardNavigation();

const enableSync = ref(false);
const host = ref("");
const db = ref("");
const username = ref("");
const password = ref("");
const port = ref("");

const saving = ref(false);
const saveSuccess = ref(false);
const saveError = ref("");

const testing = ref(false);
const testSuccess = ref(false);
const testError = ref("");

const migrating = ref(false);
const migrateSuccess = ref(false);
const migrateError = ref("");

const props = defineProps({
  secondDb: {
    type: Object,
    default: () => ({}),
  },
});

// Load saved state from localStorage on mount
onMounted(() => {
  const savedEnableSync = localStorage.getItem("enableSync");
  const savedTestSuccess = localStorage.getItem("testSuccess");

  if (savedEnableSync === "true") {
    enableSync.value = true;
  }

  if (savedTestSuccess === "true") {
    testSuccess.value = true;
  }

  // Load saved sync items if they exist
  const savedSyncItems = localStorage.getItem("syncItems");
  if (savedSyncItems) {
    try {
      syncItems.value = JSON.parse(savedSyncItems);
    } catch (e) {
      console.error("Failed to parse saved sync items", e);
    }
  }

  const savedSyncSuccess = localStorage.getItem("syncSuccess");
  if (savedSyncSuccess === "true") {
    syncSuccess.value = true;
  }
});

watch(enableSync, (val) => {
  // Save to localStorage
  localStorage.setItem("enableSync", val ? "true" : "false");

  if (val) {
    host.value = props.secondDb.host || "";
    db.value = props.secondDb.database || "";
    username.value = props.secondDb.username || "";
    password.value = props.secondDb.password || "";
    port.value = props.secondDb.port || "";
  }
});

const saveCredentials = async () => {
  saving.value = true;
  saveSuccess.value = false;

  try {
    const res = await axios.post("/settings/sync/update-second-db", {
      host: host.value,
      port: port.value,
      database: db.value,
      username: username.value,
      password: password.value,
    });

    if (res.data.success) {
      saveSuccess.value = true;
    }
  } catch (e) {
    console.error(e);
  } finally {
    saving.value = false;
  }
};

const syncItems = ref([]);
const syncing = ref(false);
const syncSuccess = ref(false);
const syncError = ref("");

const syncData = async () => {
  syncing.value = true;
  syncSuccess.value = false;
  syncError.value = "";
  syncItems.value = [];

  try {
    // 1. Get Modules
    const res = await axios.get("/settings/sync/list");
    if (!res.data.success) throw new Error(res.data.message);

    // Initialize all items with 'syncing' status (they'll all sync simultaneously)
    syncItems.value = res.data.modules.map((mod) => ({
      name: mod,
      status: "syncing",
      message: "",
    }));

    // Verify items exist
    if (syncItems.value.length === 0) {
      syncSuccess.value = true;
      syncError.value = "No modules found to sync.";
      return;
    }

    // 2. Sync All Modules Simultaneously
    const syncPromises = syncItems.value.map(async (item) => {
      try {
        const syncRes = await axios.post("/settings/sync/module", { module: item.name });

        if (syncRes.data.success) {
          item.status = "completed";
        } else {
          item.status = "failed";
          item.message = syncRes.data.message;
          syncError.value = "Some modules failed to sync.";
        }
      } catch (err) {
        item.status = "failed";
        item.message = err.response?.data?.message || err.message;
        syncError.value = "Error occurred during sync.";
      }
    });

    // Wait for all syncs to complete
    await Promise.all(syncPromises);

    if (!syncError.value) {
      syncSuccess.value = true;
      // Save sync success state
      localStorage.setItem("syncSuccess", "true");

      // Log single activity for the entire sync operation
      try {
        await axios.post("/products/log-activity", {
          action: "sync",
          module: "sync setting",
          details: {
            status: "synced",
          },
        });
      } catch (e) {
        console.error("Activity log failed", e);
      }
    }

    // Save sync items to localStorage
    localStorage.setItem("syncItems", JSON.stringify(syncItems.value));
  } catch (e) {
    syncError.value = e.response?.data?.message || e.message || "Failed to start sync.";
  } finally {
    syncing.value = false;
  }
};

const testConnection = async () => {
  testing.value = true;
  testSuccess.value = false;
  testError.value = "";

  try {
    const res = await axios.post("/settings/sync/test-connection", {
      host: host.value,
      db: db.value,
      username: username.value,
      password: password.value,
      port: port.value,
    });

    testSuccess.value = res.data.success;
    if (!res.data.success) testError.value = res.data.message;

    // Save test success state to localStorage
    if (res.data.success) {
      localStorage.setItem("testSuccess", "true");
    } else {
      localStorage.removeItem("testSuccess");
    }
  } catch (e) {
    testError.value = e.response?.data?.message || e.message;
    localStorage.removeItem("testSuccess");
  } finally {
    testing.value = false;
  }
};

const runMigration = async () => {
  migrating.value = true;
  migrateSuccess.value = false;
  migrateError.value = "";

  try {
    const res = await axios.post("/settings/sync/migrate-second-db");

    if (res.data.success) {
      migrateSuccess.value = true;
    } else {
      migrateError.value = res.data.message;
    }
  } catch (e) {
    migrateError.value = e.response?.data?.message || e.message || "Migration failed";
  } finally {
    migrating.value = false;
  }
};

const modules = [
  "products",
  "brands",
  "categories",
  "types",
  "units",
  "purchase orders",
  "goods received",
  "goods received notes return",
  "expenses",
  "suppliers",
  "product transfer request",
  "product release notes",
  "stock returns",
  "customers",
  "discounts",
  "taxes",
  "sales",
  "product return",
  "sales report",
  "sales history",
  "sync report",
  "database backup",
  "bill setting",
  "import & export",
  "stock report",
  "activity log",
  "expenses report",
  "income report",
  "product release report",
  "stock return report",
  "low stock report",
  "goods received notes report",
  "goods received notes return report",
  "product movement report",
  "users",
  "company info",
  "app setting",
  "sync setting",
];
</script>
