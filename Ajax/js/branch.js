// Branches Management JavaScript
$(document).ready(function () {
  // Initialize DataTable
  const branchesTable = $("#branchesTable").DataTable({
    ajax: {
      url: "../../Ajax/php/branch.php",
      type: "GET",
      data: { action: "list" },
      dataSrc: "data",
    },
    columns: [
      { data: "id" },
      {
        data: "branch_code",
        render: function (data) {
          return `<code class="text-success">${data}</code>`;
        },
      },
      {
        data: "branch_name",
        render: function (data, type, row) {
          let badge =
            row.is_main == 1
              ? '<span class="badge bg-warning text-dark ms-2">Main</span>'
              : "";
          return `<span class="fw-semibold">${data}</span>${badge}`;
        },
      },
      {
        data: "company_name",
        render: function (data) {
          return data || '<span class="text-muted">N/A</span>';
        },
      },
      {
        data: "phone",
        render: function (data) {
          return data || '<span class="text-muted">N/A</span>';
        },
      },
      {
        data: null,
        render: function (data, type, row) {
          return `${row.user_count || 0} users, ${row.employee_count || 0} employees`;
        },
      },
      {
        data: "is_active",
        render: function (data) {
          return data == 1
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-secondary">Inactive</span>';
        },
      },
      {
        data: null,
        orderable: false,
        render: function (data, type, row) {
          let mainBtn =
            row.is_main == 1
              ? ""
              : `<button class="btn btn-sm btn-icon btn-outline-warning set-main-branch" data-id="${row.id}" 
                      title="Set as Main Branch">
                  <i class="fas fa-star"></i>
              </button>`;
          return `
            <div class="branch-actions">
              ${mainBtn}
              <button class="btn btn-sm btn-icon btn-outline-primary edit-branch" data-id="${row.id
            }" 
                      title="Edit Branch">
                  <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-icon btn-outline-danger delete-branch" data-id="${row.id
            }" 
                      ${row.is_main == 1 ? "disabled" : ""
            } title="Delete Branch">
                  <i class="fas fa-trash"></i>
              </button>
            </div>`;
        },
      },
    ],
    order: [[0, "desc"]],
    responsive: true,
    language: {
      emptyTable: 'No branches found. Click "Add New Branch" to create one.',
      loadingRecords:
        '<div class="spinner-border text-primary" role="status"></div> Loading...',
    },
  });

  // Load companies for dropdown
  function loadCompanies() {
    $.get(
      "../../Ajax/php/company.php",
      { action: "list" },
      function (response) {
        if (response.status === "success" && response.data) {
          const select = $("#company_id");
          select.empty().append('<option value="">Select Company</option>');
          response.data.forEach(function (company) {
            select.append(
              `<option value="${company.id}">${company.name} (${company.company_code})</option>`
            );
          });
        }
      }
    );
  }

  // Handle form submission
  $("#branchForm").on("submit", function (e) {
    e.preventDefault();

    const formData = {
      action: $("#branch_id").val() ? "update" : "create",
      id: $("#branch_id").val(),
      company_id: $("#company_id").val(),
      branch_code: $("#branch_code").val(),
      branch_name: $("#branch_name").val(),
      address: $("#address").val(),
      phone: $("#phone").val(),
      email: $("#email").val(),
      is_main: $("#is_main").is(":checked") ? 1 : 0,
      is_active: $("#is_active").is(":checked") ? 1 : 0,
    };

    const submitBtn = $("#saveBranchBtn");
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
      url: "../../Ajax/php/branch.php",
      type: "POST",
      data: formData,
      success: function (response) {
        if (response.status === "success") {
          showAlert(
            "success",
            response.message || "Operation completed successfully"
          );
          $("#branchModal").modal("hide");
          branchesTable.ajax.reload();
          $("#branchForm")[0].reset();
          $("#branchForm").removeClass("was-validated");
          $("#branch_id").val("");
          $("#is_active").prop("checked", true);
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

  // Reset form when modal is opened
  $("#branchModal").on("show.bs.modal", function () {
    loadCompanies();
  });

  // Reset form when modal is closed
  $("#branchModal").on("hidden.bs.modal", function () {
    const form = $("#branchForm");
    form[0].reset();
    form.removeClass("was-validated");
    $("#branch_id").val("");
    $("#branchModalLabel").text("Add New Branch");
    $("#is_active").prop("checked", true);
    $("#company_id").prop("disabled", false);
  });

  // Edit branch
  $(document).on("click", ".edit-branch", function () {
    const branchId = $(this).data("id");
    loadCompanies();

    $.get(
      "../../Ajax/php/branch.php",
      { action: "get", id: branchId },
      function (response) {
        if (response.status === "success" && response.data) {
          const branch = response.data;
          fillBranchForm(branch);
          $("#branchModalLabel").text("Edit Branch");
          $("#company_id").prop("disabled", true); // Can't change company
          $("#branchModal").modal("show");
        } else {
          showAlert("error", response.message || "Failed to load branch data");
        }
      }
    ).fail(function () {
      showAlert("error", "An error occurred while loading branch data");
    });
  });

  // Delete branch
  $(document).on("click", ".delete-branch", function () {
    const branchId = $(this).data("id");

    Swal.fire({
      title: "Are you sure?",
      text: "This will permanently delete the branch!",
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
          "../../Ajax/php/branch.php",
          { action: "delete", id: branchId },
          function (response) {
            if (response.status === "success") {
              showAlert(
                "success",
                response.message || "Branch deleted successfully"
              );
              branchesTable.ajax.reload();
              loadStats();
            } else {
              showAlert("error", response.message || "Failed to delete branch");
            }
          }
        ).fail(function () {
          showAlert("error", "An error occurred while deleting the branch");
        });
      }
    });
  });

  // Set as main branch
  $(document).on("click", ".set-main-branch", function () {
    const branchId = $(this).data("id");

    Swal.fire({
      title: "Set as Main Branch?",
      text: "This will make this branch the main/head office branch.",
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#d97706",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, set as main",
      cancelButtonText: "Cancel",
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(
          "../../Ajax/php/branch.php",
          { action: "set_main", id: branchId },
          function (response) {
            if (response.status === "success") {
              showAlert(
                "success",
                response.message || "Branch set as main successfully"
              );
              branchesTable.ajax.reload();
            } else {
              showAlert("error", response.message || "Failed to set as main");
            }
          }
        ).fail(function () {
          showAlert("error", "An error occurred");
        });
      }
    });
  });

  // Load statistics
  function loadStats() {
    $.get(
      "../../Ajax/php/branch.php",
      { action: "get_stats" },
      function (response) {
        if (response.status === "success" && response.data) {
          $("#stat-total").text(response.data.total || 0);
          $("#stat-active").text(response.data.active || 0);
          $("#stat-inactive").text(response.data.inactive || 0);
          $("#stat-main").text(response.data.main || 0);
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
  function fillBranchForm(branch) {
    $("#branch_id").val(branch.id);
    $("#company_id").val(branch.company_id);
    $("#branch_code").val(branch.branch_code);
    $("#branch_name").val(branch.branch_name);
    $("#address").val(branch.address || "");
    $("#phone").val(branch.phone || "");
    $("#email").val(branch.email || "");
    $("#is_main").prop("checked", branch.is_main == 1);
    $("#is_active").prop("checked", branch.is_active == 1);
  }

  // Initial stats load
  loadStats();
});
