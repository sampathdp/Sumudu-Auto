// Initialize DataTable with enhanced options
$(document).ready(function () {
  const stagesTable = $("#stagesTable").DataTable({
    ajax: {
      url: "../../Ajax/php/service-stage.php",
      type: "GET",
      data: {
        action: "list",
      },
      dataSrc: "data",
    },
    columns: [
      { data: "id" },
      {
        data: "stage_name",
        render: function (data, type, row) {
          return `<span class="fw-semibold">${data}</span>`;
        },
      },
      { data: "stage_order" },
      {
        data: "icon",
        render: function (data) {
          return data
            ? `<span class="text-primary">${data}</span>`
            : '<span class="text-muted">N/A</span>';
        },
      },
      {
        data: "estimated_duration",
        render: function (data) {
          return data ? `${data} min` : '<span class="text-muted">N/A</span>';
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
              <button class="btn btn-sm btn-icon btn-outline-info view-stage" data-id="${row.id}" title="View Details">
                <i class="fas fa-eye"></i>
              </button>
              <button class="btn btn-sm btn-icon btn-outline-primary edit-stage" data-id="${row.id}" title="Edit">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn btn-sm btn-icon btn-outline-danger delete-stage" data-id="${row.id}" title="Delete">
                <i class="fas fa-trash"></i>
              </button>
            </div>`;
        },
      },
    ],
    order: [[2, "asc"]],
    responsive: true,
    language: {
      emptyTable:
        'No stages found. Click the "Add New Stage" button to get started.',
      loadingRecords:
        '<div class="spinner-border text-primary" role="status"></div> Loading...',
      zeroRecords: "No matching stages found",
      info: "Showing _START_ to _END_ of _TOTAL_ stages",
      infoEmpty: "Showing 0 to 0 of 0 stages",
      infoFiltered: "(filtered from _MAX_ total stages)",
      search: '<i class="fas fa-search"></i>',
      searchPlaceholder: "Search stages...",
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
      $(".dataTables_length select").addClass("form-select form-control-sm");
    },
  });

  let currentStageData = null;
  let currentStageId = null;

  // Centralized function to load and display stage data
  function loadStageData(stageId, callback) {
    $.get(
      "../../Ajax/php/service-stage.php",
      {
        action: "get",
        id: stageId,
      },
      function (response) {
        if (response.status === "success" && response.data) {
          callback(response.data);
        } else {
          showAlert("error", response.message || "Failed to load stage data");
        }
      }
    ).fail(function () {
      showAlert("error", "An error occurred while loading stage data");
    });
  }

  // Centralized function to populate form with stage data
  function populateStageForm(data) {
    $("#stage_id").val(data.id);
    $("#stage_name").val(data.stage_name);
    $("#stage_order").val(data.stage_order);
    $("#icon").val(data.icon);
    $("#estimated_duration").val(data.estimated_duration);
  }

  // Centralized function to delete stage
  function deleteStage(stageId, onSuccess) {
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
          "../../Ajax/php/service-stage.php",
          {
            action: "delete",
            id: stageId,
          },
          function (response) {
            if (response.status === "success") {
              showAlert(
                "success",
                response.message || "Stage deleted successfully"
              );
              stagesTable.ajax.reload();
              if (onSuccess) onSuccess();
            } else {
              showAlert("error", response.message || "Failed to delete stage");
            }
          }
        ).fail(function () {
          showAlert("error", "An error occurred while deleting the stage");
        });
      }
    });
  }

  // Handle form submission
  $("#stageForm").on("submit", function (e) {
    e.preventDefault();
    const form = $(this);
    const submitBtn = $("#saveStageBtn");
    const originalBtnText = submitBtn.html();

    if (!form[0].checkValidity()) {
      e.stopPropagation();
      form.addClass("was-validated");
      return;
    }

    const formData = new FormData(this);
    formData.append("action", $("#stage_id").val() ? "update" : "create");
    if ($("#stage_id").val()) {
      formData.append("id", $("#stage_id").val());
    }

    // Disable button and show loading state
    submitBtn
      .prop("disabled", true)
      .html(
        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...'
      );

    $.ajax({
      url: "../../Ajax/php/service-stage.php",
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
          $("#stageModal").modal("hide");
          stagesTable.ajax.reload();
          form[0].reset();
          form.removeClass("was-validated");
          $("#stage_id").val("");
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
  $("#stageModal").on("hidden.bs.modal", function () {
    const form = $("#stageForm");
    form[0].reset();
    form.removeClass("was-validated");
    $("#stage_id").val("");
    $("#stageModalLabel").text("Add New Stage");
  });

  // Edit stage from table
  $(document).on("click", ".edit-stage", function (e) {
    e.stopPropagation();
    const stageId = $(this).data("id");

    loadStageData(stageId, function (data) {
      currentStageData = data;
      populateStageForm(data);
      $("#stageModalLabel").text("Edit Stage");
      $("#stageModal").modal("show");
    });
  });

  // Delete stage from table
  $(document).on("click", ".delete-stage", function (e) {
    e.stopPropagation();
    const stageId = $(this).data("id");
    deleteStage(stageId);
  });

  // View stage
  $(document).on("click", ".view-stage", function () {
    const stageId = $(this).data("id");

    loadStageData(stageId, function (data) {
      currentStageId = data.id;
      currentStageData = data;

      const detailsHtml = `
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Stage Name:</label>
            <p class="mb-0">${data.stage_name}</p>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Order:</label>
            <p class="mb-0">#${data.stage_order}</p>
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Icon:</label>
            <p class="mb-0">${
              data.icon
                ? `<span class="text-primary">${data.icon}</span>`
                : "N/A"
            }</p>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Estimated Duration:</label>
            <p class="mb-0">${
              data.estimated_duration
                ? `${data.estimated_duration} minutes`
                : "N/A"
            }</p>
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

      $("#stageDetails").html(detailsHtml);
      $("#viewStageModalLabel").text(`Stage Details - ${data.stage_name}`);
      $("#viewStageModal").modal("show");
    });
  });

  // Edit from view modal
  $(document).on("click", "#editFromViewStage", function () {
    if (!currentStageData) return;

    $("#viewStageModal").modal("hide");
    populateStageForm(currentStageData);
    $("#stageModalLabel").text("Edit Stage");
    $("#stageModal").modal("show");
  });

  // Delete from view modal
  $(document).on("click", "#deleteFromViewStage", function () {
    if (!currentStageId) return;

    deleteStage(currentStageId, function () {
      $("#viewStageModal").modal("hide");
      currentStageData = null;
      currentStageId = null;
    });
  });

  // Reset view modal
  $("#viewStageModal").on("hidden.bs.modal", function () {
    currentStageData = null;
    currentStageId = null;
    $("#stageDetails").empty();
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
