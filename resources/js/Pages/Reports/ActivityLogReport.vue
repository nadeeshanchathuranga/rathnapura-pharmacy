<template>
  <Head title="Activity Log Report" />

  <AppLayout>
    <div
      class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 p-6"
      :key="startDate + '-' + endDate + '-' + selectedUser + '-' + selectedModule"
    >
      <div>
        <!-- Header with Date Filter -->
        <div class="flex items-center justify-between mb-8">
          <div class="flex items-center gap-4">
            <button
              @click="goToReportsTab"
              class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
            >
              ← Back
            </button>
            <h1 class="text-4xl font-bold text-gray-800">Activity Log Report</h1>
          </div>
          <!-- Compact Date & User Filter -->
          <div
            class="flex items-center gap-2 bg-white rounded-lg p-3 shadow-sm border border-gray-200"
          >
            <input
              type="date"
              v-model="startDate"
              class="px-3 py-2 bg-white text-gray-800 text-sm border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
            />
            <span class="text-gray-400">to</span>
            <input
              type="date"
              v-model="endDate"
              class="px-3 py-2 bg-white text-gray-800 text-sm border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
            />
            <select
              v-model.number="selectedUser"
              class="px-3 py-2 bg-white text-gray-800 text-sm border border-gray-300 rounded-[5px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">All Users</option>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }} (ID: {{ user.id }})
              </option>
            </select>
            <button
              @click="filterLogs"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-[5px] transition-all duration-200"
            >
              Apply
            </button>
            <button
              @click="resetFilter"
              class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-[5px] transition-all duration-200"
            >
              Reset
            </button>
          </div>
        </div>

        <!-- Activity Log Table -->
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-semibold text-gray-800">Activity Log Details</h3>
            <div class="flex gap-2">
              <button
                @click="exportPdf"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-[5px] hover:scale-105 transition-all duration-300 flex items-center gap-2"
              >
                📄 Export PDF
              </button>
              <button
                @click="exportExcel"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-[5px] hover:scale-105 transition-all duration-300 flex items-center gap-2"
              >
                  Export Excel
              </button>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b-2 border-blue-600">
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">User</th>
                  <th class="px-4 py-3 text-center text-blue-600 font-semibold text-sm">
                    Module
                  </th>
                  <th class="px-4 py-3 text-center text-blue-600 font-semibold text-sm">
                    Action
                  </th>
                  <th class="px-4 py-3 text-center text-blue-600 font-semibold text-sm">
                    Date & Time
                  </th>
                  <th class="px-4 py-3 text-blue-600 font-semibold text-sm">Details</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="log in logs.data"
                  :key="log.id"
                  class="border-b border-gray-200 hover:bg-blue-50/50 transition-colors duration-200"
                >
                  <td class="px-4 py-4">
                    <div class="flex items-center gap-2">
                      <span class="text-xl">📝</span>
                      <span class="font-semibold text-gray-900">{{ log.user_name }}</span>
                    </div>
                  </td>
                  <td class="px-4 py-4 text-center">
                    <span
                      class="text-sm text-blue-600 bg-blue-50 px-3 py-1 rounded-[5px] font-medium"
                      >{{ log.module }}</span
                    >
                  </td>
                  <td class="px-4 py-4 text-center">
                    <span class="text-green-600 font-semibold">{{ log.action }}</span>
                  </td>
                  <td class="px-4 py-4 text-center text-sm text-gray-700">
                    {{ formatDateTime(log.created_at) }}
                  </td>
                  <td class="px-4 py-4 text-sm text-gray-600">{{ log.details }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div
            v-if="!logs.data || logs.data.length === 0"
            class="text-center text-gray-500 py-8 font-medium"
          >
            No activity logs found for selected filters
          </div>

          <!-- Pagination -->
          <div
            v-if="logs.data?.length > 0"
            class="flex items-center justify-between px-6 py-4 mt-4"
          >
            <div class="text-sm text-gray-600 font-medium">
              Showing {{ logs.from }} to {{ logs.to }} of {{ logs.total }} results
            </div>
            <div class="flex gap-2">
              <template v-for="(link, index) in logs.links" :key="index">
                <a
                  v-if="link.url"
                  :href="link.url"
                  @click.prevent="
                    router.visit(link.url, {
                      preserveState: false,
                      preserveScroll: false,
                    })
                  "
                  :class="[
                    'px-4 py-2 text-sm rounded-[5px] font-medium transition-all duration-200',
                    link.active
                      ? 'bg-blue-600 text-white'
                      : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50',
                  ]"
                  v-html="link.label"
                ></a>
                <span
                  v-else
                  :class="[
                    'px-4 py-2 text-sm rounded-[5px]',
                    'bg-gray-100 text-gray-400 cursor-not-allowed',
                  ]"
                  v-html="link.label"
                ></span>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";
import axios from "axios";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";

const { goToReportsTab } = useDashboardNavigation();

const props = defineProps({
  logs: Object,
  users: Array,
  modules: Array,
  startDate: String,
  endDate: String,
  selectedUser: [String, Number, null],
  selectedModule: String,
});

const startDate = ref(props.startDate);
const endDate = ref(props.endDate);
const selectedUser = ref(props.selectedUser || "");
const selectedModule = ref(props.selectedModule || "");

const users = props.users;
const modules = props.modules;
const logs = props.logs;

const filterLogs = () => {
  router.get(
    route("reports.activity-log"),
    {
      start_date: startDate.value,
      end_date: endDate.value,
      user_id: selectedUser.value,
      module: selectedModule.value,
    },
    {
      preserveState: false,
      preserveScroll: false,
    }
  );
};

const resetFilter = () => {
  router.get(
    route("reports.activity-log"),
    {},
    {
      preserveState: false,
      preserveScroll: false,
    }
  );
};

const formatDateTime = (dateTime) => {
  if (!dateTime) return "N/A";
  const date = new Date(dateTime);
  return date.toLocaleString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};

const logExportActivity = async (type) => {
  try {
    await axios.post("/products/log-activity", {
      action: "export",
      module: "activity log report",
      details: {
        export_type: type,
        start_date: startDate.value,
        end_date: endDate.value,
        user_id: selectedUser.value,
        module_filter: selectedModule.value,
      },
    });
  } catch (e) {
    // Optionally handle/log error
    console.error("Activity log failed", e);
  }
};

const exportPdf = () => {
  logExportActivity("pdf");
  window.open(
    route("reports.export.activity-log.pdf", {
      start_date: startDate.value,
      end_date: endDate.value,
      user_id: selectedUser.value,
      module: selectedModule.value,
    }),
    "_blank"
  );
};

const exportExcel = () => {
  logExportActivity("excel");
  window.open(
    route("reports.export.activity-log.excel", {
      start_date: startDate.value,
      end_date: endDate.value,
      user_id: selectedUser.value,
      module: selectedModule.value,
    }),
    "_blank"
  );
};
</script>
