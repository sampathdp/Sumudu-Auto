// Initialize DataTable
$(document).ready(function () {
  const categoriesTable = $("#categoriesTable").DataTable({
    ajax: {
      url: "../../Ajax/php/inventory_category.php",
      type: "GET",
      data: { action: "list" },
      dataSrc: "data",
    },
    columns: [
      { data: "id" },
      {
        data: "category_name",
        render: function (data) {
          return `<span class="fw-semibold">${data}</span>`;
        },
      },
      {
        data: "description",
        render: function (data) {
          return data || '<span class="text-muted">N/A</span>';
        },
      },
      {
        data: "parent_name",
        render: function (data) {
          return data
            ? `<span class="badge bg-info">${data}</span>`
            : '<span class="text-muted">Root</span>';
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
                        <div class="category-actions">
                            <button class="btn btn-sm btn-icon btn-outline-primary edit-category" data-id="${row.id}" 
                                    title="Edit Category">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-icon btn-outline-danger delete-category" data-id="${row.id}" 
                                    title="Delete Category">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>`;
        },
      },
    ],
    order: [[1, "asc"]],
    responsive: true,
  });

  // Load parent categories for dropdown
  function loadParentCategories() {
    $.get(
      "../../Ajax/php/inventory_category.php",
      { action: "list" },
      function (response) {
        if (response.status === "success") {
          const select = $("#parent_category_id");
          select.html('<option value="">-- None (Root Category) --</option>');
          response.data.forEach(function (cat) {
            select.append(
              `<option value="${cat.id}">${cat.category_name}</option>`
            );
          });
        }
      }
    );
  }

  // Handle form submission
  $("#categoryForm").on("submit", function (e) {
    e.preventDefault();

    const formData = {
      action: $("#category_id").val() ? "update" : "create",
      id: $("#category_id").val(),
      category_name: $("#category_name").val(),
      description: $("#description").val(),
      parent_category_id: $("#parent_category_id").val(),
      is_active: $("#is_active").is(":checked") ? 1 : 0,
    };

    const submitBtn = $("#saveCategoryBtn");
    const originalBtnText = submitBtn.html();

    if (!this.checkValidity()) {
      e.stopPropagation();
      $(this).addClass("was-validated");
      return;
    }

    submitBtn
      .prop("disabled", true)
      .html(
        '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
      );

    $.ajax({
      url: "../../Ajax/php/inventory_category.php",
      type: "POST",
      data: formData,
      success: function (response) {
        if (response.status === "success") {
          showAlert(
            "success",
            response.message || "Operation completed successfully"
          );
          $("#categoryModal").modal("hide");
          categoriesTable.ajax.reload();
          $("#categoryForm")[0].reset();
          $("#categoryForm").removeClass("was-validated");
          $("#category_id").val("");
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

  // Reset form when modal is opened
  $("#categoryModal").on("show.bs.modal", function () {
    loadParentCategories();
  });

  // Reset form when modal is closed
  $("#categoryModal").on("hidden.bs.modal", function () {
    const form = $("#categoryForm");
    form[0].reset();
    form.removeClass("was-validated");
    $("#category_id").val("");
    $("#categoryModalLabel").text("Add New Category");
    $("#is_active").prop("checked", true);
  });

  // Edit category
  $(document).on("click", ".edit-category", function () {
    const categoryId = $(this).data("id");

    loadParentCategories();

    $.get(
      "../../Ajax/php/inventory_category.php",
      { action: "get", id: categoryId },
      function (response) {
        if (response.status === "success" && response.data) {
          const category = response.data;
          fillCategoryForm(category);
          $("#categoryModalLabel").text("Edit Category");
          $("#categoryModal").modal("show");
        } else {
          showAlert(
            "error",
            response.message || "Failed to load category data"
          );
        }
      }
    ).fail(function () {
      showAlert("error", "An error occurred while loading category data");
    });
  });

  // Delete category
  $(document).on("click", ".delete-category", function () {
    const categoryId = $(this).data("id");

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
          "../../Ajax/php/inventory_category.php",
          { action: "delete", id: categoryId },
          function (response) {
            if (response.status === "success") {
              showAlert(
                "success",
                response.message || "Category deleted successfully"
              );
              categoriesTable.ajax.reload();
            } else {
              showAlert(
                "error",
                response.message || "Failed to delete category"
              );
            }
          }
        ).fail(function () {
          showAlert("error", "An error occurred while deleting the category");
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
    });

    Toast.fire({ icon: type, title: message });
  }

  // Helper function to fill form for editing
  function fillCategoryForm(category) {
    $("#category_id").val(category.id);
    $("#category_name").val(category.category_name);
    $("#description").val(category.description || "");
    $("#parent_category_id").val(category.parent_category_id || "");
    $("#is_active").prop("checked", category.is_active == 1);
  }
});
