/**
 * Item Selector Modal Component
 * Reusable modal with DataTable for selecting inventory items
 */

class ItemSelectorModal {
    constructor(options = {}) {
        this.modalId = options.modalId || 'itemSelectorModal';
        this.onSelect = options.onSelect || function () { };

        // Get base URL with fallback
        const baseUrl = this.getBaseUrl();
        this.apiUrl = options.apiUrl || baseUrl + 'Ajax/php/inventory_item.php';
        this.categoryUrl = baseUrl + 'Ajax/php/inventory_category.php';

        this.dataTable = null;
        this.init();
    }

    getBaseUrl() {
        // Use global BASE_URL if defined
        if (typeof BASE_URL !== 'undefined') {
            return BASE_URL;
        }
        // Fallback: try to detect from current page URL
        const path = window.location.pathname;
        const match = path.match(/^(.*?\/VSC\/)/);
        if (match) {
            return window.location.origin + match[1];
        }
        // Last fallback
        return '/VSC/';
    }

    init() {
        // Create modal HTML if not exists
        if (!document.getElementById(this.modalId)) {
            this.createModalHtml();
        }
        this.bindEvents();
    }

    createModalHtml() {
        const modalHtml = `
            <div class="modal fade" id="${this.modalId}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-box me-2"></i>Select Inventory Item
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="p-3 bg-light border-bottom">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                            <input type="text" class="form-control" id="${this.modalId}_search" 
                                                   placeholder="Search by item name or code...">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <select class="form-select" id="${this.modalId}_category">
                                            <option value="">All Categories</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-hover mb-0" id="${this.modalId}_table">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="width: 100px;">Code</th>
                                            <th>Item Name</th>
                                            <th>Category</th>
                                            <th style="width: 80px;" class="text-center">Stock</th>
                                            <th style="width: 120px;" class="text-end">Unit Price</th>
                                            <th style="width: 100px;" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="${this.modalId}_tbody">
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Add custom styles
        if (!document.getElementById('itemSelectorStyles')) {
            const styles = `
                <style id="itemSelectorStyles">
                    #${this.modalId} .table tbody tr {
                        cursor: pointer;
                        transition: all 0.2s ease;
                    }
                    #${this.modalId} .table tbody tr:hover {
                        background-color: rgba(67, 97, 238, 0.08);
                    }
                    #${this.modalId} .table tbody tr.selected {
                        background-color: rgba(67, 97, 238, 0.15);
                    }
                    #${this.modalId} .stock-low {
                        color: #dc3545;
                        font-weight: 600;
                    }
                    #${this.modalId} .stock-ok {
                        color: #28a745;
                        font-weight: 600;
                    }
                    #${this.modalId} .stock-out {
                        color: #dc3545;
                        font-weight: 600;
                        background-color: rgba(220, 53, 69, 0.1);
                    }
                    #${this.modalId} .btn-select-item {
                        padding: 0.25rem 0.75rem;
                        font-size: 0.8rem;
                    }
                    #${this.modalId} .table thead th {
                        border-bottom: 2px solid #dee2e6;
                        font-weight: 600;
                        font-size: 0.85rem;
                        text-transform: uppercase;
                        color: #495057;
                    }
                    #${this.modalId} .item-code {
                        font-family: monospace;
                        font-weight: 600;
                        color: #4361ee;
                    }
                </style>
            `;
            document.head.insertAdjacentHTML('beforeend', styles);
        }
    }

    bindEvents() {
        const modal = document.getElementById(this.modalId);
        const searchInput = document.getElementById(`${this.modalId}_search`);
        const categorySelect = document.getElementById(`${this.modalId}_category`);

        // Load items when modal opens
        modal.addEventListener('show.bs.modal', () => {
            this.loadItems();
            this.loadCategories();
        });

        // Search functionality
        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.filterItems();
            }, 300);
        });

        // Category filter
        categorySelect.addEventListener('change', () => {
            this.filterItems();
        });

        // Row click to select
        document.getElementById(`${this.modalId}_tbody`).addEventListener('click', (e) => {
            const selectBtn = e.target.closest('.btn-select-item');
            if (selectBtn) {
                const row = selectBtn.closest('tr');
                this.selectItem(row);
            }
        });
    }

    loadItems() {
        const tbody = document.getElementById(`${this.modalId}_tbody`);
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </td>
            </tr>
        `;

        fetch(`${this.apiUrl}?action=all`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.items = data.data.filter(item => item.is_active == 1);
                    this.renderItems(this.items);
                } else {
                    this.showError('Failed to load items');
                }
            })
            .catch(error => {
                console.error('Error loading items:', error);
                this.showError('Failed to load items');
            });
    }

    loadCategories() {
        const categorySelect = document.getElementById(`${this.modalId}_category`);

        fetch(`${this.categoryUrl}?action=all`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    categorySelect.innerHTML = '<option value="">All Categories</option>';
                    data.data.filter(c => c.is_active == 1).forEach(category => {
                        categorySelect.innerHTML += `<option value="${category.id}">${category.category_name}</option>`;
                    });
                }
            })
            .catch(error => {
                console.error('Error loading categories:', error);
            });
    }

    renderItems(items) {
        const tbody = document.getElementById(`${this.modalId}_tbody`);

        if (!items || items.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">No items found</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = items.map(item => {
            const stock = parseFloat(item.current_stock) || 0;
            const minStock = parseFloat(item.minimum_stock) || 0;
            let stockClass = 'stock-ok';
            if (stock <= 0) {
                stockClass = 'stock-out';
            } else if (stock <= minStock) {
                stockClass = 'stock-low';
            }

            return `
                <tr data-item='${JSON.stringify(item)}'>
                    <td><span class="item-code">${this.escapeHtml(item.item_code)}</span></td>
                    <td>
                        <strong>${this.escapeHtml(item.item_name)}</strong>
                        ${item.description ? `<br><small class="text-muted">${this.escapeHtml(item.description.substring(0, 50))}</small>` : ''}
                    </td>
                    <td><span class="badge bg-light text-dark">${this.escapeHtml(item.category_name || 'Uncategorized')}</span></td>
                    <td class="text-center ${stockClass}">${stock}</td>
                    <td class="text-end"><strong>LKR ${this.formatNumber(item.selling_price || item.unit_cost || 0)}</strong></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-primary btn-select-item">
                            <i class="fas fa-check me-1"></i>Select
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    filterItems() {
        const searchTerm = document.getElementById(`${this.modalId}_search`).value.toLowerCase();
        const categoryId = document.getElementById(`${this.modalId}_category`).value;

        let filtered = this.items || [];

        if (searchTerm) {
            filtered = filtered.filter(item =>
                item.item_name.toLowerCase().includes(searchTerm) ||
                item.item_code.toLowerCase().includes(searchTerm) ||
                (item.description && item.description.toLowerCase().includes(searchTerm))
            );
        }

        if (categoryId) {
            filtered = filtered.filter(item => item.category_id == categoryId);
        }

        this.renderItems(filtered);
    }

    selectItem(row) {
        const itemData = JSON.parse(row.dataset.item);
        this.onSelect(itemData);

        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById(this.modalId));
        if (modal) {
            modal.hide();
        }
    }

    show() {
        const modal = new bootstrap.Modal(document.getElementById(this.modalId));
        modal.show();
    }

    hide() {
        const modal = bootstrap.Modal.getInstance(document.getElementById(this.modalId));
        if (modal) {
            modal.hide();
        }
    }

    showError(message) {
        const tbody = document.getElementById(`${this.modalId}_tbody`);
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5 text-danger">
                    <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                    <p class="mb-0">${message}</p>
                </td>
            </tr>
        `;
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatNumber(num) {
        return parseFloat(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

// Export for usage
window.ItemSelectorModal = ItemSelectorModal;
