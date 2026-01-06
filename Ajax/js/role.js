jQuery(document).ready(function () {
  const scriptPath = document.querySelector('script[src*="role.js"]').src;
  const baseUrl = scriptPath.substring(0, scriptPath.lastIndexOf("/Ajax/js/"));
  const BASE_URL = baseUrl.endsWith("/") ? baseUrl : baseUrl + "/";
  const AJAX_URL = BASE_URL + "Ajax/php/role.php";

  function getCsrfToken() {
    return $('input[name="csrf_token"]').val() || $('meta[name="csrf-token"]').attr("content") || '';
  }

  function showError(title, message) {
    return Swal.fire({
      icon: "error",
      title: title,
      text: message,
      confirmButtonColor: "#d33",
    });
  }

  function showSuccess(message, reload = true) {
    Swal.fire({
      icon: "success",
      title: "Success",
      text: message,
      timer: 1500,
      showConfirmButton: false,
      timerProgressBar: true,
    }).then(() => {
      if (reload) window.location.reload();
    });
  }

  function loadButton($btn, loading, original = null) {
    if (loading) {
      original = original || $btn.html();
      $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span> Loading...');
      return original;
    } else {
      $btn.prop("disabled", false).html(original);
    }
  }

  // Client validation
  function validateRoleName(name) {
    const regex = /^[a-zA-Z0-9 _-]{3,50}$/;
    return regex.test(name);
  }

  // DataTable
  if ($("#rolesTable").length) {
    $("#rolesTable").DataTable({
      ajax: {
        url: AJAX_URL,
        type: "GET",
        data: { action: "list" },
        dataSrc: "data"
      },
      columns: [
        { data: "id" },
        { data: "role_name", render: data => `<strong>${data}</strong>` },
        { data: "description", render: data => data || '<em class="text-muted">No description</em>' },
        { data: "is_system_role", render: data => data == 1 ? '<span class="badge bg-primary">System</span>' : '<span class="badge bg-secondary">Custom</span>' },
        { data: "created_at", render: data => data ? new Date(data).toLocaleString() : '-' },
        {
          data: null,
          orderable: false,
          render: (data, type, row) => `
            <button class="btn btn-sm btn-outline-primary edit-role" data-id="${row.id}">
              <i class="fas fa-edit"></i>
            </button>
            ${row.is_system_role == 0 ? `
            <button class="btn btn-sm btn-outline-danger delete-role" data-id="${row.id}">
              <i class="fas fa-trash"></i>
            </button>` : ''}
          `
        }
      ],
      responsive: true,
      order: [[0, "desc"]]
    });
  }

  // Form submit
  $("#roleForm").on("submit", function (e) {
    e.preventDefault();
    clearValidation();

    const roleName = $("#role_name").val().trim();
    if (!roleName) {
      showFieldError("role_name", "Role name is required");
      return;
    }
    if (!validateRoleName(roleName)) {
      showFieldError("role_name", "3–50 chars, letters, numbers, space, underscore, hyphen only");
      return;
    }

    const formData = new FormData(this);
    formData.append("action", $("#role_id").val() ? "update" : "create");
    formData.append("csrf_token", getCsrfToken());

    const $btn = $(this).find("button[type=submit]");
    const original = loadButton($btn, true);

    $.ajax({
      url: AJAX_URL,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: function (res) {
        if (res.status === "success") {
          $("#roleModal").modal("hide");
          showSuccess(res.message || "Role saved!");
        } else {
          showError("Error", res.message || "Operation failed");
        }
      },
      error: () => showError("Error", "Network or server error"),
      complete: () => loadButton($btn, false, original)
    });
  });

  // Edit
  $(document).on("click", ".edit-role", function () {
    const id = $(this).data("id");
    const $btn = $(this);
    const orig = loadButton($btn, true);

    $.get(AJAX_URL, { action: "get", id: id }, function (res) {
      if (res.status === "success") {
        const r = res.data;
        $("#role_id").val(r.id);
        $("#role_name").val(r.role_name);
        $("#description").val(r.description);
        $("#is_system_role").prop("checked", !!r.is_system_role);
        $("#roleModalLabel").text("Edit Role");
        $("#roleModal").modal("show");
      } else {
        showError("Failed", res.message);
      }
    }, "json").always(() => loadButton($btn, false, orig));
  });

  // Delete
  $(document).on("click", ".delete-role", function () {
    const id = $(this).data("id");
    const $btn = $(this);

    Swal.fire({
      title: "Delete role?",
      text: "This cannot be undone!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      confirmButtonText: "Yes, delete!"
    }).then(result => {
      if (result.isConfirmed) {
        const orig = loadButton($btn, true);
        $.post(AJAX_URL, {
          action: "delete",
          id: id,
          csrf_token: getCsrfToken()
        }, function (res) {
          if (res.status === "success") {
            $("#rolesTable").DataTable().ajax.reload();
            showSuccess("Role deleted");
          } else {
            showError("Error", res.message);
          }
        }, "json").always(() => loadButton($btn, false, orig));
      }
    });
  });

  // Reset modal
  $("#roleModal").on("hidden.bs.modal", function () {
    $("#roleForm")[0].reset();
    $("#role_id").val("");
    $("#roleModalLabel").text("Add New Role");
    clearValidation();
  });

  function showFieldError(field, msg) {
    const $f = $(`#${field}`);
    $f.addClass("is-invalid");
    $f.siblings(".invalid-feedback").remove();
    $f.after(`<div class="invalid-feedback d-block">${msg}</div>`);
  }

  function clearValidation() {
    $(".is-invalid").removeClass("is-invalid");
    $(".invalid-feedback").remove();
  }
});