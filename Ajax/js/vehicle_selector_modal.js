class VehicleSelectorModal {
  constructor(options = {}) {
    this.options = {
      onSelect: options.onSelect || function () {},
      modalId: "vehicleSelectorModal",
      tableId: "vehicleSelectorTable",
    };
    this.vehicles = [];
    this.init();
  }

  init() {
    // Create modal HTML
    const modalHtml = `
            <div class="modal fade" id="${this.options.modalId}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Select Vehicle</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="${this.options.tableId}" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Registration</th>
                                            <th>Make/Model</th>
                                            <th>Customer</th>
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
    this.loadVehicles();
  }

  loadVehicles() {
    const self = this;

    $(`#${this.options.tableId}`).DataTable({
      processing: true,
      destroy: true, // Allow re-initialization
      ajax: {
        url: "../../Ajax/php/vehicle.php",
        type: "GET",
        data: { action: "list_with_customer" },
        dataSrc: function (json) {
          if (json.status === "success") {
            self.vehicles = json.data;
            return json.data;
          }
          return [];
        },
      },
      columns: [
        {
          data: "registration_number",
          render: (data) => `<span class="fw-bold text-primary">${data}</span>`,
        },
        {
          data: null,
          render: (data, type, row) => `${row.make} ${row.model}`,
        },
        {
          data: "customer_name",
          render: (data, type, row) => `
                <div>${data}</div>
                <small class="text-muted">${row.customer_phone || ""}</small>
            `,
        },
        {
          data: null,
          render: function (data, type, row) {
            return `<button class="btn btn-sm btn-primary select-vehicle-btn" data-id="${row.id}">Select</button>`;
          },
        },
      ],
      drawCallback: function () {
        $(".select-vehicle-btn")
          .off("click")
          .on("click", function () {
            const id = $(this).data("id");
            const vehicle = self.vehicles.find((v) => v.id == id);
            if (vehicle) {
              self.options.onSelect(vehicle);
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
