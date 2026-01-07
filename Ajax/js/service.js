$(document).ready(function () {
  let selectedCustomer = null;
  let selectedVehicle = null;
  let selectedPackage = null;
  let selectedEmployee = null;
  let allStages = null; // Initialize to null to distinguish loading vs empty

  // Load initial data
  loadCustomers();
  loadPackages();
  loadEmployees(); // Load employees for assignment

  // Load stages first, then services to ensure stages are available for dropdowns
  loadStages().always(function () {
    loadServices();
  });

  // Load customers
  function loadCustomers() {
    $.get(
      BASE_URL + "Ajax/php/customer.php",
      { action: "list" },
      function (response) {
        if (response.status === "success" && response.data) {
          const select = $("#customer_id");
          select
            .empty()
            .append('<option value="">Choose a customer...</option>');
          response.data.forEach((customer) => {
            select.append(
              `<option value="${customer.id}" data-customer='${JSON.stringify(
                customer
              )}'>${customer.name} - ${customer.phone}</option>`
            );
          });
        }
      }
    );
  }

  // Load vehicles for selected customer
  function loadVehicles(customerId) {
    $.get(
      BASE_URL + "Ajax/php/vehicle.php",
      { action: "list", customer_id: customerId },
      function (response) {
        if (response.status === "success" && response.data) {
          const select = $("#vehicle_id");
          select
            .empty()
            .append('<option value="">Choose a vehicle...</option>');
          response.data.forEach((vehicle) => {
            select.append(
              `<option value="${vehicle.id}" data-vehicle='${JSON.stringify(
                vehicle
              )}'>${vehicle.make} ${vehicle.model} - ${
                vehicle.registration_number
              }</option>`
            );
          });
        }
      }
    );
  }

  // Store packages data and selected packages for multi-select
  let allPackages = [];
  let selectedPackages = [];

  // Load packages
  function loadPackages() {
    console.log(
      "Loading packages from:",
      BASE_URL + "Ajax/php/service-package.php"
    );
    $.get(
      BASE_URL + "Ajax/php/service-package.php",
      { action: "list" },
      function (response) {
        console.log("Packages response:", response);
        if (response.status === "success" && response.data) {
          allPackages = response.data.filter((pkg) => pkg.is_active);
          console.log("Active packages loaded:", allPackages.length);

          // Populate the package selector dropdown
          const select = $("#package_selector");
          select
            .empty()
            .append('<option value="">Choose a package...</option>');
          allPackages.forEach((pkg) => {
            select.append(
              `<option value="${pkg.id}" data-package='${JSON.stringify(
                pkg
              )}'>${pkg.package_name} - LKR ${parseFloat(
                pkg.base_price
              ).toFixed(2)}</option>`
            );
          });

          // Also populate legacy #package_id if it exists (backward compatibility)
          const legacySelect = $("#package_id");
          if (legacySelect.length) {
            legacySelect
              .empty()
              .append('<option value="">Choose a package...</option>');
            allPackages.forEach((pkg) => {
              legacySelect.append(
                `<option value="${pkg.id}" data-package='${JSON.stringify(
                  pkg
                )}'>${pkg.package_name} - LKR ${parseFloat(
                  pkg.base_price
                ).toFixed(2)}</option>`
              );
            });
          }
        } else {
          console.error(
            "Failed to load packages:",
            response.message || "No data"
          );
        }
      }
    ).fail(function (xhr, status, error) {
      console.error("Package load AJAX error:", status, error);
      console.error("Response:", xhr.responseText);
    });
  }

  // Enable/disable Add Package button based on selection
  $(document).on("change", "#package_selector", function () {
    $("#addPackageBtn").prop("disabled", !$(this).val());
  });

  // Add package to selection
  $(document).on("click", "#addPackageBtn", function () {
    console.log("Add Package button clicked");
    console.log("allPackages array:", allPackages);

    const select = $("#package_selector");
    const pkgId = parseInt(select.val());
    console.log("Selected package ID:", pkgId);

    if (!pkgId) {
      console.log("No package ID selected, returning");
      return;
    }

    // Check if already added
    if (selectedPackages.find((p) => parseInt(p.id) === pkgId)) {
      showAlert("warning", "This package is already added");
      return;
    }

    // Find package data
    const pkg = allPackages.find((p) => parseInt(p.id) === pkgId);
    console.log("Found package:", pkg);

    if (!pkg) {
      console.error("Package not found in allPackages array!");
      return;
    }

    selectedPackages.push({
      id: pkg.id,
      name: pkg.package_name,
      description: pkg.description || "",
      price: parseFloat(pkg.base_price),
    });

    renderSelectedPackages();
    select.val(""); // Reset dropdown
    $("#addPackageBtn").prop("disabled", true);
    updateSummary();
  });

  // Remove package from selection
  $(document).on("click", ".remove-package-btn", function () {
    const pkgId = parseInt($(this).data("pkg-id"));
    selectedPackages = selectedPackages.filter((p) => p.id !== pkgId);
    renderSelectedPackages();
    updateSummary();
  });

  // Handle price change
  $(document).on("input change", ".pkg-price-input", function () {
    const pkgId = parseInt($(this).data("pkg-id"));
    const newPrice = parseFloat($(this).val()) || 0;

    // Update package in array
    const pkgIndex = selectedPackages.findIndex((p) => p.id === pkgId);
    if (pkgIndex !== -1) {
      selectedPackages[pkgIndex].price = newPrice;

      // Update validation/totals without re-rendering the whole table (to keep focus)
      let total = 0;
      selectedPackages.forEach((p) => (total += p.price));
      $("#packagesTotal").text(`LKR ${total.toFixed(2)}`);

      // Update hidden input
      const packageData = selectedPackages.map((p) => ({
        id: p.id,
        price: p.price,
      }));
      $("#selected_packages").val(JSON.stringify(packageData));

      updateSummary();
    }
  });

  // Render selected packages table
  function renderSelectedPackages() {
    const tbody = $("#selectedPackagesBody");
    tbody.empty();

    if (selectedPackages.length === 0) {
      tbody.html(`
        <tr id="noPackagesRow">
          <td colspan="4" class="text-center text-muted py-4">
            <i class="fas fa-info-circle me-2"></i>No packages added yet. Select a package above.
          </td>
        </tr>
      `);
      $("#packagesTotal").text("LKR 0.00");
      $("#packagesError").hide();
    } else {
      let total = 0;
      selectedPackages.forEach((pkg) => {
        total += pkg.price;
        tbody.append(`
          <tr data-pkg-id="${pkg.id}">
            <td>${pkg.name}</td>
            <td>${pkg.description || "-"}</td>
            <td class="text-end" style="width: 150px;">
              <input type="number" class="form-control form-control-sm text-end pkg-price-input" 
                     value="${pkg.price.toFixed(
                       2
                     )}" min="0" step="0.01" data-pkg-id="${pkg.id}">
            </td>
            <td class="text-center">
              <button type="button" class="btn btn-sm btn-outline-danger remove-package-btn" data-pkg-id="${
                pkg.id
              }">
                <i class="fas fa-times"></i>
              </button>
            </td>
          </tr>
        `);
      });
      $("#packagesTotal").text(`LKR ${total.toFixed(2)}`);
      $("#packagesError").hide();
    }

    // Update hidden input with selected packages (ids + prices)
    const packageData = selectedPackages.map((p) => ({
      id: p.id,
      price: p.price,
    }));
    $("#selected_packages").val(JSON.stringify(packageData));
  }

  // Load service stages
  function loadStages() {
    return $.get(
      BASE_URL + "Ajax/php/service-stage.php",
      { action: "list" },
      function (response) {
        if (response.status === "success" && response.data) {
          allStages = response.data;
        } else {
          allStages = []; // Ensure it's empty array if no data
        }
      }
    ).fail(function () {
      console.error("Failed to load service stages");
      allStages = []; // Handle failure
    });
  }

  // Load employees for assignment
  function loadEmployees() {
    $.get(
      BASE_URL + "Ajax/php/employee.php",
      { action: "list", active_only: "1" },
      function (response) {
        if (response.status === "success" && response.data) {
          const select = $("#employee_id");
          select
            .empty()
            .append('<option value="">Select an employee (optional)</option>');
          response.data.forEach((emp) => {
            const name = `${emp.first_name} ${emp.last_name}`;
            const position = emp.position ? ` - ${emp.position}` : "";
            select.append(
              `<option value="${emp.id}" data-employee='${JSON.stringify(
                emp
              )}'>${name}${position}</option>`
            );
          });
        }
      }
    ).fail(function () {
      console.error("Failed to load employees");
    });
  }

  // Customer selection
  $("#customer_id").on("change", function () {
    const selectedOption = $(this).find("option:selected");
    if (selectedOption.val()) {
      selectedCustomer = JSON.parse(selectedOption.attr("data-customer"));
      $("#customer_name_display").text(selectedCustomer.name);
      $("#customer_phone_display").text(selectedCustomer.phone);
      $("#customer_email_display").text(selectedCustomer.email || "N/A");
      $("#customerDetails").slideDown();
      loadVehicles(selectedCustomer.id);
      updateSummary();
    } else {
      selectedCustomer = null;
      $("#customerDetails").slideUp();
      $("#vehicle_id")
        .empty()
        .append('<option value="">Choose a vehicle...</option>');
      updateSummary();
    }
  });

  // Vehicle selection
  $("#vehicle_id").on("change", function () {
    const selectedOption = $(this).find("option:selected");
    if (selectedOption.val()) {
      selectedVehicle = JSON.parse(selectedOption.attr("data-vehicle"));
      $("#vehicle_make_model_display").text(
        `${selectedVehicle.make} ${selectedVehicle.model}`
      );
      $("#vehicle_reg_display").text(selectedVehicle.registration_number);
      $("#vehicle_year_display").text(selectedVehicle.year || "N/A");
      $("#vehicleDetails").slideDown();
      updateSummary();
    } else {
      selectedVehicle = null;
      $("#vehicleDetails").slideUp();
      updateSummary();
    }
  });

  // ============================================
  // Quick Add Both (Customer + Vehicle) Handler
  // ============================================
  $(document).on("click", "#saveQuickBoth", function () {
    const btn = $(this);

    // Validate required fields
    const customerName = $("#both_customer_name").val().trim();
    const customerPhone = $("#both_customer_phone").val().trim();
    const vehicleMake = $("#both_vehicle_make").val().trim();
    const vehicleModel = $("#both_vehicle_model").val().trim();
    const vehicleReg = $("#both_vehicle_registration").val().trim();

    if (
      !customerName ||
      !customerPhone ||
      !vehicleMake ||
      !vehicleModel ||
      !vehicleReg
    ) {
      showAlert("error", "Please fill in all required fields");
      return;
    }

    btn
      .prop("disabled", true)
      .html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

    // Step 1: Create Customer
    $.post(BASE_URL + "Ajax/php/customer.php", {
      action: "create",
      name: customerName,
      phone: customerPhone,
      email: $("#both_customer_email").val().trim(),
    })
      .done(function (customerResponse) {
        if (customerResponse.status === "success") {
          // Customer API returns id directly, not in data object
          const newCustomerId = customerResponse.id;

          // Step 2: Create Vehicle linked to new customer
          $.post(BASE_URL + "Ajax/php/vehicle.php", {
            action: "create",
            customer_id: newCustomerId,
            make: vehicleMake,
            model: vehicleModel,
            registration_number: vehicleReg,
            year: $("#both_vehicle_year").val() || null,
            current_mileage: $("#both_vehicle_mileage").val() || 0,
          })
            .done(function (vehicleResponse) {
              if (vehicleResponse.status === "success") {
                // Vehicle API returns id directly, not in data object
                const newVehicleId = vehicleResponse.id;

                // Close modal
                bootstrap.Modal.getInstance(
                  document.getElementById("quickAddBothModal")
                ).hide();

                // Reset form
                $("#quickAddBothForm")[0].reset();

                showAlert(
                  "success",
                  "Customer & Vehicle created successfully!"
                );

                // Reload customers dropdown and auto-select the new one
                $.get(
                  BASE_URL + "Ajax/php/customer.php",
                  { action: "list" },
                  function (resp) {
                    if (resp.status === "success" && resp.data) {
                      const select = $("#customer_id");
                      select
                        .empty()
                        .append(
                          '<option value="">Choose a customer...</option>'
                        );
                      resp.data.forEach((customer) => {
                        select.append(
                          `<option value="${
                            customer.id
                          }" data-customer='${JSON.stringify(customer)}'>${
                            customer.name
                          } - ${customer.phone}</option>`
                        );
                      });

                      // Auto-select new customer
                      select.val(newCustomerId).trigger("change");

                      // Wait for vehicles to load, then auto-select the new vehicle
                      setTimeout(function () {
                        $("#vehicle_id").val(newVehicleId).trigger("change");

                        // ====== JUMP TO STEP 3 (Package Selection) ======
                        // Mark steps 1 and 2 as completed
                        $('[data-step="1"]')
                          .removeClass("active")
                          .addClass("completed");
                        $('[data-step="2"]')
                          .removeClass("active")
                          .addClass("completed");
                        $('[data-step="3"]').addClass("active");

                        // Hide step 1 and 2 forms, show step 3
                        $("#step1").hide();
                        $("#step2").hide();
                        $("#step3").addClass("animate-fade-in").show();

                        updateSummary();
                      }, 500);
                    }
                  }
                );
              } else {
                showAlert(
                  "error",
                  vehicleResponse.message || "Failed to create vehicle"
                );
              }
            })
            .fail(function () {
              showAlert("error", "Error creating vehicle");
            })
            .always(function () {
              btn
                .prop("disabled", false)
                .html('<i class="fas fa-save me-1"></i> Save & Go to Packages');
            });
        } else {
          showAlert(
            "error",
            customerResponse.message || "Failed to create customer"
          );
          btn
            .prop("disabled", false)
            .html('<i class="fas fa-save me-1"></i> Save & Go to Packages');
        }
      })
      .fail(function () {
        showAlert("error", "Error creating customer");
        btn
          .prop("disabled", false)
          .html('<i class="fas fa-save me-1"></i> Save & Go to Packages');
      });
  });

  // Package selection
  $("#package_id").on("change", function () {
    const selectedOption = $(this).find("option:selected");
    if (selectedOption.val()) {
      selectedPackage = JSON.parse(selectedOption.attr("data-package"));
      $("#package_name_display").text(selectedPackage.package_name);
      $("#package_price_display").text(
        `LKR ${parseFloat(selectedPackage.base_price).toFixed(2)}`
      );
      $("#package_duration_display").text(
        `${selectedPackage.estimated_duration} minutes`
      );
      $("#package_description_display").text(
        selectedPackage.description || "No description"
      );
      $("#packageDetails").slideDown();
      $("#override_price").val(selectedPackage.base_price);
      $("#override_duration").val(selectedPackage.estimated_duration);
      $("#overrideSection").slideDown();
      updateSummary();
    } else {
      selectedPackage = null;
      $("#packageDetails").slideUp();
      $("#overrideSection").slideUp();
      updateSummary();
    }
  });

  // Update summary panel
  function updateSummary() {
    let summaryHtml = "";
    if (selectedCustomer) {
      summaryHtml += `
        <div class="mb-3">
          <h6 class="text-muted mb-2">Customer</h6>
          <p class="mb-0"><strong>${selectedCustomer.name}</strong></p>
          <p class="mb-0 small text-muted">${selectedCustomer.phone}</p>
        </div>
      `;
    }
    if (selectedVehicle) {
      summaryHtml += `
        <div class="mb-3">
          <h6 class="text-muted mb-2">Vehicle</h6>
          <p class="mb-0"><strong>${selectedVehicle.make} ${selectedVehicle.model}</strong></p>
          <p class="mb-0 small text-muted">${selectedVehicle.registration_number}</p>
        </div>
      `;
    }
    if (selectedPackage) {
      const price = $("#override_price").val() || selectedPackage.base_price;
      summaryHtml += `
        <div class="mb-3">
          <h6 class="text-muted mb-2">Package</h6>
          <p class="mb-0"><strong>${selectedPackage.package_name}</strong></p>
          <p class="mb-0 small text-muted">LKR ${parseFloat(price).toFixed(
            2
          )}</p>
        </div>
      `;
    }

    if (summaryHtml) {
      $("#jobSummary").html(summaryHtml);
    } else {
      $("#jobSummary").html(
        '<p class="text-muted text-center py-3">Fill in the form to see job summary</p>'
      );
    }
  }

  // Step navigation
  $(".next-step").on("click", function () {
    const nextStep = $(this).data("next");
    const currentStep = $(this).closest(".form-step");

    // Validate current step
    let isValid = true;
    currentStep.find("input, select, textarea").each(function () {
      if ($(this).prop("required") && !$(this).val()) {
        isValid = false;
        $(this).addClass("is-invalid");
      } else {
        $(this).removeClass("is-invalid");
      }
    });

    if (!isValid) {
      showAlert("error", "Please fill in all required fields");
      return;
    }

    // Update step indicators
    $(`.step[data-step="${nextStep - 1}"]`)
      .removeClass("active")
      .addClass("completed");
    $(`.step[data-step="${nextStep}"]`).addClass("active");

    // Show next step
    currentStep.fadeOut(200, function () {
      $(`#step${nextStep}`).fadeIn(200);
    });

    // Update summary for confirmation step
    if (nextStep === 4) {
      updateConfirmationSummary();
    }
  });

  $(".prev-step").on("click", function () {
    const prevStep = $(this).data("prev");
    const currentStep = $(this).closest(".form-step");

    // Update step indicators
    $(`.step[data-step="${prevStep + 1}"]`).removeClass("active");
    $(`.step[data-step="${prevStep}"]`)
      .addClass("active")
      .removeClass("completed");

    // Show previous step
    currentStep.fadeOut(200, function () {
      $(`#step${prevStep}`).fadeIn(200);
    });
  });

  // Update confirmation summary
  function updateConfirmationSummary() {
    // Calculate totals from selected packages
    const totalPrice = selectedPackages.reduce(
      (sum, pkg) => sum + pkg.price,
      0
    );
    const packageNames =
      selectedPackages.map((p) => p.name).join(", ") || "No packages selected";
    const packageCount = selectedPackages.length;

    // Get selected employee name
    const employeeSelect = $("#employee_id");
    const employeeName = employeeSelect.val()
      ? employeeSelect.find("option:selected").text()
      : "Not Assigned";

    // Build packages list HTML
    let packagesListHtml = "";
    if (selectedPackages.length > 0) {
      packagesListHtml = '<ul class="list-unstyled mb-0">';
      selectedPackages.forEach((pkg) => {
        packagesListHtml += `<li><small>• ${pkg.name} - LKR ${pkg.price.toFixed(
          2
        )}</small></li>`;
      });
      packagesListHtml += "</ul>";
    } else {
      packagesListHtml =
        '<p class="mb-0 text-warning">No packages selected</p>';
    }

    let summaryHtml = `
      <div class="row">
        <div class="col-md-6 mb-3">
          <h6 class="text-primary"><i class="fas fa-user me-2"></i>Customer</h6>
          <p class="mb-1"><strong>${selectedCustomer.name}</strong></p>
          <p class="mb-1 small">${selectedCustomer.phone}</p>
          <p class="mb-0 small">${selectedCustomer.email || "N/A"}</p>
        </div>
        <div class="col-md-6 mb-3">
          <h6 class="text-success"><i class="fas fa-car me-2"></i>Vehicle</h6>
          <p class="mb-1"><strong>${selectedVehicle.make} ${
      selectedVehicle.model
    }</strong></p>
          <p class="mb-1 small">${selectedVehicle.registration_number}</p>
          <p class="mb-0 small">Year: ${selectedVehicle.year || "N/A"}</p>
        </div>
        <div class="col-md-6 mb-3">
          <h6 class="text-warning"><i class="fas fa-boxes me-2"></i>Service Packages (${packageCount})</h6>
          ${packagesListHtml}
          <p class="mt-2 mb-0"><strong>Total: LKR ${totalPrice.toFixed(
            2
          )}</strong></p>
        </div>
        <div class="col-md-6 mb-3">
          <h6 class="text-secondary"><i class="fas fa-user-cog me-2"></i>Assigned Employee</h6>
          <p class="mb-0"><strong>${employeeName}</strong></p>
        </div>
        <div class="col-12">
          <h6 class="text-info"><i class="fas fa-sticky-note me-2"></i>Notes</h6>
          <p class="mb-0 small">${
            $("#notes").val() || "No additional notes"
          }</p>
        </div>
      </div>
    `;
    $("#summaryDetails").html(summaryHtml);
  }

  // Form submission
  $("#jobForm").on("submit", function (e) {
    e.preventDefault();

    // Validate at least one package is selected
    if (selectedPackages.length === 0) {
      $("#packagesError").show();
      showAlert("error", "Please add at least one service package");
      return;
    }

    const submitBtn = $("#submitJobBtn");
    const originalBtnText = submitBtn.html();

    // Disable button and show loading state
    submitBtn
      .prop("disabled", true)
      .html(
        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Creating Job...'
      );

    // Calculate total from selected packages
    const totalAmount = selectedPackages.reduce(
      (sum, pkg) => sum + pkg.price,
      0
    );
    const packageIds = selectedPackages.map((p) => p.id);

    const formData = {
      action: "create_multi",
      customer_id: selectedCustomer.id,
      vehicle_id: selectedVehicle.id,
      package_ids: JSON.stringify(
        selectedPackages.map((p) => ({
          id: p.id,
          price: p.price,
        }))
      ),
      total_amount: totalAmount,
      notes: $("#notes").val(),
      employee_id: $("#employee_id").val() || null,
    };

    $.ajax({
      url: BASE_URL + "Ajax/php/service.php",
      type: "POST",
      data: formData,
      success: function (response) {
        if (response.status === "success") {
          // Redirect to details page
          window.location.href = `job_details.php?id=${response.data.id}`;
        } else {
          showAlert("error", response.message || "Failed to create job");
          submitBtn.prop("disabled", false).html(originalBtnText);
        }
      },
      error: function (xhr) {
        const errorMsg =
          xhr.responseJSON && xhr.responseJSON.message
            ? xhr.responseJSON.message
            : "An error occurred while creating the job";
        showAlert("error", errorMsg);
        submitBtn.prop("disabled", false).html(originalBtnText);
      },
    });
  });

  // Quick add customer
  $("#saveQuickCustomer").on("click", function () {
    const name = $("#quick_customer_name").val().trim();
    const phone = $("#quick_customer_phone").val().trim();
    const email = $("#quick_customer_email").val().trim();

    if (!name || !phone) {
      showAlert("error", "Please fill in required fields");
      return;
    }

    const btn = $(this);
    const originalText = btn.html();
    btn
      .prop("disabled", true)
      .html('<span class="spinner-border spinner-border-sm"></span>');

    $.ajax({
      url: BASE_URL + "Ajax/php/customer.php",
      type: "POST",
      data: {
        action: "create",
        name: name,
        phone: phone,
        email: email,
      },
      success: function (response) {
        if (response.status === "success") {
          showAlert("success", "Customer added successfully");
          $("#addCustomerModal").modal("hide");
          $("#quickAddCustomerForm")[0].reset();
          loadCustomers();
          setTimeout(() => {
            $("#customer_id").val(response.data.id).trigger("change");
          }, 500);
        } else {
          showAlert("error", response.message || "Failed to add customer");
        }
      },
      error: function () {
        showAlert("error", "An error occurred while adding customer");
      },
      complete: function () {
        btn.prop("disabled", false).html(originalText);
      },
    });
  });

  // Quick add vehicle
  $("#saveQuickVehicle").on("click", function () {
    if (!selectedCustomer) {
      showAlert("error", "Please select a customer first");
      return;
    }

    const make = $("#quick_vehicle_make").val().trim();
    const model = $("#quick_vehicle_model").val().trim();
    const registration = $("#quick_vehicle_registration").val().trim();
    const year = $("#quick_vehicle_year").val().trim();
    const mileage = $("#quick_vehicle_mileage").val().trim();

    if (!make || !model || !registration) {
      showAlert("error", "Please fill in required fields");
      return;
    }

    const btn = $(this);
    const originalText = btn.html();
    btn
      .prop("disabled", true)
      .html('<span class="spinner-border spinner-border-sm"></span>');

    $.ajax({
      url: BASE_URL + "Ajax/php/vehicle.php",
      type: "POST",
      data: {
        action: "create",
        customer_id: selectedCustomer.id,
        make: make,
        model: model,
        registration_number: registration,
        year: year,
        current_mileage: mileage,
      },
      success: function (response) {
        if (response.status === "success") {
          showAlert("success", "Vehicle added successfully");
          $("#addVehicleModal").modal("hide");
          $("#quickAddVehicleForm")[0].reset();
          loadVehicles(selectedCustomer.id);
          setTimeout(() => {
            $("#vehicle_id").val(response.data.id).trigger("change");
          }, 500);
        } else {
          showAlert("error", response.message || "Failed to add vehicle");
        }
      },
      error: function () {
        showAlert("error", "An error occurred while adding vehicle");
      },
      complete: function () {
        btn.prop("disabled", false).html(originalText);
      },
    });
  });

  // Handle date filter change
  $("#dateFilter").on("change", function () {
    if ($(this).val() === "custom") {
      $("#customDateRange").show();
    } else {
      $("#customDateRange").hide();
    }
  });

  // Apply filters button click handler
  $("#applyFilters").on("click", function () {
    loadServices();
  });

  // Load services with filters
  function loadServices() {
    const dateFilter = $("#dateFilter").val();
    const statusFilter = $("#statusFilter").val();

    let params = { action: "list" };

    // Set date filter parameters
    const today = new Date();
    const startOfDay = new Date(today);
    startOfDay.setHours(0, 0, 0, 0);

    switch (dateFilter) {
      case "today":
        params.date_filter = "today";
        params.date = today.toISOString().split("T")[0];
        break;
      case "yesterday":
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        params.date_filter = "date";
        params.date = yesterday.toISOString().split("T")[0];
        break;
      case "this_week":
        const startOfWeek = new Date(today);
        startOfWeek.setDate(today.getDate() - today.getDay()); // Start of current week (Sunday)
        params.date_filter = "range";
        params.start_date = startOfWeek.toISOString().split("T")[0];
        params.end_date = today.toISOString().split("T")[0];
        break;
      case "last_week":
        const lastWeekStart = new Date(today);
        lastWeekStart.setDate(today.getDate() - today.getDay() - 7); // Start of last week
        const lastWeekEnd = new Date(lastWeekStart);
        lastWeekEnd.setDate(lastWeekStart.getDate() + 6); // End of last week
        params.date_filter = "range";
        params.start_date = lastWeekStart.toISOString().split("T")[0];
        params.end_date = lastWeekEnd.toISOString().split("T")[0];
        break;
      case "this_month":
        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        params.date_filter = "range";
        params.start_date = startOfMonth.toISOString().split("T")[0];
        params.end_date = today.toISOString().split("T")[0];
        break;
      case "last_month":
        const firstDayLastMonth = new Date(
          today.getFullYear(),
          today.getMonth() - 1,
          1
        );
        const lastDayLastMonth = new Date(
          today.getFullYear(),
          today.getMonth(),
          0
        );
        params.date_filter = "range";
        params.start_date = firstDayLastMonth.toISOString().split("T")[0];
        params.end_date = lastDayLastMonth.toISOString().split("T")[0];
        break;
      case "custom":
        const startDate = $("#startDate").val();
        const endDate = $("#endDate").val();
        if (startDate && endDate) {
          params.date_filter = "range";
          params.start_date = startDate;
          params.end_date = endDate;
        }
        break;
      // 'all' case - no date filter
    }

    // Add status filter if selected
    if (statusFilter) {
      params.status = statusFilter;
    }

    // Show loading state
    const container = $("#jobsContainer");
    container.html(`
      <div class="col-12 text-center py-4">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 mb-0 text-muted">Loading jobs...</p>
      </div>
    `);

    // Make the AJAX request
    $.get(BASE_URL + "Ajax/php/service.php", params, function (response) {
      const container = $("#jobsContainer");
      if (
        response.status === "success" &&
        response.data &&
        response.data.length > 0
      ) {
        let jobsHtml = "";

        response.data.forEach((job) => {
          const progress = job.progress_percentage || 0;

          // Status badge class
          const statusInfo = {
            waiting: { text: "Waiting", class: "status-waiting" },
            in_progress: { text: "In Progress", class: "status-in-progress" },
            quality_check: {
              text: "Quality Check",
              class: "status-in-progress",
            },
            completed: { text: "Completed", class: "status-completed" },
            delivered: { text: "Delivered", class: "status-delivered" },
            cancelled: { text: "Cancelled", class: "badge-danger" },
          };

          const current = statusInfo[job.status] || statusInfo.waiting;

          jobsHtml += `
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="job-card">
                    <div class="card-header">
                        <div>
                            <span class="job-number">${job.job_number}</span>
                            <span class="job-date">${new Date(
                              job.created_at
                            ).toLocaleDateString()}</span>
                        </div>
                        <span class="status-badge ${current.class}">${
            current.text
          }</span>
                    </div>

                    <div class="card-body">
                        <!-- Customer Info -->
                        <div class="info-row">
                            <div class="info-icon icon-user">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="info-content">
                                <h6>${job.customer_name || "N/A"}</h6>
                                <p>${job.customer_phone || ""}</p>
                            </div>
                        </div>

                        <!-- Vehicle Info -->
                        <div class="info-row">
                            <div class="info-icon icon-car">
                                <i class="fas fa-car"></i>
                            </div>
                            <div class="info-content">
                                <h6>${job.make} ${job.model}</h6>
                                <p>${job.registration_number}</p>
                            </div>
                        </div>

                        <!-- Packages -->
                        ${
                          job.packages && job.packages.length > 0
                            ? `
                        <div class="info-row">
                            <div class="info-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="info-content">
                                <h6 style="font-size: 0.85rem;">${
                                  job.packages.length > 1
                                    ? "Packages"
                                    : "Package"
                                }</h6>
                                <p style="font-size: 0.8rem; color: #64748b;">${
                                  job.packages_text || "N/A"
                                }</p>
                            </div>
                        </div>
                        `
                            : ""
                        }

                        <!-- Package & Price -->
                        <div class="price-section">
                            <span class="service-price">LKR ${parseFloat(
                              job.total_amount
                            ).toFixed(2)}</span>
                        </div>
                    </div>

                    <div class="card-footer">
                        <!-- Progress Bar -->
                        <div class="progress-container">
                            <div class="progress-label">
                                <span>Completion</span>
                                <span>${progress}%</span>
                            </div>
                            <div class="custom-progress">
                                <div class="bar" style="width: ${progress}%"></div>
                            </div>
                        </div>

                        <!-- Stage Select -->
                        <div class="mb-3">
                            <select class="form-select status-select" 
                                    data-job-id="${job.id}" 
                                    data-current-stage="${
                                      job.current_stage_id || 1
                                    }"
                                    ${
                                      ["delivered", "cancelled"].includes(
                                        job.status
                                      )
                                        ? "disabled"
                                        : ""
                                    }>
                                ${generateStageOptions(
                                  job.current_stage_id || 1
                                )}
                            </select>
                        </div>

                        <a href="job_details.php?id=${
                          job.id
                        }" class="btn-view-details">
                            View Details
                        </a>
                    </div>
                </div>
            </div>`;
        });

        container.html(jobsHtml);

        // NOW attach event AFTER cards are rendered
        attachStatusChangeHandler();
      } else {
        container.html(`
                <div class="col-12 text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p>No jobs found</p>
                    <a href="add_job.php" class="btn btn-primary">Create New Job</a>
                </div>
            `);
      }
    }).fail(function () {
      container.html(
        `<div class="alert alert-danger">Failed to load jobs.</div>`
      );
    });
  }

  // Generate stage options HTML
  function generateStageOptions(currentStageId) {
    // If null, it means still attempting to load or fallback not set (though .always() handles it)
    if (allStages === null) {
      return '<option value="">Loading stages...</option>';
    }

    // If empty array, it means loaded but no stages found
    if (allStages.length === 0) {
      return '<option value="">No stages found</option>';
    }

    return allStages
      .map(
        (stage) =>
          `<option value="${stage.id}" ${
            stage.id == currentStageId ? "selected" : ""
          }>
        ${stage.stage_name}
      </option>`
      )
      .join("");
  }

  // Attach status change handler (call after loading jobs)
  function attachStatusChangeHandler() {
    $(document)
      .off("change", ".status-select")
      .on("change", ".status-select", function () {
        const select = $(this);
        const jobId =
          select.data("job-id") ||
          select.closest("[data-job-id]").data("job-id");
        const newStageId = select.val();
        const previousStageId = select.data("current-stage") || select.val();
        const card = select.closest(".card");

        // Show loading
        select
          .prop("disabled", true)
          .html('<option value="">Updating...</option>');

        $.post(
          BASE_URL + "Ajax/php/service.php",
          {
            action: "update_status",
            id: jobId,
            stage_id: newStageId, // Send stage_id instead of status
          },
          function (res) {
            if (res.status === "success") {
              // Restore options and set new value
              // Check if new status is terminal (delivered or cancelled)
              const newStatusRaw = res.new_status || "in_progress";
              const isTerminal = ["delivered", "cancelled"].includes(
                newStatusRaw
              );

              select
                .prop("disabled", isTerminal)
                .html(generateStageOptions(newStageId));
              select.val(newStageId);
              select.data("current-stage", newStageId); // Update stored stage

              // Update badge - use the new status from response
              const newStatus = res.new_status || "in_progress";
              card
                .find(".status-badge")
                .text(
                  newStatus
                    .replace(/_/g, " ")
                    .replace(/\b\w/g, (l) => l.toUpperCase())
                )
                .removeClass(
                  "status-waiting status-in-progress status-completed status-delivered badge-danger"
                )
                .addClass(getStatusClass(newStatus));

              // Update progress bar
              const progress = res.progress || 0;
              card.find(".progress-bar").css("width", progress + "%");
              card
                .find(".progress ~ .text-muted small")
                .last()
                .text(progress + "%");

              Swal.fire({
                icon: "success",
                title: "Updated!",
                text: "Job status updated successfully",
                timer: 2000,
                showConfirmButton: false,
              });
            } else {
              // Restore options with previous value
              select
                .prop("disabled", false)
                .html(generateStageOptions(previousStageId));
              select.val(previousStageId);
              alert(res.message || "Failed to update");
            }
          }
        ).fail(function () {
          // Restore options with previous value on error
          select
            .prop("disabled", false)
            .html(generateStageOptions(previousStageId));
          select.val(previousStageId);
          alert("Network error");
        });
      });
  }

  function getStatusClass(status) {
    const map = {
      waiting: "status-waiting",
      in_progress: "status-in-progress",
      quality_check: "status-in-progress",
      completed: "status-completed",
      delivered: "status-delivered",
    };
    return map[status] || "badge-secondary";
  }

  function getProgressFromStatus(status) {
    const map = {
      waiting: 10,
      in_progress: 40,
      quality_check: 70,
      completed: 90,
      delivered: 100,
      cancelled: 0,
    };
    return map[status] || 0;
  }

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

  // ============================================
  // Select Vehicle Modal Handler
  // ============================================
  let allVehiclesWithCustomer = [];

  $("#selectVehicleModal").on("show.bs.modal", function () {
    loadVehiclesWithCustomer();
  });

  function loadVehiclesWithCustomer() {
    const tbody = $("#vehicleSelectBody");
    tbody.html('<tr><td colspan="5" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i>Loading vehicles...</td></tr>');

    $.get(BASE_URL + "Ajax/php/vehicle.php", { action: "list_with_customer" }, function (response) {
      if (response.status === "success" && response.data) {
        allVehiclesWithCustomer = response.data;
        renderVehicleTable(allVehiclesWithCustomer);
      } else {
        tbody.html('<tr><td colspan="5" class="text-center py-4 text-danger"><i class="fas fa-exclamation-circle me-2"></i>Failed to load vehicles</td></tr>');
      }
    }).fail(function () {
      tbody.html('<tr><td colspan="5" class="text-center py-4 text-danger"><i class="fas fa-exclamation-circle me-2"></i>Network error</td></tr>');
    });
  }

  function renderVehicleTable(vehicles) {
    const tbody = $("#vehicleSelectBody");
    if (vehicles.length === 0) {
      tbody.html('<tr><td colspan="5" class="text-center py-4 text-muted"><i class="fas fa-info-circle me-2"></i>No vehicles found</td></tr>');
      return;
    }
    let html = "";
    vehicles.forEach((v) => {
      html += '<tr class="vehicle-row" data-vehicle=\'' + JSON.stringify(v) + '\'>';
      html += '<td><strong>' + (v.registration_number || "-") + '</strong></td>';
      html += '<td>' + (v.make || "") + ' ' + (v.model || "") + '</td>';
      html += '<td>' + (v.customer_name || "-") + '</td>';
      html += '<td>' + (v.customer_phone || "-") + '</td>';
      html += '<td class="text-center"><button type="button" class="btn btn-sm btn-primary select-vehicle-btn"><i class="fas fa-check me-1"></i>Select</button></td>';
      html += '</tr>';
    });
    tbody.html(html);
  }

  $("#vehicleSearchInput").on("keyup", function () {
    const term = $(this).val().toLowerCase().trim();
    if (!term) { renderVehicleTable(allVehiclesWithCustomer); return; }
    const filtered = allVehiclesWithCustomer.filter((v) => {
      return (v.registration_number || "").toLowerCase().includes(term) ||
        (v.make || "").toLowerCase().includes(term) ||
        (v.model || "").toLowerCase().includes(term) ||
        (v.customer_name || "").toLowerCase().includes(term) ||
        (v.customer_phone || "").toLowerCase().includes(term);
    });
    renderVehicleTable(filtered);
  });

  $(document).on("click", ".select-vehicle-btn", function () {
    const row = $(this).closest(".vehicle-row");
    const vData = row.data("vehicle");
    if (!vData) { showAlert("error", "Failed to get vehicle data"); return; }

    bootstrap.Modal.getInstance(document.getElementById("selectVehicleModal")).hide();
    $("#vehicleSearchInput").val("");

    const customerId = vData.customer_id;
    const vehicleId = vData.id;

    // Reload customers and select
    $.get(BASE_URL + "Ajax/php/customer.php", { action: "list" }, function (resp) {
      if (resp.status === "success" && resp.data) {
        const select = $("#customer_id");
        select.empty().append('<option value="">Choose a customer...</option>');
        resp.data.forEach((c) => {
          select.append('<option value="' + c.id + '" data-customer=\'' + JSON.stringify(c) + '\'>' + c.name + ' - ' + c.phone + '</option>');
        });
        select.val(customerId).trigger("change");

        // Load vehicles for customer and select
        setTimeout(function () {
          $.get(BASE_URL + "Ajax/php/vehicle.php", { action: "list", customer_id: customerId }, function (vResp) {
            if (vResp.status === "success" && vResp.data) {
              const vSelect = $("#vehicle_id");
              vSelect.empty().append('<option value="">Choose a vehicle...</option>');
              vResp.data.forEach((v) => {
                vSelect.append('<option value="' + v.id + '" data-vehicle=\'' + JSON.stringify(v) + '\'>' + v.make + ' ' + v.model + ' - ' + v.registration_number + '</option>');
              });
              vSelect.val(vehicleId).trigger("change");

              // Jump to Step 3
              setTimeout(function () {
                $('[data-step="1"]').removeClass("active").addClass("completed");
                $('[data-step="2"]').removeClass("active").addClass("completed");
                $('[data-step="3"]').addClass("active");
                $("#step1, #step2").hide();
                $("#step3").show().addClass("animate-fade-in");
                showAlert("success", "Customer & Vehicle selected! Now select packages.");
              }, 300);
            }
          });
        }, 300);
      }
    });
  });
});
