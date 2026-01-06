jQuery(document).ready(function () {
  const scriptPath = document.querySelector('script[src*="page.js"]').src;
  const baseUrl = scriptPath.substring(0, scriptPath.lastIndexOf("/Ajax/js/"));
  const BASE_URL = baseUrl.endsWith("/") ? baseUrl : baseUrl + "/";
  const AJAX_URL = BASE_URL + "Ajax/php/page.php";

  function getCsrfToken() {
    return $('input[name="csrf_token"]').val() || $('meta[name="csrf-token"]').attr("content") || '';
  }

  function showError(title, msg) {
    Swal.fire({ icon: "error", title, text: msg });
  }

  function showSuccess(msg) {
    Swal.fire({
      icon: "success",
      title: "Success",
      text: msg,
      timer: 1500,
      showConfirmButton: false,
      timerProgressBar: true
    }).then(() => window.location.reload());
  }

  function loadButton($btn, loading, orig = null) {
    if (loading) {
      orig = orig || $btn.html();
      $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span> Loading...');
      return orig;
    } else {
      $btn.prop("disabled", false).html(orig);
    }
  }

  // Validation
  function validateRoute(route) {
    return /^\/[a-zA-Z0-9\-_\/]*$/.test(route) || route === '/';
  }

  // DataTable
  $("#pagesTable").DataTable({
    ajax: { url: AJAX_URL, type: "GET", data: { action: "list" }, dataSrc: "data" },
    columns: [
      { data: "id" },
      { data: "page_name", render: d => `<strong>${d}</strong>` },
      { data: "page_route", render: d => `<code>${d}</code>` },
      { data: "page_category", render: d => d ? `<span class="badge bg-info">${d}</span>` : '<em>—</em>' },
      { data: "description", render: d => d || '<em>No description</em>' },
      { data: "icon", render: d => d ? `<i class="${d} me-2"></i>${d}` : '—' },
      { data: "display_order" },
      { data: "is_active", render: d => d ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-warning">Inactive</span>' },
      { data: "parent_page_id", render: d => d || '<em>Root</em>' },
      { data: "created_at", render: d => new Date(d).toLocaleString() },
      {
        data: null,
        orderable: false,
        render: (data, type, row) => `
          <button class="btn btn-sm btn-outline-primary edit-page" data-id="${row.id}"><i class="fas fa-edit"></i></button>
          <button class="btn btn-sm btn-outline-danger delete-page ms-1" data-id="${row.id}"><i class="fas fa-trash"></i></button>
        `
      }
    ],
    order: [[6, "asc"]],
    responsive: true
  });

  // Load parent options
  function loadParentOptions() {
    $.get(AJAX_URL, { action: "list_parents" }, res => {
      if (res.status === "success") {
        const $select = $("#parent_page_id");
        $select.empty().append('<option value="">-- No Parent (Root) --</option>');
        res.data.forEach(p => {
          $select.append(`<option value="${p.id}">${p.page_name}</option>`);
        });
      }
    });
  }
  loadParentOptions();

  // Form Submit
  $("#pageForm").on("submit", function (e) {
    e.preventDefault();
    clearValidation();

    const route = $("#page_route").val().trim();
    if (!route) {
      showFieldError("page_route", "Page route is required");
      return;
    }
    if (!validateRoute(route)) {
      showFieldError("page_route", "Invalid route format. Use like: /dashboard or /settings/profile");
      return;
    }

    const formData = new FormData(this);
    formData.append("action", $("#page_id").val() ? "update" : "create");
    formData.append("csrf_token", getCsrfToken());

    const $btn = $(this).find("button[type=submit]");
    const orig = loadButton($btn, true);

    $.ajax({
      url: AJAX_URL,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: res => {
        if (res.status === "success") {
          $("#pageModal").modal("hide");
          showSuccess(res.message || "Page saved!");
        } else {
          showError("Failed", res.message);
        }
      },
      error: () => showError("Error", "Server error"),
      complete: () => loadButton($btn, false, orig)
    });
  });

  // Edit
  $(document).on("click", ".edit-page", function () {
    const id = $(this).data("id");
    const $btn = $(this);
    const orig = loadButton($btn, true);

    $.get(AJAX_URL, { action: "get", id }, res => {
      if (res.status === "success") {
        const p = res.data;
        $("#page_id").val(p.id);
        $("#page_name").val(p.page_name);
        $("#page_route").val(p.page_route);
        $("#page_category").val(p.page_category);
        $("#description").val(p.description || '');
        $("#icon").val(p.icon || '');
        $("#display_order").val(p.display_order);
        $("#is_active").prop("checked", !!p.is_active);
        $("#parent_page_id").val(p.parent_page_id || '');
        $("#pageModalLabel").text("Edit Page");
        $("#pageModal").modal("show");
      } else showError("Error", res.message);
    }, "json").always(() => loadButton($btn, false, orig));
  });

  // Delete
  $(document).on("click", ".delete-page", function () {
    const id = $(this).data("id");
    Swal.fire({
      title: "Delete page?",
      text: "Pages with sub-pages cannot be deleted.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Yes, delete",
      confirmButtonColor: "#d33"
    }).then(result => {
      if (result.isConfirmed) {
        const orig = loadButton($(this), true);
        $.post(AJAX_URL, { action: "delete", id, csrf_token: getCsrfToken() }, res => {
          if (res.status === "success") {
            $("#pagesTable").DataTable().ajax.reload();
            showSuccess("Page deleted");
          } else showError("Cannot delete", res.message);
        }, "json").always(() => loadButton($(this), false, orig));
      }
    });
  });

  // Reset modal
  $("#pageModal").on("hidden.bs.modal", function () {
    $("#pageForm")[0].reset();
    $("#page_id").val("");
    $("#pageModalLabel").text("Add New Page");
    loadParentOptions();
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