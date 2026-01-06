/**
 * Sales Report JavaScript
 * Handles filters, AJAX requests, and chart rendering
 */

// Global variables
let revenueChart, categoryChart, paymentMethodChart, paymentStatusChart;
let currentPage = 1;
let currentFilters = {};

// Initialize on page load
$(document).ready(function () {
  initializeDatePickers();
  initializeEventListeners();
  loadReportData();
});

/**
 * Initialize Flatpickr date pickers
 */
function initializeDatePickers() {
  flatpickr("#startDate", {
    dateFormat: "Y-m-d",
    onChange: function (selectedDates, dateStr) {
      $("#endDate").flatpickr().set("minDate", dateStr);
    },
  });

  flatpickr("#endDate", {
    dateFormat: "Y-m-d",
  });
}

/**
 * Initialize event listeners
 */
function initializeEventListeners() {
  // Quick date range buttons
  $(".quick-date-btn").on("click", function () {
    $(".quick-date-btn").removeClass("active");
    $(this).addClass("active");

    const range = $(this).data("range");

    if (range === "custom") {
      $("#customDateRange").slideDown();
    } else {
      $("#customDateRange").slideUp();
      const dates = getDateRange(range);
      $("#startDate").val(dates.start);
      $("#endDate").val(dates.end);
      loadReportData();
    }
  });

  // Apply filters button
  $("#applyFilters").on("click", function () {
    loadReportData();
  });

  // Reset filters button
  $("#resetFilters").on("click", function () {
    resetFilters();
  });

  // Export buttons
  $("#exportExcel").on("click", function () {
    exportReport("excel");
  });

  $("#exportPDF").on("click", function () {
    exportReport("pdf");
  });

  // Category checkboxes - reload on change
  $('input[type="checkbox"][id^="cat_"]').on("change", function () {
    loadReportData();
  });
}

/**
 * Get date range based on quick filter
 */
function getDateRange(range) {
  const today = new Date();
  let start, end;

  switch (range) {
    case "today":
      start = end = formatDate(today);
      break;
    case "yesterday":
      const yesterday = new Date(today);
      yesterday.setDate(yesterday.getDate() - 1);
      start = end = formatDate(yesterday);
      break;
    case "this_week":
      const weekStart = new Date(today);
      weekStart.setDate(today.getDate() - today.getDay());
      start = formatDate(weekStart);
      end = formatDate(today);
      break;
    case "this_month":
      start = formatDate(new Date(today.getFullYear(), today.getMonth(), 1));
      end = formatDate(today);
      break;
    case "last_month":
      const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
      start = formatDate(lastMonth);
      end = formatDate(new Date(today.getFullYear(), today.getMonth(), 0));
      break;
    default:
      start = end = formatDate(today);
  }

  return { start, end };
}

/**
 * Format date to Y-m-d
 */
function formatDate(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

/**
 * Get selected categories
 */
function getSelectedCategories() {
  const categories = [];
  $('input[type="checkbox"][id^="cat_"]:checked').each(function () {
    categories.push($(this).val());
  });
  return categories;
}

/**
 * Build filter parameters
 */
function buildFilterParams() {
  const activeRange = $(".quick-date-btn.active").data("range");
  let dates;

  if (activeRange === "custom") {
    dates = {
      start: $("#startDate").val() || formatDate(new Date()),
      end: $("#endDate").val() || formatDate(new Date()),
    };
  } else {
    dates = getDateRange(activeRange || "this_week");
  }

  const params = {
    start_date: dates.start,
    end_date: dates.end,
    categories: getSelectedCategories().join(","),
    payment_method: $("#paymentMethod").val(),
    payment_status: $("#paymentStatus").val(),
  };

  currentFilters = params;
  return params;
}

/**
 * Load report data from server
 */
function loadReportData() {
  showLoading();

  const params = buildFilterParams();
  params.action = "sales_summary";

  $.ajax({
    url: "../../Ajax/php/report.php",
    type: "GET",
    data: params,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        updateSummaryCards(response.data.summary, response.data.comparison);
        updateCharts(response.data);
        updateTopItems(response.data.top_items);
        loadInvoiceList(1);
      } else {
        showError("Failed to load report data: " + response.message);
      }
    },
    error: function (xhr, status, error) {
      showError("Error loading report: " + error);
    },
    complete: function () {
      hideLoading();
    },
  });
}

/**
 * Update summary cards
 */
function updateSummaryCards(summary, comparison) {
  $("#totalRevenue").text("LKR " + formatNumber(summary.total_revenue));
  $("#totalInvoices").text(summary.total_invoices);
  $("#avgOrderValue").text("LKR " + formatNumber(summary.average_order_value));
  $("#pendingAmount").text("LKR " + formatNumber(summary.pending_amount));

  // Update comparison
  const change = comparison.percentage_change;
  const changeClass = change >= 0 ? "text-success" : "text-danger";
  const changeIcon = change >= 0 ? "fa-arrow-up" : "fa-arrow-down";

  $("#revenueChange")
    .removeClass("text-success text-danger")
    .addClass(changeClass)
    .html(
      `<i class="fas ${changeIcon}"></i><span>${Math.abs(
        change
      )}% from previous period</span>`
    );
}

/**
 * Update all charts
 */
function updateCharts(data) {
  updateRevenueChart(data.daily_sales);
  updateCategoryChart(data.category_breakdown);
  updatePaymentMethodChart(data.payment_method_breakdown);
  updatePaymentStatusChart(data.payment_status_breakdown);
}

/**
 * Update revenue trend chart
 */
function updateRevenueChart(dailySales) {
  const ctx = document.getElementById("revenueChart").getContext("2d");

  if (revenueChart) {
    revenueChart.destroy();
  }

  const labels = dailySales.map((d) => d.sale_date);
  const values = dailySales.map((d) => parseFloat(d.daily_revenue));

  revenueChart = new Chart(ctx, {
    type: "line",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Revenue",
          data: values,
          borderColor: "#6366f1",
          backgroundColor: "rgba(99, 102, 241, 0.1)",
          tension: 0.4,
          fill: true,
          pointBackgroundColor: "#fff",
          pointBorderColor: "#6366f1",
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function (context) {
              return "LKR " + formatNumber(context.parsed.y);
            },
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function (value) {
              return "LKR " + formatNumber(value);
            },
          },
        },
      },
    },
  });
}

/**
 * Update category breakdown chart
 */
function updateCategoryChart(categories) {
  const ctx = document.getElementById("categoryChart").getContext("2d");

  if (categoryChart) {
    categoryChart.destroy();
  }

  const labels = categories.map((c) => capitalizeFirst(c.category));
  const values = categories.map((c) => parseFloat(c.total_revenue));
  const colors = ["#6366f1", "#10b981", "#f59e0b", "#ef4444", "#3b82f6"];

  categoryChart = new Chart(ctx, {
    type: "doughnut",
    data: {
      labels: labels,
      datasets: [
        {
          data: values,
          backgroundColor: colors.slice(0, labels.length),
          borderWidth: 2,
          borderColor: "#fff",
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: "bottom",
        },
        tooltip: {
          callbacks: {
            label: function (context) {
              return context.label + ": LKR " + formatNumber(context.parsed);
            },
          },
        },
      },
    },
  });
}

/**
 * Update payment method chart
 */
function updatePaymentMethodChart(methods) {
  const ctx = document.getElementById("paymentMethodChart").getContext("2d");

  if (paymentMethodChart) {
    paymentMethodChart.destroy();
  }

  const labels = methods.map((m) => capitalizeFirst(m.payment_method));
  const values = methods.map((m) => parseFloat(m.total_revenue));

  paymentMethodChart = new Chart(ctx, {
    type: "bar",
    data: {
      labels: labels,
      datasets: [
        {
          label: "Revenue",
          data: values,
          backgroundColor: "#6366f1",
          borderRadius: 8,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function (context) {
              return "LKR " + formatNumber(context.parsed.y);
            },
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function (value) {
              return "LKR " + formatNumber(value);
            },
          },
        },
      },
    },
  });
}

/**
 * Update payment status chart
 */
function updatePaymentStatusChart(statuses) {
  const ctx = document.getElementById("paymentStatusChart").getContext("2d");

  if (paymentStatusChart) {
    paymentStatusChart.destroy();
  }

  const labels = statuses.map((s) => s.payment_status);
  const values = statuses.map((s) => parseFloat(s.total_amount));
  const colors = ["#10b981", "#f59e0b"];

  paymentStatusChart = new Chart(ctx, {
    type: "pie",
    data: {
      labels: labels,
      datasets: [
        {
          data: values,
          backgroundColor: colors.slice(0, labels.length),
          borderWidth: 2,
          borderColor: "#fff",
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: "bottom",
        },
        tooltip: {
          callbacks: {
            label: function (context) {
              return context.label + ": LKR " + formatNumber(context.parsed);
            },
          },
        },
      },
    },
  });
}

/**
 * Update top items table
 */
function updateTopItems(items) {
  const tbody = $("#topItemsTable");
  tbody.empty();

  if (items.length === 0) {
    tbody.append(
      '<tr><td colspan="5" class="text-center py-4">No data available</td></tr>'
    );
    return;
  }

  items.forEach((item) => {
    const row = `
            <tr>
                <td>${escapeHtml(item.description)}</td>
                <td><span class="badge bg-primary">${capitalizeFirst(
                  item.item_type
                )}</span></td>
                <td>${item.times_sold}</td>
                <td>${formatNumber(item.total_quantity)}</td>
                <td class="text-end">LKR ${formatNumber(
                  item.total_revenue
                )}</td>
            </tr>
        `;
    tbody.append(row);
  });
}

/**
 * Load invoice list with pagination
 */
function loadInvoiceList(page = 1) {
  const params = { ...currentFilters };
  params.action = "invoice_list";
  params.page = page;
  params.per_page = 20;

  $.ajax({
    url: "../../Ajax/php/report.php",
    type: "GET",
    data: params,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        updateInvoiceTable(response.data);
        updatePagination(response.pagination);
      }
    },
    error: function (xhr, status, error) {
      console.error("Error loading invoices:", error);
    },
  });
}

/**
 * Update invoice table
 */
function updateInvoiceTable(invoices) {
  const tbody = $("#invoiceTable");
  tbody.empty();

  if (invoices.length === 0) {
    tbody.append(
      '<tr><td colspan="7" class="text-center py-4">No invoices found</td></tr>'
    );
    return;
  }

  invoices.forEach((invoice) => {
    const statusClass = invoice.payment_date ? "paid" : "pending";
    const statusText = invoice.payment_date ? "Paid" : "Pending";

    const row = `
            <tr>
                <td><strong>${escapeHtml(invoice.invoice_number)}</strong></td>
                <td>${formatDateTime(invoice.created_at)}</td>
                <td>${escapeHtml(invoice.customer_name || "N/A")}</td>
                <td>${escapeHtml(invoice.job_number || "-")}</td>
                <td>${capitalizeFirst(invoice.payment_method || "N/A")}</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td class="text-end"><strong>LKR ${formatNumber(
                  invoice.total_amount
                )}</strong></td>
            </tr>
        `;
    tbody.append(row);
  });
}

/**
 * Update pagination
 */
function updatePagination(pagination) {
  const info = `Showing ${
    (pagination.current_page - 1) * pagination.per_page + 1
  } to ${Math.min(
    pagination.current_page * pagination.per_page,
    pagination.total
  )} of ${pagination.total} invoices`;
  $("#paginationInfo").text(info);

  const paginationDiv = $("#pagination");
  paginationDiv.empty();

  if (pagination.total_pages <= 1) return;

  let html = '<nav><ul class="pagination mb-0">';

  // Previous button
  html += `<li class="page-item ${
    pagination.current_page === 1 ? "disabled" : ""
  }">
        <a class="page-link" href="#" data-page="${
          pagination.current_page - 1
        }">Previous</a>
    </li>`;

  // Page numbers
  for (let i = 1; i <= pagination.total_pages; i++) {
    if (
      i === 1 ||
      i === pagination.total_pages ||
      (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)
    ) {
      html += `<li class="page-item ${
        i === pagination.current_page ? "active" : ""
      }">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
    } else if (
      i === pagination.current_page - 3 ||
      i === pagination.current_page + 3
    ) {
      html +=
        '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
  }

  // Next button
  html += `<li class="page-item ${
    pagination.current_page === pagination.total_pages ? "disabled" : ""
  }">
        <a class="page-link" href="#" data-page="${
          pagination.current_page + 1
        }">Next</a>
    </li>`;

  html += "</ul></nav>";
  paginationDiv.html(html);

  // Pagination click events
  paginationDiv.find("a.page-link").on("click", function (e) {
    e.preventDefault();
    if (
      !$(this).parent().hasClass("disabled") &&
      !$(this).parent().hasClass("active")
    ) {
      const page = parseInt($(this).data("page"));
      loadInvoiceList(page);
    }
  });
}

/**
 * Export report
 */
function exportReport(format) {
  const params = { ...currentFilters };
  params.action = format === "excel" ? "export_excel" : "export_pdf";

  $.ajax({
    url: "../../Ajax/php/report.php",
    type: "GET",
    data: params,
    dataType: "json",
    success: function (response) {
      if (response.success) {
        window.location.href = response.file_url;
      } else {
        alert(
          response.message || "Export functionality will be available soon"
        );
      }
    },
    error: function () {
      alert("Export functionality will be available soon");
    },
  });
}

/**
 * Reset all filters
 */
function resetFilters() {
  $(".quick-date-btn").removeClass("active");
  $('.quick-date-btn[data-range="this_week"]').addClass("active");
  $("#customDateRange").slideUp();
  $('input[type="checkbox"][id^="cat_"]').prop("checked", true);
  $("#paymentMethod").val("all");
  $("#paymentStatus").val("");
  loadReportData();
}

/**
 * Utility functions
 */
function formatNumber(num) {
  return parseFloat(num)
    .toFixed(2)
    .replace(/\d(?=(\d{3})+\.)/g, "$&,");
}

function capitalizeFirst(str) {
  if (!str) return "";
  return str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, " ");
}

function escapeHtml(text) {
  if (!text) return "";
  const map = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;",
  };
  return text.replace(/[&<>"']/g, (m) => map[m]);
}

function formatDateTime(dateTime) {
  if (!dateTime) return "";
  const date = new Date(dateTime);
  return (
    date.toLocaleDateString() +
    " " +
    date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
  );
}

function showLoading() {
  $("#loadingOverlay").addClass("active");
}

function hideLoading() {
  $("#loadingOverlay").removeClass("active");
}

function showError(message) {
  alert(message);
}
