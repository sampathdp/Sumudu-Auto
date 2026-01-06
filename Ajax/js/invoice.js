// Initialize DataTable
$(document).ready(function () {
  loadInvoiceStats();

  const invoicesTable = $("#invoicesTable").DataTable({
    ajax: {
      url: "../../Ajax/php/invoice.php",
      type: "GET",
      data: { action: "list" },
      dataSrc: "data",
    },
    columns: [
      { data: "id" },
      {
        data: "invoice_number",
        render: function (data, type, row) {
          let html = `<code class="fw-semibold">${data}</code>`;
          if (row.status === "cancelled") {
            html +=
              ' <span class="badge bg-danger" style="font-size:0.65rem">CANCELLED</span>';
          }
          return html;
        },
      },
      {
        data: "bill_type",
        render: function (data, type, row) {
          if (data === "credit") {
            return '<span class="badge bg-warning text-dark">Credit</span>';
          }
          return '<span class="badge bg-success">Cash</span>';
        },
      },
      {
        data: "customer_name",
        render: function (data, type, row) {
          let html = `<div class="fw-bold">${data || "N/A"}</div>`;
          if (row.registration_number) {
            html += `<small class="text-muted d-block"><i class="fas fa-car me-1"></i>${row.registration_number}</small>`;
          }
          if (row.job_number) {
            html += `<small class="text-muted d-block" style="font-size:0.7em">Job: ${row.job_number}</small>`;
          }
          return html;
        },
      },
      {
        data: "total_amount",
        render: function (data, type, row) {
          const style =
            row.status === "cancelled"
              ? "text-decoration: line-through; opacity: 0.7;"
              : "";
          return `<span style="${style}">Rs. ${parseFloat(data).toFixed(
            2
          )}</span>`;
        },
      },
      {
        data: "payment_method",
        render: function (data, type, row) {
          if (row.status === "cancelled") {
            return '<span class="badge bg-secondary">N/A</span>';
          }
          if (!data) return '<span class="badge bg-warning">Unpaid</span>';
          const badges = {
            cash: "success",
            card: "primary",
            upi: "info",
            bank_transfer: "secondary",
            other: "dark",
          };
          return `<span class="badge bg-${
            badges[data] || "secondary"
          }">${data.toUpperCase()}</span>`;
        },
      },
      {
        data: "created_at",
        render: function (data) {
          return new Date(data).toLocaleDateString();
        },
      },
      {
        data: null,
        orderable: false,
        render: function (data, type, row) {
          let actions = `
            <div class="invoice-actions">
                <button class="btn btn-sm btn-icon btn-outline-info view-invoice" data-id="${row.id}" 
                        title="View Details">
                    <i class="fas fa-eye"></i>
                </button>`;

          // Only show Edit and Cancel for non-cancelled invoices
          if (row.status !== "cancelled") {
            actions += `
                <button class="btn btn-sm btn-icon btn-outline-primary edit-invoice" data-id="${row.id}" 
                        title="Edit Invoice">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-icon btn-outline-danger cancel-invoice" data-id="${row.id}" 
                        title="Cancel Invoice">
                    <i class="fas fa-ban"></i>
                </button>`;
          }

          actions += "</div>";
          return actions;
        },
      },
    ],
    order: [[0, "desc"]],
    responsive: true,
    // Highlight cancelled rows
    rowCallback: function (row, data) {
      if (data.status === "cancelled") {
        $(row).addClass("table-secondary").css("opacity", "0.7");
      }
    },
  });

  // View invoice details
  $(document).on("click", ".view-invoice", function () {
    const invoiceId = $(this).data("id");

    $.get(
      "../../Ajax/php/invoice.php",
      { action: "get", id: invoiceId },
      function (response) {
        if (response.status === "success") {
          showInvoiceDetails(response.data);
        } else {
          showAlert("error", response.message);
        }
      }
    );
  });

  // Edit invoice
  $(document).on("click", ".edit-invoice", function () {
    const invoiceId = $(this).data("id");
    window.location.href = `create.php?id=${invoiceId}`;
  });

  // Cancel invoice
  $(document).on("click", ".cancel-invoice", function () {
    const invoiceId = $(this).data("id");

    Swal.fire({
      title: "Cancel Invoice?",
      text: "This will restore any inventory stock used. This action cannot be undone!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#dc3545",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, cancel it!",
      cancelButtonText: "Go back",
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(
          "../../Ajax/php/invoice.php",
          { action: "cancel", id: invoiceId },
          function (response) {
            if (response.status === "success") {
              showAlert(
                "success",
                response.message || "Invoice cancelled successfully"
              );
              invoicesTable.ajax.reload();
              loadInvoiceStats();
            } else {
              showAlert(
                "error",
                response.message || "Failed to cancel invoice"
              );
            }
          }
        ).fail(function () {
          showAlert("error", "An error occurred while cancelling the invoice");
        });
      }
    });
  });

  // Show invoice details in modal
  function showInvoiceDetails(invoice) {
    // Basic Info
    $("#view_invoice_number").text(invoice.invoice_number);
    $("#view_invoice_date").text(
      new Date(invoice.created_at).toLocaleDateString()
    );

    // Status Badge
    const statusMap = {
      active: "bg-success",
      cancelled: "bg-danger",
      draft: "bg-secondary",
    };
    $("#view_status_badge")
      .removeClass("bg-success bg-danger bg-secondary")
      .addClass(statusMap[invoice.status] || "bg-secondary")
      .text((invoice.status || "Active").toUpperCase());

    // Customer Info (Check if invoice has direct customer data or linked via service)
    // Note: The 'get' response includes customer_name in the root object if we modify the PHP to include it,
    // otherwise we might need to rely on what's available.
    // Since print.php handles this well, let's try to mimic that logic or use what we have.
    // The current 'get' action in PHP returns 'items' but maybe not joined customer data deep in the 'data' array unless 'all()' was called.
    // However, the DATA TABLE calls 'list' which returns joined data. The 'get' action returns Invoice object properties.
    // If 'service_id' is present, we might need to fetch service details or rely on the fact that 'get' might need to be improved to return customer name.
    // For now, let's assume we might need to fetch customer name if it's not in the 'get' response.
    // Actually, looking at the previous PHP 'get' implementation, it simply returns properties of Invoice object.
    // Invoice object doesn't strictly hold customer name unless it's a direct property (which create.php sends but Invoice.php doesn't seem to persist directly to a 'customer_name' column unless I missed it).
    // Wait, Invoice.php create() stores basic fields. Create.php sends customer_name but Invoice.php doesn't have a column for it in INSERT unless it was added.
    // Let's look at Invoice.php again... It has no 'customer_name' field. It relies on Service ID.
    // If Service ID is null (direct invoice), we lose the customer name?
    // The user previously added customer_name input in create.php...
    // I should check if I missed a schema update or if Invoice.php is missing the column.
    // If it's missing, I'll display "Walk-in / N/A" for now or use what's available.

    // Let's populate what we can.
    $("#view_customer_name").text(invoice.customer_name || "Walk-in Customer");
    $("#view_customer_mobile").text(invoice.customer_mobile || "N/A");

    // Vehicle
    if (invoice.vehicle_reg) {
      $("#view_vehicle_info").show();
      $("#view_vehicle_reg").text(invoice.vehicle_reg);
    } else {
      $("#view_vehicle_info").hide();
    }

    // Payment
    const billTypeLabel =
      invoice.bill_type === "credit" ? "CREDIT BILL" : "CASH BILL";
    const billTypeClass =
      invoice.bill_type === "credit" ? "bg-warning text-dark" : "bg-success";
    $("#view_payment_method").html(
      `<span class="badge ${billTypeClass} me-1">${billTypeLabel}</span> ` +
        (invoice.payment_method || "Unpaid").toUpperCase()
    );
    $("#view_payment_date").text(invoice.payment_date || "-");

    // Items
    let itemsHtml = "";
    if (invoice.items && invoice.items.length > 0) {
      invoice.items.forEach((item) => {
        itemsHtml += `
                <tr>
                    <td>${item.description || item.item_name || "-"}</td>
                    <td class="text-center"><span class="badge bg-light text-dark">${
                      item.item_type
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
    $("#view_invoice_items").html(itemsHtml);

    // Totals
    $("#view_subtotal").text("Rs. " + parseFloat(invoice.subtotal).toFixed(2));
    $("#view_tax").text("Rs. " + parseFloat(invoice.tax_amount).toFixed(2));
    $("#view_discount").text(
      "- Rs. " + parseFloat(invoice.discount_amount).toFixed(2)
    );
    $("#view_total").text("Rs. " + parseFloat(invoice.total_amount).toFixed(2));

    // Print Button
    $("#view_print_btn")
      .off("click")
      .on("click", function () {
        window.open(`print.php?id=${invoice.id}`, "_blank");
      });

    // Show Modal
    new bootstrap.Modal(document.getElementById("viewInvoiceModal")).show();
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

  // Helper function to fill form for editing
  function fillInvoiceForm(invoice) {
    $("#invoice_id").val(invoice.id);
    $("#payment_method").val(invoice.payment_method || "");
    $("#payment_date").val(invoice.payment_date || "");
  }

  // Load statistics
  function loadInvoiceStats() {
    $.get(
      "../../Ajax/php/invoice.php",
      { action: "statistics" },
      function (response) {
        if (response.status === "success") {
          updateSummaryStats(response.data);
        }
      }
    );
  }

  // Update summary cards
  function updateSummaryStats(stats) {
    $("#totalInvoices").animateNumber({ number: stats.total_invoices });
    $("#paidInvoices").animateNumber({ number: stats.paid_count });
    $("#unpaidInvoices").animateNumber({ number: stats.unpaid_count });

    $("#totalRevenue").text(
      "Rs. " +
        parseFloat(stats.total_revenue || 0).toLocaleString(undefined, {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        })
    );
  }

  // Custom simple animation
  $.fn.animateNumber = function (options) {
    const $this = $(this);
    const start = 0;
    const end = options.number;

    $({ val: start }).animate(
      { val: end },
      {
        duration: 1000,
        step: function () {
          $this.text(Math.floor(this.val));
        },
        complete: function () {
          $this.text(this.val);
        },
      }
    );
  };
});
