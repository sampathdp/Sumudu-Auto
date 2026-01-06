// Initialize DataTable
$(document).ready(function () {
  const suppliersTable = $("#suppliersTable").DataTable({
    ajax: {
      url: "../../Ajax/php/supplier.php",
      type: "GET",
      data: { action: "list" },
      dataSrc: "data",
    },
    columns: [
      { data: "id" },
      {
        data: "supplier_name",
        render: function (data) {
          return `<span class="fw-semibold">${data}</span>`;
        },
      },
      {
        data: "contact_person",
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
        data: "email",
        render: function (data) {
          return data || '<span class="text-muted">N/A</span>';
        },
      },
      {
        data: "payment_terms",
        render: function (data) {
          return data || '<span class="text-muted">N/A</span>';
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
          return `
                        <div class="supplier-actions">
                            <button class="btn btn-sm btn-icon btn-outline-primary edit-supplier" data-id="${row.id}" 
                                    title="Edit Supplier">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-icon btn-outline-danger delete-supplier" data-id="${row.id}" 
                                    title="Delete Supplier">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>`;
        },
      },
    ],
    order: [[0, "desc"]],
    responsive: true,
    language: {
      emptyTable: 'No suppliers found. Click "Add New Supplier" to create one.',
      loadingRecords:
        '<div class="spinner-border text-primary" role="status"></div> Loading...',
    },
  });

  // Load Stats
  function loadStats() {
    $.get(
      "../../Ajax/php/supplier.php",
      { action: "get_stats" },
      function (response) {
        if (response.status === "success" && response.data) {
          // Assuming card layout matches index.php:
          // 1st card: Total
          // 2nd card: Active
          // 3rd card: Inactive

          // Find the h3 elements in the stat-card .details and update them
          const cards = $(".stat-card .details h3");
          if (cards.length >= 3) {
            $(cards[0]).text(response.data.total);
            $(cards[1]).text(response.data.active);
            $(cards[2]).text(response.data.inactive);
          }
        }
      }
    );
  }

  // Update form success
  $("#supplierForm").on("submit", function (e) {
    e.preventDefault();

    const formData = {
      action: $("#supplier_id").val() ? "update" : "create",
      id: $("#supplier_id").val(),
      supplier_name: $("#supplier_name").val(),
      contact_person: $("#contact_person").val(),
      phone: $("#phone").val(),
      email: $("#email").val(),
      address: $("#address").val(),
      tax_id: $("#tax_id").val(),
      payment_terms: $("#payment_terms").val(),
      is_active: $("#is_active").is(":checked") ? 1 : 0,
    };

    const submitBtn = $("#saveSupplierBtn");
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
      url: "../../Ajax/php/supplier.php",
      type: "POST",
      data: formData,
      success: function (response) {
        if (response.status === "success") {
          showAlert(
            "success",
            response.message || "Operation completed successfully"
          );
          $("#supplierModal").modal("hide");
          suppliersTable.ajax.reload();
          loadStats(); // Update stats
          $("#supplierForm")[0].reset();
          $("#supplierForm").removeClass("was-validated");
          $("#supplier_id").val("");
          $("#is_active").prop("checked", true);
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

  // Delete supplier
  $(document).on("click", ".delete-supplier", function () {
    const supplierId = $(this).data("id");

    Swal.fire({
      title: "Are you sure?",
      text: "This action cannot be undone!",
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
          "../../Ajax/php/supplier.php",
          { action: "delete", id: supplierId },
          function (response) {
            if (response.status === "success") {
              showAlert(
                "success",
                response.message || "Supplier deleted successfully"
              );
              suppliersTable.ajax.reload();
              loadStats(); // Update stats
            } else {
              showAlert(
                "error",
                response.message || "Failed to delete supplier"
              );
            }
          }
        ).fail(function () {
          showAlert("error", "An error occurred while deleting the supplier");
        });
      }
    });
  });

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
  function fillSupplierForm(supplier) {
    $("#supplier_id").val(supplier.id);
    $("#supplier_name").val(supplier.supplier_name);
    $("#contact_person").val(supplier.contact_person || "");
    $("#phone").val(supplier.phone || "");
    $("#email").val(supplier.email || "");
    $("#address").val(supplier.address || "");
    $("#tax_id").val(supplier.tax_id || "");
    $("#payment_terms").val(supplier.payment_terms || "");
    $("#is_active").prop("checked", supplier.is_active == 1);
  }
});
