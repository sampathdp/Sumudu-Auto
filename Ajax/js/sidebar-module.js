$(document).ready(function () {
  let selectedCompanyId = null;

  // Load companies first
  loadCompanies();

  // Load companies for dropdown
  function loadCompanies() {
    $.get(
      BASE_URL + "Ajax/php/sidebar-module.php",
      { action: "get_companies" },
      function (response) {
        if (response.status === "success" && response.data) {
          const select = $("#companySelector");
          select
            .empty()
            .append('<option value="">-- Select a Company --</option>');
          response.data.forEach(function (company) {
            select.append(
              `<option value="${company.id}">${company.name}</option>`
            );
          });

          // If only one company, auto-select it
          if (response.data.length === 1) {
            select.val(response.data[0].id).trigger("change");
          }
        } else {
          showAlert("error", response.message || "Failed to load companies");
        }
      }
    ).fail(function () {
      showAlert("error", "Failed to connect to server");
    });
  }

  // Company selection change handler
  $("#companySelector").on("change", function () {
    selectedCompanyId = $(this).val();
    if (selectedCompanyId) {
      loadModules(selectedCompanyId);
    } else {
      $("#modulesContainer").html(`
        <div class="text-center py-5">
          <i class="fas fa-building fa-3x text-muted mb-3"></i>
          <p class="text-muted">Select a company to manage its sidebar modules.</p>
        </div>
      `);
    }
  });

  function loadModules(companyId) {
    $("#modulesContainer").html(`
      <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Loading modules...</p>
      </div>
    `);

    $.get(
      BASE_URL + "Ajax/php/sidebar-module.php",
      { action: "list", company_id: companyId },
      function (response) {
        if (response.status === "success") {
          renderModules(response.data);
        } else {
          showAlert("error", response.message || "Failed to load modules");
        }
      }
    ).fail(function () {
      showAlert("error", "Failed to connect to server");
    });
  }

  function renderModules(sections) {
    if (!sections || sections.length === 0) {
      $("#modulesContainer").html(`
        <div class="text-center py-5">
          <i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i>
          <p class="text-muted">No modules found for this company. Modules will be initialized automatically.</p>
        </div>
      `);
      return;
    }

    let html = "";

    sections.forEach(function (section) {
      html += `
        <div class="module-section">
          <div class="section-header">
            <h3>
              <i class="fas ${section.icon || "fa-folder"}"></i>
              ${section.module_name}
            </h3>
            <label class="toggle-switch">
              <input type="checkbox" class="module-toggle" 
                     data-key="${section.module_key}" 
                     ${section.is_visible == 1 ? "checked" : ""}>
              <span class="toggle-slider"></span>
            </label>
          </div>
          <ul class="module-list">`;

      if (section.children && section.children.length > 0) {
        section.children.forEach(function (child) {
          html += `
            <li class="module-item">
              <div class="module-info">
                <div class="module-icon">
                  <i class="fas ${child.icon || "fa-circle"}"></i>
                </div>
                <span class="module-name">${child.module_name}</span>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" class="module-toggle" 
                       data-key="${child.module_key}" 
                       data-parent="${section.module_key}"
                       ${child.is_visible == 1 ? "checked" : ""}>
                <span class="toggle-slider"></span>
              </label>
            </li>`;
        });
      } else {
        html += `<li class="module-item text-muted text-center">No sub-modules</li>`;
      }

      html += `</ul></div>`;
    });

    $("#modulesContainer").html(html);
  }

  // Toggle module visibility
  $(document).on("change", ".module-toggle", function () {
    const $toggle = $(this);
    const moduleKey = $toggle.data("key");
    const parentKey = $toggle.data("parent"); // Will be undefined for parent modules
    const isVisible = $toggle.is(":checked") ? 1 : 0;

    if (!selectedCompanyId) {
      showAlert("error", "Please select a company first");
      $toggle.prop("checked", !isVisible);
      return;
    }

    // Check if this is a parent module (no parent key means it's a parent)
    const isParent = !parentKey;

    $toggle.prop("disabled", true);
    $("#loadingOverlay").addClass("show");

    // If parent, also update all child toggles in the UI
    if (isParent) {
      $(`.module-toggle[data-parent="${moduleKey}"]`).each(function () {
        $(this)
          .prop("checked", isVisible === 1)
          .prop("disabled", true);
      });
    }

    $.post(
      BASE_URL + "Ajax/php/sidebar-module.php",
      {
        action: "toggle",
        module_key: moduleKey,
        is_visible: isVisible,
        company_id: selectedCompanyId,
      },
      function (response) {
        if (response.status === "success") {
          const childCount = response.children_updated || 0;
          let msg = `Module ${isVisible ? "shown" : "hidden"} successfully`;
          if (childCount > 0) {
            msg += ` (${childCount} sub-module${
              childCount > 1 ? "s" : ""
            } also updated)`;
          }
          showAlert("success", msg);
        } else {
          // Revert toggle
          $toggle.prop("checked", !isVisible);
          // Revert children too if this was a parent
          if (isParent) {
            $(`.module-toggle[data-parent="${moduleKey}"]`).each(function () {
              $(this).prop("checked", !isVisible);
            });
          }
          showAlert("error", response.message || "Failed to update");
        }
      }
    )
      .fail(function () {
        $toggle.prop("checked", !isVisible);
        // Revert children too if this was a parent
        if (isParent) {
          $(`.module-toggle[data-parent="${moduleKey}"]`).each(function () {
            $(this).prop("checked", !isVisible);
          });
        }
        showAlert("error", "Connection error");
      })
      .always(function () {
        $toggle.prop("disabled", false);
        // Re-enable children
        if (isParent) {
          $(`.module-toggle[data-parent="${moduleKey}"]`).prop(
            "disabled",
            false
          );
        }
        $("#loadingOverlay").removeClass("show");
      });
  });

  function showAlert(type, message) {
    const Toast = Swal.mixin({
      toast: true,
      position: "top-end",
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
    });
    Toast.fire({ icon: type, title: message });
  }
});
