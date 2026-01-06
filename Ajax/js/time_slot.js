// Initialize DataTable with enhanced options
$(document).ready(function () {
  const timeSlotsTable = $("#timeSlotsTable").DataTable({
    ajax: {
      url: "../../Ajax/php/time_slot.php",
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
        data: "slot_start",
        render: function (data) {
          return formatTime(data);
        },
      },
      {
        data: "slot_end",
        render: function (data) {
          return formatTime(data);
        },
      },
      {
        data: "max_bookings",
        render: function (data) {
          return `<span class="badge bg-info text-dark">${data}</span>`;
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
                                  <div class="time-slot-actions">
                                      <button class="btn btn-sm btn-icon btn-outline-primary edit-slot" data-id="${row.id}" 
                                              title="Edit Slot">
                                          <i class="fas fa-edit"></i>
                                      </button>
                                      <button class="btn btn-sm btn-icon btn-outline-danger delete-slot" data-id="${row.id}" 
                                              title="Delete Slot">
                                          <i class="fas fa-trash"></i>
                                      </button>
                                  </div>`;
        },
      },
    ],
    order: [[1, "asc"]],
    responsive: true,
    language: {
      emptyTable:
        'No time slots found. Click "Add New Time Slot" to create one.',
      loadingRecords:
        '<div class="spinner-border text-primary" role="status"></div> Loading...',
      zeroRecords: "No matching time slots found",
      info: "Showing _START_ to _END_ of _TOTAL_ slots",
      infoEmpty: "Showing 0 to 0 of 0 slots",
      infoFiltered: "(filtered from _MAX_ total slots)",
      search: '<i class="fas fa-search"></i>',
      searchPlaceholder: "Search slots...",
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

  // Helper to format 24h time to 12h AM/PM
  function formatTime(timeString) {
    if (!timeString) return "";
    const [hours, minutes] = timeString.split(":");
    const h = parseInt(hours);
    const ampm = h >= 12 ? "PM" : "AM";
    const formattedHours = h % 12 || 12;
    return `${formattedHours}:${minutes} ${ampm}`;
  }

  // Handle form submission
  $("#timeSlotForm").on("submit", function (e) {
    e.preventDefault();

    const formData = {
      action: $("#slot_id").val() ? "update" : "create",
      id: $("#slot_id").val(),
      slot_start: $("#slot_start").val(),
      slot_end: $("#slot_end").val(),
      max_bookings: $("#max_bookings").val(),
      is_active: $("#is_active").is(":checked") ? 1 : 0,
    };

    const submitBtn = $("#saveSlotBtn");
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
      url: "../../Ajax/php/time_slot.php",
      type: "POST",
      data: formData,
      success: function (response) {
        if (response.status === "success") {
          showAlert(
            "success",
            response.message || "Operation completed successfully"
          );
          $("#timeSlotModal").modal("hide");
          timeSlotsTable.ajax.reload();
          $("#timeSlotForm")[0].reset();
          $("#timeSlotForm").removeClass("was-validated");
          $("#slot_id").val("");
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
  $("#timeSlotModal").on("hidden.bs.modal", function () {
    const form = $("#timeSlotForm");
    form[0].reset();
    form.removeClass("was-validated");
    $("#slot_id").val("");
    $("#timeSlotModalLabel").text("Add New Time Slot");
    $("#is_active").prop("checked", true);
  });

  // Edit slot
  $(document).on("click", ".edit-slot", function () {
    const slotId = $(this).data("id");

    $.get(
      "../../Ajax/php/time_slot.php",
      {
        action: "get",
        id: slotId,
      },
      function (response) {
        if (response.status === "success" && response.data) {
          const slot = response.data;
          fillTimeSlotForm(slot);
          $("#timeSlotModalLabel").text("Edit Time Slot");
          $("#timeSlotModal").modal("show");
        } else {
          showAlert(
            "error",
            response.message || "Failed to load time slot data"
          );
        }
      }
    ).fail(function () {
      showAlert("error", "An error occurred while loading time slot data");
    });
  });

  // Delete slot
  $(document).on("click", ".delete-slot", function () {
    const slotId = $(this).data("id");

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
          "../../Ajax/php/time_slot.php",
          {
            action: "delete",
            id: slotId,
          },
          function (response) {
            if (response.status === "success") {
              showAlert(
                "success",
                response.message || "Time slot deleted successfully"
              );
              timeSlotsTable.ajax.reload();
            } else {
              showAlert(
                "error",
                response.message || "Failed to delete time slot"
              );
            }
          }
        ).fail(function () {
          showAlert("error", "An error occurred while deleting the time slot");
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

  // Helper function to fill form for editing
  function fillTimeSlotForm(slot) {
    $("#slot_id").val(slot.id);
    $("#slot_start").val(slot.slot_start);
    $("#slot_end").val(slot.slot_end);
    $("#max_bookings").val(slot.max_bookings);
    $("#is_active").prop("checked", slot.is_active == 1);
  }
});
