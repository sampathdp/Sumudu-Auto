// Initialize DataTable
$(document).ready(function () {
  const quotationsTable = $("#quotationsTable").DataTable({
    ajax: {
      url: "../../Ajax/php/quotation.php",
      type: "GET",
      type: "GET",
      data: { action: "list" }
    },
    columns: [
      { data: "id" },
      {
        data: "quotation_number",
        render: function (data) {
          return `<code class="fw-semibold">${data}</code>`;
        },
      },
      {
        data: "customer_name",
        render: function (data) {
          return data || '<span class="text-muted">N/A</span>';
        },
      },
      {
        data: "total_amount",
        render: function (data) {
          return `Rs. ${parseFloat(data).toFixed(2)}`;
        },
      },
      {
        data: "valid_until",
        render: function (data) {
          return data
            ? new Date(data).toLocaleDateString()
            : '<span class="text-muted">-</span>';
        },
      },
      {
        data: "status",
        render: function (data) {
          const badges = {
            pending: "warning",
            accepted: "success",
            rejected: "danger",
          };
          return `<span class="badge bg-${badges[data] || "secondary"}">${(
            data || ""
          ).toUpperCase()}</span>`;
        },
      },
      {
        data: null,
        orderable: false,
        render: function (data, type, row) {
          return `
                        <div class="quotation-actions">
                            <button class="btn btn-sm btn-icon btn-outline-info view-quotation" data-id="${row.id}" 
                                    title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-icon btn-outline-danger delete-quotation" data-id="${row.id}" 
                                    title="Delete Quotation">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>`;
        },
      },
    ],
    order: [[0, "desc"]],
    responsive: true,
  });

  //View quotation details
  $(document).on("click", ".view-quotation", function () {
    const quotationId = $(this).data("id");

    $.get(
      "../../Ajax/php/quotation.php",
      { action: "get", id: quotationId },
      function (response) {
        if (response.status === "success") {
          showQuotationDetails(response.data);
        } else {
          showAlert("error", response.message);
        }
      }
    );
  });

  // Delete quotation
  $(document).on("click", ".delete-quotation", function () {
    const quotationId = $(this).data("id");

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
          "../../Ajax/php/quotation.php",
          { action: "delete", id: quotationId },
          function (response) {
            if (response.status === "success") {
              showAlert(
                "success",
                response.message || "Quotation deleted successfully"
              );
              quotationsTable.ajax.reload();
            } else {
              showAlert(
                "error",
                response.message || "Failed to delete quotation"
              );
            }
          }
        ).fail(function () {
          showAlert("error", "An error occurred while deleting the quotation");
        });
      }
    });
  });

  // Show quotation details in modal
  function showQuotationDetails(quotation) {
    // Basic Info
    $("#view_quotation_number").text(quotation.quotation_number);
    $("#view_created_at").text(
      new Date(quotation.created_at).toLocaleDateString()
    );

    // Status Badge
    const statusMap = {
      pending: "bg-warning",
      accepted: "bg-success",
      rejected: "bg-danger",
    };
    $("#view_status_badge")
      .removeClass("bg-warning bg-success bg-danger bg-secondary")
      .addClass(statusMap[quotation.status] || "bg-secondary")
      .text((quotation.status || "Pending").toUpperCase());

    // Customer Info
    $("#view_customer_name").text(quotation.customer_name || "N/A");
    $("#view_customer_mobile").text(quotation.customer_mobile || "N/A");

    // Validity
    $("#view_valid_until").text(
      quotation.valid_until
        ? new Date(quotation.valid_until).toLocaleDateString()
        : "-"
    );

    // Items
    let itemsHtml = "";
    if (quotation.items && quotation.items.length > 0) {
      quotation.items.forEach((item) => {
        itemsHtml += `
                <tr>
                    <td>${item.description || item.item_name || "-"}</td>
                    <td class="text-center"><span class="badge bg-light text-dark">${item.item_type
          }</span></td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-end">Rs. ${parseFloat(
            item.unit_price
          ).toFixed(2)}</td>
                    <td class="text-end fw-bold">Rs. ${parseFloat(
            item.total_price
          ).toFixed(2)}</td>
                </tr>`;
      });
    } else {
      itemsHtml =
        '<tr><td colspan="5" class="text-center text-muted">No items found</td></tr>';
    }
    $("#view_quotation_items").html(itemsHtml);

    // Totals
    $("#view_subtotal").text(
      "Rs. " + parseFloat(quotation.subtotal).toFixed(2)
    );
    $("#view_tax").text("Rs. " + parseFloat(quotation.tax_amount).toFixed(2));
    $("#view_discount").text(
      "- Rs. " + parseFloat(quotation.discount_amount).toFixed(2)
    );
    $("#view_total").text(
      "Rs. " + parseFloat(quotation.total_amount).toFixed(2)
    );

    // Print Button
    $("#view_print_btn")
      .off("click")
      .on("click", function () {
        window.open(`print.php?id=${quotation.id}`, "_blank");
      });

    // Show Modal
    new bootstrap.Modal(document.getElementById("viewQuotationModal")).show();
  }

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

  // Load statistics
  function loadStatistics() {
    $.get('../../Ajax/php/quotation.php', { action: 'statistics' }, function (response) {
      if (response.status === 'success') {
        const stats = response.data;

        $('#totalQuotations').text(stats.total);
        $('#pendingQuotations').text(stats.pending);
        $('#acceptedQuotations').text(stats.accepted);
        $('#rejectedQuotations').text(stats.rejected);

        // Animation if available
        if ($.fn.animateNumber) {
          $('#totalQuotations').animateNumber({ number: stats.total });
          $('#pendingQuotations').animateNumber({ number: stats.pending });
          $('#acceptedQuotations').animateNumber({ number: stats.accepted });
          $('#rejectedQuotations').animateNumber({ number: stats.rejected });
        }
      }
    });
  }

  // Load stats initially and reload when table reloads or after actions
  loadStatistics();

  // Hook into table updates if needed, or just call loadStatistics() after actions
  // ... existing delete/update success handlers should call loadStatistics() ...

  // Define animateNumber if not already defined
  if (!$.fn.animateNumber) {
    $.fn.animateNumber = function (options) {
      const $this = $(this);
      const end = options.number;
      $({ val: 0 }).animate({ val: end }, {
        duration: 1000,
        step: function () { $this.text(Math.floor(this.val)); },
        complete: function () { $this.text(this.val); }
      });
    };
  }
});
