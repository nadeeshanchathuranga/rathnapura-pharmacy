<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50 p-6">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <button
            @click="goToShopsTab"
            class="px-6 py-2.5 rounded-[5px] font-medium text-sm bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 hover:border-gray-300 transition-all duration-200"
          >
            ← Back
          </button>
          <h1 class="text-4xl font-bold text-gray-800">Sales History</h1>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl px-4 py-2 shadow-sm">
          <span class="text-gray-800 font-semibold">
            Total: {{ sales.total }} records
          </span>
        </div>
      </div>

      <!-- View Sale Modal -->
      <Modal :show="showModal" @close="closeModal" max-width="3xl">
        <div class="p-6 bg-white">
          <!-- Modal Header -->
          <div class="bg-white border-b-2 border-blue-600 p-6">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-2xl font-bold text-blue-700 mb-1">Sale Details</h2>
                <p class="text-sm text-gray-600">
                  Invoice:
                  <span class="font-semibold text-blue-600">{{
                    selectedSale?.invoice_no
                  }}</span>
                </p>
              </div>
              <div class="flex items-center gap-6">
                <button
                  @click="printReceipt(selectedSale)"
                  class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-[5px] transition-all duration-200"
                >
                  🖨️ Print
                </button>
                <button
                  @click="closeModal"
                  class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-200 rounded-full transition-all duration-200"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-6 h-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Modal Body -->
          <div class="overflow-y-auto" style="max-height: calc(90vh - 100px)">
            <div class="p-6 space-y-6">
              <!-- Sale Information Card -->
              <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                <h3
                  class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide"
                >
                  Sale Information
                </h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                  <div>
                    <span class="text-gray-600">Customer:</span>
                    <p class="font-semibold text-gray-800 mt-0.5">
                      {{
                        selectedSale?.customer
                          ? selectedSale.customer.name
                          : "Walk-in Customer"
                      }}
                    </p>
                  </div>
                  <div>
                    <span class="text-gray-600">Sale Date:</span>
                    <p class="font-semibold text-gray-800 mt-0.5">
                      {{ formatDate(selectedSale?.sale_date) }}
                    </p>
                  </div>
                  <div>
                    <span class="text-gray-600">Sale Type:</span>
                    <p class="font-semibold mt-0.5">
                      <span
                        :class="{
                          'text-green-600': selectedSale?.type === 1,
                          'text-blue-600': selectedSale?.type === 2,
                        }"
                      >
                        {{ getSaleType(selectedSale?.type) }}
                      </span>
                    </p>
                  </div>
                  <div>
                    <span class="text-gray-600">Items Count:</span>
                    <p class="font-semibold text-gray-800 mt-0.5">
                      {{ selectedSale?.products?.length || 0 }} items
                    </p>
                  </div>
                </div>
              </div>

              <!-- Products Table -->
              <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-5 py-3 border-b border-gray-200">
                  <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                    Products
                  </h3>
                </div>
                <div class="overflow-x-auto">
                  <table class="w-full">
                    <thead class="bg-gray-50 border-b-2 border-blue-600">
                      <tr>
                        <th
                          class="px-5 py-3 text-left font-semibold text-gray-800 text-sm"
                        >
                          Item
                        </th>
                        <th
                          class="px-5 py-3 text-center font-semibold text-gray-800 text-sm"
                        >
                          Qty
                        </th>
                        <th
                          class="px-5 py-3 text-right font-semibold text-gray-800 text-sm"
                        >
                          Price
                        </th>
                        <th
                          class="px-5 py-3 text-right font-semibold text-gray-800 text-sm"
                        >
                          Total
                        </th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                      <tr
                        v-for="item in selectedSale?.products || []"
                        :key="item.id"
                        class="hover:bg-gray-50 transition"
                      >
                        <td class="px-5 py-3 text-gray-800 font-medium">
                          {{
                            (item.product && item.product.name) ||
                            item.product_name ||
                            "Unknown Product"
                          }}
                        </td>
                        <td class="px-5 py-3 text-center text-gray-700 font-medium">
                          {{ item.quantity }}
                        </td>
                        <td class="px-5 py-3 text-right text-gray-700">
                          {{ page.props.currency || "" }} {{ formatCurrency(item.price) }}
                        </td>
                        <td class="px-5 py-3 text-right text-gray-800 font-semibold">
                          {{ page.props.currency || "" }}
                          {{ formatCurrency(item.total || item.price * item.quantity) }}
                        </td>
                      </tr>
                      <tr
                        v-if="
                          !selectedSale?.products || selectedSale.products.length === 0
                        "
                      >
                        <td colspan="4" class="px-5 py-8 text-center text-gray-500">
                          No products found
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Payment Summary Card -->
              <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
                <h3
                  class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide"
                >
                  Payment Summary
                </h3>
                <div class="space-y-3 text-sm">
                  <div class="flex justify-between items-center">
                    <span class="text-gray-700">Subtotal:</span>
                    <span class="font-semibold text-gray-800">
                      {{ page.props.currency || "" }}
                      {{ formatCurrency(selectedSale?.total_amount) }}
                    </span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-gray-700">Discount:</span>
                    <span class="font-semibold text-red-600">
                      - {{ page.props.currency || "" }}
                      {{ formatCurrency(selectedSale?.discount) }}
                    </span>
                  </div>
                  <div class="flex justify-between items-center pt-3 border-t-2 border-gray-300">
                    <span class="text-gray-800 font-semibold">Net Amount:</span>
                    <span class="font-bold text-lg text-blue-600">
                      {{ page.props.currency || "" }}
                      {{ formatCurrency(selectedSale?.net_amount) }}
                    </span>
                  </div>
                  
                  <div class="flex justify-between items-center pt-3 border-t-2 border-gray-300">
                    <span class="text-gray-700 font-semibold">Cash Payment:</span>
                    <span class="font-semibold text-blue-600">
                      {{ page.props.currency || "" }}
                      {{ formatCurrency(selectedSale?.payments?.filter(p => p.payment_type === 0).reduce((sum, p) => sum + Number(p.amount), 0)) }}
                    </span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-gray-700 font-semibold">Card Payment:</span>
                    <span class="font-semibold text-blue-600">
                      {{ page.props.currency || "" }}
                      {{ formatCurrency(selectedSale?.payments?.filter(p => p.payment_type === 1).reduce((sum, p) => sum + Number(p.amount), 0)) }}
                    </span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-gray-700">Paid:</span>
                    <span class="font-semibold text-gray-600">
                      {{ page.props.currency || "" }}
                      {{
                        formatCurrency(
                          (selectedSale?.net_amount || 0) - (selectedSale?.balance || 0)
                        )
                      }}
                    </span>
                  </div>
                  <div class="flex justify-between items-center pt-3 border-t border-gray-300">
                    <span class="text-gray-800 font-semibold">Balance:</span>
                    <span
                      class="font-bold text-lg"
                      :class="
                        selectedSale && selectedSale.balance > 0
                          ? 'text-red-600'
                          : 'text-green-600'
                      "
                    >
                      {{ page.props.currency || "" }}
                      {{ formatCurrency(selectedSale?.balance) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Modal>

      <div class="overflow-hidden bg-white rounded-2xl shadow-md border border-gray-200">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-gray-50 border-b-2 border-blue-600">
              <tr>
                <th class="px-6 py-3 font-semibold text-gray-800">#</th>
                <th class="px-6 py-3 font-semibold text-gray-800">Invoice Nosdc</th>
                <th class="px-6 py-3 font-semibold text-gray-800">Customer</th>
                <!-- <th class="px-6 py-3 font-semibold text-gray-800">Products</th> -->
                <th class="px-6 py-3 font-semibold text-gray-800">Type</th>
                <th class="px-6 py-3 text-right font-semibold text-gray-800">Total</th>
                <!-- <th class="px-6 py-3 text-right font-semibold text-gray-800">Discount</th> -->
                <!-- <th class="px-6 py-3 text-right font-semibold text-gray-800">Net Amount</th> -->
                <th class="px-6 py-3 text-right font-semibold text-gray-800">Returns</th>
                <!-- <th class="px-6 py-3 text-right font-semibold text-gray-800">Net After Return</th> -->
                <th class="px-6 py-3 text-right font-semibold text-gray-800">Balance</th>
                <th class="px-6 py-3 font-semibold text-gray-800">Sale Date</th>
                <th class="px-6 py-3 font-semibold text-gray-800">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(sale, index) in sales.data"
                :key="sale.id"
                class="border-b border-gray-200 hover:bg-gray-50"
              >
                <td class="px-6 py-4">
                  <div
                    class="w-8 h-8 rounded-[10px] bg-blue-100 text-blue-700 flex items-center justify-center font-semibold text-sm"
                  >
                    {{ (sales.current_page - 1) * sales.per_page + index + 1 }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <strong class="text-blue-600 font-semibold">{{
                    sale.invoice_no
                  }}</strong>
                </td>
                <td class="px-6 py-4 text-gray-800 font-medium">
                  {{ sale.customer ? sale.customer.name : "Walk-in" }}
                </td>
                <!-- <td class="px-6 py-4 max-w-xl">
                  <div class="text-sm text-gray-600">
                    {{ formatProducts(sale.products) }}
                  </div>
                </td> -->
                <td class="px-6 py-4">
                  <span
                    :class="{
                      'bg-green-500 text-white px-3 py-1 rounded-full font-medium text-sm':
                        sale.type === 1,
                      'bg-blue-500 text-white px-3 py-1 rounded-full font-medium text-sm':
                        sale.type === 2,
                    }"
                  >
                    {{ getSaleType(sale.type) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right text-gray-800 font-medium">
                  {{ page.props.currency || "" }} {{ formatCurrency(sale.net_amount) }}
                </td>
                <!-- <td class="px-6 py-4 text-right text-red-600 font-medium">{{ page.props.currency || '' }} {{ formatCurrency(sale.discount) }}</td> -->
                <!-- <td class="px-6 py-4 text-right">
                  <strong class="text-gray-800 font-semibold">{{ page.props.currency || '' }} {{ formatCurrency(sale.net_amount) }}</strong>
                </td> -->
                <td class="px-6 py-4 text-right">
                  <div class="text-red-600 font-semibold">
                    - {{ page.props.currency || "" }}
                    {{ formatCurrency(sale.returns_total || 0) }}
                  </div>
                  <div class="text-xs text-gray-500">
                    {{ sale.returns_count || 0 }} returns
                  </div>
                </td>
                <!-- <td class="px-6 py-4 text-right">
                  <strong class="text-gray-800 font-semibold">{{ page.props.currency || '' }} {{ formatCurrency(sale.net_after_return || sale.net_amount) }}</strong>
                </td> -->
                <td
                  class="px-6 py-4 text-right"
                  :class="
                    sale.balance > 0
                      ? 'text-red-600 font-bold'
                      : 'text-green-600 font-semibold'
                  "
                >
                  {{ page.props.currency || "" }} {{ formatCurrency(sale.balance) }}
                </td>
                <td class="px-6 py-4 text-gray-600">
                  {{ formatDate(sale.sale_date) }}
                </td>
                <td class="px-6 py-4">
                  <div class="flex gap-2">
                    <button
                      @click="viewSale(sale)"
                      class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-[5px] text-white text-sm font-medium transition"
                    >
                      View
                    </button>
                    <!-- <button
                      @click="printReceipt(sale)"
                      class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded-[5px] text-white text-sm font-medium transition"
                    >
                      Print
                    </button> -->
                  </div>
                </td>
              </tr>
              <tr v-if="!sales.data || sales.data.length === 0">
                <td colspan="13" class="px-6 py-8 text-center text-gray-500 font-medium">
                  No sales found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div
          class="flex items-center justify-between px-6 py-4 bg-blue-50 border-t border-gray-200"
          v-if="sales.links"
        >
          <div class="text-sm text-gray-700 font-medium">
            Showing {{ sales.from }} to {{ sales.to }} of {{ sales.total }} results
          </div>
          <div class="flex space-x-2">
            <button
              v-for="link in sales.links"
              :key="link.label"
              @click="link.url ? router.visit(link.url) : null"
              :disabled="!link.url"
              :class="[
                'px-4 py-2 rounded-[5px] font-medium transition',
                link.active
                  ? 'bg-blue-600 text-white'
                  : link.url
                  ? 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300'
                  : 'bg-gray-200 text-gray-400 cursor-not-allowed',
              ]"
              v-html="link.label"
            ></button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { router, usePage } from "@inertiajs/vue3";
const page = usePage();
import { ref } from "vue";
import axios from "axios";
import Modal from "@/Components/Modal.vue";
import { useDashboardNavigation } from "@/composables/useDashboardNavigation";


const { goToShopsTab } = useDashboardNavigation();

const props = defineProps({
  sales: {
    type: Object,
    required: true,
  },
  billSetting: {
    type: Object,
    required: false,
  },
});

const formatCurrency = (amount) => {
  const num = Number(amount) || 0;
  return new Intl.NumberFormat("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(num);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
};

const getSaleType = (type) => {
  return type === 1 ? "Retail" : type === 2 ? "Wholesale" : "Unknown";
};

const formatProducts = (products) => {
  if (!products || products.length === 0) return "-";
  return products
    .map((p) => {
      const name =
        p.product && p.product.name ? p.product.name : p.product_name || "Unknown";
      return `${name} x${p.quantity}`;
    })
    .join(", ");
};

const showModal = ref(false);
const selectedSale = ref(null);

const viewSale = (sale) => {
  selectedSale.value = sale;
  showModal.value = true;
  logViewActivity(sale);
};

const closeModal = () => {
  showModal.value = false;
  selectedSale.value = null;
};

const getPaymentTypeText = (type) => {
  return ["Cash", "Card", "Credit"][type] || "-";
};

const printReceipt = (sale) => {
  const s = sale && sale.value ? sale.value : sale;
  const bill =
    typeof props.billSetting !== "undefined" && props.billSetting
      ? props.billSetting
      : {};
  const allowed = ["58mm", "80mm", "112mm", "210mm"];
  const rawSize = (bill.print_size || "80mm").toString();
  const width = allowed.includes(rawSize) ? rawSize : "80mm";

  const invoice = s?.invoice_no || "";
  const saleDate = formatDate(s?.sale_date) || "";
  const customer = s?.customer && s.customer.name ? s.customer.name : "Walk-in";
  const items = (s?.products || []).map((p) => ({
    product_name:
      p.product && p.product.name ? p.product.name : p.product_name || "Unknown",
    quantity: p.quantity || 0,
    price: parseFloat(p.price) || 0,
  }));

  const subtotal =
    parseFloat(s?.total_amount) ||
    items.reduce((sum, i) => sum + i.price * i.quantity, 0);
  const discount = parseFloat(s?.discount) || 0;
  const net = parseFloat(s?.net_amount) || subtotal - discount;
  const balance = parseFloat(s?.balance) || 0;
  const paid = (net - balance).toFixed(2);

  const receiptContent = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Receipt - ${invoice}</title>
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
            bill.logo_path
              ? `<div style="margin-bottom:6px;"><img src="/storage/${bill.logo_path}" alt="logo" style="max-height:40px; max-width:100%; object-fit:contain;"/></div>`
              : ""
          }
          <h1>${bill.company_name || "SALES RECEIPT"}</h1>
          ${bill.address ? `<p>${bill.address}</p>` : ""}
          ${
            bill.mobile_1 || bill.mobile_2
              ? `<p>Tel: ${[bill.mobile_1, bill.mobile_2]
                  .filter(Boolean)
                  .join(" / ")}</p>`
              : ""
          }
          ${bill.email ? `<p>${bill.email}</p>` : ""}
          ${bill.website_url ? `<p>${bill.website_url}</p>` : ""}
        </div>

        <div class="info">
          <div class="info-row"><span><strong>Invoice:</strong></span><span>${invoice}</span></div>
          <div class="info-row"><span><strong>Date:</strong></span><span>${saleDate}</span></div>
          <div class="info-row"><span><strong>Customer:</strong></span><span>${customer}</span></div>
          <div class="info-row"><span><strong>Payment:</strong></span><span>-</span></div>
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
            ${items
              .map(
                (item) => `
              <tr>
                <td class="item-name">${item.product_name}</td>
                <td class="item-qty">${item.quantity}</td>
                <td class="item-price">${item.price.toFixed(2)}</td>
                <td class="item-total">${(item.price * item.quantity).toFixed(2)}</td>
              </tr>
            `
              )
              .join("")}
          </tbody>
        </table>

        <div class="totals">
          <div class="total-row"><span>Sub Total</span><span>${
            page.props.currency || ""
          } ${subtotal.toFixed(2)}</span></div>
          <div class="total-row"><span>Discount</span><span>${
            page.props.currency || ""
          } ${discount.toFixed(2)}</span></div>
          <div class="total-row"><span>Custom Discount</span><span>0.00 %</span></div>
          <div class="total-row grand"><span>Total</span><span>${
            page.props.currency || ""
          } ${net.toFixed(2)}</span></div>
          <div class="total-row"><span>Cash</span><span>${
            page.props.currency || ""
          } ${paid}</span></div>
          <div class="total-row" style="font-weight:bold"><span>Balance</span><span>${
            page.props.currency || ""
          } ${Math.abs(balance).toFixed(2)}</span></div>
        </div>

        <div class="footer"><p><strong>${
          bill.footer_description || "Thank you for your business!"
        }</strong></p><p>${
    bill.footer_description ? "" : "Please visit us again!"
  }</p><p style="margin-top:6px; font-size:9px;">Powered by POS System</p></div>
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

  const w = window.open("", "_blank", "width=320,height=640");
  if (!w) {
    alert("Please allow pop-ups to print receipt");
    return;
  }
  w.document.write(receiptContent);
  w.document.close();
};

const logViewActivity = async (sale) => {
  try {
    await axios.post("/products/log-activity", {
      action: "view",
      module: "sales history",
      details: {
        sale_id: sale.id,
        invoice_no: sale.invoice_no,
        customer: sale.customer ? sale.customer.name : "Walk-in",
        sale_date: sale.sale_date,
        total_amount: sale.total_amount,
        net_amount: sale.net_amount,
        balance: sale.balance,
      },
    });
  } catch (e) {
    // Optionally handle/log error
    console.error("Activity log failed", e);
  }
};
</script>
