// Companies Management JavaScript
$(document).ready(function () {
  // Initialize DataTable
  const companiesTable = $("#companiesTable").DataTable({
    ajax: {
      url: "../../Ajax/php/company.php",
      type: "GET",
      data: { action: "list" },
      dataSrc: "data",
    },
    columns: [
      { data: "id" },
      {
        data: "company_code",
        render: function (data) {
          return `<code class="text-primary">${data}</code>`;
        },
      },
      {
        data: "name",
        render: function (data) {
          return `<span class="fw-semibold">${data}</span>`;
        },
      },
      {
        data: "package_type",
        render: function (data) {
          const badges = {
            starter: "bg-secondary",
            business: "bg-info",
            pro: "bg-primary",
            enterprise: "bg-warning text-dark",
          };
          return `<span class="badge ${badges[data] || "bg-secondary"}">${data.charAt(0).toUpperCase() + data.slice(1)
            }</span>`;
        },
      },
      {
        data: "status",
        render: function (data) {
          const badges = {
            active: "bg-success",
            trial: "bg-info",
            suspended: "bg-warning text-dark",
            cancelled: "bg-danger",
          };
          return `<span class="badge ${badges[data] || "bg-secondary"}">${data.charAt(0).toUpperCase() + data.slice(1)
            }</span>`;
        },
      },
      {
        data: null,
        render: function (data, type, row) {
          return `${row.user_count || 0} / ${row.max_users}`;
        },
      },
      {
        data: null,
        render: function (data, type, row) {
          return `${row.branch_count || 0} / ${row.max_branches}`;
        },
      },
      {
        data: "created_at",
        render: function (data) {
          return data ? new Date(data).toLocaleDateString() : "N/A";
        },
      },
      {
        data: null,
        orderable: false,
        render: function (data, type, row) {
          return `
            <div class="company-actions">
              <button class="btn btn-sm btn-icon btn-outline-primary edit-company" data-id="${row.id}" 
                      title="Edit Company">
                  <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-icon btn-outline-danger delete-company" data-id="${row.id}" 
                      title="Delete Company">
                  <i class="fas fa-trash"></i>
              </button>
            </div>`;
        },
      },
    ],
    order: [[0, "desc"]],
    responsive: true,
    language: {
      emptyTable: 'No companies found. Click "Add New Company" to create one.',
      loadingRecords:
        '<div class="spinner-border text-primary" role="status"></div> Loading...',
    },
  });

  // Handle form submission
  $("#companyForm").on("submit", function (e) {
    e.preventDefault();

    const formData = {
      action: $("#company_id").val() ? "update" : "create",
      id: $("#company_id").val(),
      company_code: $("#company_code").val(),
      name: $("#company_name").val(),
      package_type: $("#package_type").val(),
      status: $("#status").val(),
      max_users: $("#max_users").val(),
      max_employees: $("#max_employees").val(),
      max_branches: $("#max_branches").val(),
      admin_username: $("#admin_username").val(),
      admin_password: $("#admin_password").val(),
    };

    const submitBtn = $("#saveCompanyBtn");
    const originalBtnText = submitBtn.html();

    if (!this.checkValidity()) {
      e.stopPropagation();
      $(this).addClass("was-validated");
      return;
    }

    submitBtn
      .prop("disabled", true)
      .html(
        '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
      );

    $.ajax({
      url: "../../Ajax/php/company.php",
      type: "POST",
      data: formData,
      success: function (response) {
        if (response.status === "success") {
          showAlert(
            "success",
            response.message || "Operation completed successfully"
          );
          $("#companyModal").modal("hide");
          companiesTable.ajax.reload();
          $("#companyForm")[0].reset();
          $("#companyForm").removeClass("was-validated");
          $("#company_id").val("");
          loadStats();
        } else {
          showAlert("error", response.message || "An error occurred");
        }
      },
      error: function (xhr) {
        const errorMsg =
          xhr.responseJSON && xhr.responseJSON.message
            ? xhr.responseJSON.message
            : "An error occurred while processing your request";
        showAlert("error", errorMsg);
      },
      complete: function () {
        submitBtn.prop("disabled", false).html(originalBtnText);
      },
    });
  });

  // Reset form when modal is closed
  $("#companyModal").on("hidden.bs.modal", function () {
    const form = $("#companyForm");
    form[0].reset();
    form.removeClass("was-validated");
    $("#company_id").val("");
    $("#initialAdminSection").show();
    $("#companyModalLabel").text("Add New Company");
  });

  // Edit company
  $(document).on("click", ".edit-company", function () {
    const companyId = $(this).data("id");

    $.get(
      "../../Ajax/php/company.php",
      { action: "get", id: companyId },
      function (response) {
        if (response.status === "success" && response.data) {
          const company = response.data;
          fillCompanyForm(company);
          $("#companyModalLabel").text("Edit Company");
          $("#initialAdminSection").hide();
          $("#companyModal").modal("show");
        } else {
          showAlert("error", response.message || "Failed to load company data");
        }
      }
    ).fail(function () {
      showAlert("error", "An error occurred while loading company data");
    });
  });

  // Delete company
  $(document).on("click", ".delete-company", function () {
    const companyId = $(this).data("id");

    Swal.fire({
      title: "Are you sure?",
      text: "This will permanently delete the company and cannot be undone!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc3545",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, delete it!",
      cancelButtonText: "Cancel",
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(
          "../../Ajax/php/company.php",
          { action: "delete", id: companyId },
          function (response) {
            if (response.status === "success") {
              showAlert(
                "success",
                response.message || "Company deleted successfully"
              );
              companiesTable.ajax.reload();
              loadStats();
            } else {
              showAlert(
                "error",
                response.message || "Failed to delete company"
              );
            }
          }
        ).fail(function () {
          showAlert("error", "An error occurred while deleting the company");
        });
      }
    });
  });

  // Update limits when package type changes
  $("#package_type").on("change", function () {
    const packageType = $(this).val();
    $.get(
      "../../Ajax/php/company.php",
      { action: "get_package_limits", package_type: packageType },
      function (response) {
        if (response.status === "success" && response.data) {
          $("#max_users").val(response.data.max_users);
          $("#max_employees").val(response.data.max_employees);
          $("#max_branches").val(response.data.max_branches);
        }
      }
    );
  });

  // Load statistics
  function loadStats() {
    $.get(
      "../../Ajax/php/company.php",
      { action: "get_stats" },
      function (response) {
        if (response.status === "success" && response.data) {
          $("#stat-total").text(response.data.total || 0);
          $("#stat-active").text(response.data.active || 0);
          $("#stat-trial").text(response.data.trial || 0);
          $("#stat-suspended").text(response.data.suspended || 0);
        }
      }
    );
  }

  // Helper function to show alerts
  function showAlert(type, message) {
    const Toast = Swal.mixin({
      toast: true,
      position: "top-end",
      showConfirmButton: false,
      timer: 5000,
      timerProgressBar: true,
    });

    Toast.fire({ icon: type, title: message });
  }

  // Helper function to fill form for editing
  function fillCompanyForm(company) {
    $("#company_id").val(company.id);
    $("#company_code").val(company.company_code);
    $("#company_name").val(company.name);
    $("#package_type").val(company.package_type);
    $("#status").val(company.status);
    $("#max_users").val(company.max_users);
    $("#max_employees").val(company.max_employees);
    $("#max_branches").val(company.max_branches);
  }

  // Initial stats load
  loadStats();
});
