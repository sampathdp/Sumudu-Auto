// Initialize DataTable with enhanced options
$(document).ready(function () {
  const vehiclesTable = $("#vehiclesTable").DataTable({
    ajax: {
      url: "../../Ajax/php/vehicle.php",
      type: "GET",
      data: {
        action: "list",
      },
      dataSrc: "data",
    },
    columns: [
      {
        data: "customer_name",
        render: function (data, type, row) {
          return `<span class="fw-semibold">${data}</span>`;
        },
      },
      { data: "registration_number" },
      { data: "make" },
      { data: "model" },
      {
        data: null,
        orderable: false,
        render: function (data, type, row) {
          return `
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-icon btn-outline-info view-vehicle" data-id="${row.id}" title="View Details">
                <i class="fas fa-eye"></i>
              </button>
              <button class="btn btn-sm btn-icon btn-outline-primary edit-vehicle" data-id="${row.id}" title="Edit">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-icon btn-outline-danger delete-vehicle" data-id="${row.id}" title="Delete">
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
        'No vehicles found. Click the "Add New Vehicle" button to get started.',
      loadingRecords:
        '<div class="spinner-border text-primary" role="status"></div> Loading...',
      zeroRecords: "No matching vehicles found",
      info: "Showing _START_ to _END_ of _TOTAL_ vehicles",
      infoEmpty: "Showing 0 to 0 of 0 vehicles",
      infoFiltered: "(filtered from _MAX_ total vehicles)",
      search: '<i class="fas fa-search"></i>',
      searchPlaceholder: "Search vehicles...",
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

  let customers = []; // Cache for customers
  let currentVehicleData = null;
  let currentVehicleId = null;

  // Load customers for dropdown
  function loadCustomers() {
    return new Promise((resolve) => {
      if (customers.length > 0) {
        populateCustomerSelect();
        resolve(customers);
        return;
      }

      $.get(
        "../../Ajax/php/vehicle.php",
        { action: "get_customers" },
        function (response) {
          if (response.status === "success") {
            customers = response.data;
            populateCustomerSelect();
            resolve(customers);
          } else {
            showAlert("error", "Failed to load customers");
            resolve([]);
          }
        }
      ).fail(function () {
        showAlert("error", "Error loading customers");
        resolve([]);
      });
    });
  }

  // Populate customer select
  function populateCustomerSelect() {
    const select = $("#customer_id");
    select.empty().append('<option value="">Select Customer...</option>');
    if (customers && customers.length > 0) {
      customers.forEach((customer) => {
        select.append(
          `<option value="${customer.id}">${customer.name} (${customer.phone})</option>`
        );
      });
    }
  }

  // Centralized function to populate form with vehicle data
  function populateVehicleForm(data) {
    // Set basic form fields
    $("#vehicle_id").val(data.id);
    $("#registration_number").val(data.registration_number);
    $("#make").val(data.make);
    $("#model").val(data.model);
    $("#year").val(data.year);
    $("#color").val(data.color);
    $("#current_mileage").val(data.current_mileage);
    $("#last_service_date").val(data.last_service_date);
    $("#last_oil_change_date").val(data.last_oil_change_date);

    // First ensure customers are loaded
    loadCustomers()
      .then(() => {
        // Then set the customer value
        if (data.customer_id) {
          $("#customer_id").val(data.customer_id);
        }
      })
      .catch((error) => {
        console.error("Error loading customers:", error);
      });
  }

  // Centralized function to load and display vehicle data
  function loadVehicleData(vehicleId, callback) {
    $.get(
      "../../Ajax/php/vehicle.php",
      {
        action: "get",
        id: vehicleId,
      },
      function (response) {
        if (response.status === "success" && response.data) {
          callback(response.data);
        } else {
          showAlert("error", response.message || "Failed to load vehicle data");
        }
      }
    ).fail(function () {
      showAlert("error", "An error occurred while loading vehicle data");
    });
  }

  // Centralized function to delete vehicle
  function deleteVehicle(vehicleId, onSuccess) {
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
          "../../Ajax/php/vehicle.php",
          {
            action: "delete",
            id: vehicleId,
          },
          function (response) {
            if (response.status === "success") {
              showAlert(
                "success",
                response.message || "Vehicle deleted successfully"
              );
              vehiclesTable.ajax.reload();
              if (onSuccess) onSuccess();
            } else {
              showAlert(
                "error",
                response.message || "Failed to delete vehicle"
              );
            }
          }
        ).fail(function () {
          showAlert("error", "An error occurred while deleting the vehicle");
        });
      }
    });
  }

  // Handle form submission
  $("#vehicleForm").on("submit", function (e) {
    e.preventDefault();
    const form = $(this);
    const submitBtn = $("#saveVehicleBtn");
    const originalBtnText = submitBtn.html();

    if (!form[0].checkValidity()) {
      e.stopPropagation();
      form.addClass("was-validated");
      return;
    }

    const formData = new FormData(this);
    formData.append("action", $("#vehicle_id").val() ? "update" : "create");
    if ($("#vehicle_id").val()) {
      formData.append("id", $("#vehicle_id").val());
    }

    // Disable button and show loading state
    submitBtn
      .prop("disabled", true)
      .html(
        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...'
      );

    $.ajax({
      url: "../../Ajax/php/vehicle.php",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === "success") {
          showAlert(
            "success",
            response.message || "Operation completed successfully"
          );
          $("#vehicleModal").modal("hide");
          vehiclesTable.ajax.reload();
          form[0].reset();
          form.removeClass("was-validated");
          $("#vehicle_id").val("");
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
  $("#vehicleModal").on("hidden.bs.modal", function () {
    const form = $("#vehicleForm");
    form[0].reset();
    form.removeClass("was-validated");
    $("#vehicle_id").val("");
    $("#customer_id")
      .empty()
      .append('<option value="">Select Customer...</option>');
    $("#vehicleModalLabel").text("Add New Vehicle");
  });

  // Edit vehicle from table
  $(document).on("click", ".edit-vehicle", function (e) {
    e.stopPropagation();
    const vehicleId = $(this).data("id");

    loadVehicleData(vehicleId, function (data) {
      currentVehicleData = data;
      populateVehicleForm(data);
      $("#vehicleModalLabel").text("Edit Vehicle");
      $("#vehicleModal").modal("show");
    });
  });

  // Delete vehicle from table
  $(document).on("click", ".delete-vehicle", function (e) {
    e.stopPropagation();
    const vehicleId = $(this).data("id");
    deleteVehicle(vehicleId);
  });

  // View vehicle
  $(document).on("click", ".view-vehicle", function () {
    const vehicleId = $(this).data("id");

    loadVehicleData(vehicleId, function (data) {
      currentVehicleId = data.id;
      currentVehicleData = data;

      const detailsHtml = `
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Customer:</label>
            <p class="mb-0">${data.customer_name || "N/A"}</p>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Registration Number:</label>
            <p class="mb-0">${data.registration_number}</p>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Make:</label>
            <p class="mb-0">${data.make}</p>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Model:</label>
            <p class="mb-0">${data.model}</p>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Year:</label>
            <p class="mb-0">${data.year || "N/A"}</p>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Color:</label>
            <p class="mb-0">${data.color || "N/A"}</p>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Current Mileage:</label>
            <p class="mb-0">${data.current_mileage || 0} km</p>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Last Service Date:</label>
            <p class="mb-0">${
              data.last_service_date
                ? new Date(data.last_service_date).toLocaleDateString()
                : "N/A"
            }</p>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Last Oil Change Date:</label>
            <p class="mb-0">${
              data.last_oil_change_date
                ? new Date(data.last_oil_change_date).toLocaleDateString()
                : "N/A"
            }</p>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Created At:</label>
            <p class="mb-0">${
              data.created_at
                ? new Date(data.created_at).toLocaleDateString()
                : "N/A"
            }</p>
          </div>
        </div>
      `;

      $("#vehicleDetails").html(detailsHtml);
      $("#viewVehicleModalLabel").text(
        `Vehicle Details - ${data.registration_number}`
      );
      $("#viewVehicleModal").modal("show");
    });
  });

  // Edit from view modal
  $(document).on("click", "#editFromView", function () {
    if (!currentVehicleData) return;

    $("#viewVehicleModal").modal("hide");
    populateVehicleForm(currentVehicleData);
    $("#vehicleModalLabel").text("Edit Vehicle");
    $("#vehicleModal").modal("show");
  });

  // Delete from view modal
  $(document).on("click", "#deleteFromView", function () {
    if (!currentVehicleId) return;

    deleteVehicle(currentVehicleId, function () {
      $("#viewVehicleModal").modal("hide");
      currentVehicleData = null;
      currentVehicleId = null;
    });
  });

  // Reset view modal
  $("#viewVehicleModal").on("hidden.bs.modal", function () {
    currentVehicleData = null;
    currentVehicleId = null;
    $("#vehicleDetails").empty();
  });

  // Load customers when edit modal shows
  $("#vehicleModal").on("show.bs.modal", function () {
    loadCustomers();
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
});
