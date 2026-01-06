class CustomerSelectorModal {
  constructor(options = {}) {
    this.options = {
      onSelect: options.onSelect || function () {},
      modalId: "customerSelectorModal",
      tableId: "customerSelectorTable",
    };
    this.customers = [];
    this.init();
  }

  init() {
    // Create modal HTML
    const modalHtml = `
            <div class="modal fade" id="${this.options.modalId}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Select Customer</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="${this.options.tableId}" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Mobile</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;

    // Append to body if not exists
    if ($(`#${this.options.modalId}`).length === 0) {
      $("body").append(modalHtml);
    }

    this.modalElement = new bootstrap.Modal(
      document.getElementById(this.options.modalId)
    );
    this.loadCustomers();
  }

  loadCustomers() {
    const self = this;
    // Use existing item_selector structure but adapted for customers
    // Since we don't have a dedicated search API for customers besides list, we'll load all and use DataTable's search
    // If the list is huge, we should switch to server-side processing later.

    $(`#${this.options.tableId}`).DataTable({
      processing: true,
      destroy: true, // Allow re-initialization
      ajax: {
        url: "../../Ajax/php/customer.php",
        type: "POST",
        data: { action: "list" },
        dataSrc: function (json) {
          if (json.status === "success") {
            self.customers = json.data;
            return json.data;
          }
          return [];
        },
      },
      columns: [
        { data: "name" },
        {
          data: "phone",
          render: function (data, type, row) {
            return data || "N/A";
          },
        },
        {
          data: null,
          render: function (data, type, row) {
            return `<button class="btn btn-sm btn-primary select-customer-btn" data-id="${row.id}">Select</button>`;
          },
        },
      ],
      drawCallback: function () {
        $(".select-customer-btn")
          .off("click")
          .on("click", function () {
            const id = $(this).data("id");
            const customer = self.customers.find((c) => c.id == id);
            if (customer) {
              self.options.onSelect(customer);
              self.hide();
            }
          });
      },
    });
  }

  show() {
    this.modalElement.show();
  }

  hide() {
    this.modalElement.hide();
  }
}
