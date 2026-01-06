// Initialize DataTable
$(document).ready(function () {
  const itemsTable = $("#itemsTable").DataTable({
    ajax: {
      url: "../../Ajax/php/inventory_item.php",
      type: "GET",
      data: { action: "list" },
      dataSrc: "data",
    },
    columns: [
      { data: "id" },
      {
        data: "item_code",
        render: function (data) {
          return `<code>${data}</code>`;
        },
      },
      {
        data: "item_name",
        render: function (data) {
          return `<span class="fw-semibold">${data}</span>`;
        },
      },
      {
        data: "category_name",
        render: function (data) {
          return data
            ? `<span class="badge bg-info">${data}</span>`
            : '<span class="text-muted">N/A</span>';
        },
      },
      {
        data: "unit_of_measure",
        render: function (data) {
          return `<span class="badge bg-secondary">${data}</span>`;
        },
      },
      {
        data: "current_stock",
        render: function (data, type, row) {
          const stock = data ? parseFloat(data) : 0;
          const reorder = parseFloat(row.reorder_level) || 0;
          const colorClass =
            stock <= reorder ? "text-danger fw-bold" : "text-success";
          return `<span class="${colorClass}">${stock.toLocaleString()}</span>`;
        },
      },
      {
        data: "unit_price",
        render: function (data) {
          return `Rs. ${parseFloat(data).toFixed(2)}`;
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
                        <div class="item-actions">
                            <button class="btn btn-sm btn-icon btn-outline-primary edit-item" data-id="${row.id}" 
                                    title="Edit Item">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-icon btn-outline-danger delete-item" data-id="${row.id}" 
                                    title="Delete Item">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>`;
        },
      },
    ],
    order: [[2, "asc"]],
    responsive: true,
  });

  // Load categories for dropdown
  function loadCategories() {
    $.get(
      "../../Ajax/php/inventory_item.php",
      { action: "categories" },
      function (response) {
        if (response.status === "success") {
          const select = $("#category_id");
          select.html('<option value="">-- Select Category --</option>');
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
  $("#itemForm").on("submit", function (e) {
    e.preventDefault();

    const formData = {
      action: $("#item_id").val() ? "update" : "create",
      id: $("#item_id").val(),
      item_code: $("#item_code").val(),
      item_name: $("#item_name").val(),
      description: $("#description").val(),
      category_id: $("#category_id").val(),
      unit_of_measure: $("#unit_of_measure").val(),
      reorder_level: $("#reorder_level").val(),
      max_stock_level: $("#max_stock_level").val(),
      unit_cost: $("#unit_cost").val(),
      unit_price: $("#unit_price").val(),
      is_active: $("#is_active").is(":checked") ? 1 : 0,
    };

    const submitBtn = $("#saveItemBtn");
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
      url: "../../Ajax/php/inventory_item.php",
      type: "POST",
      data: formData,
      success: function (response) {
        if (response.status === "success") {
          showAlert(
            "success",
            response.message || "Operation completed successfully"
          );
          $("#itemModal").modal("hide");
          itemsTable.ajax.reload();
          $("#itemForm")[0].reset();
          $("#itemForm").removeClass("was-validated");
          $("#item_id").val("");
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

  // Load categories when modal opens
  $("#itemModal").on("show.bs.modal", function () {
    loadCategories();
  });

  // Reset form when modal is closed
  $("#itemModal").on("hidden.bs.modal", function () {
    const form = $("#itemForm");
    form[0].reset();
    form.removeClass("was-validated");
    $("#item_id").val("");
    $("#itemModalLabel").text("Add New Item");
    $("#is_active").prop("checked", true);
  });

  // Edit item
  $(document).on("click", ".edit-item", function () {
    const itemId = $(this).data("id");

    loadCategories();

    $.get(
      "../../Ajax/php/inventory_item.php",
      { action: "get", id: itemId },
      function (response) {
        if (response.status === "success" && response.data) {
          const item = response.data;
          fillItemForm(item);
          $("#itemModalLabel").text("Edit Item");
          $("#itemModal").modal("show");
        } else {
          showAlert("error", response.message || "Failed to load item data");
        }
      }
    ).fail(function () {
      showAlert("error", "An error occurred while loading item data");
    });
  });

  // Delete item
  $(document).on("click", ".delete-item", function () {
    const itemId = $(this).data("id");

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
          "../../Ajax/php/inventory_item.php",
          { action: "delete", id: itemId },
          function (response) {
            if (response.status === "success") {
              showAlert(
                "success",
                response.message || "Item deleted successfully"
              );
              itemsTable.ajax.reload();
            } else {
              showAlert("error", response.message || "Failed to delete item");
            }
          }
        ).fail(function () {
          showAlert("error", "An error occurred while deleting the item");
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
  function fillItemForm(item) {
    $("#item_id").val(item.id);
    $("#item_code").val(item.item_code);
    $("#item_name").val(item.item_name);
    $("#description").val(item.description || "");
    $("#category_id").val(item.category_id || "");
    $("#unit_of_measure").val(item.unit_of_measure);
    $("#reorder_level").val(item.reorder_level);
    $("#max_stock_level").val(item.max_stock_level || "");
    $("#unit_cost").val(item.unit_cost);
    $("#unit_price").val(item.unit_price);
    $("#is_active").prop("checked", item.is_active == 1);
  }
});
