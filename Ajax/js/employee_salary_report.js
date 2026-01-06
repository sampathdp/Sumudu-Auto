/**
 * Employee Salary Report JavaScript
 * Handles filtering, data loading, and PDF export
 */

// Global variables
let currentPage = 1;
let currentFilters = {};

// Initialize on document ready
document.addEventListener("DOMContentLoaded", function () {
  initializeDatePickers();
  loadEmployees();
  setupEventListeners();

  // Set default date range to this month
  setDateRange("this_month");
});

// Initialize Flatpickr date pickers
function initializeDatePickers() {
  flatpickr("#startDate", {
    dateFormat: "Y-m-d",
    maxDate: "today",
    onChange: function () {
      document
        .querySelectorAll(".quick-date-btn")
        .forEach((b) => b.classList.remove("active"));
      document.querySelector('[data-range="custom"]').classList.add("active");
    },
  });

  flatpickr("#endDate", {
    dateFormat: "Y-m-d",
    maxDate: "today",
    onChange: function () {
      document
        .querySelectorAll(".quick-date-btn")
        .forEach((b) => b.classList.remove("active"));
      document.querySelector('[data-range="custom"]').classList.add("active");
    },
  });
}

// Setup event listeners
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

  // Apply filters
  document
    .getElementById("applyFilters")
    .addEventListener("click", function () {
      currentPage = 1;
      loadData();
    });

  // Reset filters
  document
    .getElementById("resetFilters")
    .addEventListener("click", resetFilters);

  // Export PDF
  document.getElementById("exportPDF").addEventListener("click", exportToPDF);

  // Print
  document.getElementById("printReport").addEventListener("click", function () {
    openPrintPage();
  });
}

// Set date range based on preset
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
      startDate = formatDate(today);
      endDate = formatDate(today);
  }

  document.getElementById("startDate").value = startDate;
  document.getElementById("endDate").value = endDate;

  // Update Flatpickrs if they exist
  if (document.getElementById("startDate")._flatpickr)
    document.getElementById("startDate")._flatpickr.setDate(startDate);
  if (document.getElementById("endDate")._flatpickr)
    document.getElementById("endDate")._flatpickr.setDate(endDate);

  currentPage = 1;
  loadData();
}

// Format date to YYYY-MM-DD
function formatDate(date) {
  return date.toISOString().split("T")[0];
}

// Load employees for filter dropdown
function loadEmployees() {
  fetch(BASE_URL + "Ajax/php/employee_salary_report.php?action=get_employees")
    .then((res) => res.json())
    .then((data) => {
      if (data.status === "success") {
        const select = document.getElementById("employeeFilter");
        data.data.forEach((emp) => {
          const option = document.createElement("option");
          option.value = emp.id;
          option.textContent = `${emp.name} (${emp.employee_code})`;
          select.appendChild(option);
        });
      }
    });
}

// Get current filter values
function getFilters() {
  return {
    start_date: document.getElementById("startDate").value,
    end_date: document.getElementById("endDate").value,
    employee_id: document.getElementById("employeeFilter").value,
    salary_type: document.getElementById("salaryTypeFilter").value,
    status: document.getElementById("statusFilter").value,
    page: currentPage,
  };
}

// Load data with current filters
function loadData() {
  showLoading();
  currentFilters = getFilters();

  const params = new URLSearchParams({
    action: "get_report",
    ...currentFilters,
  });

  fetch(BASE_URL + "Ajax/php/employee_salary_report.php?" + params.toString())
    .then((res) => res.json())
    .then((data) => {
      hideLoading();
      if (data.status === "success") {
        updateSummaryCards(data.summary);
        renderTable(data.data);
        renderPagination(data.pagination);
      } else {
        showError(data.message);
      }
    })
    .catch((err) => {
      hideLoading();
      showError("Failed to load data");
    });
}

// Update summary cards with data
function updateSummaryCards(summary) {
  document.getElementById("totalEmployees").textContent =
    summary.total_employees;
  document.getElementById("totalEarnings").textContent = formatCurrency(
    summary.total_earnings
  );
  document.getElementById("totalPaid").textContent = formatCurrency(
    summary.total_paid
  );
  document.getElementById("totalPending").textContent = formatCurrency(
    summary.total_pending
  );
}

// Format currency
function formatCurrency(amount) {
  return (
    "LKR " +
    parseFloat(amount || 0).toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  );
}

// Render salary table
function renderTable(records) {
  const tbody = document.getElementById("salaryTable");

  if (!records || records.length === 0) {
    tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center empty-state">
                    <i class="fas fa-file-invoice-dollar fa-3x text-muted mb-3"></i><br>
                    <span class="text-muted">No payment records found</span>
                </td>
            </tr>
        `;
    return;
  }

  let html = "";
  records.forEach((record) => {
    const salaryTypeClass = `salary-type-${record.salary_type}`;
    const salaryTypeLabel =
      record.salary_type.charAt(0).toUpperCase() + record.salary_type.slice(1);
    const statusClass =
      record.status === "paid" ? "status-paid" : "status-unpaid";
    const statusIcon =
      record.status === "paid" ? "fa-check-circle" : "fa-clock";

    html += `
            <tr>
                <td>${formatDisplayDate(record.payment_date)}</td>
                <td>
                    <strong>${escapeHtml(record.employee_name)}</strong><br>
                    <small class="text-muted">${escapeHtml(
                      record.employee_code
                    )}</small>
                </td>
                <td><span class="status-badge ${salaryTypeClass}">${salaryTypeLabel}</span></td>
                <td class="text-end">${formatCurrency(record.base_amount)}</td>
                <td class="text-end">${formatCurrency(
                  record.commission_amount
                )}</td>
                <td class="text-end">${
                  record.pending_amount > 0
                    ? formatCurrency(record.pending_amount)
                    : "-"
                }</td>
                <td class="text-end"><strong>${formatCurrency(
                  record.total_amount
                )}</strong></td>
                <td class="text-center">${record.jobs_count}</td>
                <td>
                    <span class="status-badge ${statusClass}">
                        <i class="fas ${statusIcon}"></i>
                        ${
                          record.status.charAt(0).toUpperCase() +
                          record.status.slice(1)
                        }
                    </span>
                </td>
            </tr>
        `;
  });

  tbody.innerHTML = html;
}

// Format date for display
function formatDisplayDate(dateString) {
  if (!dateString) return "-";
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
  if (!text) return "";
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

// Render pagination
function renderPagination(pagination) {
  const { total, per_page, current_page, total_pages } = pagination;

  // Info
  const start = (current_page - 1) * per_page + 1;
  const end = Math.min(current_page * per_page, total);
  document.getElementById("paginationInfo").textContent =
    total > 0 ? `Showing ${start} to ${end} of ${total} records` : "No records";

  // Pagination buttons
  if (total_pages <= 1) {
    document.getElementById("pagination").innerHTML = "";
    return;
  }

  let html = '<nav><ul class="pagination pagination-sm mb-0">';

  // Previous
  html += `<li class="page-item ${current_page === 1 ? "disabled" : ""}">
        <a class="page-link" href="#" onclick="goToPage(${
          current_page - 1
        }); return false;">
            <i class="fas fa-chevron-left"></i>
        </a>
    </li>`;

  // Page numbers
  for (let i = 1; i <= total_pages; i++) {
    if (
      i === 1 ||
      i === total_pages ||
      (i >= current_page - 2 && i <= current_page + 2)
    ) {
      html += `<li class="page-item ${i === current_page ? "active" : ""}">
                <a class="page-link" href="#" onclick="goToPage(${i}); return false;">${i}</a>
            </li>`;
    } else if (i === current_page - 3 || i === current_page + 3) {
      html +=
        '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
  }

  // Next
  html += `<li class="page-item ${
    current_page === total_pages ? "disabled" : ""
  }">
        <a class="page-link" href="#" onclick="goToPage(${
          current_page + 1
        }); return false;">
            <i class="fas fa-chevron-right"></i>
        </a>
    </li>`;

  html += "</ul></nav>";
  document.getElementById("pagination").innerHTML = html;
}

// Go to specific page
function goToPage(page) {
  if (page < 1) return;
  currentPage = page;
  loadData();
}

// Reset all filters
function resetFilters() {
  document.getElementById("employeeFilter").value = "";
  document.getElementById("salaryTypeFilter").value = "";
  document.getElementById("statusFilter").value = "";
  document.getElementById("customDateRange").style.display = "none";

  document
    .querySelectorAll(".quick-date-btn")
    .forEach((b) => b.classList.remove("active"));
  document.querySelector('[data-range="this_month"]').classList.add("active");

  currentPage = 1;
  setDateRange("this_month");
}

// Open print-friendly page
function openPrintPage() {
  const params = new URLSearchParams(getFilters());
  window.open(
    BASE_URL + "views/Reports/EmployeeSalary/print.php?" + params.toString(),
    "_blank"
  );
}

// Export to PDF (alias to print)
function exportToPDF() {
  openPrintPage();
}

// Show loading overlay
function showLoading() {
  document.getElementById("loadingOverlay").classList.add("active");
}

// Hide loading overlay
function hideLoading() {
  document.getElementById("loadingOverlay").classList.remove("active");
}

// Show error message
function showError(message) {
  if (typeof Swal !== "undefined") {
    Swal.fire({
      icon: "error",
      title: "Error",
      text: message,
    });
  } else {
    alert("Error: " + message);
  }
}
