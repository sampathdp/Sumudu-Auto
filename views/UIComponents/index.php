<?php
require_once __DIR__ . '/../../classes/Includes.php';

// Only allow admin users (role_id = 1)
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ' . BASE_URL . 'views/Dashboard/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Manage UI Components</title>
    <?php include '../../includes/main-css.php'; ?>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #eef2ff;
            --border: #e5e7eb;
            --bg: #f9fafb;
            --text: #111827;
            --text-secondary: #6b7280;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        body { 
            background-color: var(--bg); 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; 
        }

        .content { 
            margin-top: 70px; 
            padding: 1.5rem; 
        }

        @media (min-width: 768px) { 
            .content { padding: 2rem; } 
        }

        .page-header {
            background: #fff;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            border-left: 4px solid var(--primary);
        }

        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            margin: 0;
        }

        .page-header h1 i {
            color: var(--primary);
        }

        .page-header p {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin: 0.5rem 0 0;
        }

        .card {
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: var(--shadow);
        }

        .card-body {
            padding: 1.5rem;
        }

        .table thead th {
            background-color: var(--bg);
            border-bottom: 2px solid var(--border);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: #4338ca;
            border-color: #4338ca;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        <div class="main-panel">
            <?php include '../../includes/header.php'; ?>
            <div class="content">
                <div class="container-fluid">
                    <div class="page-header d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1><i class="fas fa-puzzle-piece me-2"></i>Manage UI Components</h1>
                            <p class="text-muted mb-0">Define available UI parts for visibility control</p>
                        </div>
                        <button class="btn btn-primary" onclick="openModal()">
                            <i class="fas fa-plus me-2"></i> Add Component
                        </button>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="componentsTable" class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Key</th>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Icon</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="componentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Component</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="componentForm">
                        <input type="hidden" id="compId" name="id">
                        
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control" name="category" id="compCategory" list="categoriesList" required>
                            <datalist id="categoriesList">
                                <option value="Dashboard">
                                <option value="Invoices">
                                <option value="Customers">
                                <option value="Reports">
                                <option value="System">
                            </datalist>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Display Name</label>
                            <input type="text" class="form-control" name="name" id="compName" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Component Key (Unique)</label>
                            <input type="text" class="form-control" name="component_key" id="compKey" required placeholder="e.g., dashboard_stats">
                            <small class="text-muted">Used in code: isUIVisible('key')</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Icon Class (FontAwesome)</label>
                            <input type="text" class="form-control" name="icon" id="compIcon" placeholder="fa-cog">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="compDesc" rows="2"></textarea>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="compActive" value="1" checked>
                            <label class="form-check-label" for="compActive">Active</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="saveComponent()">Save</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/main-js.php'; ?>
    <script>
    let componentsTable;
    
    $(document).ready(function() {
        loadTable();
    });

    function loadTable() {
        if (componentsTable) {
            componentsTable.destroy();
        }

        componentsTable = $('#componentsTable').DataTable({
            ajax: {
                url: '../../Ajax/php/ui_settings.php?action=list_components',
                dataSrc: 'data'
            },
            columns: [
                { data: 'component_key', render: d => `<code>${d}</code>` },
                { data: 'name' },
                { data: 'category', render: d => `<span class="badge bg-secondary">${d}</span>` },
                { data: 'icon', render: d => `<i class="fas ${d} fa-lg text-primary"></i> ${d}` },
                { 
                    data: 'is_active',
                    render: function(data) {
                        return data == 1 
                            ? '<span class="badge bg-success">Active</span>' 
                            : '<span class="badge bg-danger">Inactive</span>';
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return `
                            <button class="btn btn-sm btn-outline-primary me-1" onclick='editComponent(${JSON.stringify(row)})'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteComponent(${row.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                    }
                }
            ]
        });
    }

    function openModal() {
        $('#componentForm')[0].reset();
        $('#compId').val(''); // Clear ID for add
        $('#compKey').prop('readonly', false); // Allow editing key for new
        $('#modalTitle').text('Add Component');
        $('#componentModal').modal('show');
    }

    window.editComponent = function(row) {
        $('#compId').val(row.id);
        $('#compName').val(row.name);
        $('#compCategory').val(row.category);
        $('#compKey').val(row.component_key).prop('readonly', true); // Lock key on edit
        $('#compIcon').val(row.icon);
        $('#compDesc').val(row.description);
        $('#compActive').prop('checked', row.is_active == 1);
        
        $('#modalTitle').text('Edit Component');
        $('#componentModal').modal('show');
    };

    window.saveComponent = function() {
        const formData = $('#componentForm').serializeArray();
        formData.push({name: 'action', value: 'save_component'});
        
        // Handle checkbox manually if unchecked (not sent in serialize)
        if (!$('#compActive').is(':checked')) {
            formData.push({name: 'is_active', value: '0'});
        }

        $.post('../../Ajax/php/ui_settings.php', formData, function(response) {
            if (response.status === 'success') {
                $('#componentModal').modal('hide');
                loadTable();
                Swal.fire('Saved!', 'Component has been saved.', 'success');
            } else {
                Swal.fire('Error', response.message || 'Failed to save', 'error');
            }
        }, 'json').fail(function() {
            Swal.fire('Error', 'Server error', 'error');
        });
    };

    window.deleteComponent = function(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will delete all visibility rules associated with it!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../../Ajax/php/ui_settings.php', {
                    action: 'delete_component',
                    id: id
                }, function(response) {
                    if (response.status === 'success') {
                        loadTable();
                        Swal.fire('Deleted!', 'Component has been deleted.', 'success');
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }, 'json');
            }
        });
    };
    </script>
</body>
</html>
