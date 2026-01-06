// Initialize DataTable with enhanced options
$(document).ready(function () {
  const packagesTable = $("#packagesTable").DataTable({
    ajax: {
      url: "../../Ajax/php/service-package.php",
      type: "GET",
      data: {
        action: "list",
      },
      dataSrc: "data",
    },
    columns: [
      { data: "id" },
      {
        data: "package_name",
        render: function (data, type, row) {
          return `<span class="fw-semibold">${data}</span>`;
        },
      },
      {
        data: "description",
        render: function (data) {
          return data || '<span class="text-muted">No description</span>';
        },
      },
      {
        data: "base_price",
        render: function (data) {
          return `LKR ${parseFloat(data).toFixed(2)}`;
        },
      },
      { data: "estimated_duration" },
      {
        data: "is_active",
        render: function (data) {
          return data
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-secondary">Inactive</span>';
        },
      },
      {
        data: "created_at",
        render: function (data) {
          return new Date(data).toLocaleDateString();
        },
      },
      {
        data: null,
        orderable: false,
        render: function (data, type, row) {
          return `
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-icon btn-outline-info view-package" data-id="${row.id}" title="View Details">
                <i class="fas fa-eye"></i>
              </button>
              <button class="btn btn-sm btn-icon btn-outline-primary edit-package" data-id="${row.id}" title="Edit">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-icon btn-outline-danger delete-package" data-id="${row.id}" title="Delete">
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
        'No packages found. Click the "Add New Package" button to get started.',
      loadingRecords:
        '<div class="spinner-border text-primary" role="status"></div> Loading...',
      zeroRecords: "No matching packages found",
      info: "Showing _START_ to _END_ of _TOTAL_ packages",
      infoEmpty: "Showing 0 to 0 of 0 packages",
      infoFiltered: "(filtered from _MAX_ total packages)",
      search: '<i class="fas fa-search"></i>',
      searchPlaceholder: "Search packages...",
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

  let currentPackageData = null;
  let currentPackageId = null;

  // Centralized function to load and display package data
  function loadPackageData(packageId, callback) {
    $.get(
      "../../Ajax/php/service-package.php",
      {
        action: "get",
        id: packageId,
      },
      function (response) {
        if (response.status === "success" && response.data) {
          callback(response.data);
        } else {
          showAlert("error", response.message || "Failed to load package data");
        }
      }
    ).fail(function () {
      showAlert("error", "An error occurred while loading package data");
    });
  }

  // Centralized function to populate form with package data
  function populatePackageForm(data) {
    $("#package_id").val(data.id);
    $("#package_name").val(data.package_name);
    $("#description").val(data.description);
    $("#base_price").val(data.base_price);
    $("#estimated_duration").val(data.estimated_duration);
    $("#is_active").prop("checked", data.is_active);
  }

  // Centralized function to delete package
  function deletePackage(packageId, onSuccess) {
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
          "../../Ajax/php/service-package.php",
          {
            action: "delete",
            id: packageId,
          },
          function (response) {
            if (response.status === "success") {
              showAlert(
                "success",
                response.message || "Package deleted successfully"
              );
              packagesTable.ajax.reload();
              if (onSuccess) onSuccess();
            } else {
              showAlert(
                "error",
                response.message || "Failed to delete package"
              );
            }
          }
        ).fail(function () {
          showAlert("error", "An error occurred while deleting the package");
        });
      }
    });
  }

  // Handle form submission
  $("#packageForm").on("submit", function (e) {
    e.preventDefault();
    const form = $(this);
    const submitBtn = $("#savePackageBtn");
    const originalBtnText = submitBtn.html();

    if (!form[0].checkValidity()) {
      e.stopPropagation();
      form.addClass("was-validated");
      return;
    }

    const formData = new FormData(this);
    formData.append("action", $("#package_id").val() ? "update" : "create");
    if ($("#package_id").val()) {
      formData.append("id", $("#package_id").val());
    }

    // Disable button and show loading state
    submitBtn
      .prop("disabled", true)
      .html(
        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...'
      );

    $.ajax({
      url: "../../Ajax/php/service-package.php",
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
          $("#packageModal").modal("hide");
          packagesTable.ajax.reload();
          form[0].reset();
          form.removeClass("was-validated");
          $("#package_id").val("");
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

  // Reset form when modal is closed
  $("#packageModal").on("hidden.bs.modal", function () {
    const form = $("#packageForm");
    form[0].reset();
    form.removeClass("was-validated");
    $("#package_id").val("");
    $("#is_active").prop("checked", true);
    $("#packageModalLabel").text("Add New Package");
  });

  // Edit package from table
  $(document).on("click", ".edit-package", function (e) {
    e.stopPropagation();
    const packageId = $(this).data("id");

    loadPackageData(packageId, function (data) {
      currentPackageData = data;
      populatePackageForm(data);
      $("#packageModalLabel").text("Edit Package");
      $("#packageModal").modal("show");
    });
  });

  // Delete package from table
  $(document).on("click", ".delete-package", function (e) {
    e.stopPropagation();
    const packageId = $(this).data("id");
    deletePackage(packageId);
  });

  // View package
  $(document).on("click", ".view-package", function () {
    const packageId = $(this).data("id");

    loadPackageData(packageId, function (data) {
      currentPackageId = data.id;
      currentPackageData = data;

      const detailsHtml = `
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Package Name:</label>
            <p class="mb-0">${data.package_name}</p>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Status:</label>
            <p class="mb-0">${
              data.is_active
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-secondary">Inactive</span>'
            }</p>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-12">
            <label class="form-label fw-bold">Description:</label>
            <p class="mb-0">${data.description || "No description"}</p>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Base Price:</label>
            <p class="mb-0">$${parseFloat(data.base_price).toFixed(2)}</p>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Estimated Duration:</label>
            <p class="mb-0">${data.estimated_duration} minutes</p>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Created At:</label>
            <p class="mb-0">${new Date(
              data.created_at
            ).toLocaleDateString()}</p>
          </div>
        </div>
      `;

      $("#packageDetails").html(detailsHtml);
      $("#viewPackageModalLabel").text(
        `Package Details - ${data.package_name}`
      );
      $("#viewPackageModal").modal("show");
    });
  });

  // Edit from view modal
  $(document).on("click", "#editFromViewPackage", function () {
    if (!currentPackageData) return;

    $("#viewPackageModal").modal("hide");
    populatePackageForm(currentPackageData);
    $("#packageModalLabel").text("Edit Package");
    $("#packageModal").modal("show");
  });

  // Delete from view modal
  $(document).on("click", "#deleteFromViewPackage", function () {
    if (!currentPackageId) return;

    deletePackage(currentPackageId, function () {
      $("#viewPackageModal").modal("hide");
      currentPackageData = null;
      currentPackageId = null;
    });
  });

  // Reset view modal
  $("#viewPackageModal").on("hidden.bs.modal", function () {
    currentPackageData = null;
    currentPackageId = null;
    $("#packageDetails").empty();
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
