/**
 * Service History Report JavaScript
 * Handles filtering, data loading, and PDF export
 */

// Global variables
let currentPage = 1;
let currentFilters = {};
let allCustomers = [];
let allVehicles = [];

// Initialize on document ready
document.addEventListener("DOMContentLoaded", function () {
  initializeDatePickers();
  loadCustomers();
  loadVehicles();
  setupEventListeners();

  // Set default date range to this month
  setDateRange("this_month");
});

/**
 * Initialize Flatpickr date pickers
 */
function initializeDatePickers() {
  flatpickr("#startDate", {
    dateFormat: "Y-m-d",
    maxDate: "today",
    onChange: function () {
      document.querySelector('[data-range="custom"]').classList.add("active");
      document
        .querySelectorAll('.quick-date-btn:not([data-range="custom"])')
        .forEach((btn) => {
          btn.classList.remove("active");
        });
    },
  });

  flatpickr("#endDate", {
    dateFormat: "Y-m-d",
    maxDate: "today",
    onChange: function () {
      document.querySelector('[data-range="custom"]').classList.add("active");
      document
        .querySelectorAll('.quick-date-btn:not([data-range="custom"])')
        .forEach((btn) => {
          btn.classList.remove("active");
        });
    },
  });
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
  // Quick date buttons
  document.querySelectorAll(".quick-date-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      const range = this.dataset.range;
      document
        .querySelectorAll(".quick-date-btn")
        .forEach((b) => b.classList.remove("active"));
      this.classList.add("active");

      if (range === "custom") {
        document.getElementById("customDateRange").style.display = "flex";
      } else {
        document.getElementById("customDateRange").style.display = "none";
        setDateRange(range);
      }
    });
  });

  // Customer filter change
  document
    .getElementById("customerFilter")
    .addEventListener("change", function () {
      const customerId = this.value;
      loadVehicles(customerId);
    });

  // Apply filters button
  document
    .getElementById("applyFilters")
    .addEventListener("click", function () {
      currentPage = 1;
      loadData();
    });

  // Reset filters button
  document
    .getElementById("resetFilters")
    .addEventListener("click", function () {
      resetFilters();
    });

  // Export PDF button
  document.getElementById("exportPDF").addEventListener("click", function () {
    exportToPDF();
  });

  // Print button
  document.getElementById("printReport").addEventListener("click", function () {
    openPrintPage();
  });
}

/**
 * Set date range based on preset
 */
function setDateRange(range) {
  const today = new Date();
  let startDate, endDate;

  switch (range) {
    case "today":
      startDate = endDate = formatDate(today);
      break;
    case "yesterday":
      const yesterday = new Date(today);
      yesterday.setDate(yesterday.getDate() - 1);
      startDate = endDate = formatDate(yesterday);
      break;
    case "this_week":
      const weekStart = new Date(today);
      weekStart.setDate(today.getDate() - today.getDay());
      startDate = formatDate(weekStart);
      endDate = formatDate(today);
      break;
    case "this_month":
      startDate = formatDate(
        new Date(today.getFullYear(), today.getMonth(), 1)
      );
      endDate = formatDate(today);
      break;
    case "last_month":
      const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
      const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
      startDate = formatDate(lastMonth);
      endDate = formatDate(lastMonthEnd);
      break;
    case "this_year":
      startDate = formatDate(new Date(today.getFullYear(), 0, 1));
      endDate = formatDate(today);
      break;
    default:
      startDate = endDate = formatDate(today);
  }

  document.getElementById("startDate").value = startDate;
  document.getElementById("endDate").value = endDate;

  // Auto-load data
  currentPage = 1;
  loadData();
}

/**
 * Format date to YYYY-MM-DD
 */
function formatDate(date) {
  return date.toISOString().split("T")[0];
}

/**
 * Load customers for filter dropdown
 */
function loadCustomers() {
  fetch(BASE_URL + "Ajax/php/service_history.php?action=get_customers")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        allCustomers = data.data;
        const select = document.getElementById("customerFilter");
        select.innerHTML = '<option value="">All Customers</option>';

        data.data.forEach((customer) => {
          const option = document.createElement("option");
          option.value = customer.id;
          option.textContent = `${customer.name} (${customer.phone}) - ${customer.service_count} services`;
          select.appendChild(option);
        });
      }
    })
    .catch((error) => {
      console.error("Error loading customers:", error);
    });
}

/**
 * Load vehicles for filter dropdown
 */
function loadVehicles(customerId = null) {
  let url = BASE_URL + "Ajax/php/service_history.php?action=get_vehicles";
  if (customerId) {
    url += "&customer_id=" + customerId;
  }

  fetch(url)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        allVehicles = data.data;
        const select = document.getElementById("vehicleFilter");
        select.innerHTML = '<option value="">All Vehicles</option>';

        data.data.forEach((vehicle) => {
          const option = document.createElement("option");
          option.value = vehicle.id;
          let text = `${vehicle.registration_number} - ${vehicle.make} ${vehicle.model}`;
          if (vehicle.customer_name) {
            text += ` (${vehicle.customer_name})`;
          }
          text += ` - ${vehicle.service_count} services`;
          option.textContent = text;
          select.appendChild(option);
        });
      }
    })
    .catch((error) => {
      console.error("Error loading vehicles:", error);
    });
}

/**
 * Get current filter values
 */
function getFilters() {
  return {
    start_date: document.getElementById("startDate").value,
    end_date: document.getElementById("endDate").value,
    customer_id: document.getElementById("customerFilter").value,
    vehicle_id: document.getElementById("vehicleFilter").value,
    status: document.getElementById("statusFilter").value,
    payment_status: document.getElementById("paymentStatusFilter").value,
  };
}

/**
 * Load data with current filters
 */
function loadData() {
  showLoading();
  currentFilters = getFilters();

  const params = new URLSearchParams({
    action: "get_data",
    page: currentPage,
    per_page: 20,
    ...currentFilters,
  });

  fetch(BASE_URL + "Ajax/php/service_history.php?" + params.toString())
    .then((response) => response.json())
    .then((data) => {
      hideLoading();
      if (data.success) {
        updateSummaryCards(data.summary);
        renderTable(data.data);
        renderPagination(data.pagination);
      } else {
        showError(data.message || "Failed to load data");
      }
    })
    .catch((error) => {
      hideLoading();
      console.error("Error loading data:", error);
      showError("Failed to load data. Please try again.");
    });
}

/**
 * Update summary cards with data
 */
function updateSummaryCards(summary) {
  document.getElementById("totalServices").textContent =
    summary.total_services || 0;
  document.getElementById("completedServices").textContent =
    summary.completed_services || 0;
  document.getElementById("totalRevenue").textContent =
    "LKR " + formatNumber(summary.total_revenue || 0);
  document.getElementById("pendingAmount").textContent =
    "LKR " + formatNumber(summary.pending_amount || 0);
}

/**
 * Format number with commas
 */
function formatNumber(num) {
  return parseFloat(num).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

/**
 * Render service history table
 */
function renderTable(services) {
  const tbody = document.getElementById("serviceHistoryTable");

  if (!services || services.length === 0) {
    tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-5">
                    <div class="empty-state">
                        <i class="fas fa-car-crash fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No service records found for the selected filters.</p>
                    </div>
                </td>
            </tr>
        `;
    return;
  }

  tbody.innerHTML = services
    .map((service) => {
      const statusClass = getStatusClass(service.status);
      const paymentClass = service.payment_date
        ? "paid"
        : service.invoice_id
        ? "pending"
        : "no-invoice";
      const paymentLabel = service.payment_date
        ? "Paid"
        : service.invoice_id
        ? "Pending"
        : "No Invoice";

      return `
            <tr>
                <td>
                    <strong class="text-primary">${escapeHtml(
                      service.job_number
                    )}</strong>
                </td>
                <td>
                    <div class="date-cell">
                        <span class="date">${formatDisplayDate(
                          service.created_at
                        )}</span>
                    </div>
                </td>
                <td>
                    <div class="customer-info">
                        <strong>${escapeHtml(service.customer_name)}</strong>
                        <small class="text-muted d-block">${escapeHtml(
                          service.customer_phone || ""
                        )}</small>
                    </div>
                </td>
                <td>
                    <div class="vehicle-info">
                        <strong>${escapeHtml(
                          service.registration_number
                        )}</strong>
                        <small class="text-muted d-block">${escapeHtml(
                          service.make
                        )} ${escapeHtml(service.model)}</small>
                    </div>
                </td>
                <td>${escapeHtml(service.package_name)}</td>
                <td>
                    <span class="status-badge ${statusClass}">
                        ${formatStatus(service.status)}
                    </span>
                </td>
                <td class="text-end">
                    <strong>LKR ${formatNumber(
                      service.invoice_amount || service.service_amount || 0
                    )}</strong>
                </td>
                <td>
                    <span class="payment-badge ${paymentClass}">
                        ${paymentLabel}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewService(${
                          service.id
                        })" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${
                          service.invoice_id
                            ? `
                            <button class="btn btn-sm btn-outline-success" onclick="viewInvoice(${service.invoice_id})" title="View Invoice">
                                <i class="fas fa-file-invoice"></i>
                            </button>
                        `
                            : ""
                        }
                    </div>
                </td>
            </tr>
        `;
    })
    .join("");
}

/**
 * Get status badge class
 */
function getStatusClass(status) {
  const classes = {
    waiting: "status-waiting",
    in_progress: "status-progress",
    quality_check: "status-check",
    completed: "status-completed",
    delivered: "status-delivered",
    cancelled: "status-cancelled",
  };
  return classes[status] || "status-default";
}

/**
 * Format status for display
 */
function formatStatus(status) {
  const labels = {
    waiting: "Waiting",
    in_progress: "In Progress",
    quality_check: "Quality Check",
    completed: "Completed",
    delivered: "Delivered",
    cancelled: "Cancelled",
  };
  return labels[status] || status;
}

/**
 * Format date for display
 */
function formatDisplayDate(dateString) {
  if (!dateString) return "-";
  const date = new Date(dateString);
  return date.toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
  if (!text) return "";
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

/**
 * Render pagination
 */
function renderPagination(pagination) {
  const container = document.getElementById("pagination");
  const info = document.getElementById("paginationInfo");

  if (!pagination || pagination.total === 0) {
    container.innerHTML = "";
    info.innerHTML = "";
    return;
  }

  const start = (pagination.current_page - 1) * pagination.per_page + 1;
  const end = Math.min(
    pagination.current_page * pagination.per_page,
    pagination.total
  );
  info.innerHTML = `Showing ${start} to ${end} of ${pagination.total} records`;

  let html = '<nav><ul class="pagination mb-0">';

  // Previous button
  html += `
        <li class="page-item ${
          pagination.current_page === 1 ? "disabled" : ""
        }">
            <a class="page-link" href="#" onclick="goToPage(${
              pagination.current_page - 1
            }); return false;">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `;

  // Page numbers
  const maxPages = 5;
  let startPage = Math.max(
    1,
    pagination.current_page - Math.floor(maxPages / 2)
  );
  let endPage = Math.min(pagination.total_pages, startPage + maxPages - 1);

  if (endPage - startPage < maxPages - 1) {
    startPage = Math.max(1, endPage - maxPages + 1);
  }

  if (startPage > 1) {
    html += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(1); return false;">1</a></li>`;
    if (startPage > 2) {
      html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }
  }

  for (let i = startPage; i <= endPage; i++) {
    html += `
            <li class="page-item ${
              i === pagination.current_page ? "active" : ""
            }">
                <a class="page-link" href="#" onclick="goToPage(${i}); return false;">${i}</a>
            </li>
        `;
  }

  if (endPage < pagination.total_pages) {
    if (endPage < pagination.total_pages - 1) {
      html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }
    html += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${pagination.total_pages}); return false;">${pagination.total_pages}</a></li>`;
  }

  // Next button
  html += `
        <li class="page-item ${
          pagination.current_page === pagination.total_pages ? "disabled" : ""
        }">
            <a class="page-link" href="#" onclick="goToPage(${
              pagination.current_page + 1
            }); return false;">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `;

  html += "</ul></nav>";
  container.innerHTML = html;
}

/**
 * Go to specific page
 */
function goToPage(page) {
  currentPage = page;
  loadData();
}

/**
 * Reset all filters
 */
function resetFilters() {
  document.getElementById("customerFilter").value = "";
  document.getElementById("vehicleFilter").value = "";
  document.getElementById("statusFilter").value = "";
  document.getElementById("paymentStatusFilter").value = "";

  // Reset to this month
  document
    .querySelectorAll(".quick-date-btn")
    .forEach((b) => b.classList.remove("active"));
  document.querySelector('[data-range="this_month"]').classList.add("active");
  document.getElementById("customDateRange").style.display = "none";

  loadVehicles(); // Reset vehicle dropdown
  setDateRange("this_month");
}

/**
 * Open print-friendly page
 */
function openPrintPage() {
  const params = new URLSearchParams(getFilters());
  window.open(
    BASE_URL + "views/Reports/ServiceHistory/print.php?" + params.toString(),
    "_blank"
  );
}

/**
 * Export to PDF (now just aliases to print page, which browser can save as PDF)
 */
function exportToPDF() {
  openPrintPage();
}

/**
 * View service details in new page (view-only mode)
 */
function viewService(serviceId) {
  window.open(
    BASE_URL + "views/Service/job_details.php?id=" + serviceId + "&view_only=1",
    "_blank"
  );
}

/**
 * View invoice in new page
 */
function viewInvoice(invoiceId) {
  window.open(BASE_URL + "views/Invoice/?id=" + invoiceId, "_blank");
}

/**
 * Show loading overlay
 */
function showLoading() {
  document.getElementById("loadingOverlay").classList.add("active");
}

/**
 * Hide loading overlay
 */
function hideLoading() {
  document.getElementById("loadingOverlay").classList.remove("active");
}

/**
 * Show error message
 */
function showError(message) {
  // Use SweetAlert if available, otherwise alert
  if (typeof Swal !== "undefined") {
    Swal.fire({
      icon: "error",
      title: "Error",
      text: message,
    });
  } else {
    alert(message);
  }
}
