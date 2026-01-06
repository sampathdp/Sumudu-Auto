$(document).ready(function () {
  let selectedPackage = null;
  let selectedDate = null;
  let selectedTime = null;
  let availableTimeSlots = [];

  // Initialize date picker
  initializeDatePicker();
  initializePackageSelection();
  initializeFormSubmission();

  // Initialize Flatpickr date picker
  function initializeDatePicker() {
    flatpickr("#bookingDate", {
      minDate: "today",
      maxDate: new Date().fp_incr(30),
      disable: [(date) => date.getDay() === 0], // Disable Sundays
      locale: { firstDayOfWeek: 1 },
      onChange: (selectedDates, dateStr) => {
        selectedDate = dateStr;
        loadTimeSlots(dateStr);
        updateSummary();
        validateForm();
      },
    });
  }

  // Package selection
  function initializePackageSelection() {
    $(".package-card").on("click", function () {
      $(".package-card").removeClass("selected");
      $(this).addClass("selected");

      selectedPackage = {
        id: $(this).data("package-id"),
        name: $(this).find(".package-name").text(),
        price: parseFloat($(this).data("price")),
        duration: parseInt($(this).data("duration")),
      };

      updateSummary();
      validateForm();
    });
  }

  // Load available time slots
  function loadTimeSlots(date) {
    const container = $("#timeSlots");
    container.html(
      '<div class="text-center"><div class="spinner-border spinner-border-sm"></div> Loading...</div>'
    );

    $.get(
      "../../Ajax/php/booking.php",
      {
        action: "get_time_slots",
        date: date,
      },
      function (response) {
        if (response.status === "success" && response.data) {
          availableTimeSlots = response.data;
          renderTimeSlots();
        } else {
          container.html(
            '<p class="text-danger">Failed to load time slots</p>'
          );
        }
      }
    ).fail(function () {
      container.html('<p class="text-danger">Error loading time slots</p>');
    });
  }

  // Render time slots
  function renderTimeSlots() {
    const container = $("#timeSlots");
    container.empty();

    if (availableTimeSlots.length === 0) {
      container.html(
        '<p class="text-muted">No available time slots for this date</p>'
      );
      return;
    }

    availableTimeSlots.forEach((slot) => {
      const slotElement = $('<div class="time-slot"></div>');
      slotElement.text(slot.time);

      if (slot.available_slots <= 0) {
        slotElement.addClass("disabled");
        slotElement.attr("title", "Fully booked");
      } else {
        slotElement.on("click", function () {
          selectTimeSlot($(this), slot.time_24h || slot.time);
        });
        slotElement.attr(
          "title",
          `${slot.available_slots} slot${
            slot.available_slots > 1 ? "s" : ""
          } available`
        );
      }

      container.append(slotElement);
    });
  }

  // Select time slot
  function selectTimeSlot(element, time) {
    $(".time-slot:not(.disabled)").removeClass("selected");
    element.addClass("selected");
    selectedTime = time;

    updateSummary();
    validateForm();
  }

  // Update summary
  function updateSummary() {
    const summaryCard = $("#summaryCard");

    if (selectedPackage && selectedDate && selectedTime) {
      summaryCard.show();

      $("#sumPackage").text(selectedPackage.name);
      $("#sumDateTime").text(`${selectedDate} at ${selectedTime}`);
      $("#sumDuration").text(`${selectedPackage.duration} minutes`);
      $("#sumTotal").text(`LKR ${selectedPackage.price.toFixed(2)}`);
    } else {
      summaryCard.hide();
    }
  }

  // Validate form
  function validateForm() {
    const name = $("#customerName").val().trim();
    const mobile = $("#customerMobile").val().trim();
    const submitBtn = $("#submitBtn");

    const isValid =
      name &&
      mobile.length === 9 &&
      selectedPackage &&
      selectedDate &&
      selectedTime;

    submitBtn.prop("disabled", !isValid);
  }

  // Mobile input validation
  $("#customerMobile").on("input", function () {
    // Only allow digits
    const value = $(this).val().replace(/\D/g, "").substring(0, 9);
    $(this).val(value);
    validateForm();
  });

  $("#customerName").on("input", function () {
    validateForm();
  });

  // Form submission
  function initializeFormSubmission() {
    $("#bookingForm").on("submit", function (e) {
      e.preventDefault();

      const formData = {
        action: "create",
        customer_name: $("#customerName").val().trim(),
        customer_mobile: "94" + $("#customerMobile").val().trim(),
        customer_email: $("#customerEmail").val().trim(),
        service_package_id: selectedPackage.id,
        booking_date: selectedDate,
        booking_time: selectedTime,
        estimated_duration: selectedPackage.duration,
        notes: $("#bookingNotes").val().trim(),
        total_amount: selectedPackage.price,
      };

      const submitBtn = $("#submitBtn");
      const originalBtnText = submitBtn.html();

      // Show loading state
      submitBtn
        .prop("disabled", true)
        .html(
          '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Submitting...'
        );

      $("#bookingForm").hide();
      $("#loadingState").show();

      $.ajax({
        url: "../../Ajax/php/booking.php",
        type: "POST",
        data: formData,
        success: function (response) {
          if (response.status === "success") {
            showSuccess(response.booking_number);
          } else {
            showError(response.message || "Failed to submit booking");
          }
        },
        error: function (xhr) {
          const errorMsg =
            xhr.responseJSON && xhr.responseJSON.message
              ? xhr.responseJSON.message
              : "Network error. Please try again.";
          showError(errorMsg);
        },
        complete: function () {
          submitBtn.prop("disabled", false).html(originalBtnText);
        },
      });
    });
  }

  // Show success message
  function showSuccess(bookingNumber) {
    $("#loadingState").hide();
    $("#successMessage").show();

    $("#successDetails").html(`
      Your booking request <strong>#${bookingNumber}</strong> has been submitted.<br>
      <small>We'll send you a confirmation message shortly.</small>
    `);

    setTimeout(() => location.reload(), 5000);
  }

  // Show error message
  function showError(message) {
    $("#loadingState").hide();
    $("#bookingForm").show();

    showAlert("error", message);
  }

  // Helper function to show alerts using SweetAlert2
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

// Utility functions
window.BookingUtils = {
  formatTime(time) {
    const [hours, minutes] = time.split(":");
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? "PM" : "AM";
    const displayHour = hour > 12 ? hour - 12 : hour === 0 ? 12 : hour;
    return `${displayHour}:${minutes} ${ampm}`;
  },

  formatDate(date) {
    const options = {
      weekday: "short",
      year: "numeric",
      month: "short",
      day: "numeric",
    };
    return new Date(date).toLocaleDateString("en-US", options);
  },

  validateMobile(mobile) {
    return /^94\d{9}$/.test(mobile);
  },

  isWorkingDay(date) {
    return new Date(date).getDay() !== 0; // Not Sunday
  },

  getStatusBadge(status) {
    const classes = {
      pending_approval: "warning",
      approved: "success",
      rejected: "danger",
      cancelled: "secondary",
      completed: "info",
    };

    const labels = {
      pending_approval: "Pending",
      approved: "Approved",
      rejected: "Rejected",
      cancelled: "Cancelled",
      completed: "Completed",
    };

    const cssClass = classes[status] || "secondary";
    const label = labels[status] || status;

    return `<span class="badge bg-${cssClass}">${label}</span>`;
  },
};
