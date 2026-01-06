// Initialize DataTable with enhanced options
$(document).ready(function () {
  const customersTable = $("#customersTable").DataTable({
    ajax: {
      url: "../../Ajax/php/customer.php",
      type: "GET",
      data: {
        action: "list",
      },
      dataSrc: "data",
    },
    columns: [
      {
        data: "id",
      },
      {
        data: "name",
        render: function (data, type, row) {
          return `<span class="fw-semibold">${data}</span>`;
        },
      },
      {
        data: "phone",
      },
      {
        data: "email",
        render: function (data) {
          return data || '<span class="text-muted">No email</span>';
        },
      },
      {
        data: "address",
        render: function (data) {
          return data
            ? data.length > 30
              ? data.substring(0, 30) + "..."
              : data
            : '<span class="text-muted">No address</span>';
        },
      },
      {
        data: "created_at",
        render: function (data) {
          return new Date(data).toLocaleString();
        },
      },
      {
        data: null,
        orderable: false,
        render: function (data, type, row) {
          return `
                                <div class="customer-actions">
                                    <button class="btn btn-sm btn-icon btn-outline-primary edit-customer" data-id="${row.id}" 
                                            title="Edit Customer">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-icon btn-outline-danger delete-customer" data-id="${row.id}" 
                                            title="Delete Customer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>`;
        },
      },
    ],
    order: [[0, "asc"]],
    responsive: true,
    language: {
      emptyTable:
        'No customers found. Click the "Add New Customer" button to get started.',
      loadingRecords:
        '<div class="spinner-border text-primary" role="status"></div> Loading...',
      zeroRecords: "No matching customers found",
      info: "Showing _START_ to _END_ of _TOTAL_ customers",
      infoEmpty: "Showing 0 to 0 of 0 customers",
      infoFiltered: "(filtered from _MAX_ total customers)",
      search: '<i class="fas fa-search"></i>',
      searchPlaceholder: "Search customers...",
      paginate: {
        first: '<i class="fas fa-angle-double-left"></i>',
        last: '<i class="fas fa-angle-double-right"></i>',
        next: '<i class="fas fa-chevron-right"></i>',
        previous: '<i class="fas fa-chevron-left"></i>',
      },
    },
    dom: `
                    <"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"f>>
                    <"row mt-3"<"col-sm-12"tr>>
                    <"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>
                `,
    initComplete: function () {
      $(".dataTables_filter input").addClass("form-control form-control-sm");
      $(".dataTables_length select").addClass("form-select form-select-sm");
    },
  });

  // Handle form submission
  $("#customerForm").on("submit", function (e) {
    e.preventDefault();

    const formData = {
      action: $("#customer_id").val() ? "update" : "create",
      id: $("#customer_id").val(),
      name: $("#name").val(),
      phone: $("#phone").val(),
      email: $("#email").val(),
      address: $("#address").val(),
    };

    const submitBtn = $("#saveCustomerBtn");
    const originalBtnText = submitBtn.html();

    if (!this.checkValidity()) {
      e.stopPropagation();
      $(this).addClass("was-validated");
      return;
    }

    // Disable button and show loading state
    submitBtn
      .prop("disabled", true)
      .html(
        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...'
      );

    $.ajax({
      url: "../../Ajax/php/customer.php",
      type: "POST",
      data: formData,
      success: function (response) {
        if (response.status === "success") {
          showAlert(
            "success",
            response.message || "Operation completed successfully"
          );
          $("#customerModal").modal("hide");
          customersTable.ajax.reload();
          $("#customerForm")[0].reset();
          $("#customerForm").removeClass("was-validated");
          $("#customer_id").val("");
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
  $("#customerModal").on("hidden.bs.modal", function () {
    const form = $("#customerForm");
    form[0].reset();
    form.removeClass("was-validated");
    $("#customer_id").val("");
    $("#customerModalLabel").text("Add New Customer");
  });

  // Edit customer
  $(document).on("click", ".edit-customer", function () {
    const customerId = $(this).data("id");

    $.get(
      "../../Ajax/php/customer.php",
      {
        action: "get",
        id: customerId,
      },
      function (response) {
        if (response.status === "success" && response.data) {
          const customer = response.data;
          fillCustomerForm(customer);
          $("#customerModalLabel").text("Edit Customer");
          $("#customerModal").modal("show");
        } else {
          showAlert(
            "error",
            response.message || "Failed to load customer data"
          );
        }
      }
    ).fail(function () {
      showAlert("error", "An error occurred while loading customer data");
    });
  });

  // Delete customer
  $(document).on("click", ".delete-customer", function () {
    const customerId = $(this).data("id");

    Swal.fire({
      title: "Are you sure?",
      text: "This action cannot be undone!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#4361ee",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, delete it!",
      cancelButtonText: "Cancel",
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(
          "../../Ajax/php/customer.php",
          {
            action: "delete",
            id: customerId,
          },
          function (response) {
            if (response.status === "success") {
              showAlert(
                "success",
                response.message || "Customer deleted successfully"
              );
              customersTable.ajax.reload();
            } else {
              showAlert(
                "error",
                response.message || "Failed to delete customer"
              );
            }
          }
        ).fail(function () {
          showAlert("error", "An error occurred while deleting the customer");
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
      didOpen: (toast) => {
        toast.addEventListener("mouseenter", Swal.stopTimer);
        toast.addEventListener("mouseleave", Swal.resumeTimer);
      },
    });

    Toast.fire({
      icon: type,
      title: message,
    });
  }

  // Helper function to fill customer form for editing
  function fillCustomerForm(customer) {
    $("#customer_id").val(customer.id);
    $("#name").val(customer.name);
    $("#phone").val(customer.phone);
    $("#email").val(customer.email || '');
    $("#address").val(customer.address || '');
  }
});
