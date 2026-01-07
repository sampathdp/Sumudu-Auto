$(document).ready(function () {
  const BASE_URL = window.BASE_URL || "/GaragePulse1/";

  // Initialize Customers DataTable
  const customersTable = $("#customersTable").DataTable({
    ajax: {
      url: BASE_URL + "Ajax/php/customer.php",
      type: "GET",
      data: { action: "list" },
      dataSrc: "data",
    },
    columns: [
      { data: "id" },
      {
        data: "name",
        render: (data) => `<span class="fw-semibold">${data}</span>`,
      },
      { data: "phone" },
      {
        data: "email",
        render: (data) => data || '<span class="text-muted">-</span>',
      },
      {
        data: "id",
        render: function (data, type, row) {
          return `<button class="btn btn-sm btn-outline-info view-vehicles" data-id="${data}" data-name="${row.name}"><i class="fas fa-car"></i></button>`;
        },
      },
      {
        data: null,
        orderable: false,
        className: "text-end",
        render: (data, type, row) => `
          <button class="btn-action edit-customer" data-id="${row.id}" title="Edit"><i class="fas fa-edit"></i></button>
          <button class="btn-action danger delete-customer" data-id="${row.id}" title="Delete"><i class="fas fa-trash"></i></button>
        `,
      },
    ],
    order: [[0, "desc"]],
    responsive: true,
    language: {
      emptyTable: "No customers found",
      search: '<i class="fas fa-search"></i>',
      searchPlaceholder: "Search...",
    },
  });

  // Initialize Vehicles DataTable
  const vehiclesTable = $("#vehiclesTable").DataTable({
    ajax: {
      url: BASE_URL + "Ajax/php/vehicle.php",
      type: "GET",
      data: { action: "list" },
      dataSrc: "data",
    },
    columns: [
      {
        data: "registration_number",
        render: (data) => `<span class="reg-number">${data}</span>`,
      },
      {
        data: null,
        render: (data, type, row) => `${row.make} ${row.model}`,
      },
      {
        data: "customer_name",
        render: (data) => `<span class="fw-semibold">${data}</span>`,
      },
      {
        data: "customer_phone",
        render: (data) => data || '<span class="text-muted">-</span>',
      },
      {
        data: "current_mileage",
        render: (data) =>
          data ? `${data}` : '<span class="text-muted">-</span>',
      },
      {
        data: "last_service_date",
        render: (data) => data || '<span class="text-muted">-</span>',
      },
      {
        data: null,
        orderable: false,
        className: "text-end",
        render: (data, type, row) => `
          <button class="btn-action edit-vehicle" data-id="${row.id}" title="Edit"><i class="fas fa-edit"></i></button>
          <button class="btn-action danger delete-vehicle" data-id="${row.id}" title="Delete"><i class="fas fa-trash"></i></button>
        `,
      },
    ],
    order: [[0, "asc"]],
    responsive: true,
    language: {
      emptyTable: "No vehicles found",
      search: '<i class="fas fa-search"></i>',
      searchPlaceholder: "Search...",
    },
  });

  // Reload vehicles table when tab is shown
  $('button[data-bs-toggle="tab"]').on("shown.bs.tab", function (e) {
    if (e.target.id === "vehicles-tab") {
      vehiclesTable.ajax.reload();
    }
  });

  // Load customers into vehicle modal dropdown
  function loadCustomersDropdown() {
    $.get(
      BASE_URL + "Ajax/php/customer.php",
      { action: "list" },
      function (response) {
        if (response.status === "success" && response.data) {
          const select = $("#v_customer_id");
          select.empty().append('<option value="">Select Customer...</option>');
          response.data.forEach((c) => {
            select.append(
              `<option value="${c.id}">${c.name} - ${c.phone}</option>`
            );
          });
        }
      }
    );
  }

  // Load customers when vehicle modal opens
  $("#vehicleModal").on("show.bs.modal", function () {
    loadCustomersDropdown();
  });

  // ==========================================
  // Add Customer & Vehicle (Combined)
  // ==========================================
  $("#addBothForm").on("submit", function (e) {
    e.preventDefault();
    if (!this.checkValidity()) {
      $(this).addClass("was-validated");
      return;
    }

    const btn = $("#saveBothBtn");
    btn
      .prop("disabled", true)
      .html(
        '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
      );

    // Step 1: Create Customer
    const customerData = {
      action: "create",
      name: $("#both_name").val(),
      phone: $("#both_phone").val(),
      email: $("#both_email").val(),
    };

    $.post(
      BASE_URL + "Ajax/php/customer.php",
      customerData,
      function (response) {
        if (response.status === "success" && response.id) {
          const customerId = response.id;

          // Step 2: Create Vehicle with new customer ID
          const vehicleData = {
            action: "create",
            customer_id: customerId,
            make: $("#both_make").val(),
            model: $("#both_model").val(),
            registration_number: $("#both_registration").val(),
            year: $("#both_year").val() || null,
            current_mileage: $("#both_mileage").val() || null,
          };

          $.post(
            BASE_URL + "Ajax/php/vehicle.php",
            vehicleData,
            function (vResponse) {
              if (vResponse.status === "success") {
                showAlert(
                  "success",
                  "Customer and Vehicle added successfully!"
                );
                $("#addBothModal").modal("hide");
                $("#addBothForm")[0].reset();
                $("#addBothForm").removeClass("was-validated");
                customersTable.ajax.reload();
                vehiclesTable.ajax.reload();
              } else {
                showAlert(
                  "error",
                  vResponse.message || "Failed to add vehicle"
                );
              }
            }
          )
            .fail(function () {
              showAlert("error", "Error adding vehicle");
            })
            .always(function () {
              btn
                .prop("disabled", false)
                .html('<i class="fas fa-save me-1"></i>Save Both');
            });
        } else {
          showAlert("error", response.message || "Failed to add customer");
          btn
            .prop("disabled", false)
            .html('<i class="fas fa-save me-1"></i>Save Both');
        }
      }
    ).fail(function () {
      showAlert("error", "Error adding customer");
      btn
        .prop("disabled", false)
        .html('<i class="fas fa-save me-1"></i>Save Both');
    });
  });

  // ==========================================
  // Customer CRUD
  // ==========================================
  $("#customerForm").on("submit", function (e) {
    e.preventDefault();
    if (!this.checkValidity()) {
      $(this).addClass("was-validated");
      return;
    }

    const formData = {
      action: $("#customer_id").val() ? "update" : "create",
      id: $("#customer_id").val(),
      name: $("#c_name").val(),
      phone: $("#c_phone").val(),
      email: $("#c_email").val(),
      address: $("#c_address").val(),
    };

    $.post(BASE_URL + "Ajax/php/customer.php", formData, function (response) {
      if (response.status === "success") {
        showAlert("success", response.message || "Customer saved successfully");
        $("#customerModal").modal("hide");
        $("#customerForm")[0].reset();
        $("#customer_id").val("");
        customersTable.ajax.reload();
      } else {
        showAlert("error", response.message || "Failed to save customer");
      }
    }).fail(function () {
      showAlert("error", "An error occurred");
    });
  });

  $(document).on("click", ".edit-customer", function () {
    const id = $(this).data("id");
    $.get(
      BASE_URL + "Ajax/php/customer.php",
      { action: "get", id: id },
      function (response) {
        if (response.status === "success" && response.data) {
          const c = response.data;
          $("#customer_id").val(c.id);
          $("#c_name").val(c.name);
          $("#c_phone").val(c.phone);
          $("#c_email").val(c.email || "");
          $("#c_address").val(c.address || "");
          $("#customerModalLabel").text("Edit Customer");
          $("#customerModal").modal("show");
        }
      }
    );
  });

  $(document).on("click", ".delete-customer", function () {
    const id = $(this).data("id");
    Swal.fire({
      title: "Delete Customer?",
      text: "This will also delete all associated vehicles!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc2626",
      confirmButtonText: "Yes, delete",
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(
          BASE_URL + "Ajax/php/customer.php",
          { action: "delete", id: id },
          function (response) {
            if (response.status === "success") {
              showAlert("success", "Customer deleted");
              customersTable.ajax.reload();
              vehiclesTable.ajax.reload();
            } else {
              showAlert("error", response.message || "Failed to delete");
            }
          }
        );
      }
    });
  });

  $("#customerModal").on("hidden.bs.modal", function () {
    $("#customerForm")[0].reset();
    $("#customerForm").removeClass("was-validated");
    $("#customer_id").val("");
    $("#customerModalLabel").text("Add Customer");
  });

  // ==========================================
  // Vehicle CRUD
  // ==========================================
  $("#vehicleForm").on("submit", function (e) {
    e.preventDefault();
    if (!this.checkValidity()) {
      $(this).addClass("was-validated");
      return;
    }

    const formData = {
      action: $("#vehicle_id").val() ? "update" : "create",
      id: $("#vehicle_id").val(),
      customer_id: $("#v_customer_id").val(),
      registration_number: $("#v_registration").val(),
      make: $("#v_make").val(),
      model: $("#v_model").val(),
      year: $("#v_year").val() || null,
      color: $("#v_color").val() || null,
      current_mileage: $("#v_mileage").val() || null,
    };

    $.post(BASE_URL + "Ajax/php/vehicle.php", formData, function (response) {
      if (response.status === "success") {
        showAlert("success", response.message || "Vehicle saved successfully");
        $("#vehicleModal").modal("hide");
        $("#vehicleForm")[0].reset();
        $("#vehicle_id").val("");
        vehiclesTable.ajax.reload();
      } else {
        showAlert("error", response.message || "Failed to save vehicle");
      }
    }).fail(function () {
      showAlert("error", "An error occurred");
    });
  });

  $(document).on("click", ".edit-vehicle", function () {
    const id = $(this).data("id");
    loadCustomersDropdown();
    $.get(
      BASE_URL + "Ajax/php/vehicle.php",
      { action: "get", id: id },
      function (response) {
        if (response.status === "success" && response.data) {
          const v = response.data;
          $("#vehicle_id").val(v.id);
          setTimeout(function () {
            $("#v_customer_id").val(v.customer_id);
          }, 300);
          $("#v_registration").val(v.registration_number);
          $("#v_make").val(v.make);
          $("#v_model").val(v.model);
          $("#v_year").val(v.year || "");
          $("#v_color").val(v.color || "");
          $("#v_mileage").val(v.current_mileage || "");
          $("#vehicleModalLabel").text("Edit Vehicle");
          $("#vehicleModal").modal("show");
        }
      }
    );
  });

  $(document).on("click", ".delete-vehicle", function () {
    const id = $(this).data("id");
    Swal.fire({
      title: "Delete Vehicle?",
      text: "This action cannot be undone!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc2626",
      confirmButtonText: "Yes, delete",
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(
          BASE_URL + "Ajax/php/vehicle.php",
          { action: "delete", id: id },
          function (response) {
            if (response.status === "success") {
              showAlert("success", "Vehicle deleted");
              vehiclesTable.ajax.reload();
            } else {
              showAlert("error", response.message || "Failed to delete");
            }
          }
        );
      }
    });
  });

  $("#vehicleModal").on("hidden.bs.modal", function () {
    $("#vehicleForm")[0].reset();
    $("#vehicleForm").removeClass("was-validated");
    $("#vehicle_id").val("");
    $("#vehicleModalLabel").text("Add Vehicle");
  });

  // View customer's vehicles
  $(document).on("click", ".view-vehicles", function () {
    const customerId = $(this).data("id");
    const customerName = $(this).data("name");
    // Switch to vehicles tab and filter
    $('button[data-bs-target="#vehicles-panel"]').tab("show");
    setTimeout(function () {
      vehiclesTable.search(customerName).draw();
    }, 300);
  });

  // Helper function
  function showAlert(type, message) {
    Swal.mixin({
      toast: true,
      position: "top-end",
      showConfirmButton: false,
      timer: 4000,
      timerProgressBar: true,
    }).fire({ icon: type, title: message });
  }
});
