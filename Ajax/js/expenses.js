$(document).ready(function () {
  const expensesTable = $("#expensesTable").DataTable({
    ajax: {
      url: "../../Ajax/php/expenses.php",
      type: "GET",
      data: { action: "list" },
      dataSrc: "data",
    },
    columns: [
      {
        data: "expense_date",
        render: function (data) {
          return data ? new Date(data).toLocaleDateString() : "";
        },
      },
      {
        data: "category_name",
        render: function (data) {
          return `<span class="badge bg-secondary">${
            data || "Uncategorized"
          }</span>`;
        },
      },
      { data: "description" },
      {
        data: "amount",
        render: function (data) {
          return parseFloat(data).toFixed(2);
        },
      },
      {
        data: "status",
        render: function (data) {
          let color = "secondary";
          if (data === "approved") color = "success";
          if (data === "rejected") color = "danger";
          if (data === "pending") color = "warning";
          return `<span class="badge bg-${color}">${data.toUpperCase()}</span>`;
        },
      },
      { data: "paid_to" },
      {
        data: null,
        orderable: false,
        render: function (data, type, row) {
          let buttons = "";
          if (row.status === "pending") {
            buttons += `
                        <button class="btn btn-sm btn-icon btn-outline-success approve-expense" data-id="${row.id}" title="Approve">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-sm btn-icon btn-outline-danger reject-expense" data-id="${row.id}" title="Reject">
                            <i class="fas fa-times"></i>
                        </button>`;
          }
          buttons += `
                    <button class="btn btn-sm btn-icon btn-outline-primary edit-expense" data-id="${row.id}" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-icon btn-outline-danger delete-expense" data-id="${row.id}" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>`;
          return `<div class="d-flex gap-2">${buttons}</div>`;
        },
      },
    ],
    order: [[0, "desc"]],
    responsive: true,
    language: {
      emptyTable: "No expenses found",
      searchPlaceholder: "Search expenses...",
    },
    dom: `
          <"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-end"f>>
          <"row mt-3"<"col-sm-12"tr>>
          <"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>
        `,
    initComplete: function () {
      $(".dataTables_filter input").addClass("form-control form-control-sm");
      $(".dataTables_length select").addClass("form-select form-select-sm");
    },
  });

  // Load categories and accounts
  loadCategories();
  loadAccounts();

  function loadCategories() {
    $.get(
      "../../Ajax/php/expenses.php",
      { action: "get_categories" },
      function (response) {
        if (response.status === "success") {
          const select = $("#category_id");
          select.empty().append('<option value="">Select Category...</option>');
          response.data.forEach((cat) => {
            select.append(
              `<option value="${cat.id}">${cat.category_name}</option>`
            );
          });
        }
      }
    );
  }

  function loadAccounts() {
    $.get(
      "../../Ajax/php/financial.php",
      { action: "get_accounts" },
      function (response) {
        if (response.status === "success") {
          const select = $("#account_id");
          select.empty().append('<option value="">Select Account...</option>');
          response.data.forEach((acc) => {
            // Optional: Show balance in dropdown
            select.append(
              `<option value="${acc.id}">${acc.account_name} (${acc.account_type})</option>`
            );
          });
        }
      }
    );
  }

  // Save Expense
  $("#expenseForm").on("submit", function (e) {
    e.preventDefault();
    const form = $(this);
    const submitBtn = $("#saveExpenseBtn");

    if (!form[0].checkValidity()) {
      e.stopPropagation();
      form.addClass("was-validated");
      return;
    }

    const formData = new FormData(this);
    formData.append("action", $("#expense_id").val() ? "update" : "create");
    if ($("#expense_id").val()) formData.append("id", $("#expense_id").val());

    submitBtn
      .prop("disabled", true)
      .html(
        '<span class="spinner-border spinner-border-sm me-1"></span> Saving...'
      );

    $.ajax({
      url: "../../Ajax/php/expenses.php",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === "success") {
          Swal.fire("Success", response.message, "success");
          $("#expenseModal").modal("hide");
          expensesTable.ajax.reload();
          form[0].reset();
        } else {
          Swal.fire("Error", response.message, "error");
        }
      },
      complete: function () {
        submitBtn
          .prop("disabled", false)
          .html('<i class="fas fa-save me-1"></i>Save Expense');
      },
    });
  });

  // Edit Expense
  $(document).on("click", ".edit-expense", function () {
    const id = $(this).data("id");
    $.get(
      "../../Ajax/php/expenses.php",
      { action: "get", id: id },
      function (response) {
        if (response.status === "success" && response.data) {
          const data = response.data;
          $("#expense_id").val(data.id);
          $("#category_id").val(data.category_id);
          $("#expense_date").val(data.expense_date);
          $("#amount").val(data.amount);
          $("#description").val(data.description);
          $("#paid_to").val(data.paid_to);
          $("#payment_method").val(data.payment_method);
          $("#account_id").val(data.account_id); // Set Account
          $("#reference_number").val(data.reference_number);
          $("#expenseModalLabel").text("Edit Expense");
          $("#expenseModal").modal("show");
        }
      }
    );
  });

  // Reset Modal
  $("#expenseModal").on("hidden.bs.modal", function () {
    $("#expenseForm")[0].reset();
    $("#expense_id").val("");
    $("#expenseModalLabel").text("Add New Expense");
  });

  // Approve/Reject/Delete Actions
  function handleAction(action, id) {
    let title = "Are you sure?";
    let text = "This action cannot be undone.";
    let confirmBtnText = "Yes, proceed";

    if (action === "delete") {
      title = "Delete Expense?";
      confirmBtnText = "Yes, delete it";
    } else if (action === "approve") {
      title = "Approve Expense?";
      confirmBtnText = "Yes, approve";
    } else if (action === "reject") {
      title = "Reject Expense?";
      confirmBtnText = "Yes, reject";
    }

    Swal.fire({
      title: title,
      text: text,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: confirmBtnText,
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(
          "../../Ajax/php/expenses.php",
          { action: action, id: id },
          function (response) {
            if (response.status === "success") {
              Swal.fire("Success", response.message, "success");
              expensesTable.ajax.reload();
            } else {
              Swal.fire("Error", response.message, "error");
            }
          }
        );
      }
    });
  }

  $(document).on("click", ".delete-expense", function () {
    handleAction("delete", $(this).data("id"));
  });
  $(document).on("click", ".approve-expense", function () {
    handleAction("approve", $(this).data("id"));
  });
  $(document).on("click", ".reject-expense", function () {
    handleAction("reject", $(this).data("id"));
  });
});
