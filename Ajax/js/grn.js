// Initialize DataTable
$(document).ready(function () {
  const grnsTable = $("#grnsTable").DataTable({
    ajax: {
      url: "../../Ajax/php/grn.php",
      type: "GET",
      data: { action: "list" },
      dataSrc: "data",
    },
    columns: [
      { data: "id" },
      {
        data: "grn_number",
        render: function (data) {
          return `<code class="fw-semibold">${data}</code>`;
        },
      },
      {
        data: "supplier_name",
        render: function (data) {
          return data || '<span class="text-muted">N/A</span>';
        },
      },
      { data: "grn_date" },
      {
        data: "invoice_number",
        render: function (data) {
          return data || '<span class="text-muted">N/A</span>';
        },
      },
      {
        data: "net_amount",
        render: function (data) {
          return `Rs. ${parseFloat(data).toFixed(2)}`;
        },
      },
      {
        data: "status",
        render: function (data) {
          const badges = {
            draft: "secondary",
            received: "info",
            verified: "success",
            cancelled: "danger",
          };
          return `<span class="badge bg-${
            badges[data]
          }">${data.toUpperCase()}</span>`;
        },
      },
      {
        data: null,
        orderable: false,
        render: function (data, type, row) {
          let actions = `
                        <button class="btn btn-sm btn-icon btn-outline-info view-grn" data-id="${row.id}" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>`;

          if (row.status === "draft") {
            actions += `
                        <button class="btn btn-sm btn-icon btn-outline-primary edit-grn" data-id="${row.id}" title="Edit" onclick="window.location.href='create.php?id=${row.id}'">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-icon btn-outline-danger cancel-grn" data-id="${row.id}" title="Cancel GRN">
                            <i class="fas fa-ban"></i>
                        </button>`;
          }

          if (row.status === "received") {
            actions += `
                        <button class="btn btn-sm btn-icon btn-outline-success verify-grn" data-id="${row.id}" title="Verify & Update Stock">
                            <i class="fas fa-check-circle"></i>
                        </button>`;
          }

          return `<div class="grn-actions">${actions}</div>`;
        },
      },
    ],
    order: [[0, "desc"]],
    responsive: true,
  });

  // View GRN details
  $(document).on("click", ".view-grn", function () {
    const grnId = $(this).data("id");

    $.get(
      "../../Ajax/php/grn.php",
      { action: "get", id: grnId },
      function (response) {
        if (response.status === "success") {
          showGRNDetails(response.data);
        } else {
          showAlert("error", response.message);
        }
      }
    );
  });

  // Verify GRN
  $(document).on("click", ".verify-grn", function () {
    const grnId = $(this).data("id");

    Swal.fire({
      title: "Verify GRN?",
      text: "This will update inventory stock levels. This action cannot be undone!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#28a745",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, verify it!",
      cancelButtonText: "Cancel",
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(
          "../../Ajax/php/grn.php",
          { action: "verify", id: grnId },
          function (response) {
            if (response.status === "success") {
              showAlert("success", response.message);
              grnsTable.ajax.reload();
            } else {
              showAlert("error", response.message);
            }
          }
        );
      }
    });
  });

  // Cancel GRN
  $(document).on("click", ".cancel-grn", function () {
    const grnId = $(this).data("id");
    Swal.fire({
      title: "Cancel GRN?",
      text: "Are you sure you want to cancel this GRN? This action cannot be undone.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Yes, cancel it!",
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(
          "../../Ajax/php/grn.php",
          {
            action: "cancel",
            id: grnId,
          },
          function (response) {
            if (response.status === "success") {
              showAlert("success", response.message);
              grnsTable.ajax.reload();
            } else {
              showAlert("error", response.message);
            }
          }
        );
      }
    });
  });

  // Show GRN details in modal
  function showGRNDetails(grn) {
    let itemsHtml = "";
    if (grn.items && grn.items.length > 0) {
      grn.items.forEach(function (item) {
        itemsHtml += `
                    <tr>
                        <td>${item.item_code}</td>
                        <td>${item.item_name}</td>
                        <td>${item.quantity} ${item.unit_of_measure}</td>
                        <td>Rs. ${parseFloat(item.unit_price).toFixed(2)}</td>
                        <td>Rs. ${parseFloat(item.total_price).toFixed(2)}</td>
                    </tr>`;
      });
    } else {
      itemsHtml = '<tr><td colspan="5" class="text-center">No items</td></tr>';
    }

    const detailsHtml = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>GRN Number:</strong> ${grn.grn_number}<br>
                    <strong>Date:</strong> ${grn.grn_date}<br>
                    <strong>Invoice Number:</strong> ${
                      grn.invoice_number || "N/A"
                    }
                </div>
                <div class="col-md-6">
                    <strong>Status:</strong> <span class="badge bg-info">${grn.status.toUpperCase()}</span><br>
                    <strong>Total:</strong> Rs. ${parseFloat(
                      grn.total_amount
                    ).toFixed(2)}<br>
                    <strong>Net Amount:</strong> Rs. ${parseFloat(
                      grn.net_amount
                    ).toFixed(2)}
                </div>
            </div>
            <h6>Items:</h6>
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>${itemsHtml}</tbody>
            </table>`;

    Swal.fire({
      title: "GRN Details",
      html: detailsHtml,
      width: "800px",
      showCloseButton: true,
      showConfirmButton: false,
    });
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
});
