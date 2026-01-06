jQuery(document).ready(function () {
  // Dynamic base URL detection
  const scriptPath =
    document.querySelector('script[src*="user.js"]')?.src || "";
  const baseUrl = scriptPath.substring(0, scriptPath.lastIndexOf("/Ajax/js/"));
  const BASE_URL = baseUrl.endsWith("/") ? baseUrl : baseUrl + "/";
  const AJAX_URL = BASE_URL + "Ajax/php/user.php";

  // Get CSRF token
  function getCsrfToken() {
    return (
      $('input[name="csrf_token"]').val() ||
      $('meta[name="csrf-token"]').attr("content") ||
      ""
    );
  }

  // Helper functions (unchanged, but kept for consistency)
  function resetFormState($submitBtn, $spinner, $btnText) {
    $submitBtn.prop("disabled", false);
    $spinner.addClass("d-none");
    $btnText.text("Sign In");
  }

  function setLoadingState(
    $submitBtn,
    $spinner,
    $btnText,
    loadingText = "Signing in..."
  ) {
    $submitBtn.prop("disabled", true);
    $spinner.removeClass("d-none");
    $btnText.text(loadingText);
  }

  function showValidationError(field, message) {
    const $field = $(`#${field}`);
    $field.addClass("is-invalid");
    $field.siblings(".invalid-feedback").remove();
    $field.after(`<div class="invalid-feedback d-block">${message}</div>`);
    $field.focus();
  }

  function clearValidationErrors() {
    $(".is-invalid").removeClass("is-invalid");
    $(".invalid-feedback").remove();
  }

  function showError(title, message, icon = "error") {
    return Swal.fire({
      icon: icon,
      title: title,
      text: message,
      confirmButtonColor: "#3085d6",
    });
  }

  function showSuccess(message, redirect = null, timer = 1500) {
    Swal.fire({
      icon: "success",
      title: "Success",
      text: message,
      showConfirmButton: false,
      timer: timer,
      timerProgressBar: true,
      didClose: () => {
        if (redirect) window.location.href = redirect;
      },
    });
  }

  function loadButton($btn, isLoading, originalHtml = null) {
    if (isLoading) {
      originalHtml = originalHtml || $btn.html();
      const spinner = $btn.hasClass("btn-sm")
        ? '<span class="spinner-border spinner-border-sm" role="status"></span> Loading...'
        : '<span class="spinner-border" role="status"></span> Loading...';
      $btn.prop("disabled", true).html(spinner);
      return originalHtml;
    } else {
      $btn.prop("disabled", false).html(originalHtml);
    }
  }

  // Login form submission
  $("#login").on("submit", function (e) {
    e.preventDefault();
    clearValidationErrors();

    const $form = $(this);
    const $submitBtn = $form.find('button[type="submit"]');
    const $spinner = $submitBtn.find(".spinner-border");
    const $btnText = $submitBtn.find(".btn-text");

    const username = $("#username").val().trim();
    const password = $("#password").val();

    if (!username)
      return showValidationError("username", "Please enter your username");
    if (username.length < 3)
      return showValidationError(
        "username",
        "Username must be at least 3 characters"
      );
    if (!password)
      return showValidationError("password", "Please enter your password");
    if (password.length < 8)
      return showValidationError(
        "password",
        "Password must be at least 8 characters"
      );

    setLoadingState($submitBtn, $spinner, $btnText);

    const formData = new FormData(this);
    formData.append("login", true);
    formData.append("csrf_token", getCsrfToken());

    $.ajax({
      url: AJAX_URL,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          showSuccess(
            response.message || "Login successful!",
            response.redirect || BASE_URL + "views/Dashboard/index.php"
          );
        } else {
          showError(
            "Login Failed",
            response.message || "Invalid username or password"
          );
          resetFormState($submitBtn, $spinner, $btnText);
        }
      },
      error: function () {
        showError("Error", "An unexpected error occurred. Please try again.");
        resetFormState($submitBtn, $spinner, $btnText);
      },
    });
  });

  // User Management Functions
  let usersTable;

  // Load roles for dropdown
  function loadRoles() {
    $.ajax({
      url: AJAX_URL,
      type: "POST",
      data: { action: "getRoles" },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          const $roleSelect = $("#role_id");
          $roleSelect.find("option:not(:first)").remove();

          response.data.forEach(function (role) {
            $roleSelect.append(
              `<option value="${role.id}">${role.role_name}</option>`
            );
          });
        }
      },
      error: function () {
        console.error("Failed to load roles");
      },
    });
  }

  // Load branches for dropdown
  function loadBranches() {
    $.ajax({
      url: AJAX_URL,
      type: "POST",
      data: { action: "getBranches" },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          const $branchSelect = $("#branch_id");
          $branchSelect.find("option:not(:first)").remove();

          response.data.forEach(function (branch) {
            const mainBadge = branch.is_main == 1 ? " (Main)" : "";
            $branchSelect.append(
              `<option value="${branch.id}">${branch.branch_name}${mainBadge}</option>`
            );
          });
        }
      },
      error: function () {
        console.error("Failed to load branches");
      },
    });
  }

  // Initialize DataTable
  function initUsersTable() {
    if (usersTable) {
      usersTable.destroy();
    }

    usersTable = $("#usersTable").DataTable({
      ajax: {
        url: AJAX_URL,
        type: "POST",
        data: { action: "getUsers" },
        dataSrc: function (response) {
          return response.status === "success" ? response.data : [];
        },
        error: function () {
          showError("Error", "Failed to load users data");
          return [];
        },
      },
      columns: [
        { data: "id" },
        { data: "username" },
        {
          data: "role_name",
          render: function (data) {
            return data || '<span class="text-muted">No Role</span>';
          },
        },
        {
          data: "branch_name",
          render: function (data) {
            return data || '<span class="text-muted">All Branches</span>';
          },
        },
        {
          data: "is_active",
          render: function (data) {
            return data === 1
              ? '<span class="badge bg-success">Active</span>'
              : '<span class="badge bg-danger">Inactive</span>';
          },
        },
        {
          data: "last_login",
          render: function (data) {
            return data ? new Date(data).toLocaleString() : "Never";
          },
        },
        {
          data: null,
          orderable: false,
          className: "text-end",
          render: function (data, type, row) {
            return `
              <div class="user-actions">
                <button class="btn btn-sm btn-outline-primary btn-icon edit-user" data-id="${row.id}" title="Edit">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger btn-icon delete-user" data-id="${row.id}" title="Delete">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            `;
          },
        },
      ],
      responsive: true,
      pageLength: 10,
      lengthMenu: [
        [10, 25, 50, -1],
        [10, 25, 50, "All"],
      ],
      order: [[0, "desc"]],
      language: {
        emptyTable: "No users found",
        zeroRecords: "No matching users found",
      },
    });
  }

  // Load users table on page load
  if ($("#usersTable").length) {
    initUsersTable();
    loadRoles();
    loadBranches();
  }

  // Handle user form submission
  $("#userForm").on("submit", function (e) {
    e.preventDefault();
    clearValidationErrors();

    const $form = $(this);
    const $submitBtn = $form.find('button[type="submit"]');
    const userId = $("#user_id").val();
    const isEdit = userId !== "";
    const originalHtml = $submitBtn.html();

    const username = $("#username").val().trim();
    const password = $("#password").val();
    const isActive = $("#is_active").is(":checked") ? 1 : 0;
    const roleId = $("#role_id").val() ? $("#role_id").val() : null;

    // Validation
    if (!username) {
      showValidationError("username", "Username is required");
      return;
    }
    if (username.length < 3) {
      showValidationError("username", "Username must be at least 3 characters");
      return;
    }
    if (!isEdit && !password) {
      showValidationError("password", "Password is required for new users");
      return;
    }
    if (password && password.length < 8) {
      showValidationError("password", "Password must be at least 8 characters");
      return;
    }

    loadButton($submitBtn, true, originalHtml);

    const branchId = $("#branch_id").val();

    const formData = new FormData();
    formData.append("action", isEdit ? "update" : "create");
    formData.append("username", username);
    formData.append("is_active", isActive);
    if (userId) formData.append("user_id", userId);
    if (password) formData.append("password", password);
    if (roleId) formData.append("role_id", roleId);
    if (branchId) formData.append("branch_id", branchId);
    formData.append("csrf_token", getCsrfToken());

    $.ajax({
      url: AJAX_URL,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          showSuccess(response.message || "User saved successfully!");
          $("#userModal").modal("hide");
          $form[0].reset();
          $("#user_id").val("");
          $("#passwordField").show();
          if (usersTable) {
            usersTable.ajax.reload();
          }
        } else {
          showError("Error", response.message || "Failed to save user");
        }
        loadButton($submitBtn, false, originalHtml);
      },
      error: function () {
        showError("Error", "An unexpected error occurred. Please try again.");
        loadButton($submitBtn, false, originalHtml);
      },
    });
  });

  // Edit user
  $(document).on("click", ".edit-user", function () {
    const userId = $(this).data("id");

    $.ajax({
      url: AJAX_URL,
      type: "POST",
      data: { action: "get", id: userId },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          const user = response.data;
          $("#user_id").val(user.id);
          $("#username").val(user.username);
          $("#is_active").prop("checked", user.is_active === 1);
          $("#role_id").val(user.role_id || "");
          $("#branch_id").val(user.branch_id || "");
          $("#passwordField").hide();
          $("#userModalLabel").text("Edit User");
          $("#userModal").modal("show");
        } else {
          showError("Error", response.message || "Failed to load user data");
        }
      },
      error: function () {
        showError("Error", "Failed to load user data");
      },
    });
  });

  // Delete user
  $(document).on("click", ".delete-user", function () {
    const userId = $(this).data("id");
    const $btn = $(this);

    Swal.fire({
      title: "Are you sure?",
      text: "This user will be permanently deleted.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc3545",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, delete it!",
      cancelButtonText: "Cancel",
    }).then((result) => {
      if (result.isConfirmed) {
        const originalHtml = $btn.html();
        loadButton($btn, true, originalHtml);

        $.ajax({
          url: AJAX_URL,
          type: "POST",
          data: { action: "delete", id: userId },
          dataType: "json",
          success: function (response) {
            if (response.status === "success") {
              showSuccess(response.message || "User deleted successfully!");
              if (usersTable) {
                usersTable.ajax.reload();
              }
            } else {
              showError("Error", response.message || "Failed to delete user");
            }
            loadButton($btn, false, originalHtml);
          },
          error: function () {
            showError("Error", "Failed to delete user");
            loadButton($btn, false, originalHtml);
          },
        });
      }
    });
  });

  // Reset modal when hidden
  $("#userModal").on("hidden.bs.modal", function () {
    $("#userForm")[0].reset();
    $("#user_id").val("");
    $("#branch_id").val("");
    $("#passwordField").show();
    $("#userModalLabel").text("Add New User");
    clearValidationErrors();
  });

  // Rest of your JS (DataTable, user management, etc.) remains unchanged
  // ... [keep your existing code for DataTable, edit/delete/saveUser, etc.]
});
