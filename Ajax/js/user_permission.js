jQuery(document).ready(function () {
  const scriptPath = document.querySelector('script[src*="user_permission.js"]').src;
  const baseUrl = scriptPath.substring(0, scriptPath.lastIndexOf("/Ajax/js/"));
  const BASE_URL = baseUrl.endsWith("/") ? baseUrl : baseUrl + "/";
  const AJAX_URL = BASE_URL + "Ajax/php/user_permission.php";

  let selectedUserId = null;
  let pagesData = [];
  let permissionsList = [];

  function getCsrfToken() {
    return (
      $('input[name="csrf_token"]').val() ||
      $('meta[name="csrf-token"]').attr("content") ||
      ""
    );
  }

  function showSuccess(msg) {
    Swal.fire({
      icon: "success",
      title: "Success",
      text: msg,
      timer: 2000,
      showConfirmButton: false,
      timerProgressBar: true,
    });
  }

  function showError(msg) {
    Swal.fire({ icon: "error", title: "Error", text: msg });
  }

  // Load pages + permissions matrix
  function loadMatrix() {
    $.get(
      AJAX_URL,
      { action: "get_pages_with_permissions" },
      function (res) {
        if (res.status === "success") {
          pagesData = res.data;
          if (pagesData.length > 0) {
            permissionsList = pagesData[0].permissions;
            renderMatrix();
          }
        }
      },
      "json"
    );
  }

  function renderMatrix(userPerms = []) {
    let headers =
      '<th>ID</th><th>Module</th><th>Page</th><th class="text-center">All</th>';
    permissionsList.forEach(
      (p) => (headers += `<th class="text-center">${p.name}</th>`)
    );
    $("#permissionsMatrix thead tr").html(headers);

    let tbody = "";
    let currentCat = "";

    pagesData.forEach((page) => {
      if (page.category !== currentCat) {
        currentCat = page.category;
        tbody += `<tr class="table-light"><th colspan="${
          4 + permissionsList.length
        }" class="bg-light p-3"><i class="fas fa-folder-open me-2"></i>${currentCat}</th></tr>`;
      }

      let cells = "";
      permissionsList.forEach((perm) => {
        const granted = userPerms.some(
          (up) =>
            up.page_id === page.id &&
            up.permission_id === perm.id &&
            up.is_granted
        );
        cells += `<td class="text-center">
                    <input type="checkbox" class="form-check-input permission-checkbox page-${
                      page.id
                    }-permission"
                           data-page-id="${page.id}" data-permission-id="${
          perm.id
        }" ${granted ? "checked" : ""}>
                </td>`;
      });

      tbody += `<tr>
                <td>${page.id}</td>
                <td>${page.category}</td>
                <td>${page.name}</td>
                <td class="text-center"><input type="checkbox" class="form-check-input select-all-page" data-page-id="${page.id}"></td>
                ${cells}
            </tr>`;
    });

    $("#permissionsMatrix tbody").html(tbody);
    updateAllCheckboxes();
  }

  function updateAllCheckboxes() {
    $(".select-all-page").each(function () {
      const pageId = $(this).data("page-id");
      const allChecked =
        $(`.page-${pageId}-permission:checked`).length ===
        permissionsList.length;
      $(this).prop("checked", allChecked);
    });
    const globalAll =
      $(".permission-checkbox").length ===
      $(".permission-checkbox:checked").length;
    $("#select_all").prop("checked", globalAll);
  }

  // Events
  $("#user_type").on("change", function () {
    const roleId = $(this).val();
    $.get(
      AJAX_URL,
      {
        action: roleId ? "list_users_by_role" : "list_users",
        role_id: roleId || null,
      },
      (res) => {
        if (res.status === "success") {
          const opts = res.data
            .map((u) => `<option value="${u.id}">${u.username}</option>`)
            .join("");
          $("#selected_user").html(
            '<option value="">-- Select User --</option>' + opts
          );
        }
      },
      "json"
    );
  });

  $("#selected_user").on("change", function () {
    selectedUserId = $(this).val();
    if (selectedUserId) {
      $.get(
        AJAX_URL,
        { action: "get_user_permissions", user_id: selectedUserId },
        (res) => {
          if (res.status === "success") renderMatrix(res.data);
          $("#savePermissionsBtn").prop("disabled", false);
        },
        "json"
      );
    } else {
      renderMatrix();
      $("#savePermissionsBtn").prop("disabled", true);
    }
  });

  $(document).on(
    "change",
    ".permission-checkbox, .select-all-page, #select_all",
    function () {
      if (!selectedUserId) return;
      const pageId = $(this).data("page-id");
      if ($(this).hasClass("select-all-page")) {
        const checked = $(this).is(":checked");
        $(`.page-${pageId}-permission`).prop("checked", checked);
      }
      if ($(this).attr("id") === "select_all") {
        $(".permission-checkbox, .select-all-page").prop(
          "checked",
          $(this).is(":checked")
        );
      }
      updateAllCheckboxes();
      $("#savePermissionsBtn").prop("disabled", false);
    }
  );

  $("#savePermissionsBtn").on("click", function () {
    const perms = [];
    $(".permission-checkbox:checked").each(function () {
      perms.push({
        page_id: $(this).data("page-id"),
        permission_id: $(this).data("permission-id"),
      });
    });

    const fd = new FormData();
    fd.append("action", "save_user_permissions");
    fd.append("user_id", selectedUserId);
    fd.append("permissions", JSON.stringify(perms));
    fd.append("csrf_token", getCsrfToken());

    $.ajax({
      url: AJAX_URL,
      type: "POST",
      data: fd,
      processData: false,
      contentType: false,
      success: (res) => {
        if (res.status === "success") {
          showSuccess(res.message);
          loadMatrix(); // Refresh
        } else showError(res.message);
      },
    });
  });

  // Init
  loadMatrix();
});
