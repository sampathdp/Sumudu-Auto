jQuery(document).ready(function () {
    const scriptPath = document.querySelector('script[src*="permission.js"]').src;
    const baseUrl = scriptPath.substring(0, scriptPath.lastIndexOf("/Ajax/js/"));
    const BASE_URL = baseUrl.endsWith("/") ? baseUrl : baseUrl + "/";
    const AJAX_URL = BASE_URL + "Ajax/php/permission.php";

    function getCsrfToken() {
        return $('input[name="csrf_token"]').val() || $('meta[name="csrf-token"]').attr("content") || '';
    }

    function showError(title, message) {
        Swal.fire({ icon: "error", title: title, text: message, confirmButtonColor: "#d33" });
    }

    function showSuccess(message) {
        Swal.fire({
            icon: "success",
            title: "Success",
            text: message,
            timer: 2000,
            showConfirmButton: false,
            timerProgressBar: true
        }).then(() => $("#permissionsTable").DataTable().ajax.reload());
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

    // Validate permission code (uppercase, letters, underscore, hyphen)
    function validateCode(code) {
        return /^[A-Z0-9_-]{3,50}$/.test(code);
    }

    // DataTable
    if ($("#permissionsTable").length) {
        $("#permissionsTable").DataTable({
            ajax: {
                url: AJAX_URL,
                type: "GET",
                data: { action: "list" },
                dataSrc: "data"
            },
            columns: [
                { data: "id" },
                { data: "permission_name", render: data => `<strong>${data}</strong>` },
                { data: "permission_code", render: data => `<code class="bg-light px-2 py-1 rounded">${data}</code>` },
                { data: "description", render: data => data || '<em class="text-muted">No description</em>' },
                { data: "created_at", render: data => new Date(data).toLocaleString() },
                {
                    data: null,
                    orderable: false,
                    render: (data, type, row) => `
                        <button class="btn btn-sm btn-outline-primary edit-permission" data-id="${row.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-permission" data-id="${row.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    `
                }
            ],
            responsive: true,
            order: [[0, "desc"]]
        });
    }

    // Form Submit
    $("#permissionForm").on("submit", function (e) {
        e.preventDefault();
        clearValidation();

        const name = $("#permission_name").val().trim();
        const code = $("#permission_code").val().trim().toUpperCase();

        if (!name) {
            showFieldError("permission_name", "Permission name is required");
            return;
        }
        if (!code || !validateCode(code)) {
            showFieldError("permission_code", "Valid code required (uppercase, 3–50 chars, letters/numbers/_/- only)");
            return;
        }

        const formData = new FormData(this);
        formData.append("action", $("#permission_id").val() ? "update" : "create");
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
                    $("#permissionModal").modal("hide");
                    showSuccess(res.message || "Permission saved!");
                } else {
                    showError("Error", res.message);
                }
            },
            error: () => showError("Error", "Network error"),
            complete: () => loadButton($btn, false, original)
        });
    });

    // Edit
    $(document).on("click", ".edit-permission", function () {
        const id = $(this).data("id");
        const $btn = $(this);
        const orig = loadButton($btn, true);

        $.get(AJAX_URL, { action: "get", id: id }, function (res) {
            if (res.status === "success") {
                const p = res.data;
                $("#permission_id").val(p.id);
                $("#permission_name").val(p.permission_name);
                $("#permission_code").val(p.permission_code);
                $("#description").val(p.description);
                $("#permissionModalLabel").text("Edit Permission");
                $("#permissionModal").modal("show");
            } else {
                showError("Failed", res.message);
            }
        }, "json").always(() => loadButton($btn, false, orig));
    });

    // Delete
    $(document).on("click", ".delete-permission", function () {
        const id = $(this).data("id");
        const $btn = $(this);

        Swal.fire({
            title: "Delete permission?",
            text: "This action cannot be undone!",
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
                        $("#permissionsTable").DataTable().ajax.reload();
                        showSuccess("Permission deleted");
                    } else {
                        showError("Error", res.message);
                    }
                }, "json").always(() => loadButton($btn, false, orig));
            }
        });
    });

    // Reset modal
    $("#permissionModal").on("hidden.bs.modal", function () {
        $("#permissionForm")[0].reset();
        $("#permission_id").val("");
        $("#permissionModalLabel").text("Add New Permission");
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